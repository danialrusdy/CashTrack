@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')
@section('page-subtitle', 'Kelola nama, email, dan password akun')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="card p-6 fade-in">
        <div class="flex items-center gap-3 mb-6 pb-5 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                 style="background:#eef2ff;">
                <svg class="w-5 h-5" style="color:#4f46e5;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-base font-semibold" style="color: var(--text-primary);">Akun</h2>
                <p class="text-xs" style="color: var(--text-muted);">Perbarui informasi login kamu</p>
            </div>
        </div>

        <form action="{{ route('settings.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="form-label">Nama <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name"
                       value="{{ old('name', $user->name) }}"
                       class="form-input @error('name') border-rose-400 @enderror"
                       autocomplete="name" required>
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="form-label">Email <span class="text-rose-500">*</span></label>
                <input type="email" id="email" name="email"
                       value="{{ old('email', $user->email) }}"
                       class="form-input @error('email') border-rose-400 @enderror"
                       autocomplete="email" required>
                @error('email')
                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-5 border-t border-slate-100">
                <h3 class="text-sm font-semibold mb-1" style="color: var(--text-primary);">Ganti Password</h3>
                <p class="text-xs mb-4" style="color: var(--text-muted);">Kosongkan bagian ini kalau tidak ingin mengganti password.</p>

                <div class="space-y-5">
                    <div>
                        <label for="current_password" class="form-label">Password Saat Ini</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-input @error('current_password') border-rose-400 @enderror"
                               autocomplete="current-password">
                        @error('current_password')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Password Baru</label>
                        <input type="password" id="password" name="password"
                               class="form-input @error('password') border-rose-400 @enderror"
                               autocomplete="new-password">
                        @error('password')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-input"
                               autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2 border-t border-slate-100">
                <button type="submit" class="btn-primary justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Simpan Pengaturan
                </button>
                <a href="{{ route('dashboard') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
