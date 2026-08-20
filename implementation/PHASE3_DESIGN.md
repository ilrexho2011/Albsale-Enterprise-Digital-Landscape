# Phase 3 — DELVRY03/INVOIC02 të plota + Advanced ATP (aATP)

Plotëson ciklin O2C: dërgesat dhe faturat vijnë me **detaje të plota** (artikuj, shuma,
data, tracking) dhe persistohen në ERP; disponueshmëria kalon nga një lexim i thjeshtë
stoku (Phase 2) në një **kontroll aATP të vërtetë** me sasi/datë të konfirmuar.

## 1. Çfarë shton Phase 3

### A) Eventet e plota nga S/4 (DESADV + INVOIC)
Phase 1/2 përditësonin vetëm statusin. Tani:
- **DELVRY03 → Despatch:** DeliveryNo, DeliveryDate, Incoterms, Carrier, TrackingNo,
  ShipTo, pesha, dhe **artikujt** (line, material, sasia e dërguar, njësia, batch).
- **INVOIC02 → Invoice:** InvoiceNo, InvoiceDate, DueDate, Currency, Net/Tax/Gross,
  dhe **artikujt** (line, material, sasia, vlera neto, % taksa).

Këto persistohen në tabela të reja `delivery(+_item)` dhe `invoice(+_item)`.

### B) aATP i vërtetë
`IF_Salt_ATP_Check` merr {material, plant, requestedQuantity, requestedDate}, pyet aATP-në
e S/4 (Availability Information e renditur sipas datës), dhe kthen **sasinë e konfirmuar**,
**datën e konfirmimit** (kur akumulohet mjaftueshëm stok) dhe **mungesën (shortfall)** —
ndryshe nga leximi i thjeshtë i stokut në Phase 2.

## 2. Arkitektura

```
S/4HANA ──IDoc ORDRSP/DELVRY03/INVOIC02──► IF_Salt_O2C_Event_In (v2)
                                              │  Router sipas EDI_DC40/MESTYP
                        ┌─────────────────────┼─────────────────────┐
                   ORDRSP_to_Canonical   DELVRY03_to_Canonical   INVOIC02_to_Canonical
                        └─────────────────────┼─────────────────────┘
                                              ▼  event kanonik v2 (namespace default)
                                       [HTTP + X-Inbound-Token]
                                              ▼
                                   receive_event.php (v2, transaksional)
                          salesorder + order_status_history + delivery(+item) + invoice(+item)

check_atp.php ──POST {material,plant,qty,date}──► IF_Salt_ATP_Check
                                              │  OData Query aATP (renditur sipas datës)
                                              ▼
                            S/4HANA · API_AVAILABILITY_INFORMATION_SRV
                                              ▲
             JSON {confirmedQuantity, confirmedDate, shortfall, fullyConfirmed} ◄── konfirmim kumulativ
```

## 3. Objektet e reja/ndryshuara

### CI (paketa AlbsaleVloraO2C)
| Objekt | Ndryshimi |
|---|---|
| `IF_Salt_O2C_Event_In` | **v2** — Router sipas MESTYP → 3 mapping të dedikuar |
| `ORDRSP/DELVRY03/INVOIC02_to_Canonical.xsl` | Mapping të plota (të reja) |
| `EventCanonical_v2.xsd` | Kontrata e pasur e eventit |
| `IF_Salt_ATP_Check` | **i ri** — aATP OData me konfirmim sasie/date |

### ERP (dosja erp/)
| Skedar | Ndryshimi |
|---|---|
| `sql/04_phase3.sql` | tabela `delivery(+_item)`, `invoice(+_item)`, `atp_check_log`; fusha te `salesorder` |
| `public/api/integration/receive_event.php` | **v2** — persiston detajet, transaksional |
| `public/api/integration/check_atp.php` | **i ri** — kontroll aATP |
| `public/myorders.php` | shfaq Lieferungen + Rechnungen me artikuj |
| `src/Config/integration.php`, `.env` | `CPI_ATP_URL` |

## 4. Deployment

### CI
1. Importo v2 të `IF_Salt_O2C_Event_In` (bump 2.0.0) + `IF_Salt_ATP_Check`.
2. Konfiguro OData receiver-in aATP: `s4_odata_atp_url` → `API_AVAILABILITY_INFORMATION_SRV`
   (publikoje/aktivizoje shërbimin në S/4 Gateway) + Security Material `S4_ODATA_USER`.
3. Sigurohu që S/4 dërgon DELVRY03 (VL01N/output) dhe INVOIC02 (VF01/output) te porti i CI-t.

### ERP
1. Ngarko `sql/04_phase3.sql`.
2. Plotëso `.env`: `CPI_ATP_URL`.
3. Asgjë tjetër — `receive_event.php` v2 është prapakompatibël (ORDRSP i njëjtë).

## 5. Testet

### DELVRY03 / INVOIC02
- Trigger një delivery në S/4 → `IF_Salt_O2C_Event_In` (dega DESADV) → `receive_event.php`:
  rresht në `delivery` + `delivery_item`, `salesorder.order_status=DELIVERED`, `delivery_date` i vendosur.
- Trigger një faturë → dega INVOIC → `invoice` + `invoice_item`, `gross_amount` te salesorder.
- Portali `myorders.php` shfaq të dyja me artikuj.

### aATP
- `POST check_atp.php {saltcode:13455, quantity:50, date:"2026-09-01"}`
  → nëse stoku akumulohet deri në 50 më 2026-09-05 → `confirmedQuantity=50, confirmedDate=2026-09-05`.
  → nëse total < 50 → `shortfall>0, fullyConfirmed=false`. Logohet te `atp_check_log`.

### Verifikime offline (në këtë repo)
- DELVRY03 & INVOIC02 → canonical → **valid** vs `EventCanonical_v2.xsd`; `receive_event.php`
  lexon saktë header + artikuj + shuma.
- aATP konfirmim kumulativ: rasti "mjaftueshëm" (datë konfirmimi) dhe "mungesë" (shortfall) — konfirmuar.
- `php -l` pa gabime; iflw/xsl/xsd well-formed.

## 6. Hapat pas Phase 3
- aATP me **rezervim** (BOP/aATP action POST) përveç leximit.
- Idempotencë delivery/invoice me numër dokumenti (bërë: PK + ON DUPLICATE + rifreskim items).
- Faza 4+: Warehouse/EWM, Finance (FI posting), API Management, AEM monitoring, HANA analytics.
