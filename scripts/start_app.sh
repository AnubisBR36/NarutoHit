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

# ── Migrations (run every start) ──────────────────────────────────────────────
echo "[mariadb] running migrations"
mysql --socket="$SOCKET" -uroot naruto <<'MIGRATIONS'
-- Deduplicar configuracoes: manter só o registro mais recente por nome
DELETE c1 FROM configuracoes c1
  INNER JOIN configuracoes c2
  ON c1.nome = c2.nome AND c1.id < c2.id;

-- Garantir defaults essenciais (INSERT IGNORE não sobrescreve valores existentes)
INSERT IGNORE INTO configuracoes (nome, valor, descricao) VALUES
  ('cadastro_aberto', '1', 'Permite novos cadastros no site (1=sim, 0=não)'),
  ('pvp_ativo',       '1', 'Permite PVP entre jogadores (1=sim, 0=não)');

-- Adicionar UNIQUE KEY em nome se ainda não existe
SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = 'naruto'
    AND table_name   = 'configuracoes'
    AND index_name   = 'uq_nome'
);
SET @sql = IF(@idx_exists = 0,
  'ALTER TABLE configuracoes ADD UNIQUE KEY uq_nome (nome)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
MIGRATIONS
echo "[mariadb] migrations done"

# Garantir que config/database.php existe (recriado automaticamente se ausente)
DB_CONFIG="$PROJECT_ROOT/config/database.php"
if [ ! -f "$DB_CONFIG" ]; then
  echo "[config] recriando config/database.php"
  cat > "$DB_CONFIG" <<'DBCONF'
<?php
return [
    'mysql' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'dbname'  => 'naruto',
        'charset' => 'utf8mb4',
        'user'    => 'root',
        'pass'    => '',
    ],
    'mysql_forum' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'dbname'  => 'forum',
        'charset' => 'utf8mb4',
        'user'    => 'root',
        'pass'    => '',
    ],
];
DBCONF
  echo "[config] config/database.php criado"
fi

# Start PHP server in foreground
echo "[php] starting development server on 0.0.0.0:5000"
cd "$PROJECT_ROOT"
exec php -S 0.0.0.0:5000 -t .
