<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;
    private string $apiUrl;

    public function __construct()
    {
        $this->token  = config('services.telegram.token', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    // ── Public entry points ───────────────────────────────────────

    public function handleWebhook(array $payload): void
    {
        try {
            if (isset($payload['callback_query'])) {
                $this->handleCallbackQuery($payload['callback_query']);
            } elseif (isset($payload['message'])) {
                $this->handleMessage($payload['message']);
            }
        } catch (\Throwable $e) {
            Log::error('[Telegram] Webhook error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    public function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text   = trim($message['text'] ?? '');

        if ($text === '/start') {
            $this->setState($chatId, 'idle');
            $name = $message['from']['first_name'] ?? 'Kamu';
            $this->sendMessage(
                $chatId,
                "👋 Halo, *{$name}*!\n\nSelamat datang di *CashTrack Bot* 💰\n\nGunakan tombol di bawah untuk mencatat keuanganmu:",
                $this->buildMainMenu()
            );
            return;
        }

        $state = $this->getState($chatId);

        match ($state) {
            'waiting_income'  => $this->processTransactionInput($chatId, $text, 'income'),
            'waiting_expense' => $this->processTransactionInput($chatId, $text, 'expense'),
            default           => $this->sendMainMenu($chatId),
        };
    }

    public function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId   = $callbackQuery['message']['chat']['id'];
        $data     = $callbackQuery['data'] ?? '';
        $queryId  = $callbackQuery['id'];

        $this->answerCallbackQuery($queryId);

        match ($data) {
            'income'  => $this->promptIncome($chatId),
            'expense' => $this->promptExpense($chatId),
            'summary' => $this->sendSummary($chatId),
            'help'    => $this->sendHelp($chatId),
            'menu'    => $this->sendMainMenu($chatId),
            default   => $this->sendMainMenu($chatId),
        };
    }

    // ── Flow handlers ─────────────────────────────────────────────

    private function promptIncome(string|int $chatId): void
    {
        $this->setState($chatId, 'waiting_income');
        $this->sendMessage($chatId,
            "💰 *Tambah Pemasukan*\n\nKirim data dengan format:\n`nama,nominal,keterangan`\n\n*Contoh:*\n`Gaji bulanan,5000000,transfer BCA`\n`Freelance desain,750000,project logo klien`",
            $this->buildCancelMenu()
        );
    }

    private function promptExpense(string|int $chatId): void
    {
        $this->setState($chatId, 'waiting_expense');
        $this->sendMessage($chatId,
            "💸 *Tambah Pengeluaran*\n\nKirim data dengan format:\n`nama,nominal,keterangan`\n\n*Contoh:*\n`Beli ayam geprek,15000,sarapan pagi`\n`Belanja sembako,450000,Indomaret`",
            $this->buildCancelMenu()
        );
    }

    private function processTransactionInput(string|int $chatId, string $text, string $type): void
    {
        $parsed = $this->parseTransactionInput($text);

        if ($parsed === false) {
            $this->sendMessage($chatId,
                "❌ *Format salah!*\n\nGunakan format:\n`nama,nominal,keterangan`\n\n*Contoh:* `Gaji bulanan,5000000,transfer BCA`\n\nSilakan coba lagi 👇",
                $this->buildCancelMenu()
            );
            return;
        }

        $saved = $this->saveTransaction($chatId, $type, $parsed);

        if ($saved) {
            $typeLabel = $type === 'income' ? '💰 Pemasukan' : '💸 Pengeluaran';
            $this->sendMessage($chatId,
                "✅ *{$typeLabel} berhasil dicatat!*\n\n📝 Nama    : {$parsed['name']}\n💵 Nominal : Rp " . number_format($parsed['amount'], 0, ',', '.') . "\n📋 Ket     : " . ($parsed['note'] ?: '-') . "\n📅 Tanggal : " . Carbon::today()->locale('id')->translatedFormat('d F Y'),
                $this->buildMainMenu()
            );
            $this->setState($chatId, 'idle');
        } else {
            $this->sendMessage($chatId, '⚠️ Gagal menyimpan data. Silakan coba lagi.', $this->buildMainMenu());
            $this->setState($chatId, 'idle');
        }
    }

    private function sendSummary(string|int $chatId): void
    {
        $now     = Carbon::now();
        $income  = Transaction::byMonth($now->year, $now->month)->income()->sum('amount');
        $expense = Transaction::byMonth($now->year, $now->month)->expense()->sum('amount');
        $balance = $income - $expense;
        $count   = Transaction::byMonth($now->year, $now->month)->count();
        $bulan   = $now->locale('id')->translatedFormat('F Y');

        $text = "📊 *Ringkasan Bulan Ini ({$bulan})*\n\n"
            . "💰 Total Pemasukan  : Rp " . number_format($income, 0, ',', '.') . "\n"
            . "💸 Total Pengeluaran: Rp " . number_format($expense, 0, ',', '.') . "\n"
            . "💵 Saldo            : Rp " . number_format($balance, 0, ',', '.') . "\n"
            . "📋 Transaksi        : {$count} transaksi";

        $this->sendMessage($chatId, $text, $this->buildMainMenu());
    }

    private function sendHelp(string|int $chatId): void
    {
        $text = "❓ *Panduan CashTrack Bot*\n\n"
            . "*Perintah:*\n"
            . "/start — Tampilkan menu utama\n\n"
            . "*Format Input:*\n"
            . "`nama,nominal,keterangan`\n\n"
            . "*Contoh Pemasukan:*\n"
            . "`Gaji bulanan,5000000,transfer BCA`\n\n"
            . "*Contoh Pengeluaran:*\n"
            . "`Beli kopi,25000,Starbucks`\n\n"
            . "• Nominal harus berupa angka positif\n"
            . "• Keterangan boleh dikosongkan dengan tanda `-`\n"
            . "• Data langsung tersimpan ke dashboard web";

        $this->sendMessage($chatId, $text, $this->buildMainMenu());
    }

    public function sendMainMenu(string|int $chatId): void
    {
        $this->setState($chatId, 'idle');
        $this->sendMessage($chatId, "🏠 *Menu Utama* — Pilih aksi:", $this->buildMainMenu());
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function parseTransactionInput(string $text): array|false
    {
        $parts = array_map('trim', explode(',', $text, 3));

        if (count($parts) < 2) return false;

        $name   = $parts[0];
        $amount = preg_replace('/[^0-9]/', '', $parts[1]);
        $note   = $parts[2] ?? '';

        if (empty($name) || !is_numeric($amount) || (int) $amount <= 0) {
            return false;
        }

        return [
            'name'   => $name,
            'amount' => (float) $amount,
            'note'   => ($note === '-' ? '' : $note),
        ];
    }

    public function saveTransaction(string|int $chatId, string $type, array $data): bool
    {
        try {
            Transaction::create([
                'type'             => $type,
                'name'             => $data['name'],
                'amount'           => $data['amount'],
                'note'             => $data['note'] ?? null,
                'transaction_date' => Carbon::today()->toDateString(),
                'source'           => 'telegram',
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('[Telegram] Save transaction error: ' . $e->getMessage());
            return false;
        }
    }

    // ── Telegram API wrappers ─────────────────────────────────────

    public function sendMessage(string|int $chatId, string $text, ?array $replyMarkup = null): void
    {
        $params = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            Http::timeout(10)->post("{$this->apiUrl}/sendMessage", $params);
        } catch (\Throwable $e) {
            Log::error('[Telegram] sendMessage error: ' . $e->getMessage());
        }
    }

    private function answerCallbackQuery(string $queryId): void
    {
        try {
            Http::timeout(5)->post("{$this->apiUrl}/answerCallbackQuery", ['callback_query_id' => $queryId]);
        } catch (\Throwable $e) {
            Log::error('[Telegram] answerCallbackQuery error: ' . $e->getMessage());
        }
    }

    // ── Keyboard builders ─────────────────────────────────────────

    private function buildMainMenu(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '💰 Pemasukan',  'callback_data' => 'income'],
                    ['text' => '💸 Pengeluaran', 'callback_data' => 'expense'],
                ],
                [
                    ['text' => '📊 Ringkasan', 'callback_data' => 'summary'],
                    ['text' => '❓ Bantuan',    'callback_data' => 'help'],
                ],
            ],
        ];
    }

    private function buildCancelMenu(): array
    {
        return [
            'inline_keyboard' => [
                [['text' => '↩️ Kembali ke Menu', 'callback_data' => 'menu']],
            ],
        ];
    }

    // ── State management via Cache ────────────────────────────────

    private function getState(string|int $chatId): string
    {
        return Cache::get("tg_state_{$chatId}", 'idle');
    }

    private function setState(string|int $chatId, string $state): void
    {
        Cache::put("tg_state_{$chatId}", $state, now()->addHours(1));
    }
}
