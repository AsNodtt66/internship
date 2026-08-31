<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

/**
 * Login khusus: field pertama menerima Email ATAU NIP.
 *
 * - Semua role selain Pembimbing Lapangan tetap pakai email seperti biasa.
 * - Pembimbing Lapangan login pakai NIP (sesuai info dari pegawai di
 *   lokasi PKL, lihat catatan di area internship-research-management-system).
 *
 * Deteksi otomatis: kalau yang diketik berformat email (ada '@'), dicari
 * berdasarkan kolom `email`. Kalau bukan format email, dicari berdasarkan
 * kolom `nip`. Jadi satu input field, dua cara login.
 */
class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email atau NIP')
            ->helperText('Gunakan email akun Anda. Pembimbing lapangan dapat menggunakan NIP.')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = trim($data['email']);

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => $login,
                'password' => $data['password'],
            ];
        }

        // Bukan format email -> anggap NIP (khusus akun Pembimbing Lapangan).
        return [
            'nip' => $login,
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
