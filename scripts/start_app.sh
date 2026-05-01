#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DATA_DIR="$HOME/.mariadb_data"
SOCKET="$DATA_DIR/mysql.sock"
LOG_FILE="$DATA_DIR/mysqld.log"
INIT_FLAG="$DATA_DIR/.anubis_initialized"
PORT=3306

# Ensure data dir exists
if [ ! -d "$DATA_DIR/mysql" ]; then
  echo "[mariadb] initializing data directory"
  mkdir -p "$DATA_DIR"
  mariadb-install-db --auth-root-authentication-method=normal --datadir="$DATA_DIR" --user="$(whoami)" >/dev/null
fi

# Clear stale pid
rm -f "$DATA_DIR/mysqld.pid"

# Start MariaDB in background
echo "[mariadb] starting on port $PORT (socket $SOCKET)"
mysqld \
  --datadir="$DATA_DIR" \
  --socket="$SOCKET" \
  --pid-file="$DATA_DIR/mysqld.pid" \
  --port="$PORT" \
  --bind-address=127.0.0.1 \
  --log-error="$LOG_FILE" &
MYSQL_PID=$!

cleanup() {
  echo "[mariadb] stopping (pid $MYSQL_PID)"
  kill "$MYSQL_PID" 2>/dev/null || true
  wait "$MYSQL_PID" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

# Wait for MariaDB to be ready
echo "[mariadb] waiting for socket..."
for i in $(seq 1 60); do
  if mysqladmin --socket="$SOCKET" -uroot ping >/dev/null 2>&1; then
    echo "[mariadb] ready"
    break
  fi
  sleep 1
done

if ! mysqladmin --socket="$SOCKET" -uroot ping >/dev/null 2>&1; then
  echo "[mariadb] FAILED to start, last log lines:"
  tail -n 50 "$LOG_FILE" || true
  exit 1
fi

# Initialize databases on first run
if [ ! -f "$INIT_FLAG" ]; then
  echo "[mariadb] creating databases and importing dumps"
  mysql --socket="$SOCKET" -uroot <<SQL
CREATE DATABASE IF NOT EXISTS naruto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL
  if [ -f "$PROJECT_ROOT/database.sql" ]; then
    echo "[mariadb] importing database.sql -> naruto (errors tolerated)"
    mysql --socket="$SOCKET" -uroot --force naruto < "$PROJECT_ROOT/database.sql" 2>&1 | tail -n 20 || true
  fi
  if [ -f "$PROJECT_ROOT/forum.sql" ]; then
    echo "[mariadb] importing forum.sql -> forum (errors tolerated)"
    mysql --socket="$SOCKET" -uroot --force forum < "$PROJECT_ROOT/forum.sql" 2>&1 | tail -n 20 || true
  fi
  touch "$INIT_FLAG"
  echo "[mariadb] initialization complete"
fi

# Start PHP server in foreground
echo "[php] starting development server on 0.0.0.0:5000"
cd "$PROJECT_ROOT"
exec php -S 0.0.0.0:5000 -t .
