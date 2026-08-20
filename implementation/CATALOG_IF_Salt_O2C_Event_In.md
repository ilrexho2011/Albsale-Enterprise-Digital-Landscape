# Integration Catalog: IF_Salt_O2C_Event_In (v2)

## 1. Executive Summary
- **Purpose:** Pranon eventet O2C nga S/4 (ORDRSP/DELVRY03/INVOIC02) dhe i përcjell te ERP me detaje të plota.
- **Business process:** O2C — Phase 3. **Technology:** SAP CI/CPI. **Priority:** P2. **Status:** v2.0.0.
- **Source:** SAP S/4HANA (IDoc) · **Target:** ERP `receive_event.php`

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `ORDRSP_to_Canonical.xsl` | Konfirmim |
| E2 | `DELVRY03_to_Canonical.xsl` | Dërgesa e plotë |
| E3 | `INVOIC02_to_Canonical.xsl` | Faturë e plotë |
| E4 | `EventCanonical_v2.xsd` | Kontrata e pasur |
| E5 | ERP `receive_event.php` v2 | Persistim transaksional |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_O2C_Event_In |
| version | 2.0.0 |
| package_name | AlbsaleVloraO2C |
| source_system | SAP S/4HANA |
| target_system | Custom ERP |

## 4. Business Context
Router-i sipas `EDI_DC40/MESTYP` dërgon çdo IDoc te mapping-u i dedikuar. Eventi kanonik v2
mban artikuj, shuma dhe data, të cilat ERP i persiston në `delivery/invoice(+items)`.

## 5–8. Flow & Processing
1. IDoc Sender `/salt/events` pranon ORDRSP/DELVRY03/INVOIC02.
2. **Exclusive Gateway (Router)** sipas MESTYP → ORDRSP | DESADV | INVOIC | default.
3. XSLT i dedikuar → event kanonik v2 (namespace default).
4. Content Modifier ngarkon token-in; Groovy vendos `X-Inbound-Token` + MPL.
5. HTTP Receiver POST te `receive_event.php` (transaksion: header + items).

## 9. Data Contract & Mapping
| Doc | Fusha kryesore |
|---|---|
| DELVRY03 | E1EDL20 (VBELN/INCO1/TRAID/BTGEW), E1EDT13 (datë), E1ADRM1 (SP carrier/WE ship-to), E1EDL24 (item) |
| INVOIC02 | E1EDK01 (BELNR/CURCY), E1EDK03 (IDDAT 015/012 data), E1EDS01 (SUMID 010/011/012 shuma), E1EDP01+E1EDP19/26/04 (item) |
| ORDRSP | E1EDP01/MENGE + E1EDP20/EDATU (datë konfirmimi) |

## 10. Security
- Sender Client Certificate; Receiver me `X-Inbound-Token` (Secure Parameter) = `SALT_INBOUND_TOKEN` në ERP.

## 11. Error Handling & Observability
- `receive_event.php` v2 është **transaksional** (rollback nëse dështon persistimi i items).
- Idempotencë: `delivery`/`invoice` me PK + `ON DUPLICATE KEY`; items fshihen+rifuten.
- MPL: `Scenario=O2C-Event-In`, `CorrelationId`.

## 13. Dependencies
Mapping-et e dedikuara; tabelat ERP `delivery(+item)`, `invoice(+item)`.

## 15. Open Points
- Qualifiers e IDoc (IDDAT/SUMID/PARVW) të konfirmohen me metadata reale të S/4.
- Për DESADV, `S4OrderId` mund të mbushet nga referenca; çelësi primar është `DeliveryNo`.
