<x-filament-panels::page>
    <div class="manual-book" x-data="manualSearch">
        <style>
            [x-cloak] { display: none !important; }

            .manual-book {
                display: grid;
                grid-template-columns: 17rem minmax(0, 1fr);
                gap: 1.5rem;
                align-items: start;
                scroll-behavior: smooth;
            }

            .manual-book__aside {
                position: sticky;
                top: var(--topbar-height, 3.5rem);
                max-height: calc(100dvh - var(--topbar-height, 3.5rem) - 1rem);
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                gap: 1rem;
                padding: 0.75rem;
                background: var(--gray-50);
                border: 1px solid var(--gray-200);
                border-radius: 0.75rem;
            }

            .dark .manual-book__aside {
                background: color-mix(in srgb, var(--gray-900) 60%, transparent);
                border-color: var(--gray-800);
            }

            .manual-book__search input {
                width: 100%;
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
                color: var(--gray-950);
                background: rgb(255 255 255);
                border: 1px solid var(--gray-300);
                border-radius: 0.5rem;
                outline: none;
            }

            .manual-book__search input::placeholder { color: var(--gray-400); }

            .manual-book__search input:focus {
                border-color: var(--primary-500);
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-500) 15%, transparent);
            }

            .dark .manual-book__search input {
                color: var(--gray-50);
                background: color-mix(in srgb, var(--gray-800) 70%, transparent);
                border-color: var(--gray-700);
            }

            .manual-book__results {
                margin-top: 0.375rem;
                background: rgb(255 255 255);
                border: 1px solid var(--gray-200);
                border-radius: 0.5rem;
                box-shadow: 0 10px 30px color-mix(in srgb, var(--gray-950) 12%, transparent);
                max-height: 20rem;
                overflow-y: auto;
            }

            .dark .manual-book__results {
                background: var(--gray-900);
                border-color: var(--gray-700);
                box-shadow: 0 10px 30px color-mix(in srgb, rgb(0 0 0) 50%, transparent);
            }

            .manual-book__result {
                display: block;
                width: 100%;
                text-align: left;
                padding: 0.5rem 0.75rem;
                background: none;
                border: 0;
                border-bottom: 1px solid var(--gray-100);
                cursor: pointer;
            }

            .manual-book__result:last-child { border-bottom: 0; }
            .manual-book__result:hover { background: var(--gray-100); }
            .dark .manual-book__result { border-color: var(--gray-800); }
            .dark .manual-book__result:hover { background: color-mix(in srgb, var(--gray-800) 60%, transparent); }

            .manual-book__result-bab {
                display: block;
                font-size: 0.6875rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: var(--primary-600);
            }

            .dark .manual-book__result-bab { color: var(--primary-400); }

            .manual-book__result-heading {
                display: block;
                font-size: 0.8125rem;
                font-weight: 600;
                color: var(--gray-950);
            }

            .dark .manual-book__result-heading { color: var(--gray-50); }

            .manual-book__result-snippet {
                display: block;
                font-size: 0.75rem;
                color: var(--gray-500);
                overflow: hidden;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .manual-book__result-snippet mark,
            .manual-book__content mark {
                background: color-mix(in srgb, var(--warning) 30%, transparent);
                color: inherit;
                border-radius: 0.125rem;
                padding-inline: 0.125rem;
            }

            .manual-book__none {
                padding: 0.625rem 0.75rem;
                font-size: 0.8125rem;
                color: var(--gray-500);
            }

            .manual-book__nav { display: flex; flex-direction: column; gap: 0.125rem; }

            .manual-book__nav-title {
                font-size: 0.6875rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: var(--gray-400);
                padding: 0.25rem 0.5rem;
            }

            .manual-book__chapter,
            .manual-book__section {
                display: block;
                padding: 0.375rem 0.5rem;
                border-radius: 0.375rem;
                font-size: 0.8125rem;
                line-height: 1.35;
                color: var(--gray-600);
                text-decoration: none;
                cursor: pointer;
            }

            .dark .manual-book__chapter,
            .dark .manual-book__section { color: var(--gray-400); }

            .manual-book__chapter:hover,
            .manual-book__section:hover {
                background: var(--gray-100);
                color: var(--gray-950);
            }

            .dark .manual-book__chapter:hover,
            .dark .manual-book__section:hover {
                background: color-mix(in srgb, var(--gray-800) 60%, transparent);
                color: var(--gray-50);
            }

            .manual-book__chapter.is-active {
                background: color-mix(in srgb, var(--primary-600) 12%, transparent);
                color: var(--primary-700);
                font-weight: 600;
            }

            .dark .manual-book__chapter.is-active {
                background: color-mix(in srgb, var(--primary-400) 15%, transparent);
                color: var(--primary-300);
            }

            .manual-book__section { padding-inline-start: 1rem; font-size: 0.75rem; }

            .manual-book__content { min-width: 0; }

            .manual-book__content h2 {
                scroll-margin-top: calc(var(--topbar-height, 3.5rem) + 1rem);
                margin: 2rem 0 0.75rem;
                padding-bottom: 0.4rem;
                border-bottom: 1px solid var(--gray-200);
                font-size: 1.25rem;
                font-weight: 700;
                color: var(--gray-950);
            }

            .dark .manual-book__content h2 {
                border-color: var(--gray-800);
                color: var(--gray-50);
            }

            .manual-book__content h2:first-child { margin-top: 0; }

            .manual-book__content h3 {
                scroll-margin-top: calc(var(--topbar-height, 3.5rem) + 1rem);
                margin: 1.5rem 0 0.5rem;
                font-size: 1.0625rem;
                font-weight: 700;
                color: var(--gray-900);
            }

            .dark .manual-book__content h3 { color: var(--gray-100); }

            .manual-book__content h4 {
                margin: 1.25rem 0 0.4rem;
                font-size: 0.9375rem;
                font-weight: 700;
                color: var(--gray-700);
            }

            .dark .manual-book__content h4 { color: var(--gray-300); }

            .manual-book__content p,
            .manual-book__content li {
                color: var(--gray-700);
                line-height: 1.7;
            }

            .dark .manual-book__content p,
            .dark .manual-book__content li { color: var(--gray-300); }

            .manual-book__content p { margin: 0.6rem 0; }

            .manual-book__content a {
                color: var(--primary-600);
                text-decoration: underline;
                text-underline-offset: 2px;
            }

            .dark .manual-book__content a { color: var(--primary-400); }

            .manual-book__content strong { color: var(--gray-950); }
            .dark .manual-book__content strong { color: var(--gray-50); }

            .manual-book__content ul,
            .manual-book__content ol { margin: 0.6rem 0; padding-inline-start: 1.4rem; }
            .manual-book__content ul { list-style: disc; }
            .manual-book__content ol { list-style: decimal; }
            .manual-book__content li { margin-block: 0.25rem; }
            .manual-book__content li::marker { color: var(--gray-400); }

            .manual-book__content code {
                font-family: var(--mono-font-family, monospace);
                font-size: 0.8125rem;
                background: var(--gray-100);
                color: var(--gray-800);
                padding: 0.125rem 0.375rem;
                border-radius: 0.25rem;
            }

            .dark .manual-book__content code {
                background: color-mix(in srgb, var(--gray-800) 70%, transparent);
                color: var(--gray-200);
            }

            .manual-book__content table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.8125rem;
                margin: 0.75rem 0;
            }

            .manual-book__content th {
                text-align: start;
                padding: 0.45rem 0.6rem;
                background: var(--gray-100);
                border: 1px solid var(--gray-200);
                font-weight: 600;
                color: var(--gray-800);
            }

            .manual-book__content td {
                padding: 0.45rem 0.6rem;
                border: 1px solid var(--gray-200);
                color: var(--gray-700);
                vertical-align: top;
            }

            .dark .manual-book__content th {
                background: color-mix(in srgb, var(--gray-800) 70%, transparent);
                border-color: var(--gray-700);
                color: var(--gray-200);
            }

            .dark .manual-book__content td {
                border-color: var(--gray-700);
                color: var(--gray-300);
            }

            .manual-book__content blockquote {
                margin: 0.75rem 0;
                padding: 0.6rem 0.9rem;
                border-inline-start: 3px solid var(--primary-500);
                background: color-mix(in srgb, var(--primary-500) 7%, transparent);
                border-radius: 0.375rem;
            }

            .manual-book__content blockquote p { margin: 0.15rem 0; color: var(--gray-700); }
            .dark .manual-book__content blockquote p { color: var(--gray-300); }

            .manual-book__content hr {
                border: 0;
                border-top: 1px solid var(--gray-200);
                margin: 1.5rem 0;
            }

            .dark .manual-book__content hr { border-color: var(--gray-800); }

            @media (max-width: 64rem) {
                .manual-book { grid-template-columns: 1fr; }
                .manual-book__aside {
                    position: static;
                    max-height: none;
                }
            }
        </style>

        <aside class="manual-book__aside">
            <div class="manual-book__search">
                <input
                    type="search"
                    x-model="q"
                    @input="search()"
                    placeholder="Cari di panduan…"
                    aria-label="Cari di panduan"
                >

                <div class="manual-book__results" x-show="results.length > 0" x-cloak>
                    <template x-for="r in results" :key="r.bab + '-' + r.anchor">
                        <button type="button" class="manual-book__result" @click="go(r)">
                            <span class="manual-book__result-bab" x-text="r.babTitle"></span>
                            <span class="manual-book__result-heading" x-text="r.heading"></span>
                            <span class="manual-book__result-snippet" x-html="snippet(r)"></span>
                        </button>
                    </template>
                </div>

                <div class="manual-book__none" x-show="q.trim().length > 1 && results.length === 0" x-cloak>
                    Tidak ditemukan.
                </div>
            </div>

            <nav class="manual-book__nav" aria-label="Daftar bab manual">
                <span class="manual-book__nav-title">Daftar Bab</span>

                @foreach ($chapters as $chapter)
                    <a
                        href="?bab={{ $chapter['slug'] }}"
                        wire:key="manual-bab-{{ $chapter['slug'] }}"
                        class="manual-book__chapter {{ $chapter['slug'] === $current['slug'] ? 'is-active' : '' }}"
                        x-on:click.prevent="$wire.set('bab', '{{ $chapter['slug'] }}').then(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
                    >{{ $chapter['title'] }}</a>

                    @if ($chapter['slug'] === $current['slug'])
                        @foreach ($chapter['sections'] as $section)
                            @if ($section['level'] === 2)
                                <a
                                    href="#{{ $section['anchor'] }}"
                                    wire:key="manual-bag-{{ $section['anchor'] }}"
                                    class="manual-book__section"
                                >{{ $section['title'] }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </nav>
        </aside>

        <article class="manual-book__content">
            {!! $current['html'] !!}
        </article>

        <script type="application/json" x-ref="index">@json($index, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
    </div>

    <script>
        window.manualSearch = function () {
            return {
                q: '',
                index: [],
                results: [],

                init() {
                    try {
                        this.index = JSON.parse(this.$refs.index.textContent)
                    } catch (e) {
                        this.index = []
                    }
                },

                search() {
                    const q = this.q.trim().toLowerCase()

                    if (q.length < 2) {
                        this.results = []

                        return
                    }

                    this.results = this.index
                        .filter((e) => (e.heading + ' ' + e.text).toLowerCase().includes(q))
                        .slice(0, 12)
                },

                snippet(r) {
                    const q = this.q.trim().toLowerCase()
                    const text = r.text || r.heading || ''
                    const at = text.toLowerCase().indexOf(q)

                    if (at < 0) {
                        return this.esc(text.slice(0, 140)) + (text.length > 140 ? '…' : '')
                    }

                    const start = Math.max(0, at - 60)
                    const end = Math.min(text.length, at + q.length + 80)

                    return (start > 0 ? '…' : '')
                        + this.esc(text.slice(start, at))
                        + '<mark>' + this.esc(text.slice(at, at + q.length)) + '</mark>'
                        + this.esc(text.slice(at + q.length, end))
                        + (end < text.length ? '…' : '')
                },

                go(r) {
                    this.q = ''
                    this.results = []

                    this.$wire.set('bab', r.bab).then(() => {
                        setTimeout(() => {
                            const el = r.anchor
                                ? document.getElementById(r.anchor)
                                : document.querySelector('.manual-book__content')

                            if (el) {
                                el.scrollIntoView({ behavior: 'smooth', block: 'start' })
                            }
                        }, 250)
                    })
                },

                esc(s) {
                    const d = document.createElement('div')
                    d.textContent = s

                    return d.innerHTML
                },
            }
        }
    </script>
</x-filament-panels::page>
