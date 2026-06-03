<!DOCTYPE html>
<html lang="id"
      x-data="{
          sidebarOpen: false,
          darkMode: (() => {
              try {
                  return localStorage.getItem('cashtrack-theme') === 'dark';
              } catch (error) {
                  return false;
              }
          })(),
          isDesktop: window.matchMedia('(min-width: 1024px)').matches,
          init() {
              this.$watch('darkMode', value => {
                  try {
                      localStorage.setItem('cashtrack-theme', value ? 'dark' : 'light');
                  } catch (error) {}
              });

              const media = window.matchMedia('(min-width: 1024px)');
              const updateViewport = () => {
                  this.isDesktop = media.matches;
              };

              if (media.addEventListener) {
                  media.addEventListener('change', updateViewport);
              } else {
                  media.addListener(updateViewport);
              }
          }
      }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — CashTrack</title>
    <script>
        try {
            if (localStorage.getItem('cashtrack-theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        } catch (error) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">

{{-- Mobile overlay --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 z-30 lg:hidden"
     style="display: none; background: rgba(0,0,0,0.4);"></div>

{{-- Sidebar --}}
<aside class="fixed inset-y-0 left-0 z-40 flex flex-col w-64 shadow-sm"
       style="background-color: var(--bg-sidebar); border-right: 1px solid var(--border-color); transition: transform 0.3s ease, background-color 0.3s ease; transform: translateX(-100%);"
       :style="{ transform: (sidebarOpen || isDesktop) ? 'translateX(0)' : 'translateX(-100%)' }">

    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 py-4" style="border-bottom: 1px solid var(--border-color);">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background: linear-gradient(135deg,#6366f1,#8b5cf6);">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
            </svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold" style="color: var(--text-primary);">CashTrack</p>
            <p class="text-xs" style="color: var(--text-muted);">Finance Dashboard</p>
        </div>
        <button @click="sidebarOpen = false" class="ml-auto lg:hidden text-slate-400 hover:text-slate-600 p-1">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-3 py-4 overflow-y-auto" style="display:flex;flex-direction:column;gap:0.25rem;">
        <p class="px-3 mb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Menu</p>

        <a href="{{ route('dashboard') }}"
           class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('transactions.index') }}"
           class="sidebar-link {{ request()->routeIs('transactions.index') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
            </svg>
            <span>Riwayat Transaksi</span>
        </a>

        <a href="{{ route('transactions.create') }}"
           class="sidebar-link {{ request()->routeIs('transactions.create') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Tambah Transaksi</span>
        </a>

        <a href="{{ route('settings.edit') }}"
           class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.592c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.431.992a7.723 7.723 0 010 .255c-.007.379.138.751.431.992l1.003.827c.424.35.534.955.26 1.431l-1.296 2.247a1.125 1.125 0 01-1.37.49l-1.217-.456c-.355-.133-.751-.072-1.075.124a6.47 6.47 0 01-.22.127c-.332.184-.582.496-.645.87l-.213 1.281c-.09.542-.56.94-1.11.94h-2.592c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.645-.87a6.52 6.52 0 01-.22-.127c-.324-.196-.72-.257-1.075-.124l-1.217.456a1.125 1.125 0 01-1.37-.49l-1.296-2.247a1.125 1.125 0 01.26-1.431l1.003-.827c.293-.241.438-.613.431-.992a7.81 7.81 0 010-.255c.007-.379-.138-.751-.431-.992l-1.003-.827a1.125 1.125 0 01-.26-1.431l1.296-2.247a1.125 1.125 0 011.37-.49l1.217.456c.355.133.751.072 1.075-.124.073-.044.146-.087.22-.127.332-.184.582-.496.645-.87l.213-1.281z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>Pengaturan</span>
        </a>
    </nav>

    {{-- User info --}}
    <div class="p-4" style="border-top: 1px solid var(--border-color);">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold text-white flex-shrink-0"
                 style="background: linear-gradient(135deg,#6366f1,#8b5cf6);">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium truncate" style="color: var(--text-primary);">{{ auth()->user()->name ?? 'Admin' }}</p>
                <p class="text-xs truncate" style="color: var(--text-muted);">{{ auth()->user()->email ?? '' }}</p>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all duration-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>

{{-- Desktop sidebar spacer --}}
<div class="hidden lg:block w-64 flex-shrink-0"></div>

{{-- Main content --}}
<div class="lg:pl-64 min-h-screen flex flex-col">

    {{-- Top bar --}}
    <header class="sticky top-0 z-20 flex items-center gap-4 px-4 sm:px-6 py-3"
            style="background-color: var(--bg-header); border-bottom: 1px solid var(--border-color); backdrop-filter: blur(12px); transition: background-color 0.3s ease;">
        <button @click="sidebarOpen = true"
                class="lg:hidden text-slate-500 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
        </button>

        <div class="flex-1 min-w-0">
            <h1 class="text-base font-semibold truncate" style="color: var(--text-primary);">@yield('page-title', 'Dashboard')</h1>
            <p class="text-xs" style="color: var(--text-muted);">@yield('page-subtitle', now()->locale('id')->translatedFormat('l, d F Y'))</p>
        </div>

        <div class="flex items-center gap-2">
            <button @click="darkMode = !darkMode"
                    class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                    title="Toggle Dark Mode">
                <svg x-show="!darkMode" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                </svg>
                <svg x-show="darkMode" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                </svg>
            </button>

            <a href="{{ route('transactions.create') }}" class="btn-primary" style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Tambah
            </a>
        </div>
    </header>

    {{-- Flash messages --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="mx-4 sm:mx-6 mt-4 flex items-center gap-3 px-4 py-3 rounded-lg text-sm"
         style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46;">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
        <button @click="show = false" class="ml-auto opacity-60 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif

    @if($errors->any())
    <div class="mx-4 sm:mx-6 mt-4 px-4 py-3 rounded-lg text-sm"
         style="background:#fff1f2; border:1px solid #fecdd3; color:#9f1239;">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Page content --}}
    <main class="flex-1 px-4 sm:px-6 py-6">
        @yield('content')
    </main>

    <footer class="px-4 sm:px-6 py-4 text-center text-xs" style="border-top: 1px solid var(--border-color); color: var(--text-muted);">
        CashTrack &copy; {{ date('Y') }} — Built with Laravel & Tailwind CSS
    </footer>
</div>

@stack('scripts')
</body>
</html>
