<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentMethod;
use App\Exceptions\AccountingException;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\CashAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Services\Billing\PaymentService;
use App\Services\Billing\PaymentSource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('catat')
                ->label('Catat Pembayaran')
                ->icon('heroicon-m-banknotes')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('billing.payment.create') ?? false)
                ->modalWidth(Width::ThreeExtraLarge)
                ->schema([
                    Select::make('student_id')
                        ->label('Siswa')
                        ->options(fn (): array => Student::query()->orderBy('full_name')->pluck('full_name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),
                    TextInput::make('amount')
                        ->label('Jumlah Diterima (Rp)')
                        ->numeric()
                        ->minValue(1)
                        ->prefix('Rp')
                        ->live(onBlur: true)
                        ->required(),
                    Select::make('method')
                        ->label('Metode')
                        ->options(collect(PaymentMethod::cases())->mapWithKeys(
                            fn (PaymentMethod $method): array => [$method->value => $method->label()],
                        )->all())
                        ->default(PaymentMethod::Tunai->value)
                        ->required(),
                    Select::make('cash_account_id')
                        ->label('Kas/Bank')
                        ->options(fn (): array => CashAccount::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->default(fn (): ?int => CashAccount::query()->where('is_default_kas', true)->value('id'))
                        ->searchable()
                        ->required(),
                    DatePicker::make('payment_date')
                        ->label('Tanggal')
                        ->default(today())
                        ->required(),
                    Actions::make([
                        Action::make('isiFifo')
                            ->label('Isi Otomatis (FIFO)')
                            ->icon('heroicon-m-sparkles')
                            ->action(function (Get $get, Set $set): void {
                                $studentId = $get('student_id');
                                $amount = (int) $get('amount');

                                if ($studentId === null || $amount <= 0) {
                                    return;
                                }

                                $plan = PaymentService::proposeFifoAllocations((int) $studentId, $amount);

                                $set('allocations', collect($plan)->map(fn (int $value, int $invoiceId): array => [
                                    'invoice_id' => (string) $invoiceId,
                                    'amount' => $value,
                                ])->values()->all());
                            }),
                    ]),
                    Repeater::make('allocations')
                        ->label('Alokasi Tagihan (opsional)')
                        ->schema([
                            Select::make('invoice_id')
                                ->label('Tagihan')
                                ->options(fn (Get $get): array => Invoice::query()
                                    ->where('student_id', $get('../../student_id'))
                                    ->whereIn('status', ['issued', 'partially_paid'])
                                    ->orderBy('due_date')
                                    ->get()
                                    ->mapWithKeys(fn (Invoice $invoice): array => [
                                        $invoice->getKey() => "{$invoice->number} — sisa Rp ".number_format($invoice->remainingAmount(), 0, ',', '.'),
                                    ])->all())
                                ->distinct()
                                ->live()
                                ->required(),
                            TextInput::make('amount')
                                ->label('Alokasi (Rp)')
                                ->numeric()
                                ->minValue(1)
                                ->prefix('Rp')
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->addActionLabel('Tambah alokasi manual')
                        ->helperText('Kosong = sistem membagi otomatis FIFO (jatuh tempo tertua dulu).'),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): Payment {
                    $allocations = collect($data['allocations'] ?? [])
                        ->filter(fn (array $row): bool => filled($row['invoice_id'] ?? null))
                        ->mapWithKeys(fn (array $row): array => [
                            (int) $row['invoice_id'] => (int) $row['amount'],
                        ])->all();

                    try {
                        $payment = app(PaymentService::class)->record(new PaymentSource(
                            studentId: (int) $data['student_id'],
                            paymentDate: Carbon::parse($data['payment_date']),
                            method: PaymentMethod::from($data['method']),
                            cashAccountId: (int) $data['cash_account_id'],
                            amount: (int) $data['amount'],
                            allocations: $allocations,
                            notes: $data['notes'] ?? null,
                            userId: auth()->id(),
                        ));
                    } catch (AccountingException $exception) {
                        throw ValidationException::withMessages([
                            'amount' => $exception->getMessage(),
                        ]);
                    }

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title("Kwitansi {$payment->number} tercatat: Rp ".number_format($payment->amount, 0, ',', '.'))
                        ->actions([
                            Action::make('cetak')
                                ->label('Cetak Kwitansi')
                                ->url(route('billing.kwitansi', $payment), shouldOpenInNewTab: true)
                                ->button(),
                        ])
                        ->send();

                    return $payment;
                }),
        ];
    }
}
