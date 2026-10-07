<?php

namespace App\Filament\Pages;

use App\Settings\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProfilSekolah extends Page
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected string $view = 'filament.pages.profil-sekolah';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string | \UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Profil Sekolah';

    protected static ?string $title = 'Profil Sekolah';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.settings.viewAny') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill(app(SchoolSettings::class)->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Sekolah')
                    ->schema([
                        TextInput::make('school_name')
                            ->label('Nama Sekolah')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('npsn')
                            ->label('NPSN')
                            ->maxLength(20),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Kontak')
                    ->schema([
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Pejabat')
                    ->schema([
                        TextInput::make('headmaster_name')
                            ->label('Nama Kepala Sekolah')
                            ->maxLength(255),
                        TextInput::make('headmaster_nip')
                            ->label('NIP Kepala Sekolah')
                            ->maxLength(30),
                        TextInput::make('treasurer_name')
                            ->label('Nama Bendahara')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Keuangan')
                    ->schema([
                        TextInput::make('spp_due_day')
                            ->label('Tanggal Jatuh Tempo SPP')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->helperText('Hari dalam bulan ketika tagihan SPP dianggap jatuh tempo (1–31).'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = app(SchoolSettings::class);
        $settings->fill($data);
        $settings->save();

        Notification::make()
            ->success()
            ->title('Pengaturan disimpan.')
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Simpan')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }
}
