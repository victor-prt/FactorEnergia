-- ============================================================================
-- FactorEnergia — Datos de demostración (SQL Server)
-- Borra e inserta un conjunto pequeño identificable (DEMO_* / DEMOCUPS*)
-- para probar 01_consultas_1.1.sql y sp_GenerateInvoice.
-- Idempotente: puedes ejecutarlo varias veces.
-- ============================================================================

SET NOCOUNT ON;

-- Quitar demo anterior (orden respetando FKs)
DELETE i
FROM dbo.invoices AS i
INNER JOIN dbo.contracts AS c ON c.id = i.contract_id
WHERE c.cups LIKE N'DEMOCUPS%';

DELETE mr
FROM dbo.meter_readings AS mr
INNER JOIN dbo.contracts AS c ON c.id = mr.contract_id
WHERE c.cups LIKE N'DEMOCUPS%';

IF OBJECT_ID(N'dbo.erse_contract_sync', N'U') IS NOT NULL
BEGIN
    DELETE s
    FROM dbo.erse_contract_sync AS s
    INNER JOIN dbo.contracts AS c ON c.id = s.contract_id
    WHERE c.cups LIKE N'DEMOCUPS%';
END;

DELETE FROM dbo.contracts WHERE cups LIKE N'DEMOCUPS%';

DELETE FROM dbo.tariffs WHERE code IN (N'DEMO_TAR_ES', N'DEMO_TAR_PT');

DELETE FROM dbo.clients
WHERE fiscal_id IN (N'DEMO-ES-001', N'DEMO-PT-001', N'DEMO-ES-002');

-- Clientes: ES, PT y otro ES sin facturas (consulta 1.1c)
-- Solo columnas de schema.sql base; si existen street/city/postal_code (migración), opcional:
INSERT INTO dbo.clients (fiscal_id, full_name, email, country)
VALUES
    (N'DEMO-ES-001', N'María García Demo', N'maria.demo@example.com', N'ES'),
    (N'DEMO-PT-001', N'João Silva Demo', N'joao.demo@example.com', N'PT'),
    (N'DEMO-ES-002', N'Ana López Demo (sin facturas)', N'ana.demo@example.com', N'ES');

IF COL_LENGTH('dbo.clients', 'street') IS NOT NULL
BEGIN
    UPDATE dbo.clients SET street = N'Calle Demo 1', city = N'Madrid', postal_code = N'28001' WHERE fiscal_id = N'DEMO-ES-001';
    UPDATE dbo.clients SET street = N'Rua Demo 2', city = N'Porto', postal_code = N'4000-001' WHERE fiscal_id = N'DEMO-PT-001';
    UPDATE dbo.clients SET street = N'Avenida Demo 3', city = N'Valencia', postal_code = N'46001' WHERE fiscal_id = N'DEMO-ES-002';
END;

DECLARE
    @client_es1 INT = (SELECT id FROM dbo.clients WHERE fiscal_id = N'DEMO-ES-001'),
    @client_pt1 INT = (SELECT id FROM dbo.clients WHERE fiscal_id = N'DEMO-PT-001'),
    @client_es2 INT = (SELECT id FROM dbo.clients WHERE fiscal_id = N'DEMO-ES-002');

INSERT INTO dbo.tariffs (code, description, price_per_kwh, fixed_monthly, country, active)
VALUES
    (N'DEMO_TAR_ES', N'Tarifa demo España', 0.150000, 8.50, N'ES', 1),
    (N'DEMO_TAR_PT', N'Tarifa demo Portugal', 0.140000, 7.00, N'PT', 1);

DECLARE
    @tar_es INT = (SELECT id FROM dbo.tariffs WHERE code = N'DEMO_TAR_ES'),
    @tar_pt INT = (SELECT id FROM dbo.tariffs WHERE code = N'DEMO_TAR_PT');

