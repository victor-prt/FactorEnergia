-- ============================================================================
-- FactorEnergia — Parte 1.1 — Consultas (SQL Server)
-- ============================================================================

-- ---------------------------------------------------------------------------
-- a) Contratos activos: cliente, código de tarifa, kWh totales año en curso
-- ---------------------------------------------------------------------------
-- Enfoque: JOIN contracts → clients y tariffs; agregación LEFT JOIN sobre
-- meter_readings filtrando YEAR(reading_date) = año actual (GETDATE()).
-- LEFT JOIN evita perder contratos sin lecturas en el año (SUM = 0).
SELECT
    c.id AS contract_id,
    cl.full_name AS nombre_cliente,
    t.code AS codigo_tarifa,
    ISNULL(SUM(mr.kwh_consumed), 0) AS total_kwh_anio_actual
FROM dbo.contracts AS c
INNER JOIN dbo.clients AS cl ON cl.id = c.client_id
INNER JOIN dbo.tariffs AS t ON t.id = c.tariff_id
LEFT JOIN dbo.meter_readings AS mr
    ON mr.contract_id = c.id
   AND mr.reading_date >= DATEFROMPARTS(YEAR(GETDATE()), 1, 1)
   AND mr.reading_date < DATEFROMPARTS(YEAR(GETDATE()) + 1, 1, 1)
WHERE c.status = N'active'
GROUP BY
    c.id,
    cl.full_name,
    t.code
ORDER BY total_kwh_anio_actual DESC;


-- ---------------------------------------------------------------------------
-- b) Por país ES/PT: contratos activos y consumo medio mensual (últimos 6 meses)
-- ---------------------------------------------------------------------------
-- Lista fija ES/PT con VALUES; el resto son subconsultas en LEFT JOIN (sin WITH).
-- Consumo medio mensual = kWh entre hace 6 meses y hoy, dividido entre 6.
-- Fechas inline (sin DECLARE): DBeaver suele ejecutar solo el SELECT con Ctrl+Enter
-- y entonces las variables @... no existirían en ese batch.
SELECT
    p.country,
    ISNULL(n.num_contratos_activos, 0) AS contratos_activos,
    ISNULL(k.total_kwh, 0) / 6.0 AS consumo_medio_mensual_kwh
FROM (VALUES (N'ES'), (N'PT')) AS p(country)
LEFT JOIN (
    SELECT
        cl.country,
        COUNT(*) AS num_contratos_activos
    FROM dbo.contracts AS c
    INNER JOIN dbo.clients AS cl ON cl.id = c.client_id
    WHERE c.status = N'active'
      AND cl.country IN (N'ES', N'PT')
    GROUP BY cl.country
) AS n ON n.country = p.country
LEFT JOIN (
    SELECT
        cl.country,
        SUM(mr.kwh_consumed) AS total_kwh
    FROM dbo.contracts AS c
    INNER JOIN dbo.clients AS cl ON cl.id = c.client_id
    INNER JOIN dbo.meter_readings AS mr
        ON mr.contract_id = c.id
       AND mr.reading_date >= DATEADD(MONTH, -6, CAST(GETDATE() AS DATE))
       AND mr.reading_date <= CAST(GETDATE() AS DATE)
    WHERE c.status = N'active'
      AND cl.country IN (N'ES', N'PT')
    GROUP BY cl.country
) AS k ON k.country = p.country;


-- ---------------------------------------------------------------------------
-- c) Clientes con al menos un contrato y sin ninguna factura (nunca)
-- ---------------------------------------------------------------------------
-- Enfoque: EXISTS sobre contratos; NOT EXISTS sobre facturas enlazadas a
-- cualquier contrato del mismo cliente (evita falsos positivos por LEFT JOIN).
SELECT
    cl.full_name AS nombre_cliente,
    cl.fiscal_id,
    COUNT(DISTINCT c.id) AS num_contratos
FROM dbo.clients AS cl
INNER JOIN dbo.contracts AS c ON c.client_id = cl.id
WHERE NOT EXISTS (
    SELECT 1
    FROM dbo.invoices AS i
    INNER JOIN dbo.contracts AS cx ON cx.id = i.contract_id
    WHERE cx.client_id = cl.id
)
GROUP BY
    cl.id,
    cl.full_name,
    cl.fiscal_id;
