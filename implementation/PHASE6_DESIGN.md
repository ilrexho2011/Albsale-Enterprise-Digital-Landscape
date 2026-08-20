# Phase 6 — Procurement (PO/GR) + aATP me rezervim real (BOP)

Mbyll ciklin e furnizimit: kur stoku bie nën pikën e rimbushjes ose aATP jep mungesë,
ERP-ja nis një **Purchase Order** te furnitori; kur mallrat mbërrijnë (**Goods Receipt**),
stoku rimbushet dhe **Backorder Processing (BOP)** rikonfirmon porositë e mbetura.

## 1. Çfarë shton Phase 6

### A) Procurement (PO → ASN → GR)
- **Reorder trigger:** `reorder_check.php` (cron) skanon `salt` ku `stock ≤ reorder_point`
  dhe `on_order = 0`, krijon PO, e ruan (`purchase_order` + item), rezervon `on_order`, dhe
  e dërgon te CI (`IF_Salt_PO_Send` → IDoc **PORDCR** → S/4 MM).
- **Supplier ASN/PO-confirm:** `IF_Salt_Supplier_ASN_In` (ORDERS05/ORDRSP me partner LF) →
  `receive_asn.php` përditëson PO-në (CONFIRMED/REJECTED) + datat e konfirmuara.
- **Goods Receipt:** `IF_Salt_GR_In` (IDoc **MBGMCR**) → `receive_goodsreceipt.php`:
  **`salt.stock += received_qty`**, `on_order -=`, regjistron GR, mbyll PO-në, dhe nis BOP.

### B) aATP me rezervim real (BOP)
- `IF_Salt_ATP_Reserve` — thërret aATP action **ConfirmAndReserve** në S/4 (alokon stok);
  `reserve_atp.php` ruan `atp_reservation` dhe vendos `salesorder.reserved_qty/backorder_qty`.
- **BOP** (`bop_reconfirm`): pas çdo GR, rikonfirmon porositë me `backorder_qty > 0` sipas
  **FIFO**, duke ulur backorder-in deri sa lejon stoku i ri. Porositë e mbuluara plotësisht → CONFIRMED.

## 2. Arkitektura

```
salt.stock ≤ reorder_point
        │  reorder_check.php (cron)
        ▼
[HTTPS /salt/po]──► IF_Salt_PO_Send ──XSLT──► IDoc PORDCR ──► S/4HANA MM (krijon PO)
        │                                                          │ dërgon EDI te furnitori
        ▼                                                          ▼
purchase_order(+item), on_order↑                            Furnitori (EDI 850/855/856)
                                                                   │
S/4 ORDRSP (PO confirm) ──► IF_Salt_Supplier_ASN_In ──► receive_asn.php  (PO=CONFIRMED)
S/4 MBGMCR (goods receipt) ─► IF_Salt_GR_In ──► receive_goodsreceipt.php
        │  salt.stock += qty ; on_order -= qty ; PO=CLOSED
        ▼
  bop_reconfirm(saltcode)  ──►  rikonfirmon backorder-et FIFO (reserved↑, backorder↓)

Reserve:  reserve_atp.php ──[HTTPS /salt/atp/reserve]──► IF_Salt_ATP_Reserve ──OData action──► S/4 aATP
          → {reservationId, confirmedQuantity, backorderQty}
```

## 3. Objektet e reja

### CI (paketa AlbsaleVloraO2C)
| iFlow | Rol | Adapter |
|---|---|---|
| IF_Salt_PO_Send | Canonical PO → IDoc PORDCR → S/4 | HTTPS → IDoc |
| IF_Salt_Supplier_ASN_In | ORDRSP (PO confirm) → ERP | IDoc → HTTP |
| IF_Salt_GR_In | MBGMCR (goods receipt) → ERP | IDoc → HTTP |
| IF_Salt_ATP_Reserve | aATP ConfirmAndReserve (BOP) | HTTPS → OData action |

