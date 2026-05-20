import logging
import re
import time
from datetime import date

import httpx
from sqlalchemy.orm import Session

from app.config import settings
from app.models import Cache, Transaction

logger = logging.getLogger(__name__)

MONTH_NAMES = {
    1: "Januari", 2: "Februari", 3: "Maret", 4: "April",
    5: "Mei", 6: "Juni", 7: "Juli", 8: "Agustus",
    9: "September", 10: "Oktober", 11: "November", 12: "Desember",
}


def _format_idr(amount: float) -> str:
    return f"{int(amount):,}".replace(",", ".")


class TelegramService:
    def __init__(self, db: Session):
        self.db = db
        self.token = settings.telegram_bot_token
        self.api_url = f"https://api.telegram.org/bot{self.token}"

    # ── Entry points ──────────────────────────────────────────────

    def handle_webhook(self, payload: dict) -> None:
        try:
            if "callback_query" in payload:
                self.handle_callback_query(payload["callback_query"])
            elif "message" in payload:
                self.handle_message(payload["message"])
        except Exception as e:
            logger.error(f"[Telegram] Webhook error: {e}")

    def handle_message(self, message: dict) -> None:
        chat_id = str(message["chat"]["id"])
        text = (message.get("text") or "").strip()

        if text == "/start":
            self._set_state(chat_id, "idle")
            name = message.get("from", {}).get("first_name", "Kamu")
            self.send_message(
                chat_id,
                f"👋 Halo, *{name}*!\n\nSelamat datang di *CashTrack Bot* 💰\n\n"
                "Gunakan tombol di bawah untuk mencatat keuanganmu:",
                self._build_main_menu(),
            )
            return

        state = self._get_state(chat_id)
        if state == "waiting_income":
            self._process_input(chat_id, text, "income")
        elif state == "waiting_expense":
            self._process_input(chat_id, text, "expense")
        else:
            self._send_main_menu(chat_id)

    def handle_callback_query(self, callback_query: dict) -> None:
        chat_id = str(callback_query["message"]["chat"]["id"])
        data = callback_query.get("data", "")
        query_id = callback_query["id"]

        self._answer_callback(query_id)

        actions = {
            "income": self._prompt_income,
            "expense": self._prompt_expense,
            "summary": self._send_summary,
            "help": self._send_help,
            "menu": self._send_main_menu,
        }
        handler = actions.get(data, self._send_main_menu)
        handler(chat_id)

    # ── Flow handlers ─────────────────────────────────────────────

    def _prompt_income(self, chat_id: str) -> None:
        self._set_state(chat_id, "waiting_income")
        self.send_message(
            chat_id,
            "💰 *Tambah Pemasukan*\n\nKirim data dengan format:\n`nama,nominal,keterangan`\n\n"
            "*Contoh:*\n`Gaji bulanan,5000000,transfer BCA`\n`Freelance desain,750000,project logo klien`",
            self._build_cancel_menu(),
        )

    def _prompt_expense(self, chat_id: str) -> None:
        self._set_state(chat_id, "waiting_expense")
        self.send_message(
            chat_id,
            "💸 *Tambah Pengeluaran*\n\nKirim data dengan format:\n`nama,nominal,keterangan`\n\n"
            "*Contoh:*\n`Beli ayam geprek,15000,sarapan pagi`\n`Belanja sembako,450000,Indomaret`",
            self._build_cancel_menu(),
        )

    def _process_input(self, chat_id: str, text: str, tx_type: str) -> None:
        parsed = self.parse_transaction_input(text)
        if parsed is False:
            self.send_message(
                chat_id,
                "❌ *Format salah!*\n\nGunakan format:\n`nama,nominal,keterangan`\n\n"
                "*Contoh:* `Gaji bulanan,5000000,transfer BCA`\n\nSilakan coba lagi 👇",
                self._build_cancel_menu(),
            )
            return

        saved = self.save_transaction(tx_type, parsed)
        today = date.today()
        today_str = f"{today.day} {MONTH_NAMES[today.month]} {today.year}"

        if saved:
            label = "💰 Pemasukan" if tx_type == "income" else "💸 Pengeluaran"
            self.send_message(
                chat_id,
                f"✅ *{label} berhasil dicatat!*\n\n"
                f"📝 Nama    : {parsed['name']}\n"
                f"💵 Nominal : Rp {_format_idr(parsed['amount'])}\n"
                f"📋 Ket     : {parsed['note'] or '-'}\n"
                f"📅 Tanggal : {today_str}",
                self._build_main_menu(),
            )
            self._set_state(chat_id, "idle")
        else:
            self.send_message(chat_id, "⚠️ Gagal menyimpan data. Silakan coba lagi.", self._build_main_menu())
            self._set_state(chat_id, "idle")

    def _send_summary(self, chat_id: str) -> None:
        from sqlalchemy import func, extract
        today = date.today()
        income = float(
            self.db.query(func.sum(Transaction.amount))
            .filter(
                Transaction.type == "income",
                extract("year", Transaction.transaction_date) == today.year,
                extract("month", Transaction.transaction_date) == today.month,
            )
            .scalar() or 0
        )
        expense = float(
            self.db.query(func.sum(Transaction.amount))
            .filter(
                Transaction.type == "expense",
                extract("year", Transaction.transaction_date) == today.year,
                extract("month", Transaction.transaction_date) == today.month,
            )
            .scalar() or 0
        )
        count = (
            self.db.query(func.count(Transaction.id))
            .filter(
                extract("year", Transaction.transaction_date) == today.year,
                extract("month", Transaction.transaction_date) == today.month,
            )
            .scalar() or 0
        )
        bulan = f"{MONTH_NAMES[today.month]} {today.year}"
        text = (
            f"📊 *Ringkasan Bulan Ini ({bulan})*\n\n"
            f"💰 Total Pemasukan  : Rp {_format_idr(income)}\n"
            f"💸 Total Pengeluaran: Rp {_format_idr(expense)}\n"
            f"💵 Saldo            : Rp {_format_idr(income - expense)}\n"
            f"📋 Transaksi        : {count} transaksi"
        )
        self.send_message(chat_id, text, self._build_main_menu())

    def _send_help(self, chat_id: str) -> None:
        text = (
            "❓ *Panduan CashTrack Bot*\n\n"
            "*Perintah:*\n"
            "/start — Tampilkan menu utama\n\n"
            "*Format Input:*\n"
            "`nama,nominal,keterangan`\n\n"
            "*Contoh Pemasukan:*\n"
            "`Gaji bulanan,5000000,transfer BCA`\n\n"
            "*Contoh Pengeluaran:*\n"
            "`Beli kopi,25000,Starbucks`\n\n"
            "• Nominal harus berupa angka positif\n"
            "• Keterangan boleh dikosongkan dengan tanda `-`\n"
            "• Data langsung tersimpan ke dashboard web"
        )
        self.send_message(chat_id, text, self._build_main_menu())

    def _send_main_menu(self, chat_id: str) -> None:
        self._set_state(chat_id, "idle")
        self.send_message(chat_id, "🏠 *Menu Utama* — Pilih aksi:", self._build_main_menu())

    # ── Helpers ───────────────────────────────────────────────────

    def parse_transaction_input(self, text: str):
        parts = [p.strip() for p in text.split(",", 2)]
        if len(parts) < 2:
            return False
        name = parts[0]
        amount_raw = re.sub(r"[^0-9]", "", parts[1])
        note = parts[2] if len(parts) > 2 else ""
        if not name or not amount_raw or int(amount_raw) <= 0:
            return False
        return {
            "name": name,
            "amount": float(amount_raw),
            "note": "" if note == "-" else note,
        }

    def save_transaction(self, tx_type: str, data: dict) -> bool:
        try:
            tx = Transaction(
                type=tx_type,
                name=data["name"],
                amount=data["amount"],
                note=data.get("note") or None,
                transaction_date=date.today(),
                source="telegram",
            )
            self.db.add(tx)
            self.db.commit()
            return True
        except Exception as e:
            logger.error(f"[Telegram] Save transaction error: {e}")
            self.db.rollback()
            return False

    # ── Telegram API ──────────────────────────────────────────────

    def send_message(self, chat_id: str, text: str, reply_markup: dict = None) -> None:
        import json
        params = {"chat_id": chat_id, "text": text, "parse_mode": "Markdown"}
        if reply_markup:
            params["reply_markup"] = json.dumps(reply_markup)
        try:
            with httpx.Client(timeout=10) as client:
                client.post(f"{self.api_url}/sendMessage", data=params)
        except Exception as e:
            logger.error(f"[Telegram] sendMessage error: {e}")

    def _answer_callback(self, query_id: str) -> None:
        try:
            with httpx.Client(timeout=5) as client:
                client.post(f"{self.api_url}/answerCallbackQuery", data={"callback_query_id": query_id})
        except Exception as e:
            logger.error(f"[Telegram] answerCallbackQuery error: {e}")

    # ── Keyboard builders ─────────────────────────────────────────

    def _build_main_menu(self) -> dict:
        return {
            "inline_keyboard": [
                [
                    {"text": "💰 Pemasukan", "callback_data": "income"},
                    {"text": "💸 Pengeluaran", "callback_data": "expense"},
                ],
                [
                    {"text": "📊 Ringkasan", "callback_data": "summary"},
                    {"text": "❓ Bantuan", "callback_data": "help"},
                ],
            ]
        }

    def _build_cancel_menu(self) -> dict:
        return {
            "inline_keyboard": [
                [{"text": "↩️ Kembali ke Menu", "callback_data": "menu"}]
            ]
        }

    # ── State management (MySQL cache table) ──────────────────────

    def _get_state(self, chat_id: str) -> str:
        key = f"tg_state_{chat_id}"
        row = self.db.query(Cache).filter(Cache.key == key).first()
        if row and row.expiration > int(time.time()):
            return row.value
        return "idle"

    def _set_state(self, chat_id: str, state: str, ttl: int = 3600) -> None:
        key = f"tg_state_{chat_id}"
        expiration = int(time.time()) + ttl
        row = self.db.query(Cache).filter(Cache.key == key).first()
        if row:
            row.value = state
            row.expiration = expiration
        else:
            self.db.add(Cache(key=key, value=state, expiration=expiration))
        try:
            self.db.commit()
        except Exception as e:
            logger.error(f"[Telegram] setState error: {e}")
            self.db.rollback()
