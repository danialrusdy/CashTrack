<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CashTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background: linear-gradient(135deg, #eef2ff 0%, #f0f9ff 50%, #ecfdf5 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem;">

<div class="w-full" style="max-width: 400px;">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl mb-4 shadow-lg"
             style="background: linear-gradient(135deg,#6366f1,#8b5cf6);">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">CashTrack</h1>
        <p class="text-sm text-slate-500 mt-1">Dashboard Keuangan Pribadi</p>
    </div>

    {{-- Card --}}
    <div class="card p-7 shadow-lg" style="border: 1px solid rgba(99,102,241,0.1);">
        <h2 class="text-lg font-semibold text-slate-800 mb-1">Selamat datang kembali</h2>
        <p class="text-sm text-slate-400 mb-6">Masuk untuk melanjutkan</p>

        @if($errors->any())
        <div class="mb-5 px-4 py-3 rounded-lg text-sm" style="background:#fff1f2; border: 1px solid #fecdd3; color:#9f1239;">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="form-label">Alamat Email</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    <input id="email" type="email" name="email"
                           value="{{ old('email', 'admin@cashtrack.test') }}"
                           class="form-input pl-10"
                           autocomplete="email"
                           autofocus required>
                </div>
            </div>

            <div>
                <label for="password" class="form-label">Password</label>
                <div class="relative" x-data="{ show: false }">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    <input id="password" :type="show ? 'text' : 'password'" name="password"
                           class="form-input pl-10 pr-10"
                           autocomplete="current-password" required>
                    <button type="button" @click="show = !show"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-slate-300" style="accent-color:#6366f1;" {{ old('remember') ? 'checked' : '' }}>
                    <span class="text-sm text-slate-600">Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="btn-primary w-full justify-center" style="padding: 0.625rem 1rem;">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                </svg>
                Masuk ke Dashboard
            </button>
        </form>
    </div>

    {{-- Footer hint --}}
    <p class="text-center text-xs text-slate-400 mt-6">
        Default: <code class="font-mono bg-white/70 px-1.5 py-0.5 rounded">admin@cashtrack.test</code> /
        <code class="font-mono bg-white/70 px-1.5 py-0.5 rounded">password</code>
    </p>
</div>

</body>
</html>
