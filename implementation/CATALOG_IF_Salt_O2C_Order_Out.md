# Integration Catalog: IF_Salt_O2C_Order_Out

## 1. Executive Summary
- **Purpose:** Dërgon porosinë e klientit nga Custom ERP në SAP S/4HANA si Sales Order.
- **Business process:** Order-to-Cash (O2C) — Phase 1 e roadmap-it.
- **Source system:** Custom ERP (PHP/MySQL) — `send_order.php`
- **Target system:** SAP S/4HANA 2023 (client 100)
- **Technology:** SAP CI/CPI (Cloud Integration)
- **Priority:** P2
- **Status:** Design (v1.0.0) — i validuar me mostra, gati për import në tenant.

## 2. Source Map and Evidence
| Evidence ID | Source file/system | Object ID | Version | Usage |
|---|---|---|---|---|
| E1 | ERP `src/Lib/canonical.php` | `build_orders_canonical` | 2025 | Prodhon payload-in kanonik |
| E2 | ERP `public/api/integration/send_order.php` | — | 2025 | Thërret endpoint-in e CI |
| E3 | `OrderCanonical_v1.xsd` | urn:albsale:o2c:canonical:1.0 | 1.0 | Kontrata hyrëse |
| E4 | `ORDERS05_subset.xsd` | ORDERS05 | 1.0 | Kontrata dalëse (IDoc) |
| E5 | `Canonical_to_ORDERS05.xsl` | — | 1.0 | Mapping-u |

## 3. Interface Identity
| Field | Value | Evidence status |
|---|---|---|
| customer_id | ALBSALE_VLORA | derived |
| system_id | S4H_ZS4CLNT100 | assumption |
| interface_id | IF_Salt_O2C_Order_Out | source-backed |
| interface_name | Salt O2C Order Outbound | source-backed |
| integration_flow_id | IF_Salt_O2C_Order_Out | source-backed |
| package_name | AlbsaleVloraO2C | source-backed |
| source_system | Custom ERP (ALBSALE_SALT) | source-backed |
| target_system | SAP S/4HANA (ZS4CLNT100) | assumption |
| priority | P2 | derived |

## 4. Business Context
Klienti vendos porosinë në portalin ERP (`myorders.php` → butoni "An SAP senden").
ERP-ja thërret `send_order.php`, i cili ndërton XML-in kanonik ORDERS, e ruan në
`integration_outbox` (at-least-once) me `correlation_id`, dhe e POST-on te ky iFlow.
iFlow-i e harton në IDoc ORDERS05 dhe krijon Sales Order në S/4HANA. Statusi
kthehet me `IF_Salt_O2C_Event_In` (ORDRSP → CONFIRMED).

## 5. Technical Landscape
`ERP (HTTPS/XML)` → `SAP CI: HTTPS Sender /http/salt/orders` → `Content Modifier` →
`Groovy (correlation)` → `XSLT canonical→ORDERS05` → `IDoc Receiver` → `S/4HANA`.

## 6. Design-Time Objects
| Object type | Object name/ID | Description | Dependency |
|---|---|---|---|
| iFlow | IF_Salt_O2C_Order_Out.iflw | Procesi kryesor + exception subprocess | — |
| XSD | OrderCanonical_v1.xsd | Kontrata hyrëse | — |
| XSD | ORDERS05_subset.xsd | Kontrata dalëse | — |
| XSLT | Canonical_to_ORDERS05.xsl | Mapping | XSD-të |
| Groovy | setOrderHeaders.groovy | CorrelationId → header/MPL | — |
| Groovy | logAndRaise.groovy | Exception handler | — |

## 7. Runtime Configuration
| Component | Setting | Value/source | Notes |
|---|---|---|---|
| HTTPS Sender | urlPath | `/salt/orders` | endpoint i CI |
| HTTPS Sender | auth | ClientCertificate + role `ESBMessaging.send` | mos përdor Basic në prod |
| IDoc Receiver | address | `{{s4_idoc_url}}` | Externalized |
| IDoc Receiver | credential | `{{s4_credential_alias}}` | Security Material |
| Content Modifier | p_vkorg/p_vtweg/p_spart/p_auart | `{{sales_org}}`… | Externalized Parameters |

## 8. Message Flow and Processing Steps
1. HTTPS Sender pranon XML-in kanonik ORDERS nga ERP.
2. Content Modifier vendos parametrat organizativë si exchange properties.
3. Groovy nxjerr `CorrelationId` → header `X-Correlation-Id` + MPL custom header.
4. XSLT harton canonical → IDoc ORDERS05.
5. IDoc Receiver dërgon në S/4HANA.
6. Gabimet kapen nga Exception Subprocess → JSON fault (HTTP 502) te ERP.

## 9. Data Contract and Mapping
| Source (canonical) | Target (ORDERS05) | Rule | Evidence |
|---|---|---|---|
| Header/CorrelationId | E1EDK01/BELNR | referencë blerësi (gjurmim) | E5 |
| Summary/Currency | E1EDK01/CURCY | direkt | E5 |
| — (param) | E1EDK14 QUALF 008/007/006/012 | VKORG/VTWEG/SPART/AUART | E5 |
| Buyer/CustomerRef (ZINN) | E1EDKA1[PARVW=AG]/PARTN | Sold-to | E5 |
| Line/LineNo | E1EDP01/POSEX | numër pozicioni | E5 |
| Line/Quantity | E1EDP01/MENGE | sasia | E5 |
| Line/Unit | E1EDP01/MENEE | njësia | E5 |
| Line/ProductRef (saltcode) | E1EDP19[QUALF=002]/IDTNR | material | E5 |

## 10. Security and Connectivity
- Sender: HTTPS me Client Certificate (ose OAuth), rol `ESBMessaging.send`.
- Receiver: kredencial i ruajtur në Security Material (alias `{{s4_credential_alias}}`), asnjë sekret në iFlow.
- Të gjitha URL/ID/kredencialet janë Externalized Parameters.

## 11. Error Handling, Retry, and Observability
- Exception Subprocess logon detajet në MPL (attachment) dhe kthen JSON 502.
- Idempotencë: `integration_outbox.correlation_id` UNIQUE në ERP; ridërgimi s'krijon duplikate.
- Retry: rekomandohet JMS/Data Store për retry asinkron (Phase 2).
- Monitoring: MPL me custom header `CorrelationId` = `SALT-<ZINN>-<idso>-<rand>`.

## 12. MPL / Monitoring Evidence
| MessageGuid/MPL ID | Status | Error phrase | Timestamp | Related section |
|---|---|---|---|---|
| _(runtime)_ | — | — | — | §11 |

## 13. Dependencies and Related Objects
- `IF_Salt_O2C_Event_In` (rruga e kthimit të statusit).
- ERP: `integration_outbox`, `salesorder.order_status`.

## 14. Change History and Version Notes
- v1.0.0 — Krijim fillestar (Phase 1). Mapping i validuar me mostër reale.

## 15. Open Points and Assumptions
- VKORG/VTWEG/SPART/AUART janë placeholder (`1000/10/00/TA`) — konfirmo me Sales customizing.
- Modeli aktual: një artikull për porosi (një `E1EDP01`). Multi-line mbështetet nga XSLT (`for-each`).
- Numri i klientit S/4 (KUNNR) supozohet = ZINN; nëse ndryshon, shto value mapping.
