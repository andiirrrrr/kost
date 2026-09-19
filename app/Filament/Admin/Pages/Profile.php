<?php

namespace App\Filament\Admin\Pages;

use App\Models\ActivityLog;
use App\Models\Setting;
use BackedEnum;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends BaseEditProfile
{
    protected static ?string $navigationLabel = 'Profil Saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem & Data';

    protected static ?int $navigationSort = 99;

    protected static bool $shouldRegisterNavigation = true;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string|Htmlable
    {
        return 'Profil Saya';
    }

    public static function getLabel(): string
    {
        return 'Profil Saya';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Profil berhasil diperbarui';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (empty($data['phone'])) {
            $data['phone'] = Setting::get('contact_phone', '');
        }

        return $data;
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Nama Lengkap')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Alamat Email')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->live(debounce: 500);
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label('Nomor WhatsApp')
            ->helperText('Nomor WhatsApp ini menjadi kontak utama yang tampil di landing page dan portal bantuan penghuni.')
            ->tel()
            ->maxLength(30);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password Baru')
            ->helperText('Kosongkan jika tidak ingin mengubah password.')
            ->password()
            ->revealable()
            ->rule(Password::default())
            ->autocomplete('new-password')
            ->dehydrated(fn ($state): bool => filled($state))
            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Konfirmasi Password Baru')
            ->password()
            ->autocomplete('new-password')
            ->revealable()
            ->required()
            ->visible(fn (Get $get): bool => filled($get('password')))
            ->dehydrated(false);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('currentPassword')
            ->label('Password Saat Ini')
            ->helperText('Wajib diisi untuk mengonfirmasi perubahan email atau password.')
            ->password()
            ->autocomplete('current-password')
            ->currentPassword(guard: Filament::getAuthGuard())
            ->revealable()
            ->required()
            ->visible(fn (Get $get): bool => filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email')))
            ->dehydrated(false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPhoneFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $hasPassword = array_key_exists('password', $data) && filled($data['password']);
        $hasEmail = array_key_exists('email', $data) && $data['email'] !== $record->getAttributeValue('email');

        $record->update($data);

        if (array_key_exists('phone', $data) && filled($data['phone'])) {
            Setting::set('contact_phone', $data['phone']);
        }

        ActivityLog::record('admin.profile_updated', $record, [
            'updated_email' => $hasEmail,
            'updated_password' => $hasPassword,
        ]);

        return $record;
    }
}
