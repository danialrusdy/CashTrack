from sqlalchemy import Column, BigInteger, String, Numeric, Text, Date, Enum, DateTime, func
from app.database import Base


class User(Base):
    __tablename__ = "users"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    name = Column(String(255), nullable=False)
    email = Column(String(255), unique=True, nullable=False)
    email_verified_at = Column(DateTime, nullable=True)
    password = Column(String(255), nullable=False)
    remember_token = Column(String(100), nullable=True)
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())


class Transaction(Base):
    __tablename__ = "transactions"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    type = Column(Enum("income", "expense"), nullable=False)
    name = Column(String(255), nullable=False)
    amount = Column(Numeric(15, 2), nullable=False)
    note = Column(Text, nullable=True)
    transaction_date = Column(Date, nullable=False)
    source = Column(Enum("web", "telegram"), nullable=False, default="web")
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())


class Cache(Base):
    __tablename__ = "cache"

    key = Column(String(255), primary_key=True)
    value = Column(Text, nullable=False)
    expiration = Column(BigInteger, nullable=False)
