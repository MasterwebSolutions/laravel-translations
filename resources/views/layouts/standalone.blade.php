<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Translations') - {{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
    {{-- Simple top nav --}}
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-4">
@php $rp = config('translations.route_name_prefix', 'translations'); @endphp
                <a href="{{ route($rp . '.index') }}" class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                    Translations
                </a>
                <div class="flex items-center gap-2 text-sm">
                    <a href="{{ route($rp . '.index') }}"
                       class="px-3 py-1.5 rounded-lg {{ request()->routeIs($rp . '.index') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        Translations
                    </a>
                    <a href="{{ route($rp . '.memory.index') }}"
                       class="px-3 py-1.5 rounded-lg {{ request()->routeIs($rp . '.memory.*') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        Translation Memory
                    </a>
                </div>
            </div>
            <div class="text-xs text-gray-400">
                {{ config('app.name', 'Laravel') }}
            </div>
        </div>
    </nav>

    {{-- Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Scripts --}}
    @stack('scripts')
</body>
</html>
