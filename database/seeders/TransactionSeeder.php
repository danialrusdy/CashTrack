<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $transactions = [
            // Bulan ini
            ['income',  'Gaji Bulanan',           8500000, 'Transfer BCA',           $now->copy()->startOfMonth()->addDays(0)],
            ['income',  'Freelance Web Design',   2500000, 'Project landing page',   $now->copy()->startOfMonth()->addDays(3)],
            ['expense', 'Sewa Kos',               1500000, 'Bayar bulan ini',        $now->copy()->startOfMonth()->addDays(1)],
            ['expense', 'Belanja Sembako',          450000, 'Indomaret + Alfamart',  $now->copy()->startOfMonth()->addDays(5)],
            ['expense', 'Makan Siang',               85000, 'Warteg langganan',      $now->copy()->startOfMonth()->addDays(7)],
            ['income',  'Bonus Proyek',             750000, 'Extra dari klien',      $now->copy()->startOfMonth()->addDays(10)],
            ['expense', 'Tagihan Listrik',          230000, 'PLN bulan ini',         $now->copy()->startOfMonth()->addDays(12)],
            ['expense', 'Beli Ayam Geprek',          15000, 'Sarapan pagi',          $now->copy()->startOfMonth()->addDays(14)],
            ['expense', 'Pulsa Internet',            95000, 'Paket data XL',         $now->copy()->startOfMonth()->addDays(15)],
            ['income',  'Transfer dari Ortu',       500000, 'Tunjangan bulanan',     $now->copy()->startOfMonth()->addDays(16)],

            // Bulan lalu
            ['income',  'Gaji Bulanan',           8500000, 'Transfer BCA',           $now->copy()->subMonth()->startOfMonth()->addDays(0)],
            ['expense', 'Sewa Kos',               1500000, 'Bayar bulan lalu',       $now->copy()->subMonth()->startOfMonth()->addDays(1)],
            ['income',  'Freelance Logo',           600000, 'Project branding',       $now->copy()->subMonth()->startOfMonth()->addDays(8)],
            ['expense', 'Belanja Bulanan',          380000, 'Superindo',              $now->copy()->subMonth()->startOfMonth()->addDays(10)],
            ['expense', 'Tagihan Air',               75000, 'PDAM',                   $now->copy()->subMonth()->startOfMonth()->addDays(12)],
            ['expense', 'Kopi & Snack',              65000, 'Starbucks',              $now->copy()->subMonth()->startOfMonth()->addDays(15)],
            ['income',  'Konsultasi IT',            300000, 'Per jam',                $now->copy()->subMonth()->startOfMonth()->addDays(20)],

            // 2 bulan lalu
            ['income',  'Gaji Bulanan',           8500000, 'Transfer BCA',           $now->copy()->subMonths(2)->startOfMonth()->addDays(0)],
            ['expense', 'Sewa Kos',               1500000, 'Bayar 2 bulan lalu',     $now->copy()->subMonths(2)->startOfMonth()->addDays(1)],
            ['income',  'Jual Barang Online',       950000, 'Tokopedia',              $now->copy()->subMonths(2)->startOfMonth()->addDays(5)],
            ['expense', 'Beli Buku Pemrograman',   185000, 'Gramedia',               $now->copy()->subMonths(2)->startOfMonth()->addDays(8)],
            ['expense', 'Makan Malam',             120000, 'Dinner bareng teman',    $now->copy()->subMonths(2)->startOfMonth()->addDays(18)],

            // 3 bulan lalu
            ['income',  'Gaji Bulanan',           8500000, 'Transfer BCA',           $now->copy()->subMonths(3)->startOfMonth()->addDays(0)],
            ['expense', 'Sewa Kos',               1500000, 'Bayar 3 bulan lalu',     $now->copy()->subMonths(3)->startOfMonth()->addDays(1)],
            ['income',  'Bonus Tahunan',          3000000, 'Dari kantor',            $now->copy()->subMonths(3)->startOfMonth()->addDays(10)],
            ['expense', 'Liburan Akhir Tahun',    2200000, 'Ke Bali',               $now->copy()->subMonths(3)->startOfMonth()->addDays(15)],
            ['expense', 'Servis Motor',             450000, 'Bengkel resmi',          $now->copy()->subMonths(3)->startOfMonth()->addDays(22)],

            // 4 bulan lalu
            ['income',  'Gaji Bulanan',           8500000, 'Transfer BCA',           $now->copy()->subMonths(4)->startOfMonth()->addDays(0)],
            ['expense', 'Sewa Kos',               1500000, 'Bayar 4 bulan lalu',     $now->copy()->subMonths(4)->startOfMonth()->addDays(1)],
            ['expense', 'Belanja Pakaian',          750000, 'H&M sale',              $now->copy()->subMonths(4)->startOfMonth()->addDays(12)],
        ];

        $sources = ['web', 'telegram'];
        foreach ($transactions as $i => [$type, $name, $amount, $note, $date]) {
            Transaction::create([
                'type'             => $type,
                'name'             => $name,
                'amount'           => $amount,
                'note'             => $note,
                'transaction_date' => $date->toDateString(),
                'source'           => $sources[$i % 2],
            ]);
        }
    }
}
