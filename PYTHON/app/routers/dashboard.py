from datetime import date

from fastapi import APIRouter, Depends, Request
from fastapi.responses import HTMLResponse, JSONResponse, RedirectResponse
from fastapi.templating import Jinja2Templates
from sqlalchemy.orm import Session

from app.auth import formatted_today, get_csrf_token, get_flash, require_login
from app.database import get_db
from app.services.transaction_service import MONTH_NAMES, TransactionService

router = APIRouter()
templates = Jinja2Templates(directory="templates")

MONTHS = {
    1: "Januari", 2: "Februari", 3: "Maret", 4: "April",
    5: "Mei", 6: "Juni", 7: "Juli", 8: "Agustus",
    9: "September", 10: "Oktober", 11: "November", 12: "Desember",
}


@router.get("/dashboard", response_class=HTMLResponse)
async def dashboard(request: Request, db: Session = Depends(get_db)):
    redirect = require_login(request, db)
    if redirect:
        return redirect

    today = date.today()
    try:
        year = int(request.query_params.get("year", today.year))
        month = int(request.query_params.get("month", today.month))
    except (ValueError, TypeError):
        year, month = today.year, today.month

    svc = TransactionService(db)
    summary = svc.get_summary_for_month(year, month)
    chart_data = svc.get_last_12_months_data()
    highlights = svc.get_highlight_stats()
    comparison = svc.get_month_vs_last_month(year, month)
    recent_for_js = svc.get_recent_for_month(year, month)
    years = svc.get_available_years()

    flash = get_flash(request)

    user = {
        "name": request.session.get("user_name", "Admin"),
        "email": request.session.get("user_email", ""),
    }

    return templates.TemplateResponse("dashboard/index.html", {
        "request": request,
        "user": user,
        "csrf_token": get_csrf_token(request),
        "active_page": "dashboard",
        "page_subtitle": formatted_today(),
        "summary": summary,
        "chart_data": chart_data,
        "highlights": highlights,
        "comparison": comparison,
        "recent_for_js": recent_for_js,
        "year": year,
        "month": month,
        "years": years,
        "months": MONTHS,
        "current_year": today.year,
        "flash_success": flash.get("success"),
        "flash_errors": flash.get("errors"),
    })


@router.get("/api/dashboard-data")
async def dashboard_data(request: Request, db: Session = Depends(get_db)):
    if not request.session.get("user_id"):
        return JSONResponse({"error": "Unauthorized"}, status_code=401)

    today = date.today()
    try:
        year = int(request.query_params.get("year", today.year))
        month = int(request.query_params.get("month", today.month))
    except (ValueError, TypeError):
        year, month = today.year, today.month

    svc = TransactionService(db)
    summary = svc.get_summary_for_month(year, month)
    recent = svc.get_recent_for_month(year, month)

    return JSONResponse({
        "income": summary["income"],
        "expense": summary["expense"],
        "balance": summary["balance"],
        "count": summary["count"],
        "recent": recent,
    })
