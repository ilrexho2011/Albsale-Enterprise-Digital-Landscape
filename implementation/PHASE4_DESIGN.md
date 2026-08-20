# Phase 4 — Warehouse/EWM + Finance/FI posting

Zgjeron O2C përtej faturës: **përmbushja në magazinë** (picking → packing/HU → goods issue)
dhe **financa** (posting kontabël i faturës → pagesa/clearing), plus **gjendja A/R** e klientit.

## 1. Çfarë shton Phase 4

### A) Warehouse / EWM
Pas krijimit të dërgesës (Phase 3), EWM konfirmon picking-un dhe posts goods issue.
`IF_Salt_EWM_Event_In` merr SHPCON/WHSCON → `WarehouseEvent` kanonik me:
- **EventType:** PICKED / PACKED / GOODS_ISSUED
- **GoodsIssue:** movement type (601), datë, sasi totale
- **HandlingUnits:** HU id, pack material, peshë, tracking
- **Tasks:** task id, material, sasia e pickuar, bin-et, status

ERP i persiston te `warehouse_event`, `handling_unit`, `warehouse_task`, dhe përditëson
`salesorder.warehouse_status` + `gi_date`.

### B) Finance / FI
`IF_Salt_FI_Event_In` merr njoftimin financiar → `FinanceEvent` kanonik:
- **FI_POSTED:** dokumenti kontabël (accounting doc, company code, fiscal year, shuma) i faturës
- **PAYMENT_CLEARED:** clearing/pagesë (payment ref, datë, shuma, clearing doc)

ERP i persiston te `finance_document` / `payment` dhe përditëson `salesorder.fi_status`
(POSTED → CLEARED), `paid_date`, `paid_amount`.

### C) Gjendja A/R (OData)
`IF_Salt_Finance_Status` pyet open items A/R të klientit nga S/4 (OData) dhe kthen JSON;
`finance_status.php` e ekspozon, me **fallback lokal** (invoice − payment) nëse CI s'përgjigjet.

## 2. Arkitektura

```
S/4 EWM ──IDoc SHPCON/WHSCON──► IF_Salt_EWM_Event_In ──XSLT──► WarehouseEvent
                                          │ [HTTP + token]
                                          ▼
                              receive_warehouse.php (transaksional)
                     warehouse_event + handling_unit + warehouse_task; salesorder.warehouse_status/gi_date

S/4 FI ──event/IDoc──► IF_Salt_FI_Event_In ──XSLT──► FinanceEvent (FI_POSTED | PAYMENT_CLEARED)
                                          │ [HTTP + token]
                                          ▼
                              receive_finance.php (transaksional)
                     finance_document / payment; salesorder.fi_status/paid_*

finance_status.php ──GET zinn──► IF_Salt_Finance_Status ──OData A/R──► S/4  (fallback lokal)
```

## 3. Objektet e reja

### CI (paketa AlbsaleVloraO2C)
| iFlow | Rol | Adapter |
|---|---|---|
| IF_Salt_EWM_Event_In | SHPCON/WHSCON → WarehouseEvent → ERP | IDoc sender → HTTP |
| IF_Salt_FI_Event_In | FI event → FinanceEvent → ERP | HTTPS sender → HTTP |
| IF_Salt_Finance_Status | A/R open items (OData) → JSON | HTTPS → OData |

Mappings: `SHPCON_to_Warehouse.xsl`, `FIEvent_to_Canonical.xsl`.
Skemat: `WarehouseEvent.xsd`, `FinanceEvent.xsd`.

### ERP (dosja erp/)
| Skedar | Rol |
|---|---|
| `sql/05_phase4.sql` | warehouse_event, handling_unit, warehouse_task, finance_document, payment; fusha te salesorder |
| `public/api/integration/receive_warehouse.php` | persiston ngjarjet e magazinës |
| `public/api/integration/receive_finance.php` | persiston FI/pagesa |
| `public/api/integration/finance_status.php` | gjendja A/R (me fallback lokal) |
| `public/myorders.php` | kolona Lager + FI, WA date, shuma e paguar |
| `src/Config/integration.php`, `.env` | `CPI_FINANCE_URL` |

## 4. Deployment

### CI
1. Importo 3 iFlow-t e reja.
2. Konfiguro:
   - EWM: S/4 EWM të dërgojë SHPCON/WHSCON (output/BD) te porti i CI-t `/salt/ewm`.
   - FI: S/4 të dërgojë njoftimin financiar te `/salt/fi` (Event Mesh / IDoc FIDCCP02 / CDS outbound).
   - A/R: publiko OData `API_OPLACCTGDOCITEMCUBE_SRV` (ose ekuivalent) + `S4_ODATA_USER`.
3. `erp_inbound_token` (Secure Param) = `SALT_INBOUND_TOKEN` në ERP; URL-të e ERP-së në parameters.prop.

### ERP
1. Ngarko `sql/05_phase4.sql`.
2. Plotëso `.env`: `CPI_FINANCE_URL`.
3. Endpoint-et inbound (warehouse/finance) përdorin të njëjtin token si `receive_event.php`.

## 5. Testet

### Warehouse
- Trigger goods issue në S/4 EWM → `receive_warehouse.php`: rreshta në `warehouse_event`,
  `handling_unit`, `warehouse_task`; `salesorder.warehouse_status=GOODS_ISSUED`, `gi_date` i vendosur.

### Finance
- Post fatura në FI → `FI_POSTED`: rresht në `finance_document`, `salesorder.fi_status=POSTED`.
- Regjistro pagesën → `PAYMENT_CLEARED`: rresht në `payment`, `fi_status=CLEARED`, `paid_date/amount`.
- `GET finance_status.php?zinn=..` → open items + totali; fallback lokal kur CI jashtë loje.

### Verifikime offline (në këtë repo)
- SHPCON → WarehouseEvent **valid** vs `WarehouseEvent.xsd`; ERP lexon HU + task + goods issue.
- FI_POSTED & PAYMENT_CLEARED → FinanceEvent **valid** vs `FinanceEvent.xsd`; ERP lexon accounting/payment.
- A/R aggregation (open items, total): konfirmuar.
- `php -l` pa gabime; iflw/xsl/xsd well-formed.

## 6. Cikli i plotë O2C tani
NEW → QUEUED → SENT → CONFIRMED → **PICKED/PACKED/GOODS_ISSUED** → DELIVERED → INVOICED → **FI_POSTED → PAID**.

## 7. Hapat pas Phase 4
- Faza 5+: API Management (publikimi i API-ve), AEM monitoring, HANA Cloud analytics, Event Mesh.
- Procurement (PO/GR) për rimbushjen e stokut; aATP me rezervim.
