<?php

namespace App\Filament\Resources\PayrollPeriods\Pages;

use App\Enums\PayrollStatus;
use App\Exceptions\AccountingException;
use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Models\CashAccount;
use App\Services\Payroll\PayrollService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ViewPayrollPeriod extends ViewRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        $canCalculate = auth()->user()?->can('payroll.calculate') ?? false;
        $canApprove = auth()->user()?->can('payroll.approve') ?? false;
        $canPay = auth()->user()?->can('payroll.pay') ?? false;

        $status = fn (): PayrollStatus => $this->getRecord()->status;

        return [
            Action::make('hitung')
                ->label('Hitung')
                ->icon('heroicon-m-calculator')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $canCalculate && $status()->isEditable())
                ->action(function (): void {
                    try {
                        $period = app(PayrollService::class)->calculate($this->getRecord(), auth()->user());

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Payroll dihitung — total neto Rp '.number_format($period->total_net, 0, ',', '.'))
                            ->send();
                    } catch (AccountingException $exception) {
                        throw ValidationException::withMessages(['period' => $exception->getMessage()]);
                    }
                }),
            Action::make('setujui')
                ->label('Setujui')
                ->icon('heroicon-m-check-badge')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $canApprove && $status() === PayrollStatus::Calculated)
                ->action(function (): void {
                    try {
                        app(PayrollService::class)->approve($this->getRecord(), auth()->user());

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Payroll disetujui — jurnal akrual (JE #9) diposting')
                            ->send();
                    } catch (AccountingException $exception) {
                        throw ValidationException::withMessages(['period' => $exception->getMessage()]);
                    }
                }),
            Action::make('bayar')
                ->label('Bayar')
                ->icon('heroicon-m-banknotes')
                ->color('success')
                ->visible(fn (): bool => $canPay && $status() === PayrollStatus::Approved)
                ->modalWidth(Width::TwoExtraLarge)
                ->schema([
                    Select::make('cash_account_id')
                        ->label('Kas/Bank')
                        ->options(fn (): array => CashAccount::query()->where('is_active', true)
                            ->orderBy('type')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('payment_date')
                        ->label('Tanggal Pembayaran')
                        ->default(today())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $cashAccount = CashAccount::query()->findOrFail($data['cash_account_id']);
                    $paymentDate = Carbon::parse($data['payment_date']);

                    try {
                        app(PayrollService::class)->pay(
                            period: $this->getRecord(),
                            cashAccount: $cashAccount,
                            paymentDate: $paymentDate,
                            actor: auth()->user(),
                        );

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Payroll dibayar — jurnal pembayaran (JE #10) diposting')
                            ->send();
                    } catch (AccountingException $exception) {
                        throw ValidationException::withMessages(['cash_account_id' => $exception->getMessage()]);
                    }
                }),
            Action::make('batalkan')
                ->label('Batalkan')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $canCalculate && $status()->isEditable())
                ->action(function (): void {
                    try {
                        app(PayrollService::class)->cancel($this->getRecord(), auth()->user());

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Payroll dibatalkan')
                            ->send();
                    } catch (AccountingException $exception) {
                        throw ValidationException::withMessages(['period' => $exception->getMessage()]);
                    }
                }),
        ];
    }
}
