import logging

from fastapi import APIRouter, Depends, Request
from fastapi.responses import JSONResponse
from sqlalchemy.orm import Session

from app.config import settings
from app.database import get_db
from app.services.telegram_service import TelegramService

router = APIRouter()
logger = logging.getLogger(__name__)


@router.post("/api/telegram/webhook")
async def webhook(request: Request, db: Session = Depends(get_db)):
    try:
        payload = await request.json()
    except Exception:
        return JSONResponse({"ok": True})

    try:
        # Validate chat_id if configured
        allowed_chat_id = settings.telegram_allowed_chat_id
        if allowed_chat_id:
            chat_id = None
            if "message" in payload:
                chat_id = str(payload["message"].get("chat", {}).get("id", ""))
            elif "callback_query" in payload:
                chat_id = str(payload["callback_query"].get("message", {}).get("chat", {}).get("id", ""))
            if chat_id and chat_id != str(allowed_chat_id):
                logger.warning(f"[Telegram] Unauthorized chat_id: {chat_id}")
                return JSONResponse({"ok": True})

        svc = TelegramService(db)
        svc.handle_webhook(payload)
    except Exception as e:
        logger.error(f"[Telegram] Webhook processing error: {e}")

    # Always return 200 to Telegram
    return JSONResponse({"ok": True})
