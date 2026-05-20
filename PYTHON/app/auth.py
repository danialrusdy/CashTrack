import secrets
from datetime import date

from fastapi import Request
from fastapi.responses import RedirectResponse
from sqlalchemy.orm import Session

from app.models import User

MONTH_NAMES = {
    1: "Januari", 2: "Februari", 3: "Maret", 4: "April",
    5: "Mei", 6: "Juni", 7: "Juli", 8: "Agustus",
    9: "September", 10: "Oktober", 11: "November", 12: "Desember",
}

DAY_NAMES = {
    0: "Senin", 1: "Selasa", 2: "Rabu", 3: "Kamis",
    4: "Jumat", 5: "Sabtu", 6: "Minggu",
}


def get_current_user(request: Request, db: Session):
    user_id = request.session.get("user_id")
    if not user_id:
        return None
    return db.query(User).filter(User.id == user_id).first()


def require_login(request: Request, db: Session):
    """Returns redirect if not logged in, otherwise returns None."""
    if not request.session.get("user_id"):
        return RedirectResponse(url="/login", status_code=302)
    return None


def get_csrf_token(request: Request) -> str:
    if "csrf_token" not in request.session:
        request.session["csrf_token"] = secrets.token_hex(32)
    return request.session["csrf_token"]


def verify_csrf(request: Request, token: str) -> bool:
    return secrets.compare_digest(
        request.session.get("csrf_token", ""),
        token or "",
    )


def get_flash(request: Request) -> dict:
    flash = {}
    if "flash_success" in request.session:
        flash["success"] = request.session.pop("flash_success")
    if "flash_errors" in request.session:
        flash["errors"] = request.session.pop("flash_errors")
    return flash


def formatted_today() -> str:
    today = date.today()
    return f"{DAY_NAMES[today.weekday()]}, {today.day} {MONTH_NAMES[today.month]} {today.year}"
