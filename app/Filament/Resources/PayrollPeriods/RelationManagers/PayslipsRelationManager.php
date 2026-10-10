<?php

namespace App\Filament\Resources\PayrollPeriods\RelationManagers;

use App\Enums\SalaryCalculation;
use App\Models\SalaryComponent;
use App\Services\Payroll\PayrollService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PayslipsRelationManager extends RelationManager
{
    protected static string $relationship = 'payslips';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('notes')
                ->label('Catatan Slip')
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('base_salary')
                    ->label('Gaji Pokok')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_earnings')
                    ->label('Pendapatan')
                    ->money('IDR'),
                TextColumn::make('total_deductions')
                    ->label('Potongan')
                    ->money('IDR'),
                TextColumn::make('net_salary')
                    ->label('Neto')
                    ->money('IDR')
                    ->weight('semibold'),
                TextColumn::make('days')
                    ->label('H/S/I/A')
                    ->state(fn (Model $record): string => $record->days_present.'/'.$record->days_sick.'/'.$record->days_leave.'/'.$record->days_absent),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('entri')
                    ->label('Entri Manual')
                    ->icon('heroicon-m-plus-circle')
                    ->visible(fn (): bool => $this->getOwnerRecord()->status->isEditable()
                        && auth()->user()?->can('payroll.calculate'))
                    ->schema([
                        Select::make('salary_component_id')
                            ->label('Komponen')
                            ->options(fn (): array => SalaryComponent::query()
                                ->where('calculation', SalaryCalculation::ManualEntry)
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->pluck('name', 'id')
                                ->all())
                            ->live()
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Kuantitas (JP / unit)')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->helperText('HONOR_PER_JAM: nominal = JP x tarif'),
                        TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('description')
                            ->label('Keterangan')
                            ->maxLength(255),
                    ])
                    ->action(function (Model $record, array $data): void {
                        $component = SalaryComponent::query()->findOrFail($data['salary_component_id']);
                        $employee = $record->employee;

                        $finalAmount = $data['quantity'] !== null
                            ? (int) $data['quantity'] * PayrollService::manualRate($employee, $component)
                            : (int) $data['amount'];

                        app(PayrollService::class)->addManualEntry(
                            period: $this->getOwnerRecord(),
                            employee: $employee,
                            component: $component,
                            amount: $finalAmount,
                            description: $data['description'] ?? null,
                        );

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Entri manual tersimpan - jalankan Hitung untuk memperbarui BPJS/total')
                            ->send();
                    }),
                Action::make('hapus')
                    ->label('Hapus Entri')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->visible(fn (): bool => $this->getOwnerRecord()->status->isEditable()
                        && auth()->user()?->can('payroll.calculate'))
                    ->schema([
                        Select::make('payslip_item_id')
                            ->label('Komponen Manual')
                            ->options(fn (Model $record): array => $record->items()
                                ->whereHas('salaryComponent', fn ($query) => $query->where('calculation', SalaryCalculation::ManualEntry))
                                ->with('salaryComponent')
                                ->get()
                                ->mapWithKeys(fn ($item): array => [$item->getKey() => $item->salaryComponent->name])
                                ->all())
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data): void {
                        $item = $record->items()->whereKey($data['payslip_item_id'])->first();

                        if ($item === null) {
                            return;
                        }

                        app(PayrollService::class)->removeManualEntry($this->getOwnerRecord(), $item);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Entri manual dihapus - jalankan Hitung untuk memperbarui BPJS/total')
                            ->send();
                    }),
                Action::make('slip')
                    ->label('Slip Gaji')
                    ->icon('heroicon-m-document-arrow-down')
                    ->color('gray')
                    ->visible(fn (): bool => $this->getOwnerRecord()->status->isLocked())
                    ->url(fn (Model $record): string => route('payroll.slip', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }
}
