#!/bin/bash
# Setup script untuk VPS Ubuntu 22.04
# Jalankan sekali saat pertama deploy
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "=== CashTrack Setup ==="

# Install Python venv
echo "[1/4] Membuat virtual environment..."
python3 -m venv venv
source venv/bin/activate

# Install dependencies
echo "[2/4] Install dependencies..."
pip install -r requirements.txt

# Create admin user
echo "[3/4] Membuat admin user..."
python seed.py

echo "[4/4] Setup selesai!"
echo ""
echo "Cara menjalankan:"
echo "  cd $(pwd)"
echo "  source venv/bin/activate"
echo "  uvicorn app.main:app --host 0.0.0.0 --port 8000"
echo ""
echo "Atau gunakan: bash start.sh"
