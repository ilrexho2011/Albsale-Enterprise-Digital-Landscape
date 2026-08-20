# Integration Catalog: IF_Salt_ATP_Reserve
## 1. Executive Summary
- **Purpose:** aATP me rezervim real (BOP) — konfirmon & rezervon stok për një porosi.
- **Source:** ERP `reserve_atp.php` · **Target:** S/4 aATP (OData action) · **Status:** v1.0.0
## 2. Source Map
| Evidence | Source |
|---|---|
| E1 | `buildReserve.groovy`, `mapReserve.groovy` |
| E2 | ERP `reserve_atp.php` + `atp_reservation` |
## 4–8. Flow
1. HTTPS `POST /salt/atp/reserve` {material,quantity,date,idso}.
2. `buildReserve.groovy` → body OData action `ConfirmAndReserve`.
3. OData action (POST) → S/4 aATP; `mapReserve.groovy` → {reservationId, confirmedQuantity, confirmedDate, backorderQty}.
## 9. Contract
backorderQty = requested − confirmed; fullyConfirmed kur backorder=0. ERP vendos salesorder.reserved_qty/backorder_qty.
## 10. Security
Sender Client Certificate; OData Security Material; input i sanituar.
## 15. Open Points
Emri i action-it/fushave (ReservationID/ConfirmedQuantity) të konfirmohet me aATP OData real; lidhja me BOP job segments.
