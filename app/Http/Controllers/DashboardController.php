<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private TransactionService $service) {}

    public function index(Request $request): View
    {
        $year  = (int) ($request->query('year',  now()->year));
        $month = (int) ($request->query('month', now()->month));

        $summary     = $this->service->getSummaryForMonth($year, $month);
        $chartData   = $this->service->getLast12MonthsData();
        $highlights  = $this->service->getHighlightStats();
        $comparison  = $this->service->getMonthVsLastMonth();
        $recent = Transaction::orderByDesc('transaction_date')
                      ->orderByDesc('id')
                      ->limit(10)
                      ->get();

        $recentForJs = $recent->map(function ($t) {
            return [
                'id'               => $t->id,
                'type'             => $t->type,
                'name'             => $t->name,
                'amount'           => (float) $t->amount,
                'note'             => $t->note,
                'source'           => $t->source,
                'transaction_date' => $t->transaction_date->format('d/m/Y'),
                'delete_url'       => route('transactions.destroy', $t->id),
            ];
        })->values()->toArray();

        $years  = range(now()->year, now()->year - 4);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',     4  => 'April',
            5 => 'Mei',     6 => 'Juni',      7 => 'Juli',      8  => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('dashboard.index', compact(
            'summary', 'chartData', 'highlights', 'comparison',
            'recent', 'recentForJs', 'year', 'month', 'years', 'months'
        ));
    }

    public function getData(Request $request): JsonResponse
    {
        $year  = (int) ($request->query('year',  now()->year));
        $month = (int) ($request->query('month', now()->month));

        $summary = $this->service->getSummaryForMonth($year, $month);

        $recent = Transaction::byMonth($year, $month)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id'               => $t->id,
                'type'             => $t->type,
                'name'             => $t->name,
                'amount'           => $t->amount,
                'formatted_amount' => $t->formatted_amount,
                'note'             => $t->note,
                'source'           => $t->source,
                'transaction_date' => $t->transaction_date->format('d/m/Y'),
                'delete_url'       => route('transactions.destroy', $t->id),
            ]);

        return response()->json(array_merge($summary, ['recent' => $recent]));
    }
}
