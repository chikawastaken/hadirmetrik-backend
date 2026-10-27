<?php

use App\Modules\Identity\Application\RegisterEmployee\RegisterEmployeeInput;
use App\Modules\Identity\Application\RegisterEmployee\RegisterEmployeeUseCase;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('email:verification-test {email}', function (string $email, RegisterEmployeeUseCase $useCase) {
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
    $employeeNumber = $employee?->employee_number ?? 'EMP-VERIFY-'.Str::upper(Str::random(8));
    $invitationCode = 'INV-VERIFY-'.Str::upper(Str::random(12));

    DB::transaction(function () use ($employee, $employeeNumber, $email, $invitationCode, $password, $useCase): void {
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

        DB::table('invitation_codes')->insert([
            'code' => $invitationCode,
            'is_used' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $useCase->execute(new RegisterEmployeeInput(
            $employeeNumber,
            $email,
            $password,
            $invitationCode,
        ));
    });

    $this->info("Akun uji tersimpan dan email verifikasi dikirim ke {$email}.");

    return Command::SUCCESS;
})->purpose('Buat akun uji persisten dan kirim email verifikasi via SMTP');
