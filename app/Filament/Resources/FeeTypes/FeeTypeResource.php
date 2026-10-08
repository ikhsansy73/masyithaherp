<?php

namespace App\Filament\Resources\FeeTypes;

use App\Enums\FeeCategory;
use App\Filament\Resources\FeeTypes\Pages\ManageFeeTypes;
use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\StudentFee;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeeTypeResource extends Resource
{
    protected static ?string $model = FeeType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'Jenis Biaya';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Jenis Biaya';
    }

    /**
     * Fee configuration (doc 09): billing.fee — bendahara manages,
     * kepala_sekolah/operator_tu view.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        return static::billingFeeResponse($action, $record);
    }

    /**
     * Shared billing.fee mapping for the fee-configuration resources.
     */
    public static function billingFeeResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'billing.fee.view',
            'create', 'update', 'replicate', 'reorder' => 'billing.fee.update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => 'billing.fee.delete',
            default => null,
        };

        if ($permission === null) {
            return parent::getAuthorizationResponse($action, $record);
        }

        if ($record !== null && $ability === 'delete' && static::hasGeneratedInvoices($record)) {
            return Response::deny(static::deleteBlockMessage($record));
        }

        return auth()->user()?->can($permission)
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Whether the record already produced invoices: a fee type with invoice
     * items, or a student fee whose student has an invoice for the same
     * fee type and year.
     */
    public static function hasGeneratedInvoices(Model $record): bool
    {
        if ($record instanceof FeeType) {
            return $record->invoiceItems()->exists();
        }

        if ($record instanceof StudentFee) {
            return Invoice::query()
                ->where('student_id', $record->student_id)
                ->where('academic_year_id', $record->academic_year_id)
                ->whereHas('items', fn (Builder $items): Builder => $items->where('fee_type_id', $record->fee_type_id))
                ->exists();
        }

        return false;
    }

    public static function deleteBlockMessage(Model $record): string
    {
        return $record instanceof StudentFee
            ? 'Biaya siswa sudah menghasilkan tagihan dan tidak dapat dihapus.'
            : 'Jenis biaya sudah dipakai pada tagihan dan tidak dapat dihapus.';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('category')
                    ->label('Kategori')
                    ->options(collect(FeeCategory::cases())
                        ->mapWithKeys(fn (FeeCategory $category): array => [
                            $category->value => $category->label(),
                        ])->all())
                    ->required()
                    ->helperText('Bulanan = ditagih per bulan (SPP); Sekali = satu tagihan; Opsional = manual.'),
                Select::make('revenue_account_id')
                    ->label('Akun Pendapatan')
                    ->relationship('revenueAccount', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->code} — {$record->name}")
                    ->searchable()
                    ->preload()
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (FeeCategory $state): string => $state->label())
                    ->color(fn (FeeCategory $state): array => match ($state) {
                        FeeCategory::Bulanan => Color::Blue,
                        FeeCategory::Sekali => Color::Amber,
                        FeeCategory::Opsional => Color::Gray,
                    }),
                TextColumn::make('revenueAccount.code')
                    ->label('Akun Pendapatan'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFeeTypes::route('/'),
        ];
    }
}
