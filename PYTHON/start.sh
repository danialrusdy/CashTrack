#!/bin/bash
# Script untuk menjalankan CashTrack di VPS Ubuntu
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Aktifkan virtualenv jika ada
if [ -d "venv" ]; then
    source venv/bin/activate
fi

echo "Starting CashTrack..."
uvicorn app.main:app --host 0.0.0.0 --port 8000 --workers 1
