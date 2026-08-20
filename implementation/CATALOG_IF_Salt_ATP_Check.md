# Integration Catalog: IF_Salt_ATP_Check

## 1. Executive Summary
- **Purpose:** Kontroll aATP i vërtetë — kthen sasi/datë të konfirmuar dhe mungesë (shortfall).
- **Business process:** O2C — availability check (Phase 3). **Technology:** SAP CI/CPI (OData Request-Reply).
- **Source:** ERP `check_atp.php` · **Target:** S/4HANA `API_AVAILABILITY_INFORMATION_SRV`
- **Priority:** P2 · **Status:** Design v1.0.0

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `buildAtpRequest.groovy` | Parse JSON, anti-injection, query options |
| E2 | `mapAtpResponse.groovy` | Konfirmim kumulativ sipas datës |
| E3 | ERP `check_atp.php`, `atp_check_log` | Thirrja + logu |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_ATP_Check |
| package_name | AlbsaleVloraO2C |
| source_system | Custom ERP |
| target_system | SAP S/4HANA (aATP OData) |

## 4. Business Context
Përpara/gjatë porosisë, jep një premtim realist: sa sasi mund të konfirmohet dhe kur.
Ndryshe nga Phase 2 (lexim stoku), llogarit konfirmim **kumulativ sipas datave** të disponueshmërisë.

## 5–8. Flow & Processing
1. HTTPS Sender `POST /salt/atp` {material, plant, quantity, date}.
2. `buildAtpRequest.groovy` pastron input-in, vendos `p_material/p_plant/p_date/p_reqQty`.
3. Request-Reply → OData `AvailabilityInformation` (`$filter` material/plant/date, `$orderby` date asc).
4. `mapAtpResponse.groovy`: akumulon `AvailableQuantity` sipas datës → `confirmedQuantity=min(kërkesa,total)`,
   `confirmedDate` = data kur akumulohet mjafueshëm, `shortfall`, `fullyConfirmed`.

## 9. Data Contract & Mapping
| Request (JSON) | → | Response (JSON) |
|---|---|---|
| material, plant, quantity, date | | material, plant, requestedQuantity/Date, availableQuantity, confirmedQuantity, confirmedDate, shortfall, fullyConfirmed |

## 10. Security
- Sender Client Certificate; OData me Security Material `{{s4_odata_credential}}`.
- Input sanitohet (whitelist) përpara `$filter` (anti OData-injection).

## 11. Error Handling & Observability
- OData jo-2xx → HTTP 502; ERP logon te `atp_check_log`.
- Idempotent (read-only); pa efekte anësore në S/4.

## 13. Dependencies
S/4 Gateway `API_AVAILABILITY_INFORMATION_SRV` i publikuar.

## 15. Open Points
- Për **rezervim** (jo vetëm lexim) përdor aATP BOP/action (POST) — v2 e ardhshme.
- Emri i entitetit/fushave (`AvailabilityInformation`, `AvailableQuantity`) të konfirmohet me shërbimin real.
