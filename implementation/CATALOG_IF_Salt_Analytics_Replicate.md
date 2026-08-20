# Integration Catalog: IF_Salt_Analytics_Replicate

## 1. Executive Summary
- **Purpose:** Replikon delta-n O2C nga ERP në SAP HANA Cloud për analitikë/KPI.
- **Business process:** Analytics (Phase 5). **Technology:** SAP CI/CPI (Timer + JDBC).
- **Source:** ERP `extract.php` · **Target:** HANA Cloud `FACT_O2C_ORDER` (+dim) · **Status:** v1.0.0

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `mapToHanaUpsert.groovy` | JSON → JDBC batch UPDATE_INSERT |
| E2 | `hana/sql/01_*`, `02_*` | Star schema + KPI views |
| E3 | ERP `extract.php` | Delta O2C (token) |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_Analytics_Replicate |
| package_name | AlbsaleVloraO2C |
| target_system | SAP HANA Cloud (JDBC) |

## 4–8. Flow
1. **Timer** (çdo 15 min) → Content Modifier vendos watermark `since`.
2. Request-Reply → ERP `extract.php?since=..` (token X-Inbound-Token).
3. `mapToHanaUpsert.groovy` → XML batch JDBC (UPDATE_INSERT për FACT_O2C_ORDER + DIM_CUSTOMER).
4. JDBC Receiver upsert në HANA Cloud (idempotent me çelës IDSO).

## 9. Data Contract & Mapping
| ERP JSON (orders[]) | HANA FACT_O2C_ORDER |
|---|---|
| idso (key) | IDSO |
| zinn, saltcode, title, quantity, value, currency | ZINN, SALTCODE, TITLE, QUANTITY, VALUE, CURRENCY |
| order_status, warehouse_status, fi_status | ORDER_STATUS, WAREHOUSE_STATUS, FI_STATUS |
| delivery_no, invoice_no, gi_date, invoice_date, paid_amount | DELIVERY_NO, INVOICE_NO, GI_DATE, INVOICE_DATE, PAID_AMOUNT |

## 10. Security
- ERP me token; HANA me JDBC Data Source alias (kredencial në CI, jo hardkoduar).

## 11. Observability
- Idempotent (UPDATE_INSERT); rerun i sigurt. KPI views ushqejnë dashboard-in.

## 15. Open Points
- Watermark real `lastRun` (Data Store / Write Variables) për delta të saktë.
- DIM_DATE population (kalendar) me një job fillestar.
