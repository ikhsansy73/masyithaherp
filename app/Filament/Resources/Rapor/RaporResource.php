<?php

namespace App\Filament\Resources\Rapor;

use App\Enums\ReportCardStatus;
use App\Filament\Resources\Rapor\Pages\ManageRapor;
use App\Filament\Resources\Rapor\Pages\ViewRapor;
use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Services\Academics\ReportCardService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Rapor per student per term with the approval workflow (doc 06 §7). */
class RaporResource extends Resource
{
    protected static ?string $model = ReportCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Rapor';

    protected static ?string $slug = 'rapor';

    public static function getModelLabel(): string
    {
        return 'Rapor';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['student', 'classroom.academicYear', 'term'])
                ->whereHas('classroom', fn (Builder $classroom): Builder => $classroom
                    ->visibleToStaff(auth()->user())))
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Siswa')
                    ->searchable(),
                TextColumn::make('classroom.name')
                    ->label('Rombel')
                    ->sortable(),
                TextColumn::make('term.name')
                    ->label('Semester'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('submitted_at')
                    ->label('Diajukan')
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('approved_at')
                    ->label('Disetujui')
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('published_at')
                    ->label('Diterbitkan')
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('classroom')
                    ->label('Rombel')
                    ->relationship('classroom', 'name'),
                SelectFilter::make('term')
                    ->label('Semester')
                    ->relationship('term', 'name'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ReportCardStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                static::submitAction(),
                static::approveAction(),
                static::requestRevisionAction(),
                static::publishAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRapor::route('/'),
            'view' => ViewRapor::route('/{record}'),
        ];
    }

    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'rapor.view',
            'create', 'update', 'delete' => 'rapor.input',
            default => null,
        };

        if ($permission === null) {
            return Response::deny();
        }

        return auth()->user()?->can($permission)
            ? Response::allow()
            : Response::deny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('rapor.view') ?? false;
    }

    /**
     * Header action: generate draft rapor for a rombel × semester.
     */
    public static function generateAction(): Action
    {
        return Action::make('generateRapor')
            ->label('Generate Rapor')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->authorize(fn (): bool => auth()->user()?->can('rapor.input') ?? false)
            ->schema([
                Select::make('classroom_id')
                    ->label('Rombel')
                    ->options(fn (): array => Classroom::query()
                        ->with('academicYear')
                        ->where('is_active', true)
                        ->visibleToStaff(auth()->user())
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Classroom $classroom): array => [
                            $classroom->getKey() => $classroom->name.' — '.$classroom->academicYear?->name,
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('academic_term_id')
                    ->label('Semester')
                    ->options(fn (): array => AcademicTerm::query()
                        ->with('academicYear')
                        ->orderByDesc('academic_year_id')
                        ->get()
                        ->mapWithKeys(fn (AcademicTerm $term): array => [
                            $term->getKey() => $term->name.' — '.$term->academicYear?->name,
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (array $data): void {
                $written = app(ReportCardService::class)->generate(
                    AcademicTerm::query()->findOrFail($data['academic_term_id']),
                    Classroom::query()->findOrFail($data['classroom_id']),
                );

                Notification::make()
                    ->success()
                    ->title('Rapor dibuat')
                    ->body($written.' rapor draft dibuat.')
                    ->send();
            });
    }

    /**
     * Wali kelas: Draft/Revisi → Diajukan.
     */
    public static function submitAction(): Action
    {
        return Action::make('submit')
            ->label('Ajukan')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->authorize(fn (Model $record): bool => auth()->user()?->can('rapor.submit') ?? false)
            ->visible(fn (Model $record): bool => in_array($record->status, [ReportCardStatus::Draft, ReportCardStatus::Revisi], true))
            ->requiresConfirmation()
            ->modalDescription('Ajukan rapor ini kepada kepala sekolah?')
            ->action(function (Model $record): void {
                static::runWorkflow('submit', $record, 'Rapor diajukan');
            });
    }

    /**
     * Kepala sekolah: Diajukan → Disetujui.
     */
    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Setujui')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->authorize(fn (Model $record): bool => auth()->user()?->can('rapor.approve') ?? false)
            ->visible(fn (Model $record): bool => $record->status === ReportCardStatus::Diajukan)
            ->requiresConfirmation()
            ->action(function (Model $record): void {
                static::runWorkflow('approve', $record, 'Rapor disetujui');
            });
    }

    /**
     * Kepala sekolah: Diajukan → Revisi with a mandatory note.
     */
    public static function requestRevisionAction(): Action
    {
        return Action::make('requestRevision')
            ->label('Minta Revisi')
            ->icon('heroicon-o-arrow-path')
            ->color('danger')
            ->authorize(fn (Model $record): bool => auth()->user()?->can('rapor.approve') ?? false)
            ->visible(fn (Model $record): bool => $record->status === ReportCardStatus::Diajukan)
            ->schema([
                Textarea::make('revision_note')
                    ->label('Catatan Revisi')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->action(function (Model $record, array $data): void {
                static::runWorkflow('requestRevision', $record, 'Permintaan revisi dikirim', ['note' => $data['revision_note'] ?? '']);
            });
    }

    /**
     * Kepala sekolah: Disetujui → Diterbitkan (parents can see it).
     */
    public static function publishAction(): Action
    {
        return Action::make('publish')
            ->label('Terbitkan')
            ->icon('heroicon-o-globe-alt')
            ->color('primary')
            ->authorize(fn (Model $record): bool => auth()->user()?->can('rapor.publish') ?? false)
            ->visible(fn (Model $record): bool => $record->status === ReportCardStatus::Disetujui)
            ->requiresConfirmation()
            ->modalDescription('Terbitkan rapor ini? Orang tua akan melihatnya di portal.')
            ->action(function (Model $record): void {
                static::runWorkflow('publish', $record, 'Rapor diterbitkan');
            });
    }

    /**
     * Run one service transition with notification feedback.
     *
     * @param  array<string, mixed>  $extra
     */
    protected static function runWorkflow(string $method, Model $record, string $successTitle, array $extra = []): void
    {
        $service = app(ReportCardService::class);

        try {
            match ($method) {
                'submit' => $service->submit($record, auth()->user()),
                'approve' => $service->approve($record, auth()->user()),
                'requestRevision' => $service->requestRevision($record, auth()->user(), (string) ($extra['note'] ?? '')),
                'publish' => $service->publish($record, auth()->user()),
            };

            Notification::make()->success()->title($successTitle)->send();
        } catch (\App\Exceptions\SchoolException $exception) {
            Notification::make()
                ->danger()
                ->title('Gagal memproses rapor')
                ->body($exception->getMessage())
                ->send();
        }
    }
}
