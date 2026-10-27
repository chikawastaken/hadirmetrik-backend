<?php

use App\Mail\RegistrationMail;
use App\Modules\Identity\Contracts\AccountRepository;
use App\Modules\Identity\Contracts\PasswordHasher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('email:verification-test {email}', function (
    string $email,
    AccountRepository $accounts,
    PasswordHasher $hasher,
) {
    if (! app()->environment('local')) {
        $this->error('Perintah ini hanya boleh dijalankan pada APP_ENV=local.');

        return Command::FAILURE;
    }

    $email = strtolower($email);
    $validator = Validator::make(['email' => $email], ['email' => ['required', 'email']]);

    if ($validator->fails()) {
        $this->error($validator->errors()->first('email'));

        return Command::FAILURE;
    }

    if (! $this->confirm('Akun uji dan data karyawan akan disimpan permanen di database aplikasi: '.config('database.connections.'.config('database.default').'.database').'. Lanjutkan?')) {
        return Command::FAILURE;
    }

    if (DB::table('users')->where('email', $email)->exists()) {
        $this->error('Email tersebut sudah terdaftar sebagai akun.');

        return Command::FAILURE;
    }

    $password = $this->secret('Password akun uji (minimal 8 karakter)');
    $passwordConfirmation = $this->secret('Konfirmasi password');
    $passwordValidator = Validator::make([
        'password' => $password,
        'password_confirmation' => $passwordConfirmation,
    ], [
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    if ($passwordValidator->fails()) {
        $this->error($passwordValidator->errors()->first('password'));

        return Command::FAILURE;
    }

    $employee = DB::table('employees')->where('work_email', $email)->first();
    $employeeNumber = $employee?->employee_number ?? 'HM-VERIFY-'.Str::upper(Str::random(8));

    $account = DB::transaction(function () use ($accounts, $email, $employee, $employeeNumber, $hasher, $password) {
        if ($employee === null) {
            DB::table('employees')->insert([
                'employee_number' => $employeeNumber,
                'full_name' => 'Email Verification Test',
                'work_email' => $email,
                'employment_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $account = $accounts->create(
            'Email Verification Test',
            $email,
            $hasher->hash($password),
            'employee',
            'active',
            false,
        );

        DB::table('employees')
            ->where('work_email', $email)
            ->update(['user_id' => $account->id, 'updated_at' => now()]);

        return $account;
    });

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $account->id,
            'hash' => sha1($account->email),
        ],
    );

    Mail::to($account->email)->send(new RegistrationMail($account, $verificationUrl));

    $this->info("Akun uji tersimpan dan email verifikasi dikirim ke {$email}.");

    return Command::SUCCESS;
})->purpose('Buat akun uji persisten dan kirim email verifikasi via SMTP');
