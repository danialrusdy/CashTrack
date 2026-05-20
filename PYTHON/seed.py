"""
Run this once to create the initial admin user.
Usage: python seed.py
"""
from app.database import SessionLocal, engine
from app.models import Base, User
from passlib.context import CryptContext

pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")


def main():
    Base.metadata.create_all(bind=engine)

    db = SessionLocal()
    try:
        existing = db.query(User).filter(User.email == "admin@cashtrack.test").first()
        if existing:
            print("Admin user already exists.")
            return

        user = User(
            name="Admin",
            email="admin@cashtrack.test",
            password=pwd_context.hash("password"),
        )
        db.add(user)
        db.commit()
        print("Admin user created: admin@cashtrack.test / password")
    finally:
        db.close()


if __name__ == "__main__":
    main()
