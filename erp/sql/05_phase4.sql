-- =====================================================================
-- Albsale Vlora — Custom ERP · Phase 4 (Warehouse/EWM + Finance/FI)
-- Tabela për ngjarjet e magazinës (goods issue, HU, tasks) dhe financën (FI + pagesa).
-- DB: albsale-vlora (MySQL/MariaDB)
-- =====================================================================
SET NAMES utf8mb4;

-- ------------------------- WAREHOUSE / EWM ---------------------------
CREATE TABLE IF NOT EXISTS `warehouse_event` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `event_type`     VARCHAR(15)  NOT NULL,          -- PICKED / PACKED / GOODS_ISSUED
  `delivery_no`    VARCHAR(20)  NULL,
  `idso`           INT(11)      NULL,
  `zinn`           VARCHAR(30)  NULL,
  `s4_order_id`    VARCHAR(20)  NULL,
  `warehouse`      VARCHAR(10)  NULL,
  `movement_type`  VARCHAR(10)  NULL,
  `gi_date`        DATE         NULL,
  `total_qty`      DECIMAL(18,3) NULL,
  `unit`           VARCHAR(10)  NULL,
  `correlation_id` VARCHAR(60)  NULL,
  `created`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_whe_delivery` (`delivery_no`),
  KEY `ix_whe_idso` (`idso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `handling_unit` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `delivery_no`   VARCHAR(20)  NULL,
  `hu_id`         VARCHAR(30)  NOT NULL,
  `pack_material` VARCHAR(30)  NULL,
  `gross_weight`  DECIMAL(18,3) NULL,
  `weight_unit`   VARCHAR(10)  NULL,
  `tracking_no`   VARCHAR(40)  NULL,
  PRIMARY KEY (`id`),
  KEY `ix_hu_delivery` (`delivery_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `warehouse_task` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `delivery_no`  VARCHAR(20)  NULL,
  `task_id`      VARCHAR(20)  NULL,
  `product_ref`  VARCHAR(30)  NULL,
  `picked_qty`   DECIMAL(18,3) NULL,
  `unit`         VARCHAR(10)  NULL,
  `source_bin`   VARCHAR(20)  NULL,
  `dest_bin`     VARCHAR(20)  NULL,
  `status`       VARCHAR(15)  NULL,
  PRIMARY KEY (`id`),
  KEY `ix_wt_delivery` (`delivery_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- FINANCE / FI ------------------------------
CREATE TABLE IF NOT EXISTS `finance_document` (
  `accounting_doc` VARCHAR(20)  NOT NULL,
  `company_code`   VARCHAR(8)   NOT NULL DEFAULT '1000',
  `fiscal_year`    VARCHAR(4)   NOT NULL DEFAULT '',
  `idso`           INT(11)      NULL,
  `zinn`           VARCHAR(30)  NULL,
  `invoice_no`     VARCHAR(20)  NULL,
  `s4_order_id`    VARCHAR(20)  NULL,
  `posting_date`   DATE         NULL,
  `document_type`  VARCHAR(4)   NULL,
  `amount`         DECIMAL(18,2) NULL,
  `currency`       VARCHAR(10)  NULL,
  `fi_status`      VARCHAR(15)  NOT NULL DEFAULT 'POSTED',  -- POSTED / CLEARED
  `correlation_id` VARCHAR(60)  NULL,
  `created`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`accounting_doc`, `company_code`, `fiscal_year`),
  KEY `ix_fd_idso` (`idso`),
  KEY `ix_fd_invoice` (`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `payment_ref`    VARCHAR(30)  NULL,
  `clearing_doc`   VARCHAR(20)  NULL,
  `idso`           INT(11)      NULL,
  `zinn`           VARCHAR(30)  NULL,
  `invoice_no`     VARCHAR(20)  NULL,
  `payment_date`   DATE         NULL,
  `amount`         DECIMAL(18,2) NULL,
  `currency`       VARCHAR(10)  NULL,
  `clearing_status` VARCHAR(10) NULL,   -- OPEN / CLEARED
  `correlation_id` VARCHAR(60)  NULL,
  `created`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_pay_invoice` (`invoice_no`),
  KEY `ix_pay_idso` (`idso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------- salesorder denormalizim -------------------
ALTER TABLE `salesorder`
  ADD COLUMN IF NOT EXISTS `warehouse_status` VARCHAR(15) NULL,   -- PICKED/PACKED/GOODS_ISSUED
  ADD COLUMN IF NOT EXISTS `gi_date`          DATE        NULL,
  ADD COLUMN IF NOT EXISTS `fi_doc`           VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS `fi_status`        VARCHAR(15) NULL,   -- POSTED/CLEARED
  ADD COLUMN IF NOT EXISTS `paid_date`        DATE        NULL,
  ADD COLUMN IF NOT EXISTS `paid_amount`      DECIMAL(18,2) NULL;
