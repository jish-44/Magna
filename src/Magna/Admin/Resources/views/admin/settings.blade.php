<x-filament-panels::page>
    <style>
        [id^="settings-"] { scroll-margin-top: 6rem; }
    </style>

    <div
        x-data="{
            active: '{{ $this->sections()[0]['id'] ?? '' }}',
            init() {
                const sections = Array.from(document.querySelectorAll('[id^=&quot;settings-&quot;]'));
                if (! sections.length) return;
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.active = entry.target.id.replace('settings-', '');
                        }
                    });
                }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });
                sections.forEach((el) => observer.observe(el));
            },
            go(id) {
                const el = document.getElementById('settings-' + id);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                this.active = id;
            },
        }"
        class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_minmax(0,1fr)]"
    >
        {{-- Sticky section sub-navigation --}}
        <aside class="hidden lg:block">
            <nav class="sticky top-24 space-y-0.5">
                @foreach ($this->sections() as $section)
                    <a
                        href="#settings-{{ $section['id'] }}"
                        @click.prevent="go('{{ $section['id'] }}')"
                        :class="active === '{{ $section['id'] }}'
                            ? 'bg-primary-500/10 text-primary-500 font-semibold'
                            : 'text-gray-500 hover:bg-white/5 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition"
                    >
                        @svg($section['icon'], 'h-4 w-4 shrink-0')
                        <span>{{ $section['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- Scrollable settings form --}}
        <div>
            {{ $this->form }}
        </div>
    </div>
</x-filament-panels::page>
