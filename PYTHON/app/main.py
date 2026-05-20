import logging

from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
from starlette.middleware.sessions import SessionMiddleware

from app.config import settings

logging.basicConfig(level=logging.INFO)

# ── Jinja2 setup (shared, loaded before routers import it) ────────

def _format_number(value) -> str:
    try:
        return f"{int(float(value)):,}".replace(",", ".")
    except (ValueError, TypeError):
        return "0"


templates = Jinja2Templates(directory="templates")
templates.env.filters["format_number"] = _format_number
templates.env.globals["max"] = max
templates.env.globals["min"] = min

# ── App setup ─────────────────────────────────────────────────────

app = FastAPI(title="CashTrack", docs_url=None, redoc_url=None)

app.add_middleware(
    SessionMiddleware,
    secret_key=settings.app_secret_key,
    max_age=86400 * 30,
    https_only=False,
)

app.mount("/static", StaticFiles(directory="static"), name="static")

# ── Import routers after templates is set up ──────────────────────
# Each router creates its own Jinja2Templates instance; we patch them here.
from app.routers import auth as auth_mod
from app.routers import dashboard as dash_mod
from app.routers import transactions as tx_mod
from app.routers import telegram as tg_mod

for mod in [auth_mod, dash_mod, tx_mod]:
    mod.templates.env.filters["format_number"] = _format_number
    mod.templates.env.globals["max"] = max
    mod.templates.env.globals["min"] = min

app.include_router(auth_mod.router)
app.include_router(dash_mod.router)
app.include_router(tx_mod.router)
app.include_router(tg_mod.router)
