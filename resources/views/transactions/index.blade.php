@extends('layouts.app')

@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi')
@section('page-subtitle', 'Semua catatan pemasukan dan pengeluaran')

@section('content')
<div x-data="{ showDeleteId: null }">

    {{-- Toolbar --}}
    <div class="card p-4 mb-5 fade-in">
        <form method="GET" action="{{ route('transactions.index') }}" class="flex flex-wrap gap-3 items-end">
            {{-- Search --}}
            <div class="flex-1 min-w-48">
                <label class="form-label">Cari nama</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nama transaksi..."
                           class="form-input pl-9">
                </div>
            </div>

            {{-- Tipe --}}
            <div>
                <label class="form-label">Tipe</label>
                <select name="type" class="form-input" style="min-width: 130px;">
                    <option value="">Semua Tipe</option>
                    <option value="income"  {{ request('type') === 'income'  ? 'selected' : '' }}>Pemasukan</option>
                    <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
            </div>

            {{-- Bulan --}}
            <div>
                <label class="form-label">Bulan</label>
                <select name="month" class="form-input">
                    <option value="">Semua Bulan</option>
                    @foreach($months as $num => $label)
                        <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tahun --}}
            <div>
                <label class="form-label">Tahun</label>
                <select name="year" class="form-input">
                    <option value="">Semua Tahun</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('transactions.index') }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    {{-- Action bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 fade-in">
        <p class="text-sm text-slate-500">
            Menampilkan <span class="font-semibold text-slate-700">{{ $transactions->count() }}</span>
            dari <span class="font-semibold text-slate-700">{{ $transactions->total() }}</span> transaksi
        </p>
        <div class="flex gap-2">
            <a href="{{ route('transactions.export') }}?{{ http_build_query(request()->query()) }}"
               class="btn-secondary" style="font-size:0.75rem; padding:0.375rem 0.75rem;">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Export CSV
            </a>
            <a href="{{ route('transactions.create') }}" class="btn-primary" style="font-size:0.75rem; padding:0.375rem 0.75rem;">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Tambah Transaksi
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden fade-in">
        @if($transactions->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-slate-400">
            <svg class="w-12 h-12 text-slate-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
            </svg>
            <p class="text-sm font-medium">Tidak ada transaksi</p>
            <p class="text-xs mt-1">Coba ubah filter atau tambah transaksi baru</p>
            <a href="{{ route('transactions.create') }}" class="btn-primary mt-4" style="font-size:0.75rem;">
                Tambah Sekarang
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead style="background:#f8fafc; border-bottom: 1px solid #f1f5f9;">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Tanggal</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Nama / Keterangan</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide hidden sm:table-cell">Tipe</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide whitespace-nowrap">Nominal</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide hidden lg:table-cell">Sumber</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($transactions as $transaction)
                    <tr class="table-row">
                        <td class="px-5 py-3.5 text-xs text-slate-500 font-medium whitespace-nowrap">
                            {{ $transaction->transaction_date->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="font-medium text-slate-700">{{ $transaction->name }}</p>
                            @if($transaction->note)
                            <p class="text-xs text-slate-400 mt-0.5 truncate max-w-xs">{{ $transaction->note }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 hidden sm:table-cell">
                            @if($transaction->type === 'income')
                                <span class="badge-income">Pemasukan</span>
                            @else
                                <span class="badge-expense">Pengeluaran</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right font-semibold whitespace-nowrap {{ $transaction->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $transaction->type === 'income' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5 text-center hidden lg:table-cell">
                            @if($transaction->source === 'web')
                                <span class="badge-web">Web</span>
                            @else
                                <span class="badge-telegram">Telegram</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <form action="{{ route('transactions.destroy', $transaction->id) }}" method="POST"
                                  @submit.prevent="if(confirm('Yakin ingin menghapus transaksi ini?')) $event.target.submit()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger" style="padding: 0.25rem 0.5rem;">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
        <div class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-slate-400">
                Halaman {{ $transactions->currentPage() }} dari {{ $transactions->lastPage() }}
            </p>
            <div class="flex items-center gap-1">
                {{-- Previous --}}
                @if($transactions->onFirstPage())
                    <span class="px-3 py-1.5 text-xs text-slate-300 rounded-lg border border-slate-100 cursor-not-allowed">← Prev</span>
                @else
                    <a href="{{ $transactions->previousPageUrl() }}"
                       class="px-3 py-1.5 text-xs text-slate-600 hover:text-indigo-600 rounded-lg border border-slate-200 hover:border-indigo-300 transition-colors">
                        ← Prev
                    </a>
                @endif

                {{-- Page numbers --}}
                @foreach($transactions->getUrlRange(max(1, $transactions->currentPage() - 2), min($transactions->lastPage(), $transactions->currentPage() + 2)) as $page => $url)
                    @if($page == $transactions->currentPage())
                        <span class="px-3 py-1.5 text-xs font-semibold text-white rounded-lg" style="background:#6366f1;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}"
                           class="px-3 py-1.5 text-xs text-slate-600 hover:text-indigo-600 rounded-lg border border-slate-200 hover:border-indigo-300 transition-colors">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if($transactions->hasMorePages())
                    <a href="{{ $transactions->nextPageUrl() }}"
                       class="px-3 py-1.5 text-xs text-slate-600 hover:text-indigo-600 rounded-lg border border-slate-200 hover:border-indigo-300 transition-colors">
                        Next →
                    </a>
                @else
                    <span class="px-3 py-1.5 text-xs text-slate-300 rounded-lg border border-slate-100 cursor-not-allowed">Next →</span>
                @endif
            </div>
        </div>
        @endif

        @endif
    </div>

</div>
@endsection