-- Contratos activos (uno por cliente demo)
INSERT INTO dbo.contracts (client_id, tariff_id, cups, start_date, end_date, status)
VALUES
    (@client_es1, @tar_es, N'DEMOCUPSES0000001', DATEADD(YEAR, -1, CAST(GETDATE() AS DATE)), NULL, N'active'),
    (@client_pt1, @tar_pt, N'DEMOCUPSPT0000001', DATEADD(YEAR, -1, CAST(GETDATE() AS DATE)), NULL, N'active'),
    (@client_es2, @tar_es, N'DEMOCUPSES0000002', DATEADD(MONTH, -3, CAST(GETDATE() AS DATE)), NULL, N'active');

DECLARE
    @ct_es1 INT = (SELECT id FROM dbo.contracts WHERE cups = N'DEMOCUPSES0000001'),
    @ct_pt1 INT = (SELECT id FROM dbo.contracts WHERE cups = N'DEMOCUPSPT0000001'),
    @ct_es2 INT = (SELECT id FROM dbo.contracts WHERE cups = N'DEMOCUPSES0000002');

-- Lecturas: año en curso + ventana últimos 6 meses (1.1a y 1.1b)
DECLARE
    @y INT = YEAR(GETDATE()),
    @d0 DATE = CAST(GETDATE() AS DATE),
    @m6 DATE = DATEADD(MONTH, -6, CAST(GETDATE() AS DATE));

INSERT INTO dbo.meter_readings (contract_id, reading_date, kwh_consumed, source)
VALUES
    -- ES1: consumo notable en año actual
    (@ct_es1, DATEFROMPARTS(@y, 1, 12),  120.500, N'smart_meter'),
    (@ct_es1, DATEFROMPARTS(@y, 2, 8),   95.250,  N'manual'),
    (@ct_es1, DATEFROMPARTS(@y, 3, 5),   140.000, N'smart_meter'),
    (@ct_es1, DATEADD(DAY, -20, @d0),    60.000,  N'estimate'),
    (@ct_es1, DATEADD(MONTH, -2, @d0),  88.000,  N'smart_meter'),
    (@ct_es1, @m6,                      45.000,  N'manual'),

    -- PT1
    (@ct_pt1, DATEFROMPARTS(@y, 1, 20),  200.000, N'smart_meter'),
    (@ct_pt1, DATEFROMPARTS(@y, 2, 15),  180.500, N'smart_meter'),
    (@ct_pt1, DATEADD(DAY, -10, @d0),    70.000,  N'manual'),
    (@ct_pt1, DATEADD(MONTH, -4, @d0),  110.000,  N'smart_meter'),
    (@ct_pt1, DATEADD(MONTH, -5, @d0),  90.000,   N'estimate'),

    -- ES2 (sin facturas): lecturas para ver totales en 1.1a/b
    (@ct_es2, DATEFROMPARTS(@y, 2, 1),   55.000,  N'manual'),
    (@ct_es2, DATEFROMPARTS(@y, 3, 10),  62.500,  N'smart_meter'),
    (@ct_es2, DATEADD(MONTH, -1, @d0),  40.000,  N'smart_meter');

-- Facturas solo para DEMO-ES-001 y DEMO-PT-001 → DEMO-ES-002 aparece en 1.1c
INSERT INTO dbo.invoices (contract_id, billing_period, total_kwh, total_amount, status, issued_at)
VALUES
    (@ct_es1, FORMAT(DATEADD(MONTH, -2, @d0), N'yyyy-MM'), 300.000, 55.90, N'issued', DATEADD(DAY, -40, GETDATE())),
    (@ct_pt1, FORMAT(DATEADD(MONTH, -1, @d0), N'yyyy-MM'), 250.000, 49.50, N'paid',   DATEADD(DAY, -15, GETDATE()));

PRINT N'Seed demo aplicado: 3 clientes, 2 tarifas, 3 contratos, lecturas y 2 facturas (Ana López sin facturas).';
