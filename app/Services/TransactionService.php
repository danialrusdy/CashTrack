<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TransactionService
{
    public function getSummaryForMonth(int $year, int $month): array
    {
        $base = Transaction::byMonth($year, $month);

        $income  = (clone $base)->income()->sum('amount');
        $expense = (clone $base)->expense()->sum('amount');
        $count   = (clone $base)->count();

        return [
            'income'  => (float) $income,
            'expense' => (float) $expense,
            'balance' => (float) ($income - $expense),
            'count'   => (int) $count,
        ];
    }

    public function getLast12MonthsData(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = Carbon::now()->subMonths($i);
        }

        $labels   = [];
        $incomes  = [];
        $expenses = [];
        $balances = [];

        foreach ($months as $month) {
            $labels[]   = $month->locale('id')->translatedFormat('M Y');
            $income     = Transaction::byMonth($month->year, $month->month)->income()->sum('amount');
            $expense    = Transaction::byMonth($month->year, $month->month)->expense()->sum('amount');
            $incomes[]  = (float) $income;
            $expenses[] = (float) $expense;
            $balances[] = (float) ($income - $expense);
        }

        return compact('labels', 'incomes', 'expenses', 'balances');
    }

    public function getHighlightStats(): array
    {
        $monthly = Transaction::selectRaw(
            'YEAR(transaction_date) as year, MONTH(transaction_date) as month,
             SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income,
             SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense'
        )
            ->groupByRaw('YEAR(transaction_date), MONTH(transaction_date)')
            ->get();

        if ($monthly->isEmpty()) {
            return $this->emptyHighlights();
        }

        $bestIncomeMonth  = $monthly->sortByDesc('total_income')->first();
        $worstIncomeMonth = $monthly->sortBy('total_income')->first();
        $bestExpMonth     = $monthly->sortByDesc('total_expense')->first();
        $worstExpMonth    = $monthly->sortBy('total_expense')->first();

        return [
            'best_income_month'  => $this->formatMonthStat($bestIncomeMonth, 'total_income'),
            'worst_income_month' => $this->formatMonthStat($worstIncomeMonth, 'total_income'),
            'best_exp_month'     => $this->formatMonthStat($bestExpMonth, 'total_expense'),
            'worst_exp_month'    => $this->formatMonthStat($worstExpMonth, 'total_expense'),
        ];
    }

    public function getMonthVsLastMonth(): array
    {
        $now      = Carbon::now();
        $current  = $this->getSummaryForMonth($now->year, $now->month);
        $lastDate = $now->copy()->subMonth();
        $last     = $this->getSummaryForMonth($lastDate->year, $lastDate->month);

        $incomeDiff  = $last['income']  > 0 ? (($current['income']  - $last['income'])  / $last['income'])  * 100 : 0;
        $expenseDiff = $last['expense'] > 0 ? (($current['expense'] - $last['expense']) / $last['expense']) * 100 : 0;

        return [
            'current'      => $current,
            'last'         => $last,
            'income_diff'  => round($incomeDiff, 1),
            'expense_diff' => round($expenseDiff, 1),
        ];
    }

    private function formatMonthStat(object $row, string $field): array
    {
        $date = Carbon::createFromDate($row->year, $row->month, 1);
        return [
            'label'  => $date->locale('id')->translatedFormat('F Y'),
            'amount' => (float) $row->{$field},
        ];
    }

    private function emptyHighlights(): array
    {
        $empty = ['label' => '-', 'amount' => 0];
        return [
            'best_income_month'  => $empty,
            'worst_income_month' => $empty,
            'best_exp_month'     => $empty,
            'worst_exp_month'    => $empty,
        ];
    }
}
