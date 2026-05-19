<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Transaction::query()->orderByDesc('transaction_date')->orderByDesc('id');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('year')) {
            $query->whereYear('transaction_date', $request->year);
        }
        if ($request->filled('month')) {
            $query->whereMonth('transaction_date', $request->month);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $transactions = $query->paginate(15)->withQueryString();

        $years  = range(now()->year, now()->year - 4);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',     4  => 'April',
            5 => 'Mei',     6 => 'Juni',      7 => 'Juli',      8  => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('transactions.index', compact('transactions', 'years', 'months'));
    }

    public function create(): View
    {
        return view('transactions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type'             => 'required|in:income,expense',
            'name'             => 'required|string|max:255',
            'amount'           => 'required|numeric|min:1',
            'note'             => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
        ]);

        $validated['source'] = 'web';

        Transaction::create($validated);

        return redirect()->route('transactions.index')
            ->with('success', 'Transaksi berhasil ditambahkan!');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    public function export(Request $request): Response
    {
        $query = Transaction::query()->orderByDesc('transaction_date');

        if ($request->filled('type'))  $query->where('type', $request->type);
        if ($request->filled('year'))  $query->whereYear('transaction_date', $request->year);
        if ($request->filled('month')) $query->whereMonth('transaction_date', $request->month);

        $transactions = $query->get();

        $csv  = "Tanggal,Nama,Tipe,Nominal,Keterangan,Sumber\n";
        foreach ($transactions as $t) {
            $csv .= implode(',', [
                $t->transaction_date->format('d/m/Y'),
                '"' . str_replace('"', '""', $t->name) . '"',
                $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                $t->amount,
                '"' . str_replace('"', '""', $t->note ?? '') . '"',
                $t->source,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transaksi-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}
