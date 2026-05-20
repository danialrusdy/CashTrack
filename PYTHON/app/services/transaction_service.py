import calendar
from datetime import date, timedelta
from decimal import Decimal

from sqlalchemy import func, extract
from sqlalchemy.orm import Session

from app.models import Transaction

MONTH_NAMES = {
    1: "Januari", 2: "Februari", 3: "Maret", 4: "April",
    5: "Mei", 6: "Juni", 7: "Juli", 8: "Agustus",
    9: "September", 10: "Oktober", 11: "November", 12: "Desember",
}

SHORT_MONTHS = {
    1: "Jan", 2: "Feb", 3: "Mar", 4: "Apr",
    5: "Mei", 6: "Jun", 7: "Jul", 8: "Agu",
    9: "Sep", 10: "Okt", 11: "Nov", 12: "Des",
}


def _subtract_months(d: date, months: int) -> date:
    month = d.month - months
    year = d.year
    while month <= 0:
        month += 12
        year -= 1
    max_day = calendar.monthrange(year, month)[1]
    return date(year, month, min(d.day, max_day))


class TransactionService:
    def __init__(self, db: Session):
        self.db = db

    def get_summary_for_month(self, year: int, month: int) -> dict:
        income = float(
            self.db.query(func.sum(Transaction.amount))
            .filter(
                Transaction.type == "income",
                extract("year", Transaction.transaction_date) == year,
                extract("month", Transaction.transaction_date) == month,
            )
            .scalar() or 0
        )
        expense = float(
            self.db.query(func.sum(Transaction.amount))
            .filter(
                Transaction.type == "expense",
                extract("year", Transaction.transaction_date) == year,
                extract("month", Transaction.transaction_date) == month,
            )
            .scalar() or 0
        )
        count = (
            self.db.query(func.count(Transaction.id))
            .filter(
                extract("year", Transaction.transaction_date) == year,
                extract("month", Transaction.transaction_date) == month,
            )
            .scalar() or 0
        )
        return {
            "income": income,
            "expense": expense,
            "balance": income - expense,
            "count": count,
        }

    def get_last_12_months_data(self) -> dict:
        today = date.today()
        current = date(today.year, today.month, 1)
        months_list = [_subtract_months(current, i) for i in range(11, -1, -1)]

        labels, incomes, expenses, balances = [], [], [], []
        for m in months_list:
            income = float(
                self.db.query(func.sum(Transaction.amount))
                .filter(
                    Transaction.type == "income",
                    extract("year", Transaction.transaction_date) == m.year,
                    extract("month", Transaction.transaction_date) == m.month,
                )
                .scalar() or 0
            )
            expense = float(
                self.db.query(func.sum(Transaction.amount))
                .filter(
                    Transaction.type == "expense",
                    extract("year", Transaction.transaction_date) == m.year,
                    extract("month", Transaction.transaction_date) == m.month,
                )
                .scalar() or 0
            )
            labels.append(f"{SHORT_MONTHS[m.month]} {m.year}")
            incomes.append(income)
            expenses.append(expense)
            balances.append(income - expense)

        return {"labels": labels, "incomes": incomes, "expenses": expenses, "balances": balances}

    def get_highlight_stats(self) -> dict:
        rows = (
            self.db.query(
                Transaction.type,
                extract("year", Transaction.transaction_date).label("yr"),
                extract("month", Transaction.transaction_date).label("mo"),
                func.sum(Transaction.amount).label("total"),
            )
            .group_by(
                Transaction.type,
                extract("year", Transaction.transaction_date),
                extract("month", Transaction.transaction_date),
            )
            .all()
        )

        income_rows = [(r.yr, r.mo, float(r.total)) for r in rows if r.type == "income"]
        expense_rows = [(r.yr, r.mo, float(r.total)) for r in rows if r.type == "expense"]

        def _label(yr, mo):
            return f"{SHORT_MONTHS[int(mo)]} {int(yr)}" if yr and mo else "—"

        empty = {"label": "—", "amount": 0}

        best_income = max(income_rows, key=lambda x: x[2], default=None)
        worst_income = min(income_rows, key=lambda x: x[2], default=None)
        best_exp = max(expense_rows, key=lambda x: x[2], default=None)
        worst_exp = min(expense_rows, key=lambda x: x[2], default=None)

        return {
            "best_income_month": {"label": _label(*best_income[:2]), "amount": best_income[2]} if best_income else empty,
            "worst_income_month": {"label": _label(*worst_income[:2]), "amount": worst_income[2]} if worst_income else empty,
            "best_exp_month": {"label": _label(*best_exp[:2]), "amount": best_exp[2]} if best_exp else empty,
            "worst_exp_month": {"label": _label(*worst_exp[:2]), "amount": worst_exp[2]} if worst_exp else empty,
        }

    def get_month_vs_last_month(self, year: int, month: int) -> dict:
        current = self.get_summary_for_month(year, month)
        last_month = month - 1 if month > 1 else 12
        last_year = year if month > 1 else year - 1
        last = self.get_summary_for_month(last_year, last_month)

        def _pct(curr, prev):
            if prev == 0:
                return 100 if curr > 0 else 0
            return round((curr - prev) / prev * 100)

        return {
            "current": current,
            "last": last,
            "income_diff": _pct(current["income"], last["income"]),
            "expense_diff": _pct(current["expense"], last["expense"]),
        }

    def get_available_years(self) -> list:
        rows = (
            self.db.query(extract("year", Transaction.transaction_date).label("yr"))
            .distinct()
            .order_by(extract("year", Transaction.transaction_date).desc())
            .all()
        )
        years = [int(r.yr) for r in rows if r.yr]
        today_year = date.today().year
        if today_year not in years:
            years.insert(0, today_year)
        return years

    def get_recent_for_month(self, year: int, month: int, limit: int = 10) -> list:
        rows = (
            self.db.query(Transaction)
            .filter(
                extract("year", Transaction.transaction_date) == year,
                extract("month", Transaction.transaction_date) == month,
            )
            .order_by(Transaction.transaction_date.desc(), Transaction.id.desc())
            .limit(limit)
            .all()
        )
        result = []
        for tx in rows:
            result.append({
                "id": tx.id,
                "name": tx.name,
                "type": tx.type,
                "amount": float(tx.amount),
                "note": tx.note or "",
                "transaction_date": tx.transaction_date.strftime("%d/%m/%Y") if tx.transaction_date else "",
                "source": tx.source,
                "delete_url": f"/transactions/{tx.id}/delete",
            })
        return result
