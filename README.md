# Prueba técnica FactorEnergia 2026

**Framework:** Yii2  
**Idioma de la entrega:** español (documentación y explicaciones).

## Estructura

| Ruta | Contenido |
|------|-----------|
| [part1-sql/](part1-sql/) | Parte 1: consultas 1.1, `sp_GenerateInvoice`, índices y explicaciones |
| [docs/](docs/) | Partes 2–4: revisión, refactor, **Parte 3.1** ([parte3-3.1-respuesta.md](docs/parte3-3.1-respuesta.md)), **Parte 3.2** ([parte3-3.2-respuesta.md](docs/parte3-3.2-respuesta.md)), preguntas 3.4 y batch/escalado |
| [yii2-app/](yii2-app/) | Aplicación Yii2 (Parte 3 API ERSE + refactor Parte 2.2) |
| [schema.sql](schema.sql) | Esquema base SQL Server del enunciado |
| [docker-compose.yml](docker-compose.yml) | SQL Server + PHP/Apache con extensiones `pdo_sqlsrv` |

## Requisitos locales (sin Docker)

- PHP 8+ con extensiones `pdo_sqlsrv` / `sqlsrv`
- Composer
- SQL Server accesible por red

```bash
cd yii2-app
composer install
# En la raíz del repo: cp .env.example .env y exportar DB_* / ERSE_* si aplica
# O editar yii2-app/config/db.php
php yii migrate --interactive=0
php -S localhost:8080 -t web
```

## Docker (recomendado para evaluadores)

> La imagen de SQL Server es **linux/amd64**. En Mac Apple Silicon puede requerir emulación (más lenta) o usar un host x86/WSL2.

1. Copia variables de ejemplo:

   ```bash
   cp .env.example .env
   ```

2. Arranca los contenedores:

   ```bash
   docker compose up -d --build
   ```

3. Inicializa la base y migraciones:

   ```bash
   chmod +x scripts/docker-db-init.sh
   ./scripts/docker-db-init.sh
   ```

4. Abre la aplicación: [http://localhost:8080](http://localhost:8080)

### Sincronización ERSE (Parte 3)

Define un token (aunque sea de prueba) para evitar error de configuración:

```bash
export ERSE_BEARER_TOKEN=tu_token_de_prueba
docker compose up -d
```

Ejemplo de petición (contrato existente con cliente `country = PT`):

```bash
curl -s -X POST http://localhost:8080/api/contracts/sync \
  -H "Content-Type: application/json" \
  -d '{"contract_id": 1}'
```

Si no existe `mod_rewrite` o fallan URLs amigables, usa:

`http://localhost:8080/index.php?r=api/sync`

### Parte 1 en SQL Server

Tras crear la base, puedes ejecutar los scripts de [part1-sql/](part1-sql/) con `sqlcmd`, Azure Data Studio o SSMS (objetos `dbo`).

## Entrega

- Código y SQL como en este repositorio.  
- Explicaciones: [part1-sql/EXPLICACIONES_PARTE1.md](part1-sql/EXPLICACIONES_PARTE1.md) y archivos en [docs/](docs/).  
- Clase original de referencia del enunciado: [InvoiceCalculator.php](InvoiceCalculator.php) (no usada en runtime; el refactor está en `yii2-app/services/`).

## Licencia del esqueleto

Código de aplicación entregado como parte de la prueba técnica; Yii2 y dependencias conservan sus licencias originales.
