<?php

namespace Tests\Unit\Modules\Identity;

use App\Modules\Audit\Contracts\SecurityEventRecorder;
use App\Modules\Employees\Contracts\Data\EmployeeSummary;
use App\Modules\Employees\Contracts\EmployeeDirectory;
use App\Modules\Employees\Contracts\InvitationCodeConsumer;
use App\Modules\Identity\Application\Exceptions\AccountAlreadyExists;
use App\Modules\Identity\Application\Exceptions\RegistrationFailed;
use App\Modules\Identity\Application\RegisterEmployee\RegisterEmployeeInput;
use App\Modules\Identity\Application\RegisterEmployee\RegisterEmployeeUseCase;
use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\Data\Account;
use App\Modules\Identity\Contracts\Data\AccountCredentials;
use App\Modules\Identity\Contracts\Data\IssuedToken;
use App\Modules\Identity\Contracts\PasswordHasher;
use App\Modules\Identity\Contracts\TokenIssuer;
use App\Shared\Contracts\TransactionManager;
use PHPUnit\Framework\TestCase;

class RegisterEmployeeUseCaseTest extends TestCase
{
    private FakeEmployeeDirectory $employees;

    private FakeInvitationCodeConsumer $invitationCodes;

    private FakeAccountRepository $accounts;

    private FakePasswordHasher $hasher;

    private FakeTokenIssuer $tokens;

    private RecordingSecurityEventRecorder $events;

    private RegisterEmployeeUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employees = new FakeEmployeeDirectory;
        $this->invitationCodes = new FakeInvitationCodeConsumer;
        $this->accounts = new FakeAccountRepository;
        $this->hasher = new FakePasswordHasher;
        $this->tokens = new FakeTokenIssuer;
        $this->events = new RecordingSecurityEventRecorder;

        $this->useCase = new RegisterEmployeeUseCase(
            $this->employees,
            $this->invitationCodes,
            $this->accounts,
            $this->hasher,
            $this->tokens,
            $this->events,
            new ImmediateTransactionManager,
        );
    }

    public function test_it_creates_an_account_and_returns_a_token(): void
    {
        $employee = new EmployeeSummary(10, 'EMP-0001', 'Karyawan Uji', 'uji@hadirmetrik.local', true);
        $this->employees->add($employee);
        $this->invitationCodes->consumable = true;

        $result = $this->useCase->execute(new RegisterEmployeeInput(
            'EMP-0001',
            'uji@hadirmetrik.local',
            'rahasia123',
            'INVITE01',
        ));

        $this->assertSame('uji@hadirmetrik.local', $result->account->email);
        $this->assertSame('employee', $result->account->role);
        $this->assertSame('token-'.$this->accounts->lastId, $result->token->token);
        $this->assertContains('INVITE01', $this->invitationCodes->consumed);
        $this->assertSame('employee', $this->accounts->created[0]['role']);
        $this->assertSame('register.succeeded', $this->events->events[0]['event']);
    }

    public function test_it_rejects_a_work_email_without_an_employee_record(): void
    {
        $this->invitationCodes->consumable = true;

        try {
            $this->useCase->execute(new RegisterEmployeeInput(
                'EMP-0001',
                'tidak-ada@hadirmetrik.local',
                'rahasia123',
                'INVITE01',
            ));
            $this->fail('Expected RegistrationFailed.');
        } catch (RegistrationFailed $e) {
            $this->assertArrayHasKey('work_email', $e->errors());
        }

        $this->assertSame([], $this->accounts->created);
    }

    public function test_it_rejects_a_mismatched_employee_number(): void
    {
        $this->employees->add(new EmployeeSummary(10, 'EMP-0001', 'Karyawan Uji', 'uji@hadirmetrik.local', true));
        $this->invitationCodes->consumable = true;

        try {
            $this->useCase->execute(new RegisterEmployeeInput(
                'EMP-9999',
                'uji@hadirmetrik.local',
                'rahasia123',
                'INVITE01',
            ));
            $this->fail('Expected RegistrationFailed.');
        } catch (RegistrationFailed $e) {
            $this->assertArrayHasKey('employee_number', $e->errors());
        }
    }

    public function test_it_rejects_an_unusable_invitation_code(): void
    {
        $this->employees->add(new EmployeeSummary(10, 'EMP-0001', 'Karyawan Uji', 'uji@hadirmetrik.local', true));
        $this->invitationCodes->consumable = false;

        try {
            $this->useCase->execute(new RegisterEmployeeInput(
                'EMP-0001',
                'uji@hadirmetrik.local',
                'rahasia123',
                'INVITE01',
            ));
            $this->fail('Expected RegistrationFailed.');
        } catch (RegistrationFailed $e) {
            $this->assertArrayHasKey('invitation_code', $e->errors());
        }
    }

    public function test_it_rejects_an_email_that_already_has_an_account(): void
    {
        $this->employees->add(new EmployeeSummary(10, 'EMP-0001', 'Karyawan Uji', 'uji@hadirmetrik.local', true));
        $this->invitationCodes->consumable = true;
        $this->accounts->existingEmails[] = 'uji@hadirmetrik.local';

        $this->expectException(AccountAlreadyExists::class);

        $this->useCase->execute(new RegisterEmployeeInput(
            'EMP-0001',
            'uji@hadirmetrik.local',
            'rahasia123',
            'INVITE01',
        ));
    }
}

