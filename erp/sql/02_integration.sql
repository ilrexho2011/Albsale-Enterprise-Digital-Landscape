-- =====================================================================
-- Albsale Vlora — Salt ERP  ·  Integrimi O2C me SAP CI / S4H (ZRC_IR OrderFlow)
-- Zgjeron `salesorder` me fusha statusi + shton audit dhe outbox.
-- DB: albsale-vlora (MariaDB)
-- =====================================================================

-- 1) Fusha të reja në salesorder për gjurmimin e ciklit O2C
ALTER TABLE `salesorder`
  ADD COLUMN IF NOT EXISTS `s4_order_id`   VARCHAR(10)  NULL AFTER `idso`,
  ADD COLUMN IF NOT EXISTS `correlation_id` VARCHAR(60) NULL,
  ADD COLUMN IF NOT EXISTS `order_status`  VARCHAR(15)  NOT NULL DEFAULT 'NEW',
      -- NEW -> SENT -> CONFIRMED -> DELIVERED -> INVOICED / REJECTED
  ADD COLUMN IF NOT EXISTS `confirmed_qty` INT          NULL,
  ADD COLUMN IF NOT EXISTS `delivery_no`   VARCHAR(20)  NULL,
  ADD COLUMN IF NOT EXISTS `invoice_no`    VARCHAR(20)  NULL,
  ADD COLUMN IF NOT EXISTS `last_event`    VARCHAR(20)  NULL,
  ADD COLUMN IF NOT EXISTS `updated`       TIMESTAMP    NOT NULL
        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 2) Histori statusesh (një rresht për çdo dokument O2C të marrë nga CI)
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id`             INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `idso`           INT(11)      NULL,
  `s4_order_id`    VARCHAR(10)  NULL,
  `zinn`           VARCHAR(30)  NULL,
  `event_type`     VARCHAR(20)  NOT NULL,   -- ORDRSP / DESADV / INVOIC
  `status`         VARCHAR(15)  NOT NULL,   -- CONFIRMED / DELIVERED / INVOICED / REJECTED
  `doc_ref`        VARCHAR(40)  NULL,       -- delivery_no / invoice_no / etj.
  `message`        VARCHAR(255) NULL,
  `correlation_id` VARCHAR(60)  NULL,
  `created`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `ix_hist_zinn` (`zinn`),
  KEY `ix_hist_idso` (`idso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 3) Outbox për dërgim "at-least-once" të porosive drejt CI
CREATE TABLE IF NOT EXISTS `integration_outbox` (
  `id`             INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `idso`           INT(11)      NOT NULL,
  `zinn`           VARCHAR(30)  NOT NULL,
  `doc_type`       VARCHAR(20)  NOT NULL DEFAULT 'ORDERS',
  `correlation_id` VARCHAR(60)  NOT NULL,
  `payload`        MEDIUMTEXT   NOT NULL,   -- XML kanonik i porosisë
  `status`         VARCHAR(15)  NOT NULL DEFAULT 'PENDING', -- PENDING/SENT/FAILED
  `attempts`       INT(11)      NOT NULL DEFAULT 0,
  `last_error`     VARCHAR(255) NULL,
  `created`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_outbox_corr` (`correlation_id`),
  KEY `ix_outbox_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 4) View: pamja e klientit (My Orders) me statusin e fundit
CREATE OR REPLACE VIEW `v_customer_orders` AS
SELECT s.`idso`, s.`s4_order_id`, s.`ZINN`, s.`saltcode`, s.`title`,
       s.`quantity`, s.`unit`, s.`value`, s.`currency`,
       s.`order_status`, s.`confirmed_qty`, s.`delivery_no`, s.`invoice_no`,
       s.`created`, s.`updated`
FROM `salesorder` s;
