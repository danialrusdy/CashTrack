@extends('layouts.app')

@section('title', 'Tambah Transaksi')
@section('page-title', 'Tambah Transaksi')
@section('page-subtitle', 'Catat pemasukan atau pengeluaran baru')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="card p-6 fade-in"
         x-data="{
             type: '{{ old('type', 'income') }}',
             amount: '',
             formatAmount() {
                 let raw = this.amount.replace(/[^0-9]/g, '');
                 this.$refs.hiddenAmount.value = raw;
                 this.amount = raw ? parseInt(raw).toLocaleString('id-ID') : '';
             }
         }">

        <div class="flex items-center gap-3 mb-6 pb-5 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                 :style="type === 'income' ? 'background:#ecfdf5' : 'background:#fff1f2'">
                <svg class="w-5 h-5" :style="type === 'income' ? 'color:#059669' : 'color:#e11d48'"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33"/>
                </svg>
            </div>
            <div>
                <h2 class="text-base font-semibold text-slate-800">Tambah Transaksi Baru</h2>
                <p class="text-xs text-slate-400">Isi semua field yang diperlukan</p>
            </div>
        </div>

        <form action="{{ route('transactions.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Tipe --}}
            <div>
                <label class="form-label">Tipe Transaksi <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="type" value="income" x-model="type" class="sr-only">
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all duration-200"
                             :class="type === 'income'
                                 ? 'border-emerald-400 bg-emerald-50'
                                 : 'border-slate-200 bg-white hover:border-slate-300'">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 :class="type === 'income' ? 'bg-emerald-100' : 'bg-slate-100'">
                                <svg class="w-4 h-4" :class="type === 'income' ? 'text-emerald-600' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="type === 'income' ? 'text-emerald-700' : 'text-slate-600'">Pemasukan</p>
                                <p class="text-xs" :class="type === 'income' ? 'text-emerald-500' : 'text-slate-400'">Uang masuk</p>
                            </div>
                        </div>
                    </label>

                    <label class="relative cursor-pointer">
                        <input type="radio" name="type" value="expense" x-model="type" class="sr-only">
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all duration-200"
                             :class="type === 'expense'
                                 ? 'border-rose-400 bg-rose-50'
                                 : 'border-slate-200 bg-white hover:border-slate-300'">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 :class="type === 'expense' ? 'bg-rose-100' : 'bg-slate-100'">
                                <svg class="w-4 h-4" :class="type === 'expense' ? 'text-rose-600' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" :class="type === 'expense' ? 'text-rose-700' : 'text-slate-600'">Pengeluaran</p>
                                <p class="text-xs" :class="type === 'expense' ? 'text-rose-500' : 'text-slate-400'">Uang keluar</p>
                            </div>
                        </div>
                    </label>
                </div>
                @error('type')
                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nama --}}
            <div>
                <label for="name" class="form-label">Nama / Keterangan Singkat <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       placeholder="Contoh: Gaji bulanan, Beli sembako..."
                       class="form-input @error('name') border-rose-400 @enderror"
                       required>
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nominal --}}
            <div>
                <label for="amountDisplay" class="form-label">Nominal (Rp) <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-400">Rp</span>
                    <input type="text" id="amountDisplay"
                           x-model="amount"
                           @input="formatAmount()"
                           @keydown.enter.prevent=""
                           placeholder="0"
                           class="form-input pl-10 @error('amount') border-rose-400 @enderror"
                           inputmode="numeric"
                           required>
                    <input type="hidden" name="amount" x-ref="hiddenAmount" value="{{ old('amount') }}">
                </div>
                @error('amount')
                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Keterangan --}}
            <div>
                <label for="note" class="form-label">Keterangan Tambahan</label>
                <textarea id="note" name="note" rows="3"
                          placeholder="Keterangan opsional..."
                          class="form-input resize-none @error('note') border-rose-400 @enderror">{{ old('note') }}</textarea>
                @error('note')
                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tanggal --}}
            <div>
                <label for="transaction_date" class="form-label">Tanggal Transaksi <span class="text-rose-500">*</span></label>
                <input type="date" id="transaction_date" name="transaction_date"
                       value="{{ old('transaction_date', now()->format('Y-m-d')) }}"
                       class="form-input @error('transaction_date') border-rose-400 @enderror"
                       required>
                @error('transaction_date')
                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 pt-2 border-t border-slate-100">
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Simpan Transaksi
                </button>
                <a href="{{ route('transactions.index') }}" class="btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const display = document.getElementById('amountDisplay');
    const hidden  = document.querySelector('input[name="amount"]');
    const oldVal  = '{{ old('amount') }}';
    if (oldVal && display) {
        const num = parseInt(oldVal);
        if (!isNaN(num)) {
            display.value = num.toLocaleString('id-ID');
        }
    }
});
</script>
@endpush
@endsection