final class FakeEmployeeDirectory implements EmployeeDirectory
{
    /** @var array<string, EmployeeSummary> */
    private array $byEmail = [];

    public function add(EmployeeSummary $employee): void
    {
        $this->byEmail[$employee->workEmail] = $employee;
    }

    public function findByWorkEmail(string $workEmail): ?EmployeeSummary
    {
        return $this->byEmail[$workEmail] ?? null;
    }
}

final class FakeInvitationCodeConsumer implements InvitationCodeConsumer
{
    public bool $consumable = false;

    /** @var list<string> */
    public array $consumed = [];

    public function isConsumable(string $code): bool
    {
        return $this->consumable;
    }

    public function consume(string $code): bool
    {
        if (! $this->consumable) {
            return false;
        }

        $this->consumed[] = $code;

        return true;
    }
}

final class FakeAccountRepository implements AccountRepository
{
    /** @var list<string> */
    public array $existingEmails = [];

    /** @var list<array{name: string, email: string, passwordHash: string, role: string}> */
    public array $created = [];

    public int $lastId = 0;

    public function findById(int $id): ?Account
    {
        return null;
    }

    public function findCredentialsByEmail(string $email): ?AccountCredentials
    {
        return null;
    }

    public function existsByEmail(string $email): bool
    {
        return in_array($email, $this->existingEmails, true);
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        string $role,
        string $status = 'active',
        bool $mustChangePassword = true,
    ): Account {
        $this->created[] = compact('name', 'email', 'passwordHash', 'role');
        $this->lastId = count($this->created);

        return new Account($this->lastId, $name, $email, $role, $status, $mustChangePassword);
    }
}

final class FakePasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return 'hashed:'.$plain;
    }

    public function verify(string $plain, string $hash): bool
    {
        return $hash === 'hashed:'.$plain;
    }
}

final class FakeTokenIssuer implements TokenIssuer
{
    public function issue(Account $account, string $deviceName): IssuedToken
    {
        return new IssuedToken('token-'.$account->id, 'Bearer');
    }

    public function revoke(string $tokenId): void {}
}

final class RecordingSecurityEventRecorder implements SecurityEventRecorder
{
    /** @var list<array{event: string, context: array, actorUserId: ?int}> */
    public array $events = [];

    public function record(string $event, array $context = [], ?int $actorUserId = null): void
    {
        $this->events[] = compact('event', 'context', 'actorUserId');
    }
}

final class ImmediateTransactionManager implements TransactionManager
{
    public function run(callable $callback): mixed
    {
        return $callback();
    }
}
