import csv
import io
from datetime import date
from urllib.parse import urlencode

from fastapi import APIRouter, Depends, Request
from fastapi.responses import HTMLResponse, RedirectResponse, StreamingResponse
from fastapi.templating import Jinja2Templates
from sqlalchemy import extract, or_
from sqlalchemy.orm import Session

from app.auth import formatted_today, get_csrf_token, get_flash, require_login, verify_csrf
from app.database import get_db
from app.models import Transaction

router = APIRouter()
templates = Jinja2Templates(directory="templates")

MONTHS = {
    1: "Januari", 2: "Februari", 3: "Maret", 4: "April",
    5: "Mei", 6: "Juni", 7: "Juli", 8: "Agustus",
    9: "September", 10: "Oktober", 11: "November", 12: "Desember",
}

PER_PAGE = 15


class Paginator:
    def __init__(self, items, total, page, per_page, base_url, query_params):
        self.items = items
        self._total = total
        self._page = page
        self._per_page = per_page
        self._base_url = base_url
        self._query_params = {k: v for k, v in query_params.items() if k != "page" and v}

    def __iter__(self):
        return iter(self.items)

    @property
    def page_count(self):
        return len(self.items)

    @property
    def total(self):
        return self._total

    @property
    def current_page(self):
        return self._page

    @property
    def last_page(self):
        return max(1, (self._total + self._per_page - 1) // self._per_page)

    @property
    def on_first_page(self):
        return self._page == 1

    @property
    def has_more_pages(self):
        return self._page < self.last_page

    @property
    def has_pages(self):
        return self.last_page > 1

    @property
    def is_empty(self):
        return self._total == 0

    def _build_url(self, page):
        params = dict(self._query_params)
        params["page"] = str(page)
        return f"{self._base_url}?{urlencode(params)}"

    @property
    def previous_page_url(self):
        return self._build_url(self._page - 1) if not self.on_first_page else None

    @property
    def next_page_url(self):
        return self._build_url(self._page + 1) if self.has_more_pages else None

    def get_url_range(self, start, end):
        return {p: self._build_url(p) for p in range(start, end + 1)}


def _build_query(db: Session, params: dict):
    q = db.query(Transaction)
    if params.get("type") in ("income", "expense"):
        q = q.filter(Transaction.type == params["type"])
    if params.get("year"):
        try:
            q = q.filter(extract("year", Transaction.transaction_date) == int(params["year"]))
        except (ValueError, TypeError):
            pass
    if params.get("month"):
        try:
            q = q.filter(extract("month", Transaction.transaction_date) == int(params["month"]))
        except (ValueError, TypeError):
            pass
    if params.get("search"):
        q = q.filter(Transaction.name.ilike(f"%{params['search']}%"))
    return q


def _get_years(db: Session) -> list:
    from sqlalchemy import func
    rows = (
        db.query(extract("year", Transaction.transaction_date).label("yr"))
        .distinct()
        .order_by(extract("year", Transaction.transaction_date).desc())
        .all()
    )
    years = [int(r.yr) for r in rows if r.yr]
    today_year = date.today().year
    if today_year not in years:
        years.insert(0, today_year)
    return years


@router.get("/transactions", response_class=HTMLResponse)
async def index(request: Request, db: Session = Depends(get_db)):
    redirect = require_login(request, db)
    if redirect:
        return redirect

    params = dict(request.query_params)
    try:
        page = max(1, int(params.get("page", 1)))
    except (ValueError, TypeError):
        page = 1

    q = _build_query(db, params).order_by(
        Transaction.transaction_date.desc(), Transaction.id.desc()
    )
    total = q.count()
    items = q.offset((page - 1) * PER_PAGE).limit(PER_PAGE).all()

    paginator = Paginator(items, total, page, PER_PAGE, "/transactions", params)
    flash = get_flash(request)

    user = {
        "name": request.session.get("user_name", "Admin"),
        "email": request.session.get("user_email", ""),
    }

    return templates.TemplateResponse("transactions/index.html", {
        "request": request,
        "user": user,
        "csrf_token": get_csrf_token(request),
        "active_page": "transactions",
        "page_subtitle": "Semua catatan pemasukan dan pengeluaran",
        "transactions": paginator,
        "years": _get_years(db),
        "months": MONTHS,
        "current_year": date.today().year,
        "params": params,
        "flash_success": flash.get("success"),
        "flash_errors": flash.get("errors"),
    })


@router.get("/transactions/create", response_class=HTMLResponse)
async def create(request: Request, db: Session = Depends(get_db)):
    redirect = require_login(request, db)
    if redirect:
        return redirect

    user = {
        "name": request.session.get("user_name", "Admin"),
        "email": request.session.get("user_email", ""),
    }

    return templates.TemplateResponse("transactions/create.html", {
        "request": request,
        "user": user,
        "csrf_token": get_csrf_token(request),
        "active_page": "create",
        "page_subtitle": "Catat pemasukan atau pengeluaran baru",
        "today": date.today().strftime("%Y-%m-%d"),
        "errors": {},
        "old": {},
        "current_year": date.today().year,
    })


@router.post("/transactions")
async def store(request: Request, db: Session = Depends(get_db)):
    redirect = require_login(request, db)
    if redirect:
        return redirect

    form = await request.form()
    csrf = form.get("csrf_token", "")

    if not verify_csrf(request, csrf):
        return RedirectResponse(url="/transactions/create", status_code=302)

    tx_type = form.get("type", "")
    name = (form.get("name") or "").strip()
    amount_raw = form.get("amount", "")
    note = (form.get("note") or "").strip()
    tx_date = form.get("transaction_date", "")

    errors = {}
    if tx_type not in ("income", "expense"):
        errors["type"] = "Tipe harus dipilih."
    if not name:
        errors["name"] = "Nama wajib diisi."
    elif len(name) > 255:
        errors["name"] = "Nama maksimal 255 karakter."

    amount = None
    try:
        amount = float(amount_raw)
        if amount < 1:
            errors["amount"] = "Nominal minimal 1."
    except (ValueError, TypeError):
        errors["amount"] = "Nominal harus berupa angka."

    if not tx_date:
        errors["transaction_date"] = "Tanggal wajib diisi."
    else:
        try:
            from datetime import datetime
            tx_date_obj = datetime.strptime(tx_date, "%Y-%m-%d").date()
        except ValueError:
            errors["transaction_date"] = "Format tanggal tidak valid."
            tx_date_obj = None

    if errors:
        user = {
            "name": request.session.get("user_name", "Admin"),
            "email": request.session.get("user_email", ""),
        }
        return templates.TemplateResponse("transactions/create.html", {
            "request": request,
            "user": user,
            "csrf_token": get_csrf_token(request),
            "active_page": "create",
            "page_subtitle": "Catat pemasukan atau pengeluaran baru",
            "today": date.today().strftime("%Y-%m-%d"),
            "errors": errors,
            "old": dict(form),
            "current_year": date.today().year,
        }, status_code=422)

    tx = Transaction(
        type=tx_type,
        name=name,
        amount=amount,
        note=note or None,
        transaction_date=tx_date_obj,
        source="web",
    )
    db.add(tx)
    db.commit()

    request.session["flash_success"] = "Transaksi berhasil disimpan!"
    return RedirectResponse(url="/transactions", status_code=302)


@router.post("/transactions/{transaction_id}/delete")
async def destroy(request: Request, transaction_id: int, db: Session = Depends(get_db)):
    redirect = require_login(request, db)
    if redirect:
        return redirect

    form = await request.form()
    csrf = form.get("csrf_token", "")
    if not verify_csrf(request, csrf):
        return RedirectResponse(url="/transactions", status_code=302)

    tx = db.query(Transaction).filter(Transaction.id == transaction_id).first()
    if tx:
        db.delete(tx)
        db.commit()
        request.session["flash_success"] = "Transaksi berhasil dihapus."

    referer = request.headers.get("referer", "")
    if "dashboard" in referer:
        return RedirectResponse(url="/dashboard", status_code=302)
    return RedirectResponse(url="/transactions", status_code=302)


@router.get("/transactions/export")
async def export(request: Request, db: Session = Depends(get_db)):
    redirect = require_login(request, db)
    if redirect:
        return redirect

    params = dict(request.query_params)
    q = _build_query(db, params).order_by(
        Transaction.transaction_date.desc(), Transaction.id.desc()
    )
    rows = q.all()

    output = io.StringIO()
    writer = csv.writer(output, quoting=csv.QUOTE_ALL)
    writer.writerow(["Tanggal", "Nama", "Tipe", "Nominal", "Keterangan", "Sumber"])
    for tx in rows:
        writer.writerow([
            tx.transaction_date.strftime("%d/%m/%Y") if tx.transaction_date else "",
            tx.name,
            "Pemasukan" if tx.type == "income" else "Pengeluaran",
            float(tx.amount),
            tx.note or "",
            tx.source,
        ])

    output.seek(0)
    filename = f"cashtrack_{date.today().strftime('%Y%m%d')}.csv"
    return StreamingResponse(
        iter([output.getvalue()]),
        media_type="text/csv",
        headers={"Content-Disposition": f"attachment; filename={filename}"},
    )
