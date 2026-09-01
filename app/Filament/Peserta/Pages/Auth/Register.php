<?php

namespace App\Filament\Peserta\Pages\Auth;

use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Services\DynamicFormFieldBuilder;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportRedirects\Redirector;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getNimFormComponent(),
            $this->getUniversitasFormComponent(),
            $this->getJurusanFormComponent(),
            ...app(DynamicFormFieldBuilder::class)->buildFor('registrasi_peserta'),
        ]);
    }

    /**
     * Override total dari Register::register() bawaan Filament.
     *
     * Alasan: implementasi bawaan otomatis meng-auth-kan (login) user yang
     * baru saja register (Filament::auth()->login($user)) lalu langsung
     * redirect ke dashboard. Sesuai permintaan, alur yang diinginkan adalah
     * Register -> berhasil -> redirect ke halaman Login -> peserta login
     * manual -> Dashboard. Jadi baris login-otomatis SENGAJA dihapus di
     * sini, diganti notifikasi sukses + redirect ke getLoginUrl().
     */
    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function (): Model {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        $this->sendEmailVerificationNotification($user);

        Notification::make()
            ->title('Registrasi berhasil')
            ->body('Akun berhasil dibuat. Silakan masuk menggunakan email dan kata sandi yang Anda daftarkan.')
            ->success()
            ->send();

        // Bungkus redirect ke halaman Login sebagai RegistrationResponse
        // (bukan Filament::auth()->login() + redirect ke dashboard seperti
        // bawaan) supaya kontrak return type register() tetap terpenuhi.
        return new class implements RegistrationResponse
        {
            public function toResponse($request): RedirectResponse|Redirector
            {
                return redirect(Filament::getLoginUrl());
            }
        };
    }

    protected function getNimFormComponent(): Component
    {
        return TextInput::make('nim')
            ->label('NIM atau NISN')
            ->helperText('Masukkan nomor induk yang digunakan oleh kampus atau sekolah Anda.')
            ->required()
            ->maxLength(255);
    }

    protected function getUniversitasFormComponent(): Component
    {
        return TextInput::make('universitas')
            ->label('Universitas atau Sekolah')
            ->autocomplete('organization')
            ->required()
            ->maxLength(255);
    }

    protected function getJurusanFormComponent(): Component
    {
        return TextInput::make('jurusan')
            ->label('Jurusan atau Program Studi')
            ->required()
            ->maxLength(255);
    }

    /**
     * Buat User dengan role 'peserta' otomatis, lalu buat record Peserta
     * (NIM, universitas, jurusan) sekaligus dalam satu transaksi.
     */
    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $roleId = Role::where('slug', 'peserta')->value('id');

            $user = $this->getUserModel()::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $roleId,
                'is_active' => true,
            ]);

            if (! $user instanceof User) {
                throw new \LogicException('Panel peserta harus membuat model pengguna aplikasi.');
            }

            Peserta::create([
                'user_id' => $user->id,
                'nim' => $data['nim'],
                'universitas' => $data['universitas'],
                'jurusan' => $data['jurusan'],
                'data_tambahan' => $data['data_tambahan'] ?? null,
            ]);

            return $user;
        });
    }
}
