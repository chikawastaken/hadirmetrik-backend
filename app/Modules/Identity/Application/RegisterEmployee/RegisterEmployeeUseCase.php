<?php

namespace App\Modules\Identity\Application\RegisterEmployee;

use App\Mail\RegistrationMail;
use App\Modules\Audit\Contracts\SecurityEventRecorder;
use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Employees\Contracts\InvitationCodeConsumer;
use App\Modules\Identity\Application\Exceptions\AccountAlreadyExists;
use App\Modules\Identity\Application\Exceptions\RegistrationFailed;
use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\Data\AuthResult;
use App\Modules\Identity\Contracts\PasswordHasher;
use App\Modules\Identity\Contracts\TokenIssuer;
use App\Shared\Contracts\TransactionManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Registers an employee against a pre-existing HR record and a one-time
 * invitation code. The role is always assigned by the backend.
 */
final class RegisterEmployeeUseCase
{
    private const DEFAULT_ROLE = 'employee';

    public function __construct(
        private readonly EmployeeDirectory $employees,
        private readonly InvitationCodeConsumer $invitationCodes,
        private readonly AccountRepository $accounts,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
        private readonly SecurityEventRecorder $events,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(RegisterEmployeeInput $input): AuthResult
    {
        $employee = $this->employees->findByWorkEmail($input->workEmail);
        $errors = $this->validate($input, $employee);

        if ($errors !== []) {
            throw new RegistrationFailed('Validasi gagal.', $errors);
        }

        if ($this->accounts->existsByEmail($input->workEmail)) {
            throw new AccountAlreadyExists;
        }

        $result = $this->transactions->run(function () use ($input, $employee): AuthResult {
            if (! $this->invitationCodes->consume($input->invitationCode)) {
                throw new RegistrationFailed('Validasi gagal.', [
                    'invitation_code' => ['Kode undangan tidak valid atau sudah dipakai.'],
                ]);
            }

            $account = $this->accounts->create(
                $employee->name,
                $input->workEmail,
                $this->hasher->hash($input->password),
                self::DEFAULT_ROLE,
            );

            $issuedToken = $this->tokens->issue($account, 'mobile');

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $account->id,
                    'hash' => sha1($account->email),
                ],
            );

            Mail::to($account->email)
                ->send(new RegistrationMail($account, $verificationUrl));

            return new AuthResult($account, $issuedToken);
        });

        $this->events->record('register.succeeded', [
            'employee_number' => $employee->employeeNumber,
        ], $result->account->id);

        return $result;
    }

    /**
     * @return array<string, list<string>>
     */
    private function validate(RegisterEmployeeInput $input, ?EmployeeSummary $employee): array
    {
        $errors = [];

        if ($employee === null || ! $employee->isActive) {
            $errors['work_email'][] = 'Data karyawan dengan email ini tidak ditemukan.';
        } elseif (! hash_equals($employee->employeeNumber, $input->employeeNumber)) {
            $errors['employee_number'][] = 'Nomor karyawan tidak cocok dengan data HR.';
        }

        if (! $this->invitationCodes->isConsumable($input->invitationCode)) {
            $errors['invitation_code'][] = 'Kode undangan tidak valid atau sudah dipakai.';
        }

        return $errors;
    }
}
