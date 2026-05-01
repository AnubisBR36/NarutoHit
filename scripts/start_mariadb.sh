#!/bin/bash
set -e

DATA_DIR="$HOME/.mariadb_data"
SOCKET="$DATA_DIR/mysql.sock"
PID_FILE="$DATA_DIR/mysqld.pid"
LOG_FILE="$DATA_DIR/mysqld.log"
PORT=3306

if [ -S "$SOCKET" ] && mysqladmin --socket="$SOCKET" -uroot ping >/dev/null 2>&1; then
  echo "[mariadb] already running"
  exit 0
fi

if [ ! -d "$DATA_DIR/mysql" ]; then
  echo "[mariadb] initializing data directory at $DATA_DIR"
  mkdir -p "$DATA_DIR"
  mariadb-install-db --auth-root-authentication-method=normal --datadir="$DATA_DIR" --user="$(whoami)" >/dev/null
fi

echo "[mariadb] starting server on port $PORT"
setsid mysqld \
  --datadir="$DATA_DIR" \
  --socket="$SOCKET" \
  --pid-file="$PID_FILE" \
  --port="$PORT" \
  --bind-address=127.0.0.1 \
  --skip-networking=0 \
  --log-error="$LOG_FILE" \
  </dev/null >>"$LOG_FILE" 2>&1 &

for i in $(seq 1 30); do
  if mysqladmin --socket="$SOCKET" -uroot ping >/dev/null 2>&1; then
    echo "[mariadb] server is up (socket)"
    break
  fi
  sleep 1
done

if ! mysqladmin --socket="$SOCKET" -uroot ping >/dev/null 2>&1; then
  echo "[mariadb] failed to start, see $LOG_FILE"
  tail -n 50 "$LOG_FILE" || true
  exit 1
fi

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
INIT_FLAG="$DATA_DIR/.anubis_initialized"

if [ ! -f "$INIT_FLAG" ]; then
  echo "[mariadb] creating databases and importing dumps"
  mysql --socket="$SOCKET" -uroot <<SQL
CREATE DATABASE IF NOT EXISTS Anubis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS forum_anubis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL

  if [ -f "$PROJECT_ROOT/database.sql" ]; then
    mysql --socket="$SOCKET" -uroot Anubis < "$PROJECT_ROOT/database.sql"
    echo "[mariadb] imported database.sql -> Anubis"
  fi
  if [ -f "$PROJECT_ROOT/forum.sql" ]; then
    mysql --socket="$SOCKET" -uroot forum_anubis < "$PROJECT_ROOT/forum.sql"
    echo "[mariadb] imported forum.sql -> forum_anubis"
  fi
  touch "$INIT_FLAG"
fi

echo "[mariadb] ready"
