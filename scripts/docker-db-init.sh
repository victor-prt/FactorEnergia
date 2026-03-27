#!/usr/bin/env bash
# Inicializa la base `factor`, aplica schema.sql y ejecuta migraciones Yii (desde contenedor web).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

PW="${MSSQL_SA_PASSWORD:-YourStrong!Passw0rd}"

echo "Creando base factor (si no existe)..."
docker compose exec -T db /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$PW" -C -Q "IF DB_ID('factor') IS NULL CREATE DATABASE factor;"

echo "Aplicando schema.sql..."
docker compose cp "$ROOT/schema.sql" db:/tmp/schema.sql
docker compose exec -T db /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$PW" -C -d factor -i /tmp/schema.sql

echo "Ejecutando migraciones Yii..."
docker compose exec -T web bash -c "cd /var/www/html && php yii migrate --interactive=0"

echo "Listo. Opcional: aplicar part1-sql (consultas, SP, índices) con SSMS o sqlcmd."
