<x-filament-panels::page>

@assets
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0..1,0" rel="stylesheet">
<style>
.msri {
    font-family: 'Material Symbols Rounded';
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    display: inline-block; line-height: 1; vertical-align: -3px;
}
.pulse-green  { box-shadow: 0 0 0 0 rgba(16,185,129,.7);  animation: syi-pg 1.8s infinite; }
.pulse-amber  { box-shadow: 0 0 0 0 rgba(245,158,11,.7);  animation: syi-pa 1.8s infinite; }
.pulse-red    { box-shadow: 0 0 0 0 rgba(239,68,68,.7);   animation: syi-pr 1.8s infinite; }
@keyframes syi-pg { 70% { box-shadow: 0 0 0 8px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }
@keyframes syi-pa { 70% { box-shadow: 0 0 0 8px rgba(245,158,11,0); } 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); } }
@keyframes syi-pr { 70% { box-shadow: 0 0 0 8px rgba(239,68,68,0);  } 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0);  } }
#sysTerminal::-webkit-scrollbar       { width: 5px; }
#sysTerminal::-webkit-scrollbar-thumb { background: #334155; border-radius: 99px; }
</style>
@endassets

<div class="space-y-8">

    {{-- ── 4 Stats Cards ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        {{-- PHP Engine --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-2xl p-5 shadow-sm hover:border-violet-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Engine PHP</span>
                <div class="p-2 rounded-lg bg-violet-100 dark:bg-violet-950/30 text-violet-500">
                    <span class="msri text-lg">memory</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black font-mono text-slate-900 dark:text-white">v{{ $php_version }}</h3>
                <p class="text-[11px] text-emerald-500 flex items-center gap-1 mt-1 font-medium">
                    <span class="msri text-xs">done_all</span>
                    Active Production Ready
                </p>
            </div>
        </div>

        {{-- Database --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-2xl p-5 shadow-sm hover:border-sky-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Database Driver</span>
                <div class="p-2 rounded-lg bg-sky-100 dark:bg-sky-950/30 text-sky-500">
                    <span class="msri text-lg">database</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black font-mono text-slate-900 dark:text-white capitalize">{{ $db_driver }}</h3>
                <p class="text-[11px] text-slate-400 mt-1 font-mono">Client Engine v{{ $db_version }}</p>
            </div>
        </div>

        {{-- Environment --}}
        @if($environment === 'production')
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-2xl p-5 shadow-sm hover:border-emerald-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Environment Target</span>
                <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-950/30 text-emerald-500">
                    <span class="msri text-lg">shield_with_house</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black font-mono text-emerald-500 uppercase">{{ $environment }}</h3>
                <p class="text-[11px] text-emerald-500 font-semibold flex items-center gap-1 mt-1">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                    Debug Mode Off
                </p>
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-2xl p-5 shadow-sm hover:border-amber-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Environment Target</span>
                <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-950/30 text-amber-500">
                    <span class="msri text-lg">shield_with_house</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black font-mono text-amber-500 uppercase">{{ $environment }}</h3>
                @if($debug_mode)
                <p class="text-[11px] text-amber-500 font-semibold flex items-center gap-1 mt-1">
                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-ping"></span>
                    Debug Mode Active
                </p>
                @else
                <p class="text-[11px] text-slate-400 flex items-center gap-1 mt-1">
                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    Debug Mode Off
                </p>
                @endif
            </div>
        </div>
        @endif

        {{-- Cache --}}
        @if($cache_status === 'ok')
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-2xl p-5 shadow-sm hover:border-emerald-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cache Service</span>
                <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-950/30 text-emerald-500">
                    <span class="msri text-lg">cached</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black font-mono text-slate-900 dark:text-white capitalize">{{ $cache_driver }}</h3>
                <p class="text-[11px] text-emerald-500 font-semibold flex items-center gap-1 mt-1">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full pulse-green"></span>
                    Status: OK
                </p>
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-2xl p-5 shadow-sm hover:border-red-500/30 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cache Service</span>
                <div class="p-2 rounded-lg bg-red-100 dark:bg-red-950/30 text-red-500">
                    <span class="msri text-lg">cached</span>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-black font-mono text-slate-900 dark:text-white capitalize">{{ $cache_driver }}</h3>
                <p class="text-[11px] text-red-500 font-semibold flex items-center gap-1 mt-1">
                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full pulse-red"></span>
                    Status: ERROR
                </p>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Primary Layout (2/3 + 1/3) ──────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Left column (2/3) --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- Software Versions panel --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-3xl p-6 lg:p-8 shadow-sm">
                <div class="flex items-center justify-between pb-6 border-b border-slate-100 dark:border-slate-800/80">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-violet-500 tracking-widest font-mono">Software Spec</span>
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Framework Runtime Versions</h3>
                    </div>
                    <span class="msri text-slate-300 dark:text-slate-600 text-2xl">deployed_code</span>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800/40 font-medium">
                    {{-- Magna CMS --}}
                    <div class="py-4 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                        <span class="text-sm text-slate-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded bg-violet-500 flex-shrink-0"></span>
                            <span>Magna CMS Edition</span>
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 font-mono text-xs font-bold rounded-lg bg-violet-50 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 border border-violet-100 dark:border-violet-900/30">{{ $magna_version }}</span>
                            <span class="text-[11px] font-bold text-slate-400 font-mono">Active Dev Node</span>
                        </div>
                    </div>
                    {{-- Laravel --}}
                    <div class="py-4 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                        <span class="text-sm text-slate-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded bg-rose-500 flex-shrink-0"></span>
                            <span>Laravel Framework</span>
                        </span>
                        <div class="flex items-center gap-2 font-mono">
                            <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $laravel_version }}</span>
                            <span class="text-xs text-slate-400">(LTS)</span>
                        </div>
                    </div>
                    {{-- PHP --}}
                    <div class="py-4 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                        <span class="text-sm text-slate-400 flex items-center gap-2">
                            <span class="w-2 h-2 rounded bg-indigo-500 flex-shrink-0"></span>
                            <span>PHP Engine</span>
                        </span>
                        <div class="flex items-center gap-2 font-mono">
                            <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $php_version }}</span>
                            <span class="text-[10px] px-2 py-0.5 bg-emerald-500/10 text-emerald-500 rounded font-semibold">cli</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Infrastructure panel --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-3xl p-6 lg:p-8 shadow-sm">
                <div class="flex items-center justify-between pb-6 border-b border-slate-100 dark:border-slate-800/80">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-sky-500 tracking-widest font-mono">Infrastructure Components</span>
                        <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">Database & Services Node Configuration</h3>
                    </div>
                    <span class="msri text-slate-300 dark:text-slate-600 text-2xl">settings_suggest</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    {{-- DB Driver --}}
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/30 dark:border-slate-700/30 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-sky-500/10 text-sky-500">
                                <span class="msri text-lg">database</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block font-semibold uppercase">DB Driver</span>
                                <span class="text-sm font-bold font-mono text-slate-800 dark:text-white capitalize">{{ $db_driver }}</span>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-300 rounded font-mono font-bold">Driver</span>
                    </div>

                    {{-- DB Version --}}
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/30 dark:border-slate-700/30 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-sky-500/10 text-sky-500">
                                <span class="msri text-lg">history_edu</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block font-semibold uppercase">DB Version</span>
                                <span class="text-sm font-bold font-mono text-slate-800 dark:text-white">{{ $db_version }}</span>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-300 rounded font-mono font-bold">v{{ explode('.', $db_version)[0] ?? '?' }}</span>
                    </div>

                    {{-- Cache --}}
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/30 dark:border-slate-700/30 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-500">
                                <span class="msri text-lg">cloud_sync</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block font-semibold uppercase">Cache Connection</span>
                                <span class="text-sm font-bold font-mono text-slate-800 dark:text-white capitalize">{{ $cache_driver }}</span>
                            </div>
                        </div>
                        @if($cache_status === 'ok')
                        <span class="text-xs px-2 py-0.5 bg-emerald-500/15 text-emerald-500 rounded font-mono font-bold">OK</span>
                        @else
                        <span class="text-xs px-2 py-0.5 bg-red-500/15 text-red-500 rounded font-mono font-bold">ERR</span>
                        @endif
                    </div>

                    {{-- Queue --}}
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/30 dark:border-slate-700/30 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-violet-500/10 text-violet-500">
                                <span class="msri text-lg">reorder</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block font-semibold uppercase">Queue Connection</span>
                                <span class="text-sm font-bold font-mono text-slate-800 dark:text-white capitalize">{{ $queue_connection }}</span>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-300 rounded font-mono font-bold capitalize">{{ $queue_connection }}</span>
                    </div>

                    {{-- Storage --}}
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/30 dark:border-slate-700/30 flex items-center justify-between md:col-span-2">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-500">
                                <span class="msri text-lg">hard_drive</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block font-semibold uppercase">Storage Disk</span>
                                <span class="text-sm font-bold font-mono text-slate-800 dark:text-white capitalize">{{ $storage_disk }}://{{ storage_path() }}</span>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-0.5 bg-indigo-500/15 text-indigo-500 rounded font-mono font-bold capitalize">{{ $storage_disk }}</span>
                    </div>
                </div>
            </section>
        </div>

        {{-- Right column (1/3) --}}
        <div class="space-y-8">

            {{-- Environment Flag panel --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-3xl p-6 shadow-sm"
                 x-data="{ debugOn: @js($debug_mode) }">
                <span class="text-[10px] uppercase font-bold text-amber-500 tracking-wider block font-mono">App Environment</span>
                <h4 class="text-md font-extrabold text-slate-900 dark:text-white mt-1">Environment Flag & Debug</h4>

                {{-- Debug toggle --}}
                <div class="mt-5 p-4 rounded-2xl bg-amber-500/5 border border-amber-500/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full {{ $debug_mode ? 'pulse-amber' : '' }}" style="background:{{ $debug_mode ? '#f59e0b' : '#94a3b8' }}"></div>
                            <div>
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300 block">Debug Mode Status</span>
                                <span class="text-[11px] font-mono font-semibold uppercase {{ $debug_mode ? 'text-amber-500' : 'text-slate-400' }}">{{ $debug_mode ? 'ENABLED' : 'DISABLED' }}</span>
                            </div>
                        </div>
                        <button @click="debugOn = !debugOn; $wire.toggleDebugMode()"
                            class="w-12 h-6 rounded-full p-0.5 transition-all relative flex items-center focus:outline-none"
                            :class="debugOn ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-700'">
                            <span class="w-5 h-5 rounded-full bg-white shadow-md transition-all duration-300"
                                  :class="debugOn ? 'translate-x-6' : 'translate-x-0'"></span>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-3 leading-normal">
                        Writes <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded text-[10px]">APP_DEBUG</code> to <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded text-[10px]">.env</code> — active immediately.
                    </p>
                </div>

                {{-- Env details --}}
                <div class="mt-5 space-y-3.5 pt-5 border-t border-slate-100 dark:border-slate-800/40">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">Environment Value:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-100">{{ $environment }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">Domain Node Host:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-100">{{ $app_url }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">Session Lifetime:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-100">{{ $session_lifetime }} minutes</span>
                    </div>
                </div>
            </div>

            {{-- Plugins panel --}}
            <section class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800/50 rounded-3xl p-6 shadow-sm">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/80">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block font-mono">Modularity System</span>
                        <h3 class="text-md font-extrabold text-slate-900 dark:text-white">Active Plugin Modules</h3>
                    </div>
                    <div class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500">
                        <span class="msri text-lg">extension</span>
                    </div>
                </div>

                {{-- Plugin counts --}}
                <div class="grid grid-cols-3 gap-2 text-center mt-6">
                    <div class="p-3 bg-slate-50 dark:bg-slate-950/40 rounded-2xl border border-slate-200/10">
                        <span class="text-xl font-black block font-mono text-slate-900 dark:text-white">{{ $plugins_total }}</span>
                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold block mt-1">Installed</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-950/40 rounded-2xl border border-slate-200/10">
                        <span class="text-xl font-black block font-mono text-emerald-500">{{ $plugins_enabled }}</span>
                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold block mt-1">Enabled</span>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-950/40 rounded-2xl border border-slate-200/10">
                        <span class="text-xl font-black block font-mono text-slate-400">{{ $plugins_disabled }}</span>
                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold block mt-1">Disabled</span>
                    </div>
                </div>

                <div class="mt-5 pt-5 border-t border-slate-100 dark:border-slate-800/40">
                    <a href="{{ \Magna\Admin\Pages\PluginsPage::getUrl() }}" class="w-full flex items-center justify-center gap-2 py-2.5 bg-violet-600/10 hover:bg-violet-600/20 text-violet-600 dark:text-violet-400 font-bold text-xs rounded-xl transition-all border border-violet-500/15">
                        <span class="msri text-sm">open_in_new</span>
                        <span>Manage Plugins</span>
                    </a>
                </div>
            </section>
        </div>
    </div>

    {{-- ── Terminal Console ─────────────────────────────────────────────────── --}}
    <section class="bg-slate-950 text-slate-100 rounded-3xl p-6 lg:p-8 shadow-xl border border-slate-800/80">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500 flex-shrink-0"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-500 flex-shrink-0"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-500 flex-shrink-0"></span>
                    <span class="text-xs font-mono text-slate-500 ml-2">magna-cms@cli-engine:~</span>
                </div>
                <h3 class="text-lg font-extrabold text-white mt-2">Active Dev Console & Diagnostics</h3>
            </div>

            {{-- Console action buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                <button
                    wire:click="runDiagnostics"
                    wire:loading.attr="disabled"
                    wire:target="runDiagnostics"
                    class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-bold font-mono text-emerald-400 rounded-lg transition-all active:scale-[0.98] disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="runDiagnostics">diagnostics:run</span>
                    <span wire:loading wire:target="runDiagnostics">running…</span>
                </button>
                <button
                    wire:click="clearCache"
                    wire:loading.attr="disabled"
                    wire:target="clearCache"
                    class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-bold font-mono text-violet-400 rounded-lg transition-all active:scale-[0.98] disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="clearCache">cache:clear</span>
                    <span wire:loading wire:target="clearCache">clearing…</span>
                </button>
                <button
                    wire:click="clearTerminal"
                    class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-bold font-mono text-rose-400 rounded-lg transition-all active:scale-[0.98]"
                >terminal:clear</button>
            </div>
        </div>

        {{-- Terminal output --}}
        <div id="sysTerminal" class="mt-6 font-mono text-xs space-y-2 h-64 overflow-y-auto p-4 bg-black/30 rounded-2xl border border-slate-900 leading-relaxed text-slate-300">
            <p class="text-slate-600">// Magna CMS Shell Client Interface initialized.</p>
            <p class="text-slate-600">// Running on {{ $environment }} environment — PHP {{ $php_version }} / Laravel {{ $laravel_version }}.</p>
            @if(empty($terminalLines))
            <p class="text-slate-500">
                <span class="text-violet-500">magna-cms$</span>
                type commands below or click mock triggers to run diagnostic operations…
            </p>
            @endif
            @foreach($terminalLines as $line)
            <p>
                @if($line['type'] === 'cmd')
                    <span class="text-slate-500 font-bold">magna-cms$</span>
                    <span class="text-violet-400">{{ $line['text'] }}</span>
                @elseif($line['type'] === 'init')
                    <span class="text-slate-500 font-bold">magna-cms$</span>
                    <span class="text-sky-400">{{ $line['text'] }}</span>
                @elseif($line['type'] === 'success')
                    <span class="text-slate-500 font-bold">magna-cms$</span>
                    <span class="text-emerald-400">{{ $line['text'] }}</span>
                @elseif($line['type'] === 'error')
                    <span class="text-slate-500 font-bold">magna-cms$</span>
                    <span class="text-red-400">{{ $line['text'] }}</span>
                @else
                    <span class="text-slate-500 font-bold">magna-cms$</span>
                    <span class="text-slate-300">{{ $line['text'] }}</span>
                @endif
            </p>
            @endforeach
        </div>
    </section>
</div>

@script
<script>
document.addEventListener('livewire:update', () => {
    const el = document.getElementById('sysTerminal');
    if (el) el.scrollTop = el.scrollHeight;
});
</script>
@endscript

</x-filament-panels::page>
