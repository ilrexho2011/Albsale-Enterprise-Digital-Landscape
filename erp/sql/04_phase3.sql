-- =====================================================================
-- Albsale Vlora — Custom ERP · Phase 3 (DELVRY03/INVOIC02 të plota)
-- Tabela për dërgesat dhe faturat me artikuj, të mbushura nga receive_event.php v2.
-- DB: albsale-vlora (MySQL/MariaDB)
-- =====================================================================
SET NAMES utf8mb4;

-- ---------------------- Dërgesa (header) -----------------------------
CREATE TABLE IF NOT EXISTS `delivery` (
  `delivery_no`     VARCHAR(20)  NOT NULL,
  `idso`            INT(11)      NULL,
  `zinn`            VARCHAR(30)  NULL,
  `s4_order_id`     VARCHAR(20)  NULL,
  `delivery_date`   DATE         NULL,
  `incoterms`       VARCHAR(10)  NULL,
  `carrier`         VARCHAR(60)  NULL,
  `tracking_no`     VARCHAR(40)  NULL,
  `ship_to`         VARCHAR(30)  NULL,
  `gross_weight`    DECIMAL(18,3) NULL,
  `weight_unit`     VARCHAR(10)  NULL,
  `correlation_id`  VARCHAR(60)  NULL,
  `created`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`delivery_no`),
  KEY `ix_delivery_idso` (`idso`),
  KEY `ix_delivery_zinn` (`zinn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `delivery_item` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `delivery_no`   VARCHAR(20)  NOT NULL,
  `line_no`       VARCHAR(10)  NULL,
  `product_ref`   VARCHAR(30)  NULL,
  `description`   VARCHAR(80)  NULL,
  `delivered_qty` DECIMAL(18,3) NULL,
  `unit`          VARCHAR(10)  NULL,
  `batch`         VARCHAR(20)  NULL,
  PRIMARY KEY (`id`),
  KEY `ix_ditem_delivery` (`delivery_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------- Faturë (header) ------------------------------
CREATE TABLE IF NOT EXISTS `invoice` (
  `invoice_no`     VARCHAR(20)  NOT NULL,
  `idso`           INT(11)      NULL,
  `zinn`           VARCHAR(30)  NULL,
  `s4_order_id`    VARCHAR(20)  NULL,
  `invoice_date`   DATE         NULL,
  `due_date`       DATE         NULL,
  `currency`       VARCHAR(10)  NULL,
  `net_amount`     DECIMAL(18,2) NULL,
  `tax_amount`     DECIMAL(18,2) NULL,
  `gross_amount`   DECIMAL(18,2) NULL,
  `correlation_id` VARCHAR(60)  NULL,
  `created`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`invoice_no`),
  KEY `ix_invoice_idso` (`idso`),
  KEY `ix_invoice_zinn` (`zinn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_item` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `invoice_no`  VARCHAR(20)  NOT NULL,
  `line_no`     VARCHAR(10)  NULL,
  `product_ref` VARCHAR(30)  NULL,
  `description` VARCHAR(80)  NULL,
  `quantity`    DECIMAL(18,3) NULL,
  `unit`        VARCHAR(10)  NULL,
  `net_value`   DECIMAL(18,2) NULL,
  `tax_rate`    DECIMAL(5,2) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_iitem_invoice` (`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fusha ndihmëse në salesorder për datat/vlerat e fundit (denormalizim i lehtë)
ALTER TABLE `salesorder`
  ADD COLUMN IF NOT EXISTS `delivery_date` DATE          NULL,
  ADD COLUMN IF NOT EXISTS `invoice_date`  DATE          NULL,
  ADD COLUMN IF NOT EXISTS `gross_amount`  DECIMAL(18,2) NULL,
  ADD COLUMN IF NOT EXISTS `confirmed_date` DATE         NULL;

-- ---------------------- Log i kontrolleve aATP -----------------------
CREATE TABLE IF NOT EXISTS `atp_check_log` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `saltcode`      INT(11)      NOT NULL,
  `plant`         VARCHAR(10)  NOT NULL DEFAULT '1000',
  `requested_qty` DECIMAL(18,3) NULL,
  `requested_date` DATE        NULL,
  `confirmed_qty` DECIMAL(18,3) NULL,
  `confirmed_date` DATE        NULL,
  `shortfall`     DECIMAL(18,3) NULL,
  `fully_confirmed` TINYINT(1) NULL,
  `checked_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_atp_saltcode` (`saltcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
