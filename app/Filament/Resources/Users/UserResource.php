<?php

namespace App\Filament\Resources\Users;

use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan Akses';

    protected static ?string $navigationLabel = 'Pengguna';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),

                Components\TextInput::make('email')
                    ->label('Email / NIP')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Isi email biasa untuk akun kantor, atau NIP (angka saja) untuk akun Pembimbing Lapangan.')
                    ->rule(function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if (! filter_var($value, FILTER_VALIDATE_EMAIL) && ! ctype_digit((string) $value)) {
                                $fail('Isi dengan email yang valid, atau NIP berupa angka saja.');
                            }
                        };
                    })
                    // Tampilkan NIP polos ke user (buang suffix teknis '@nip.internal').
                    ->formatStateUsing(fn (?string $state) => $state ? preg_replace('/@nip\.internal$/', '', $state) : $state)
                    // Saat disimpan: kalau yang diketik cuma angka (NIP), tempel lagi suffix di belakang layar
                    // supaya tetap valid sebagai kolom email unik di database.
                    ->dehydrateStateUsing(fn (?string $state) => $state && ctype_digit((string) $state) ? $state.'@nip.internal' : $state),

                Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),

                Components\Select::make('role_id')
                    ->label('Role')
                    ->relationship('role', 'nama_role')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false)
                    ->helperText(fn (?User $record): string => $record?->is(Auth::user())
                        ? 'Role akun Anda sendiri dikunci untuk mencegah self-demotion dan kehilangan akses administrasi.'
                        : 'Wajib dipilih -- akun tidak bisa memproses apapun kalau Role kosong.'),

                Components\Toggle::make('is_active')
                    ->label('Akun Aktif')
                    ->default(true)
                    ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false)
                    ->helperText(fn (?User $record): string => $record?->is(Auth::user())
                        ? 'Akun Anda sendiri tidak dapat dinonaktifkan dari halaman ini.'
                        : 'Nonaktifkan akun untuk mencabut akses tanpa menghapus riwayat data.'),

                Components\Select::make('bagian_id')
                    ->label('Bagian')
                    ->relationship('bagian', 'nama_bagian')
                    ->searchable()
                    ->preload()
                    ->helperText('Isi kalau akun ini terikat ke satu Bagian tertentu (mis. Kepala Bagian). Boleh dikosongkan untuk role yang berlaku lintas bagian seperti GM/Kabag SDM/Staff SDM.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email / NIP')
                    ->formatStateUsing(fn (?string $state) => $state ? preg_replace('/@nip\.internal$/', '', $state) : $state)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role.nama_role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (?string $state) => $state ? 'success' : 'danger')
                    ->placeholder('BELUM DISET')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bagian.nama_bagian')
                    ->label('Bagian')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Role')
                    ->relationship('role', 'nama_role'),
                Tables\Filters\TernaryFilter::make('role_id')
                    ->label('Role Belum Diset')
                    ->queries(
                        true: fn ($query) => $query->whereNull('role_id'),
                        false: fn ($query) => $query->whereNotNull('role_id'),
                    ),
            ])
            ->actions([
                Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role?->slug === 'pic';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
