-- =====================================================================
-- Albsale Vlora — Custom ERP · Phase 5 (APIM + AEM monitoring + Analytics)
-- Tabela e alerteve të integrimit + KPI views lokale (mirror i HANA për dashboard/extract).
-- DB: albsale-vlora (MySQL/MariaDB)
-- =====================================================================
SET NAMES utf8mb4;

-- Alerte të integrimit (nga IF_Salt_Monitoring_Collector)
CREATE TABLE IF NOT EXISTS `integration_alert` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `severity`       VARCHAR(10)  NOT NULL DEFAULT 'WARNING',  -- INFO/WARNING/CRITICAL
  `scenario`       VARCHAR(60)  NULL,
  `correlation_id` VARCHAR(60)  NULL,
  `message_id`     VARCHAR(64)  NULL,
  `error_phrase`   VARCHAR(255) NULL,
  `event_ts`       VARCHAR(40)  NULL,
  `acknowledged`   TINYINT(1)   NOT NULL DEFAULT 0,
  `created`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_alert_sev` (`severity`),
  KEY `ix_alert_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KPI: funnel-i i statuseve
CREATE OR REPLACE VIEW `v_kpi_status_funnel` AS
SELECT order_status AS status, COUNT(*) AS orders, COALESCE(SUM(value),0) AS value
FROM salesorder GROUP BY order_status;

-- KPI: të ardhurat sipas muajit (data e faturës)
CREATE OR REPLACE VIEW `v_kpi_revenue_monthly` AS
SELECT YEAR(invoice_date) AS y, MONTH(invoice_date) AS m,
       COALESCE(SUM(value),0) AS invoiced_value, COUNT(*) AS invoices
FROM salesorder
WHERE invoice_no IS NOT NULL AND invoice_date IS NOT NULL
GROUP BY YEAR(invoice_date), MONTH(invoice_date);

-- KPI: on-time (goods issue brenda 5 ditëve nga porosia)
CREATE OR REPLACE VIEW `v_kpi_ontime` AS
SELECT COUNT(*) AS total_shipped,
       SUM(CASE WHEN DATEDIFF(gi_date, created) <= 5 THEN 1 ELSE 0 END) AS on_time,
       ROUND(100.0 * SUM(CASE WHEN DATEDIFF(gi_date, created) <= 5 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0), 1) AS on_time_pct
FROM salesorder WHERE gi_date IS NOT NULL;

-- KPI: A/R e hapur
CREATE OR REPLACE VIEW `v_kpi_ar` AS
SELECT COALESCE(SUM(CASE WHEN fi_status <> 'CLEARED' AND invoice_no IS NOT NULL THEN value ELSE 0 END),0) AS open_ar,
       COALESCE(SUM(CASE WHEN fi_status = 'CLEARED' THEN paid_amount ELSE 0 END),0) AS collected,
       COUNT(CASE WHEN fi_status <> 'CLEARED' AND invoice_no IS NOT NULL THEN 1 END) AS open_items
FROM salesorder;

-- KPI: top produkte sipas vlerës
CREATE OR REPLACE VIEW `v_kpi_top_products` AS
SELECT saltcode, title, COUNT(*) AS orders, COALESCE(SUM(quantity),0) AS qty, COALESCE(SUM(value),0) AS value
FROM salesorder GROUP BY saltcode, title ORDER BY SUM(value) DESC;
