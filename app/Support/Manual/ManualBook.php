<?php

namespace App\Support\Manual;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Loads the manual chapters from resources/manual/*.md, converts them to HTML
 * with heading anchors, and builds a flat search index for the client-side search.
 */
class ManualBook
{
    public static function chapters(): Collection
    {
        return collect(static::files())->map(fn (string $path): array => static::parse($path));
    }

    /**
     * One chapter rendered to HTML with h2/h3 anchors.
     *
     * @return array{slug: string, title: string, html: string, sections: list<array{anchor: string, title: string, level: int}>}|null
     */
    public static function chapter(string $slug): ?array
    {
        $path = static::fileFor($slug);

        if ($path === null) {
            return null;
        }

        return static::parse($path);
    }

    /**
     * Flat list of searchable entries: one per chapter intro plus one per h2/h3 section.
     *
     * @return list<array{bab: string, babTitle: string, anchor: string, heading: string, text: string, level: int}>
     */
    public static function searchIndex(): array
    {
        $entries = [];

        foreach (static::chapters() as $chapter) {
            $entries = [...$entries, ...static::indexChapter($chapter)];
        }

        return $entries;
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return static::chapters()
            ->map(fn (array $chapter): string => $chapter['slug'])
            ->values()
            ->all();
    }

    /** @return list<string> */
    private static function files(): array
    {
        $files = glob(resource_path('manual/*.md')) ?: [];

        sort($files, SORT_NATURAL);

        return $files;
    }

    private static function fileFor(string $slug): ?string
    {
        foreach (static::files() as $file) {
            if (static::slugFromFilename(basename($file)) === $slug) {
                return $file;
            }
        }

        return null;
    }

    /** @return array{slug: string, title: string, html: string, sections: list<array{anchor: string, title: string, level: int}>} */
    private static function parse(string $path): array
    {
        $markdown = (string) file_get_contents($path);
        $html = Str::markdown($markdown);

        $used = [];
        $sections = [];

        $html = preg_replace_callback(
            '/<h([23])>(.*?)<\/h\1>/s',
            function (array $m) use (&$used, &$sections): string {
                $title = trim(strip_tags($m[2]));
                $anchor = static::anchor($title, $used);

                $used[] = $anchor;
                $sections[] = ['anchor' => $anchor, 'title' => $title, 'level' => (int) $m[1]];

                return "<h{$m[1]} id=\"{$anchor}\">{$m[2]}</h{$m[1]}>";
            },
            $html,
        );

        preg_match('/^#\s+(.+)$/m', $markdown, $titleMatch);
        $title = isset($titleMatch[1]) ? trim($titleMatch[1]) : Str::headline(static::slugFromFilename(basename($path)));

        return [
            'slug' => static::slugFromFilename(basename($path)),
            'title' => $title,
            'html' => $html,
            'sections' => $sections,
        ];
    }

    /**
     * @param  array{slug: string, title: string, html: string, sections: list<array{anchor: string, title: string, level: int}>}  $chapter
     * @return list<array{bab: string, babTitle: string, anchor: string, heading: string, text: string, level: int}>
     */
    private static function indexChapter(array $chapter): array
    {
        $entries = [];
        $current = [
            'anchor' => '',
            'heading' => $chapter['title'],
            'level' => 2,
            'text' => '',
        ];

        $flush = function () use (&$entries, &$current, $chapter): void {
            $text = trim((string) preg_replace('/\s+/u', ' ', $current['text']));
            $text = Str::limit($text, 2000);

            if ($text === '' && $current['anchor'] === '') {
                return;
            }

            $entries[] = [
                'bab' => $chapter['slug'],
                'babTitle' => $chapter['title'],
                'anchor' => $current['anchor'],
                'heading' => $current['heading'],
                'text' => $text,
                'level' => $current['level'],
            ];

            $current['text'] = '';
        };

        foreach (preg_split('/(<h[23] id="[^"]+">.*?<\/h[23]>)/s', $chapter['html'], -1, PREG_SPLIT_DELIM_CAPTURE) as $segment) {
            if (preg_match('/^<h([23]) id="([^"]+)">(.*?)<\/h\1>/s', $segment, $m)) {
                $flush();

                $current = [
                    'anchor' => $m[2],
                    'heading' => trim(strip_tags($m[3])),
                    'level' => (int) $m[1],
                    'text' => '',
                ];

                continue;
            }

            $current['text'] .= ' '.trim(strip_tags($segment));
        }

        $flush();

        return $entries;
    }

    /** @param list<string> $used */
    private static function anchor(string $title, array $used): string
    {
        $base = Str::slug($title) ?: 'bagian';
        $anchor = $base;
        $i = 2;

        while (in_array($anchor, $used)) {
            $anchor = $base.'-'.($i++);
        }

        return $anchor;
    }

    private static function slugFromFilename(string $filename): string
    {
        return (string) Str::of($filename)
            ->beforeLast('.')
            ->replaceMatches('/^\d+-/', '')
            ->replaceMatches('/[^a-z0-9-]/', '')
            ->toString();
    }
}
