-- ============================================================================
-- FactorEnergia — Parte 1.3 — Índices sugeridos (SQL Server)
-- Ejecutar tras valorar carga real; nombres ilustrativos.
-- ============================================================================

-- 1) meter_readings: agregaciones por contrato + rango de fechas (1.1a, 1.1b, SP)
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_meter_readings_contract_date')
CREATE NONCLUSTERED INDEX IX_meter_readings_contract_date
ON dbo.meter_readings (contract_id, reading_date)
INCLUDE (kwh_consumed);

-- 2) contracts: filtro por status + JOIN a clients/tariffs (consultas activos)
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_contracts_status_client')
CREATE NONCLUSTERED INDEX IX_contracts_status_client
ON dbo.contracts (status, client_id)
INCLUDE (tariff_id, cups, start_date, end_date);

-- 3) invoices: evitar duplicados por periodo y búsquedas del SP
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'UQ_invoices_contract_period')
CREATE UNIQUE NONCLUSTERED INDEX UQ_invoices_contract_period
ON dbo.invoices (contract_id, billing_period);
