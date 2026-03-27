-- ============================================================================
-- FactorEnergia — Parte 1.2 — sp_GenerateInvoice (SQL Server)
-- ============================================================================
-- Parámetros: @contract_id, @billing_period 'YYYY-MM'
-- Validaciones: contrato existe, activo, sin factura duplicada del periodo.
-- Sin lecturas en el periodo: error controlado (THROW 50004).
-- Devuelve fila insertada vía conjunto de resultados OUTPUT.
-- ============================================================================

CREATE OR ALTER PROCEDURE dbo.sp_GenerateInvoice
    @contract_id INT,
    @billing_period VARCHAR(7)
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        IF NOT EXISTS (SELECT 1 FROM dbo.contracts WHERE id = @contract_id)
        BEGIN
            ;THROW 50001, N'El contrato no existe.', 1;
        END;

        IF NOT EXISTS (
            SELECT 1 FROM dbo.contracts
            WHERE id = @contract_id AND status = N'active'
        )
        BEGIN
            ;THROW 50002, N'El contrato no está activo.', 1;
        END;

        IF EXISTS (
            SELECT 1 FROM dbo.invoices
            WHERE contract_id = @contract_id
              AND billing_period = @billing_period
        )
        BEGIN
            ;THROW 50003, N'Ya existe una factura para este contrato y periodo.', 1;
        END;

        -- Validación explícita del formato YYYY-MM y conversión a primer día de mes.
        DECLARE @period_start DATE = TRY_CONVERT(DATE, @billing_period + N'-01');
        IF @period_start IS NULL OR LEN(@billing_period) <> 7 OR SUBSTRING(@billing_period, 5, 1) <> N'-'
        BEGIN
            ;THROW 50005, N'Formato de periodo inválido. Debe ser YYYY-MM.', 1;
        END;

        DECLARE @period_end DATE = DATEADD(MONTH, 1, @period_start);
        DECLARE @total_kwh DECIMAL(12, 3);

        SELECT @total_kwh = ISNULL(SUM(mr.kwh_consumed), 0)
        FROM dbo.meter_readings AS mr
        WHERE mr.contract_id = @contract_id
          AND mr.reading_date >= @period_start
          AND mr.reading_date < @period_end;

        IF @total_kwh = 0
        BEGIN
            ;THROW 50004, N'No hay lecturas de consumo para el periodo indicado.', 1;
        END;

        DECLARE @price_per_kwh DECIMAL(10, 6);
        DECLARE @fixed_monthly DECIMAL(10, 2);

        SELECT
            @price_per_kwh = t.price_per_kwh,
            @fixed_monthly = t.fixed_monthly
        FROM dbo.contracts AS c
        INNER JOIN dbo.tariffs AS t ON t.id = c.tariff_id
        WHERE c.id = @contract_id;

        DECLARE @total_amount DECIMAL(10, 2) =
            (@total_kwh * @price_per_kwh) + @fixed_monthly;

        INSERT INTO dbo.invoices (
            contract_id,
            billing_period,
            total_kwh,
            total_amount,
            status
        )
        OUTPUT
            inserted.id,
            inserted.contract_id,
            inserted.billing_period,
            inserted.total_kwh,
            inserted.total_amount,
            inserted.status,
            inserted.created_at
        VALUES (
            @contract_id,
            @billing_period,
            @total_kwh,
            @total_amount,
            N'draft'
        );
    END TRY
    BEGIN CATCH
        -- Re-lanza el error original (negocio o sistema) para que el llamador lo gestione.
        THROW;
    END CATCH
END;
GO
