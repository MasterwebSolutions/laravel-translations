@extends(config('translations.admin_layout', 'translations::layouts.standalone'))

@section('title', 'Translations')

@section(config('translations.content_section', 'content'))
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Translations</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route(config('translations.route_name_prefix', 'translations') . '.memory.index') }}" class="px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">Translation Memory</a>
            <button onclick="syncTexts()" id="syncBtn" class="px-3 py-2 text-xs rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">Sync Templates</button>
            @if(config('translations.ai_enabled'))
            <button onclick="translateAll()" id="translateAllBtn" class="px-3 py-2 text-xs rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition">AI Translate All</button>
            @endif
        </div>
    </div>

    {{-- Language Bar --}}
    <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-4 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Languages:</span>
                @foreach($languages as $lang)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                        {{ $lang === $sourceLang ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ strtoupper($lang) }}
                        @if($lang === $sourceLang) <span class="text-[9px]">(source)</span> @endif
                        @if($lang !== $sourceLang)
                            <button onclick="removeLang('{{ $lang }}')" class="ml-1 text-red-400 hover:text-red-600" title="Remove">&times;</button>
                        @endif
                    </span>
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                <input id="newLangInput" type="text" placeholder="en, pt, de..." maxlength="10"
                       class="w-20 px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <button onclick="addLang()" class="px-2 py-1 text-xs rounded bg-blue-600 text-white hover:bg-blue-700">+ Add</button>
            </div>
        </div>
        <div id="coverageBar" class="mt-3 flex gap-3 flex-wrap"></div>
    </div>

    {{-- Filter + Search --}}
    <div class="flex items-center gap-3 mb-4">
        <select id="groupFilter" onchange="filterByGroup()" class="px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <option value="">All groups</option>
            @foreach($groups as $g)
                <option value="{{ $g }}" {{ $filterGroup === $g ? 'selected' : '' }}>{{ $g }}</option>
            @endforeach
        </select>
        <input id="searchInput" type="text" placeholder="Search keys or values..."
               class="flex-1 px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
               oninput="filterTable()">
        <span class="text-xs text-gray-500">{{ $sourceKeys->count() }} keys</span>
    </div>

    {{-- Translations Table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm" id="transTable">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500 w-12">#</th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500">Group</th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500">Key</th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500">{{ strtoupper($sourceLang) }} (source)</th>
                    @foreach(array_filter($languages, fn($l) => $l !== $sourceLang) as $lang)
                        <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500">{{ strtoupper($lang) }}</th>
                    @endforeach
                    <th class="px-3 py-2 w-20">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($sourceKeys as $i => $src)
                    <tr class="trans-row hover:bg-gray-50 dark:hover:bg-gray-800/50" data-group="{{ $src->group }}" data-key="{{ $src->key }}">
                        <td class="px-3 py-2 text-xs text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-3 py-2 text-xs font-mono text-indigo-600 dark:text-indigo-400">{{ $src->group }}</td>
                        <td class="px-3 py-2 text-xs font-mono">{{ $src->key }}</td>
                        <td class="px-3 py-2">
                            <div contenteditable="true" class="editable-cell text-sm min-w-[120px] px-1 py-0.5 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                 data-id="{{ $src->id }}" data-original="{{ e($src->value) }}"
                                 onblur="saveInline(this)">{{ $src->value }}</div>
                        </td>
                        @foreach(array_filter($languages, fn($l) => $l !== $sourceLang) as $lang)
                            @php $other = $otherTranslations["{$src->group}.{$src->key}"][$lang] ?? null; @endphp
                            <td class="px-3 py-2">
                                @if($other)
                                    <div contenteditable="true" class="editable-cell text-sm min-w-[120px] px-1 py-0.5 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 focus:outline-none focus:ring-1 focus:ring-blue-500 {{ $other->value === '' ? 'text-red-400 italic' : '' }}"
                                         data-id="{{ $other->id }}" data-original="{{ e($other->value) }}"
                                         onblur="saveInline(this)">{{ $other->value ?: '(empty)' }}</div>
                                @else
                                    <span class="text-xs text-red-400 italic">(missing)</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center gap-1 justify-center">
                                @if(config('translations.ai_enabled'))
                                <button onclick="aiTranslateKey('{{ $src->group }}', '{{ $src->key }}')"
                                        class="text-purple-500 hover:text-purple-700 text-xs" title="AI Translate">AI</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add Key Form --}}
    <div class="mt-6 bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
        <h3 class="text-sm font-bold mb-3">Add Translation Key</h3>
        <form id="addKeyForm" class="flex items-end gap-3 flex-wrap">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Group</label>
                <input name="group" type="text" placeholder="nav" required class="px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-32">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Key</label>
                <input name="key" type="text" placeholder="home" required class="px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-40">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Value ({{ strtoupper($sourceLang) }})</label>
                <input name="value" type="text" placeholder="Inicio" class="px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-48">
            </div>
            <button type="submit" class="px-4 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700">Add</button>
        </form>
    </div>
</div>

