<?php

namespace App\Filament\Peserta\Pages\Auth;

use App\Models\Peserta;
use App\Models\User;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
            $this->getNimFormComponent(),
            $this->getUniversitasFormComponent(),
            $this->getJurusanFormComponent(),
            $this->getNoHpFormComponent(),
        ]);
    }

    protected function getNimFormComponent(): Component
    {
        return TextInput::make('nim')->label('NIM / NISN')->required()->maxLength(255);
    }

    protected function getUniversitasFormComponent(): Component
    {
        return TextInput::make('universitas')->label('Universitas / Sekolah')->required()->maxLength(255);
    }

    protected function getJurusanFormComponent(): Component
    {
        return TextInput::make('jurusan')->label('Jurusan / Program Studi')->required()->maxLength(255);
    }

    protected function getNoHpFormComponent(): Component
    {
        return TextInput::make('no_hp')->label('No. HP')->tel()->maxLength(255);
    }

    /**
     * Isi form dengan data User + data Peserta terkait (relasi terpisah tabel).
     */
    protected function fillForm(): void
    {
        $user = $this->getPesertaUser();
        $data = $user->attributesToArray();

        $peserta = $user->peserta;
        $data['nim'] = $peserta?->nim;
        $data['universitas'] = $peserta?->universitas;
        $data['jurusan'] = $peserta?->jurusan;
        $data['no_hp'] = $peserta?->no_hp;

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);
    }

    /**
     * Simpan field User (name/email/password) lewat parent, lalu simpan
     * field khusus Peserta (nim/universitas/jurusan/no_hp) ke tabel terpisah.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof User) {
            throw new \LogicException('Profil peserta harus menggunakan model pengguna aplikasi.');
        }

        $pesertaData = [
            'nim' => $data['nim'] ?? null,
            'universitas' => $data['universitas'] ?? null,
            'jurusan' => $data['jurusan'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
        ];

        unset($data['nim'], $data['universitas'], $data['jurusan'], $data['no_hp']);

        parent::handleRecordUpdate($record, $data);

        Peserta::updateOrCreate(['user_id' => $record->id], $pesertaData);

        return $record;
    }

    private function getPesertaUser(): User
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new \LogicException('Panel peserta harus menggunakan model pengguna aplikasi.');
        }

        return $user;
    }
}
