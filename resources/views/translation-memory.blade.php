@extends(config('translations.admin_layout', 'layouts.app'))

@section('title', 'Translation Memory')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Translation Memory</h1>
            <p class="text-sm text-gray-500 mt-1">Reuse previous translations to save AI tokens and maintain consistency.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('translations.index') }}" class="px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">&larr; Translations</a>
            <button onclick="importExisting()" id="importBtn" class="px-3 py-2 text-xs rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">Import Existing</button>
            <button onclick="purgeAll()" class="px-3 py-2 text-xs rounded-lg bg-red-600 text-white hover:bg-red-700 transition">Purge All</button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-4">
            <div class="text-xs font-bold uppercase text-gray-500 mb-1">Total Entries</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total_entries']) }}</div>
        </div>
        <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-4">
            <div class="text-xs font-bold uppercase text-gray-500 mb-1">Reuses (tokens saved)</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($stats['total_reuses']) }}</div>
        </div>
        <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-4">
            <div class="text-xs font-bold uppercase text-gray-500 mb-1">Language Pairs</div>
            <div class="flex flex-wrap gap-1 mt-1">
                @foreach($stats['language_pairs'] as $pair)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                        {{ strtoupper($pair['source_lang']) }} → {{ strtoupper($pair['target_lang']) }} ({{ $pair['cnt'] }})
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <input id="memorySearch" type="text" placeholder="Search memories..."
               class="w-full max-w-md px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
               oninput="searchMemories()">
    </div>

    {{-- Memories Table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm" id="memoryTable">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500 w-12">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500">Source</th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500">Translation</th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500 w-20">Langs</th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500 w-16">Uses</th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase text-gray-500 w-24">Context</th>
                    <th class="px-3 py-2 w-20">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="memoryBody">
                @forelse($memories as $mem)
                    <tr class="memory-row hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-3 py-2"><input type="checkbox" class="mem-check" value="{{ $mem->id }}"></td>
                        <td class="px-3 py-2 max-w-xs truncate" title="{{ $mem->source_text }}">{{ \Illuminate\Support\Str::limit($mem->source_text, 60) }}</td>
                        <td class="px-3 py-2 max-w-xs truncate" title="{{ $mem->target_text }}">{{ \Illuminate\Support\Str::limit($mem->target_text, 60) }}</td>
                        <td class="px-3 py-2 text-xs">
                            <span class="font-mono">{{ strtoupper($mem->source_lang) }}→{{ strtoupper($mem->target_lang) }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $mem->usage_count > 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $mem->usage_count }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-400 truncate max-w-[100px]">{{ $mem->context }}</td>
                        <td class="px-3 py-2 text-center">
                            <button onclick="deleteMemory({{ $mem->id }}, this)" class="text-red-400 hover:text-red-600 text-xs">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No memories yet. Click "Import Existing" to populate from current translations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $memories->links() }}
    </div>

    {{-- Bulk Actions --}}
    <div id="bulkBar" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 bg-gray-900 text-white rounded-xl px-6 py-3 shadow-xl flex items-center gap-4 z-50">
        <span id="bulkCount">0 selected</span>
        <button onclick="bulkDeleteMemories()" class="px-3 py-1 text-xs rounded bg-red-600 hover:bg-red-700">Delete Selected</button>
    </div>

    {{-- Memory Config --}}
    <div class="mt-6 bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
        <h3 class="text-sm font-bold mb-3">Auto-Sync Settings</h3>
        <div class="flex items-end gap-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Auto Sync</label>
                <select id="autoSyncEnabled" class="px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="0">Disabled</option>
                    <option value="1">Enabled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Interval (hours)</label>
                <input id="syncInterval" type="number" min="1" max="720" value="24"
                       class="w-24 px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <button onclick="saveConfig()" class="px-4 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700">Save</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const BASE = '{{ rtrim(route("translations.memory.index"), "/") }}';

function fj(url, opts = {}) {
    return fetch(url, { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', ...opts.headers }, ...opts }).then(r => r.json());
}

function showToast(msg, ok = true) {
    const d = document.createElement('div');
    d.className = `fixed bottom-4 right-4 z-50 px-4 py-2 rounded-lg text-sm text-white ${ok ? 'bg-emerald-600' : 'bg-red-600'} shadow-lg`;
    d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(() => d.remove(), 3000);
}

async function importExisting() {
    const btn = document.getElementById('importBtn');
    btn.textContent = 'Importing...';
    const res = await fj(`${BASE}/import`, { method: 'POST' });
    btn.textContent = 'Import Existing';
    if (res.success) { showToast(`Imported: ${res.imported}, Skipped: ${res.skipped}`); setTimeout(() => location.reload(), 1000); }
    else showToast(res.error || 'Error', false);
}

async function purgeAll() {
    if (!confirm('Delete ALL translation memories? This cannot be undone.')) return;
    const res = await fj(`${BASE}/purge`, { method: 'POST' });
    if (res.success) { showToast(`Purged ${res.deleted} memories`); setTimeout(() => location.reload(), 800); }
}

async function deleteMemory(id, btn) {
    const res = await fj(`${BASE}/${id}`, { method: 'DELETE' });
    if (res.success) { btn.closest('tr').remove(); showToast('Deleted'); }
}

function toggleSelectAll() {
    const checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.mem-check').forEach(c => c.checked = checked);
    updateBulkBar();
}

document.addEventListener('change', (e) => { if (e.target.classList.contains('mem-check')) updateBulkBar(); });

function updateBulkBar() {
    const checked = document.querySelectorAll('.mem-check:checked');
    const bar = document.getElementById('bulkBar');
    if (checked.length > 0) {
        bar.classList.remove('hidden');
        document.getElementById('bulkCount').textContent = `${checked.length} selected`;
    } else {
        bar.classList.add('hidden');
    }
}

async function bulkDeleteMemories() {
    const ids = [...document.querySelectorAll('.mem-check:checked')].map(c => parseInt(c.value));
    if (!ids.length) return;
    const res = await fj(`${BASE}/bulk-delete`, { method: 'POST', body: JSON.stringify({ ids }), headers: { 'Content-Type': 'application/json' } });
    if (res.success) { showToast(`Deleted ${ids.length} memories`); setTimeout(() => location.reload(), 800); }
}

function searchMemories() {
    const q = document.getElementById('memorySearch').value.toLowerCase();
    document.querySelectorAll('.memory-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// Load config
fetch(`${BASE}/config`, { headers: { 'Accept': 'application/json' } })
    .then(r => r.json()).then(data => {
        document.getElementById('autoSyncEnabled').value = data.auto_sync_enabled ? '1' : '0';
        document.getElementById('syncInterval').value = data.sync_interval_hours || 24;
    }).catch(() => {});

async function saveConfig() {
    const res = await fj(`${BASE}/config`, {
        method: 'POST',
        body: JSON.stringify({
            auto_sync_enabled: document.getElementById('autoSyncEnabled').value === '1',
            sync_interval_hours: parseInt(document.getElementById('syncInterval').value) || 24,
        }),
        headers: { 'Content-Type': 'application/json' },
    });
    showToast(res.success ? 'Config saved' : 'Error', res.success);
}
</script>
@endpush
@endsection
