# Integration Catalog: IF_Salt_EWM_Event_In

## 1. Executive Summary
- **Purpose:** Merr ngjarjet e magazinës (picking/packing/goods issue) nga S/4 EWM dhe përditëson ERP.
- **Business process:** O2C — Warehouse (Phase 4). **Technology:** SAP CI/CPI. **Priority:** P2. **Status:** v1.0.0.
- **Source:** SAP S/4 EWM (IDoc SHPCON/WHSCON) · **Target:** ERP `receive_warehouse.php`

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `SHPCON_to_Warehouse.xsl` | Mapping goods issue + HU + tasks |
| E2 | `WarehouseEvent.xsd` | Kontrata |
| E3 | ERP `receive_warehouse.php`, tabelat warehouse_* | Persistim |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_EWM_Event_In |
| package_name | AlbsaleVloraO2C |
| source_system | SAP S/4HANA EWM |
| target_system | Custom ERP |

## 4–8. Flow
1. IDoc Sender `/salt/ewm` pranon SHPCON/WHSCON.
2. XSLT → WarehouseEvent (EventType GOODS_ISSUED nëse ka WADAT_IST, ndryshe PICKED).
3. Content Modifier token + Groovy `X-Inbound-Token` + MPL.
4. HTTP Receiver → `receive_warehouse.php` (transaksion: header + HU + tasks).

## 9. Data Contract & Mapping
| IDoc | Canonical |
|---|---|
| E1EDL20/VBELN, LGNUM, BSTNR, TRAID | DeliveryNo, Warehouse, S4OrderId, HU/TrackingNo |
| E1EDT13/NTANF (QUALF 006) | GoodsIssue/GIDate |
| E1EDL24 (POSNR/MATNR/LGMNG/VRKME) | Tasks/Task, GoodsIssue/TotalQuantity |
| E1EDL37 (EXIDV/VHILM/BRGEW/GEWEI) | HandlingUnits/HandlingUnit |

## 10. Security
- Sender Client Certificate; Receiver me `X-Inbound-Token` (Secure Parameter).

## 11. Error Handling & Observability
- `receive_warehouse.php` transaksional; HU/tasks rifreskohen sipas delivery (idempotent).
- MPL: `Scenario=O2C-Event-In`, CorrelationId.

## 15. Open Points
- WHSCON (EWM warehouse task confirmation) mund të ketë segmente të tjera; përshtat XPath sipas metadata.
- Bin-et burim/destinacion mbushen best-effort; konfirmo strukturën EWM.