Mappings: `Canonical_to_PORDCR.xsl`, `ORDRSP_PO_to_Canonical.xsl`, `MBGMCR_to_GR.xsl`.
Skemat: `PurchaseOrder.xsd`, `PORDCR_subset.xsd`, `SupplierEvent.xsd`, `GoodsReceipt.xsd`.

### ERP (dosja erp/)
| Skedar | Rol |
|---|---|
| `sql/07_phase6.sql` | supplier, purchase_order(+item), goods_receipt, atp_reservation; fusha te salt/salesorder; view v_reorder_needed |
| `src/Lib/procurement.php` | build_po_canonical + bop_reconfirm (FIFO) |
| `public/api/integration/reorder_check.php` | worker riblerjeje (cron/HTTP) |
| `public/api/integration/receive_asn.php` | konfirmim furnitori |
| `public/api/integration/receive_goodsreceipt.php` | GR → rimbush stok + BOP |
| `public/api/integration/reserve_atp.php` | rezervim aATP |
| `public/procurement.php` | pamje: stok/reorder, PO, GR, rezervime |
| `src/Config/integration.php`, `.env` | CPI_PO_SEND_URL, CPI_ATP_RESERVE_URL |

## 4. Deployment

### CI
1. Importo 4 iFlow-t; konfiguro IDoc receiver (S/4 MM) + parametrat purch_org/group/po_type.
2. S/4: aktivo output PORDCR (in), ORDRSP + MBGMCR (out) drejt porteve të CI (`/salt/asn`, `/salt/gr`).
3. aATP action `ConfirmAndReserve` (ose ekuivalent BOP OData) i publikuar; `S4_ODATA_USER`.

### ERP
1. Ngarko `sql/07_phase6.sql`; vendos `reorder_point`/`reorder_qty`/`supplier_id` te `salt`.
2. Plotëso `.env`: `CPI_PO_SEND_URL`, `CPI_ATP_RESERVE_URL`.
3. Cron për riblerjen (p.sh. çdo orë):
   `0 * * * * php /path/erp/public/api/integration/reorder_check.php >> /var/log/salt_reorder.log 2>&1`

## 5. Testet

- **Reorder:** ul `salt.stock` nën `reorder_point` → `reorder_check` krijon PO (status SENT), `on_order` rritet.
- **ASN:** ORDRSP nga furnitori → PO = CONFIRMED, datat e konfirmuara në item.
- **GR:** MBGMCR → `salt.stock += qty`, `on_order -= qty`, PO = CLOSED, rresht në `goods_receipt`.
- **BOP:** me backorder-e ekzistuese, GR-ja rikonfirmon FIFO — porositë e mbuluara → CONFIRMED.
- **Reserve:** `reserve_atp` → `atp_reservation` + `salesorder.reserved_qty/backorder_qty`.

### Verifikime offline (në këtë repo)
- PO kanonike → **valid** vs `PurchaseOrder.xsd`; → XSLT → **valid** vs `PORDCR_subset.xsd`.
- ORDRSP → **valid** vs `SupplierEvent.xsd`; MBGMCR → **valid** vs `GoodsReceipt.xsd`; ERP i lexon saktë.
- **BOP FIFO** (SQLite): stok 0 + backorder 3000/4000, GR +5000 → idso1 CONFIRMED (3000),
  idso2 BACKORDER (2000 reserved / 2000 mbetur). **PASS**.
- `php -l` pa gabime; iflw/xsl/xsd well-formed.

## 6. Cikli i mbyllur i furnizimit
Porosia me mungesë → **backorder** → riblerje (PO) → furnitori → **goods receipt** → stok i rimbushur →
**BOP** rikonfirmon → porosia përmbushet. Zinxhiri O2C tani është vetë-shërues.

## 7. Zgjerime të mëtejshme
- Rezervim që ul stokun fizik (allocation vs available-to-promise) me kontroll të dyfishtë.
- Multi-supplier sourcing rules; lead-time & safety stock dinamik; MRP-lite.
- Predictive replenishment (parashikim kërkese) + SAP Build Process Automation për aprovime PO.
