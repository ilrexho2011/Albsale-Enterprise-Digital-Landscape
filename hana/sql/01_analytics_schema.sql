-- =====================================================================
-- Albsale Vlora — SAP HANA Cloud · Skema analitike O2C (star schema)
-- Ushqehet nga IF_Salt_Analytics_Replicate (JDBC UPDATE_INSERT).
-- Schema: ALBSALE_ANALYTICS
-- =====================================================================
CREATE SCHEMA "ALBSALE_ANALYTICS";
SET SCHEMA "ALBSALE_ANALYTICS";

-- ---------------------- Dimensionet ---------------------------------
CREATE COLUMN TABLE "DIM_CUSTOMER" (
  "ZINN"    NVARCHAR(30) PRIMARY KEY,
  "NAME"    NVARCHAR(80),
  "SEGMENT" NVARCHAR(30),
  "COUNTRY" NVARCHAR(3)
);

CREATE COLUMN TABLE "DIM_PRODUCT" (
  "SALTCODE"  INTEGER PRIMARY KEY,
  "TITLE"     NVARCHAR(50),
  "PRODUCER"  NVARCHAR(50),
  "UNIT"      NVARCHAR(10)
);

CREATE COLUMN TABLE "DIM_DATE" (
  "DATE_ID"   DATE PRIMARY KEY,
  "YEAR"      INTEGER,
  "QUARTER"   INTEGER,
  "MONTH"     INTEGER,
  "MONTH_NAME" NVARCHAR(12),
  "WEEK"      INTEGER,
  "DAY"       INTEGER
);

-- ---------------------- Fakti kryesor O2C ----------------------------
CREATE COLUMN TABLE "FACT_O2C_ORDER" (
  "IDSO"             INTEGER PRIMARY KEY,
  "ZINN"             NVARCHAR(30),
  "SALTCODE"         INTEGER,
  "TITLE"            NVARCHAR(50),
  "QUANTITY"         DECIMAL(18,3),
  "VALUE"            DECIMAL(18,2),
  "CURRENCY"         NVARCHAR(10),
  "ORDER_STATUS"     NVARCHAR(15),
  "WAREHOUSE_STATUS" NVARCHAR(15),
  "FI_STATUS"        NVARCHAR(15),
  "CONFIRMED_QTY"    DECIMAL(18,3),
  "DELIVERY_NO"      NVARCHAR(20),
  "INVOICE_NO"       NVARCHAR(20),
  "GI_DATE"          DATE,
  "INVOICE_DATE"     DATE,
  "PAID_AMOUNT"      DECIMAL(18,2),
  "CREATED"          TIMESTAMP,
  "UPDATED"          TIMESTAMP
);

-- ---------------------- Historia e statuseve (event fact) ------------
CREATE COLUMN TABLE "FACT_STATUS_HISTORY" (
  "ID"             BIGINT PRIMARY KEY,
  "IDSO"           INTEGER,
  "ZINN"           NVARCHAR(30),
  "EVENT_TYPE"     NVARCHAR(20),
  "STATUS"         NVARCHAR(15),
  "DOC_REF"        NVARCHAR(40),
  "CORRELATION_ID" NVARCHAR(60),
  "CREATED"        TIMESTAMP
);

-- Indekse ndihmëse për agregim
CREATE INDEX "IX_FACT_STATUS" ON "FACT_O2C_ORDER" ("ORDER_STATUS");
CREATE INDEX "IX_FACT_CREATED" ON "FACT_O2C_ORDER" ("CREATED");
