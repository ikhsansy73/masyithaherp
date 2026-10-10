<?php

namespace App\Filament\Pages;

use App\Support\Manual\ManualBook;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

class BukuPanduan extends Page
{
    protected string $view = 'filament.pages.buku-panduan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Buku Panduan';

    protected static ?string $title = 'Buku Panduan';

    protected static ?int $navigationSort = 99;

    protected Width|string|null $maxWidth = Width::Full;

    #[Url]
    public ?string $bab = null;

    public static function canAccess(): bool
    {
        return auth()->user() !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /** @return array{slug: string, title: string, html: string, sections: list<array{anchor: string, title: string, level: int}>} */
    public function currentChapter(): array
    {
        return ManualBook::chapter($this->bab ?? '')
            ?? ManualBook::chapter((string) (ManualBook::slugs()[0] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        return [
            'chapters' => ManualBook::chapters(),
            'current' => $this->currentChapter(),
            'index' => ManualBook::searchIndex(),
        ];
    }
}
