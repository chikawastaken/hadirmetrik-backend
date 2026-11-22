<?php

namespace App\Modules\Leave\Application\Summary;

use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Leave\Infrastructure\Models\AdministrativeRequest;

final class GetLeaveSummary
{
    /**
     * @return array<string, int|float>
     */
    public function execute(EmployeeSummary $employee): array
    {
        $year = (int) now()->year;
        $quota = (int) config('hadirmetrik.leave.annual_quota_days', 12);
        $used = (float) AdministrativeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('request_type', AdministrativeRequest::TYPE_CUTI)
            ->where('category', 'tahunan')
            ->where('status', AdministrativeRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->sum('day_count');

        $izinThisMonth = AdministrativeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('request_type', AdministrativeRequest::TYPE_IZIN)
            ->whereIn('status', [
                AdministrativeRequest::STATUS_SUBMITTED,
                AdministrativeRequest::STATUS_UNDER_REVIEW,
                AdministrativeRequest::STATUS_APPROVED,
            ])
            ->whereBetween('start_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $pending = AdministrativeRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [
                AdministrativeRequest::STATUS_SUBMITTED,
                AdministrativeRequest::STATUS_UNDER_REVIEW,
            ])
            ->count();

        return [
            'annual_quota_days' => $quota,
            'annual_used_days' => $used,
            'annual_remaining_days' => max(0, $quota - $used),
            'izin_this_month' => $izinThisMonth,
            'max_izin_per_month' => (int) config('hadirmetrik.leave.max_izin_per_month', 2),
            'pending_count' => $pending,
        ];
    }
}
