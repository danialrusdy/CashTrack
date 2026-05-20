import os
from dotenv import load_dotenv

load_dotenv()


class Settings:
    app_secret_key: str = os.getenv("APP_SECRET_KEY", "change-me-to-something-very-secure")
    db_host: str = os.getenv("DB_HOST", "127.0.0.1")
    db_port: int = int(os.getenv("DB_PORT", "3306"))
    db_database: str = os.getenv("DB_DATABASE", "finance_dashboard")
    db_username: str = os.getenv("DB_USERNAME", "root")
    db_password: str = os.getenv("DB_PASSWORD", "")
    telegram_bot_token: str = os.getenv("TELEGRAM_BOT_TOKEN", "")
    telegram_allowed_chat_id: str = os.getenv("TELEGRAM_ALLOWED_CHAT_ID", "")


settings = Settings()
