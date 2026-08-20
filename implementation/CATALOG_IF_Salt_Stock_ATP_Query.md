# Integration Catalog: IF_Salt_Stock_ATP_Query

## 1. Executive Summary
- **Purpose:** Kthen disponueshmërinë (stock/ATP) të një materiali nga S/4 përmes OData.
- **Business process:** O2C — Phase 2 (availability check).
- **Source:** Custom ERP `check_stock.php` · **Target:** S/4HANA OData `API_MATERIAL_STOCK_SRV`
- **Technology:** SAP CI/CPI (Request-Reply, OData) · **Priority:** P3 · **Status:** Design v1.0.0

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `readStockRequest.groovy` | Lexon material/plant, pastrim anti-injection |
| E2 | `mapStockResponse.groovy` | OData → JSON kanonik |
| E3 | ERP `check_stock.php`, `stock_cache` | Cache TTL + fallback |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_Stock_ATP_Query |
| package_name | AlbsaleVloraO2C |
| source_system | Custom ERP |
| target_system | SAP S/4HANA (OData Gateway) |

## 4. Business Context
Klienti/agjenti sheh stokun real përpara porosisë. Redukton porositë e pamundura dhe
mospërputhjet e stokut mes ERP-së dhe S/4.

## 5–8. Flow & Processing
1. HTTPS Sender `GET /salt/stock?material=..&plant=..`.
2. `readStockRequest.groovy` nxjerr & pastron `material`/`plant` (anti OData-injection).
3. Request-Reply → OData `A_MatlStkInAcctMod` me `$filter=Material eq '..' and Plant eq '..'`.
4. `mapStockResponse.groovy` → JSON kanonik `{material, plant, availableQuantity, unit, atpQuantity, source}`.

## 9. Data Contract & Mapping
| Source (OData) | Target (JSON) | Rule |
|---|---|---|
| Material | material | direkt |
| Plant | plant | direkt |
| MatlWrhsStkQtyInMatlBaseUnit | availableQuantity / atpQuantity | numerik |
| MaterialBaseUnit | unit | direkt |

## 10. Security
- Sender: HTTPS Client Certificate.
- Receiver OData: Security Material `{{s4_odata_credential}}`.
- Input sanitohet në Groovy (whitelist `[A-Za-z0-9_-]`) përpara `$filter`.

## 11. Error Handling & Observability
- OData jo-2xx → HTTP 502; ERP kthen cache `stale:true` nëse ekziston.
- MPL: material/plant si custom properties.

## 13. Dependencies
S/4 Gateway service `API_MATERIAL_STOCK_SRV` i publikuar/aktivizuar.

## 15. Open Points
- ATP i vërtetë (aATP) kërkon OData tjetër; kjo v1 kthen stokun bazë si ATP të thjeshtuar.
- `plant` default `1000` — konfirmo plant-in e magazinës së kripës.
