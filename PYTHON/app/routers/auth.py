from fastapi import APIRouter, Depends, Request
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.templating import Jinja2Templates
from passlib.context import CryptContext
from sqlalchemy.orm import Session

from app.auth import get_csrf_token, verify_csrf
from app.database import get_db
from app.models import User

router = APIRouter()
templates = Jinja2Templates(directory="templates")
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")


@router.get("/login", response_class=HTMLResponse)
async def login_page(request: Request):
    if request.session.get("user_id"):
        return RedirectResponse(url="/dashboard", status_code=302)
    csrf = get_csrf_token(request)
    return templates.TemplateResponse("auth/login.html", {
        "request": request,
        "csrf_token": csrf,
        "errors": [],
    })


@router.post("/login")
async def login(request: Request, db: Session = Depends(get_db)):
    form = await request.form()
    email = form.get("email", "").strip()
    password = form.get("password", "")
    csrf = form.get("csrf_token", "")
    remember = form.get("remember")

    if not verify_csrf(request, csrf):
        return templates.TemplateResponse("auth/login.html", {
            "request": request,
            "csrf_token": get_csrf_token(request),
            "errors": ["Invalid request. Refresh and try again."],
        }, status_code=400)

    user = db.query(User).filter(User.email == email).first()

    if not user or not pwd_context.verify(password, user.password):
        return templates.TemplateResponse("auth/login.html", {
            "request": request,
            "csrf_token": get_csrf_token(request),
            "errors": ["Email atau password salah."],
            "old_email": email,
        }, status_code=422)

    request.session["user_id"] = user.id
    request.session["user_name"] = user.name
    request.session["user_email"] = user.email

    response = RedirectResponse(url="/dashboard", status_code=302)
    return response


@router.post("/logout")
async def logout(request: Request):
    request.session.clear()
    return RedirectResponse(url="/login", status_code=302)


@router.get("/")
async def root():
    return RedirectResponse(url="/dashboard", status_code=302)
