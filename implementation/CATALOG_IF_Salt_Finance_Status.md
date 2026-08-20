# Integration Catalog: IF_Salt_Finance_Status

## 1. Executive Summary
- **Purpose:** Kthen open items A/R të një klienti nga S/4 (OData).
- **Business process:** O2C — Finance A/R (Phase 4). **Technology:** SAP CI/CPI (OData Request-Reply).
- **Source:** ERP `finance_status.php` · **Target:** S/4 `API_OPLACCTGDOCITEMCUBE_SRV`
- **Priority:** P3 · **Status:** Design v1.0.0

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `readFinanceRequest.groovy` | Parse customer, anti-injection |
| E2 | `mapFinanceResponse.groovy` | Agregim open items → JSON |
| E3 | ERP `finance_status.php` | Thirrja + fallback lokal |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_Finance_Status |
| package_name | AlbsaleVloraO2C |
| source_system | Custom ERP |
| target_system | SAP S/4HANA (FI OData) |

## 4–8. Flow
1. HTTPS Sender `GET /salt/finance?customer=..`.
2. `readFinanceRequest.groovy` pastron `customer`.
3. Request-Reply OData `A_OperationalAcctgDocItem` (`IsCleared eq false`, doc type RV).
4. `mapFinanceResponse.groovy` → JSON {customer, openItemCount, totalOpen, currency, items[]}.

## 9. Data Contract & Mapping
| OData | JSON |
|---|---|
| AccountingDocument | accountingDoc |
| BillingDocument | invoiceNo |
| AmountInCompanyCodeCurrency | amount / totalOpen (sum) |
| NetDueDate | dueDate |

## 10. Security
- Sender Client Certificate; OData Security Material; input i sanituar përpara `$filter`.

## 11. Error Handling & Observability
- OData jo-2xx → 502; ERP kthen **fallback lokal** (invoice − payment).

## 15. Open Points
- Emri i shërbimit/entitetit OData të konfirmohet me sistemin real (A/R varion sipas release).
