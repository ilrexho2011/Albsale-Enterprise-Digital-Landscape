-- =====================================================================
-- Albsale Vlora — Custom ERP · Phase 2 (reliable messaging + ATP/stock)
-- Zgjeron integration_outbox me gjendje dispatch + backoff, dhe shton stock_cache.
-- DB: albsale-vlora (MySQL/MariaDB)
-- =====================================================================
SET NAMES utf8mb4;

-- 1) Fusha të reja në outbox për dispatcher-in asinkron me retry
ALTER TABLE `integration_outbox`
  ADD COLUMN IF NOT EXISTS `max_attempts`    INT(11)     NOT NULL DEFAULT 6 AFTER `attempts`,
  ADD COLUMN IF NOT EXISTS `next_attempt_at` TIMESTAMP   NULL     DEFAULT NULL AFTER `max_attempts`,
  ADD COLUMN IF NOT EXISTS `locked_at`       TIMESTAMP   NULL     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `updated`         TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
-- status vlerat: PENDING -> QUEUED/SENT | FAILED | DEAD (pas max_attempts)

CREATE INDEX IF NOT EXISTS `ix_outbox_dispatch` ON `integration_outbox` (`status`, `next_attempt_at`);

-- 2) Cache i disponueshmërisë (ATP/stock) nga S/4 përmes CI (OData)
CREATE TABLE IF NOT EXISTS `stock_cache` (
  `saltcode`      INT(11)      NOT NULL,
  `plant`         VARCHAR(10)  NOT NULL DEFAULT '1000',
  `available_qty` DECIMAL(18,3) NOT NULL DEFAULT 0,
  `atp_qty`       DECIMAL(18,3) NOT NULL DEFAULT 0,
  `unit`          VARCHAR(10)  NULL,
  `source`        VARCHAR(60)  NULL,
  `checked_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`saltcode`, `plant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