{{-- Inline script: works regardless of whether the host layout has @stack('scripts') --}}
<script>
(function() {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                 || '{{ csrf_token() }}';
    const BASE = '{{ rtrim(route(config("translations.route_name_prefix", "translations") . ".index"), "/") }}';

    function fj(url, opts = {}) {
        return fetch(url, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', ...opts.headers },
            ...opts
        }).then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        }).catch(err => {
            console.error('[Translations]', err);
            return { success: false, error: err.message };
        });
    }

    function showToast(msg, ok = true) {
        const d = document.createElement('div');
        d.className = `fixed bottom-4 right-4 z-50 px-4 py-2 rounded-lg text-sm text-white ${ok ? 'bg-emerald-600' : 'bg-red-600'} shadow-lg transition-opacity`;
        d.textContent = msg;
        document.body.appendChild(d);
        setTimeout(() => { d.style.opacity = '0'; setTimeout(() => d.remove(), 300); }, 3000);
    }

    window.saveInline = async function(el) {
        const id = el.dataset.id;
        const original = el.dataset.original;
        const newVal = el.textContent.trim();
        if (newVal === original || newVal === '(empty)') return;
        const res = await fj(`${BASE}/${id}/inline-update`, { method: 'POST', body: JSON.stringify({ value: newVal }), headers: { 'Content-Type': 'application/json' } });
        if (res.success) { el.dataset.original = newVal; el.classList.remove('text-red-400', 'italic'); showToast('Saved'); }
        else showToast(res.error || 'Error saving', false);
    };

    window.syncTexts = async function() {
        document.getElementById('syncBtn').textContent = 'Syncing...';
        const res = await fj(`${BASE}/sync`, { method: 'POST' });
        document.getElementById('syncBtn').textContent = 'Sync Templates';
        if (res.success) { showToast(`Synced: ${res.created} created, ${res.updated} updated`); setTimeout(() => location.reload(), 1000); }
        else showToast(res.error || 'Sync failed', false);
    };

    window.aiTranslateKey = async function(group, key) {
        const res = await fj(`${BASE}/ai-translate-key`, { method: 'POST', body: JSON.stringify({ group, key }), headers: { 'Content-Type': 'application/json' } });
        if (res.success) { showToast(`Translated to ${res.translated} languages`); setTimeout(() => location.reload(), 1000); }
        else showToast(res.error || 'Translation failed', false);
    };

    window.translateAll = async function() {
        if (!confirm('Translate ALL missing texts with AI? This may use tokens.')) return;
        const btn = document.getElementById('translateAllBtn');
        if (btn) btn.textContent = 'Translating...';
        const rows = document.querySelectorAll('.trans-row');
        const keys = [...rows].map(r => ({ group: r.dataset.group, key: r.dataset.key }));
        const BATCH = 5;
        let total = 0, mem = 0, errs = 0;
        for (let i = 0; i < keys.length; i += BATCH) {
            const batch = keys.slice(i, i + BATCH);
            const res = await fj(`${BASE}/ai-translate-batch`, { method: 'POST', body: JSON.stringify({ keys: batch }), headers: { 'Content-Type': 'application/json' } });
            if (res.success) { total += res.translated; mem += res.from_memory || 0; errs += res.errors; }
        }
        if (btn) btn.textContent = 'AI Translate All';
        showToast(`Done: ${total} translated, ${mem} from memory, ${errs} errors`);
        if (total > 0) setTimeout(() => location.reload(), 1500);
    };

    window.addLang = function() {
        const input = document.getElementById('newLangInput');
        const lang = input.value.trim().toLowerCase();
        if (!lang) return;
        fj(`${BASE}/add-language`, { method: 'POST', body: JSON.stringify({ lang }), headers: { 'Content-Type': 'application/json' } })
            .then(r => { if (r.success) location.reload(); else showToast(r.error || 'Error', false); });
    };

    window.removeLang = function(lang) {
        if (!confirm(`Remove language "${lang}" and ALL its translations?`)) return;
        fj(`${BASE}/remove-language`, { method: 'POST', body: JSON.stringify({ lang }), headers: { 'Content-Type': 'application/json' } })
            .then(r => { if (r.success) location.reload(); else showToast(r.error || 'Error', false); });
    };

    window.filterByGroup = function() {
        const group = document.getElementById('groupFilter').value;
        window.location.href = `${BASE}${group ? '?group=' + group : ''}`;
    };

    window.filterTable = function() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('.trans-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    };

    // Coverage stats on load
    fj(`${BASE}/coverage-stats`).then(data => {
        if (!data.success) return;
        const bar = document.getElementById('coverageBar');
        if (!bar) return;
        Object.entries(data.stats).forEach(([lang, s]) => {
            const el = document.createElement('div');
            el.className = 'text-xs';
            const color = s.percent >= 90 ? '#059669' : s.percent >= 50 ? '#d97706' : '#dc2626';
            el.innerHTML = `<span class="font-bold">${lang.toUpperCase()}</span> <span style="color:${color}">${s.percent}%</span> (${s.count}/${s.total})`;
            bar.appendChild(el);
        });
    });

    // Add key form
    document.getElementById('addKeyForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const body = Object.fromEntries(fd);
        const res = await fj(BASE, { method: 'POST', body: JSON.stringify(body), headers: { 'Content-Type': 'application/json' } });
        if (res.success) { showToast('Key added'); setTimeout(() => location.reload(), 800); }
        else showToast(res.error || 'Error', false);
    });
})();
</script>
@endsection
