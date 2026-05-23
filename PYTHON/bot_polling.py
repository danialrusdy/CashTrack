"""
Run Telegram bot locally without a public HTTPS webhook.

Usage:
    python bot_polling.py
"""
import logging
import time

import httpx

from app.config import settings
from app.database import SessionLocal
from app.services.telegram_service import TelegramService

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("telegram_polling")


def _chat_id_from_update(update: dict) -> str:
    if "message" in update:
        return str(update["message"].get("chat", {}).get("id", ""))
    if "callback_query" in update:
        return str(update["callback_query"].get("message", {}).get("chat", {}).get("id", ""))
    return ""


def _is_allowed(update: dict) -> bool:
    allowed_chat_id = settings.telegram_allowed_chat_id
    if not allowed_chat_id:
        return True
    return _chat_id_from_update(update) == str(allowed_chat_id)


def main() -> None:
    if not settings.telegram_bot_token:
        raise RuntimeError("TELEGRAM_BOT_TOKEN belum diisi di .env")

    api_url = f"https://api.telegram.org/bot{settings.telegram_bot_token}"
    offset = None

    with httpx.Client(timeout=35) as client:
        client.post(f"{api_url}/deleteWebhook", data={"drop_pending_updates": "false"})
        logger.info("Telegram polling started. Press Ctrl+C to stop.")

        while True:
            try:
                params = {"timeout": 30}
                if offset is not None:
                    params["offset"] = offset

                response = client.get(f"{api_url}/getUpdates", params=params)
                response.raise_for_status()
                payload = response.json()

                if not payload.get("ok"):
                    logger.warning("Telegram getUpdates returned not ok: %s", payload)
                    time.sleep(3)
                    continue

                for update in payload.get("result", []):
                    offset = update["update_id"] + 1

                    if not _is_allowed(update):
                        logger.warning("Unauthorized Telegram chat_id: %s", _chat_id_from_update(update))
                        continue

                    db = SessionLocal()
                    try:
                        TelegramService(db).handle_webhook(update)
                    finally:
                        db.close()
            except KeyboardInterrupt:
                logger.info("Telegram polling stopped.")
                break
            except Exception as exc:
                logger.error("Telegram polling error: %s", exc)
                time.sleep(3)


if __name__ == "__main__":
    main()
