<?php

namespace App\Modules\Leave\Application\SubmitRequest;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Leave\Application\Support\WorkingDays;
use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;
use App\Modules\Leave\Infrastructure\Models\RequestApproval;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubmitAdministrativeRequest
{
    private const CUTI_CATEGORIES = ['tahunan', 'khusus'];

    private const IZIN_CATEGORIES = ['pribadi', 'sakit', 'dinas_luar'];

    private const SPECIAL_REASONS = ['menikah', 'duka', 'melahirkan'];

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(EmployeeSummary $employee, array $data, int $actorUserId): AdministrativeRequest
    {
        $type = (string) $data['request_type'];

        [$start, $end, $category, $specialReason, $portion, $dayCount, $overtimeDate, $overtimeHours] = match ($type) {
            AdministrativeRequest::TYPE_LEMBUR => $this->overtime($data),
            AdministrativeRequest::TYPE_CUTI, AdministrativeRequest::TYPE_IZIN => $this->leave($data, $type),
            default => throw ValidationException::withMessages([
                'request_type' => ['Jenis pengajuan tidak dikenal.'],
            ]),
        };

        $this->checkQuota($employee, $type, $category, $start, (float) ($dayCount ?? 0));

        return DB::transaction(function () use ($employee, $type, $category, $specialReason, $start, $end, $portion, $dayCount, $overtimeDate, $overtimeHours, $data, $actorUserId): AdministrativeRequest {
            $request = AdministrativeRequest::create([
                'employee_id' => $employee->id,
                'request_type' => $type,
                'category' => $category,
                'special_reason' => $specialReason,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'day_portion' => $portion,
                'day_count' => $dayCount,
                'overtime_date' => $overtimeDate?->toDateString(),
                'overtime_hours' => $overtimeHours,
                'reason' => trim((string) $data['reason']),
                'status' => AdministrativeRequest::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            $this->createApprovalSteps($request, $employee, 1);

            $this->auditLogger->log(
                'administrative_request',
                (string) $request->id,
                'administrative_request.submitted',
                null,
                [
                    'request_type' => $request->request_type,
                    'category' => $request->category,
                    'start_date' => $request->start_date?->toDateString(),
                    'end_date' => $request->end_date?->toDateString(),
                    'day_count' => $request->day_count,
                    'status' => $request->status,
                ],
                null,
                $actorUserId,
            );

            return $request->load('approvals');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string|null, 3: string|null, 4: string, 5: float|null, 6: null, 7: null}
     */
    private function leave(array $data, string $type): array
    {
        if (empty($data['start_date']) || empty($data['end_date'])) {
            throw ValidationException::withMessages([
                'start_date' => ['Tanggal mulai dan selesai wajib diisi.'],
            ]);
        }

        $start = CarbonImmutable::parse((string) $data['start_date'])->startOfDay();
        $end = CarbonImmutable::parse((string) $data['end_date'])->startOfDay();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages([
                'end_date' => ['Tanggal selesai tidak boleh sebelum tanggal mulai.'],
            ]);
        }

        $portion = (string) ($data['day_portion'] ?? 'full');
        if (! in_array($portion, ['full', 'morning', 'afternoon'], true)) {
            throw ValidationException::withMessages([
                'day_portion' => ['Pilihan setengah hari tidak dikenal.'],
            ]);
        }

        if ($portion !== 'full' && ! $start->isSameDay($end)) {
            throw ValidationException::withMessages([
                'end_date' => ['Setengah hari hanya dapat diajukan untuk satu tanggal.'],
            ]);
        }

        $dayCount = $portion === 'full'
            ? WorkingDays::between($start, $end)
            : 0.5;

        if ($dayCount <= 0) {
            throw ValidationException::withMessages([
                'start_date' => ['Tanggal yang dipilih tidak mengandung hari kerja.'],
            ]);
        }

        $category = $data['category'] ?? null;

        if ($type === AdministrativeRequest::TYPE_CUTI) {
            if (! in_array($category, self::CUTI_CATEGORIES, true)) {
                throw ValidationException::withMessages([
                    'category' => ['Kategori cuti harus tahunan atau khusus.'],
                ]);
            }

            if ($category === 'khusus') {
                $specialReason = $data['special_reason'] ?? null;

                if (! in_array($specialReason, self::SPECIAL_REASONS, true)) {
                    throw ValidationException::withMessages([
                        'special_reason' => ['Alasan cuti khusus wajib dipilih.'],
                    ]);
                }

                $maxSpecial = (int) config('hadirmetrik.leave.max_special_days', 3);
                if ($dayCount > $maxSpecial) {
                    throw ValidationException::withMessages([
                        'end_date' => ["Cuti khusus maksimal {$maxSpecial} hari."],
                    ]);
                }
            }
        }

        if ($type === AdministrativeRequest::TYPE_IZIN && ! in_array($category, self::IZIN_CATEGORIES, true)) {
            throw ValidationException::withMessages([
                'category' => ['Kategori izin harus pribadi, sakit, atau dinas luar.'],
            ]);
        }

        return [$start, $end, $category, $category === 'khusus' ? ($data['special_reason'] ?? null) : null, $portion, $dayCount, null, null];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: null, 3: null, 4: string, 5: null, 6: CarbonImmutable, 7: float}
     */
    private function overtime(array $data): array
    {
        if (empty($data['overtime_date'])) {
            throw ValidationException::withMessages([
                'overtime_date' => ['Tanggal lembur wajib diisi.'],
            ]);
        }

        $hours = $data['overtime_hours'] ?? null;
        if (! is_numeric($hours) || (float) $hours <= 0 || (float) $hours > 12) {
            throw ValidationException::withMessages([
                'overtime_hours' => ['Jam lembur harus lebih dari 0 dan maksimal 12 jam.'],
            ]);
        }

        $date = CarbonImmutable::parse((string) $data['overtime_date'])->startOfDay();

        return [$date, $date, null, null, 'full', null, $date, (float) $hours];
    }

    private function checkQuota(
        EmployeeSummary $employee,
        string $type,
        ?string $category,
        CarbonImmutable $start,
        float $dayCount,
    ): void {
        if ($type === AdministrativeRequest::TYPE_CUTI && $category === 'tahunan') {
            $quota = (int) config('hadirmetrik.leave.annual_quota_days', 12);
            $used = (float) AdministrativeRequest::query()
                ->where('employee_id', $employee->id)
                ->where('request_type', AdministrativeRequest::TYPE_CUTI)
                ->where('category', 'tahunan')
                ->where('status', AdministrativeRequest::STATUS_APPROVED)
                ->whereYear('start_date', $start->year)
                ->sum('day_count');

            if ($dayCount > ($quota - $used)) {
                throw ValidationException::withMessages([
                    'code' => ['QUOTA_EXCEEDED'],
                    'end_date' => ['Sisa kuota cuti tahunan tidak mencukupi.'],
                ]);
            }
        }

        if ($type === AdministrativeRequest::TYPE_IZIN) {
            $limit = (int) config('hadirmetrik.leave.max_izin_per_month', 2);
            $count = AdministrativeRequest::query()
                ->where('employee_id', $employee->id)
                ->where('request_type', AdministrativeRequest::TYPE_IZIN)
                ->whereIn('status', [
                    AdministrativeRequest::STATUS_SUBMITTED,
                    AdministrativeRequest::STATUS_UNDER_REVIEW,
                    AdministrativeRequest::STATUS_APPROVED,
                ])
                ->whereBetween('start_date', [$start->startOfMonth(), $start->endOfMonth()])
                ->count();

            if ($count >= $limit) {
                throw ValidationException::withMessages([
                    'code' => ['QUOTA_EXCEEDED'],
                    'start_date' => ["Izin maksimal {$limit} kali per bulan."],
                ]);
            }
        }
    }

    public function createApprovalSteps(AdministrativeRequest $request, EmployeeSummary $employee, int $round): void
    {
        $request->approvals()->create([
            'round' => $round,
            'step_order' => 1,
            'approver_role' => RequestApproval::ROLE_SUPERVISOR,
            'approver_user_id' => $employee->managerUserId,
            'status' => RequestApproval::STATUS_PENDING,
        ]);

        $request->approvals()->create([
            'round' => $round,
            'step_order' => 2,
            'approver_role' => RequestApproval::ROLE_HR,
            'approver_user_id' => null,
            'status' => RequestApproval::STATUS_PENDING,
        ]);
    }
}
