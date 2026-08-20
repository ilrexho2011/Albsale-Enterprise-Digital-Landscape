# Integration Catalog: IF_Salt_O2C_Order_Consumer

## 1. Executive Summary
- **Purpose:** Konsumon porositë nga JMS me retry dhe krijon Sales Order në S/4 (IDoc ORDERS05).
- **Business process:** O2C — Phase 2 (reliable messaging, consumer).
- **Source:** JMS queue `salt.orders` · **Target:** S/4HANA (IDoc) + Data Store `salt_orders_dlq`
- **Technology:** SAP CI/CPI · **Priority:** P2 · **Status:** Design v1.0.0

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `Canonical_to_ORDERS05.xsl` (ri-përdorur nga Phase 1) | Mapping |
| E2 | `notifyDeadLetter.groovy` | Dead-letter + MPL |
| E3 | `ORDERS05_subset.xsd` | Kontrata dalëse |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_O2C_Order_Consumer |
| package_name | AlbsaleVloraO2C |
| source | JMS `salt.orders` |
| target_system | SAP S/4HANA (ZS4CLNT100) |

## 4. Business Context
Përpunon asinkron çdo porosi të vënë në radhë. Garanton dërgim "at-least-once" me
**JMS retry 5x (exponential)**; pas shterimit, ruan payload-in në Data Store për riproçesim.

## 5–8. Flow & Processing
1. JMS Sender lexon nga `salt.orders` (retry: max 5, interval 1min, exponential, max 60min, DLQ on).
2. Content Modifier vendos parametrat organizativë (VKORG/VTWEG/SPART/AUART) si properties.
3. XSLT `Canonical_to_ORDERS05.xsl` → IDoc ORDERS05.
4. IDoc Receiver → S/4HANA.
5. Exception Subprocess: `Write to DLQ Data Store` (`salt_orders_dlq`) + `notifyDeadLetter.groovy`.

## 9. Data Contract & Mapping
Kontrata & mapping-i identik me Phase 1 (`CATALOG_IF_Salt_O2C_Order_Out.md` §9).

## 10. Security
- Kredencial S/4: Security Material `{{s4_credential_alias}}`.
- URL/ID Externalized Parameters; asnjë sekret në iFlow.

## 11. Error Handling, Retry & Observability
- JMS retry eksponencial në CI + dead-letter Data Store `salt_orders_dlq` (30 ditë).
- ERP-anë: `dispatch_outbox.php` mban gjendjen `SENT/FAILED/DEAD`.
- Idempotenca: `CorrelationId` unik; IDoc i njëjtë s'krijon duplikat në S/4 (kontroll ALE/BD).
- MPL: `Scenario=O2C-Order-Consumer(-DLQ)`, `Status=DEAD_LETTER` në dead-letter.

## 12. MPL Evidence
| MPL ID | Status | Error phrase | Related |
|---|---|---|---|
| _(runtime)_ | Retry/Escalated/Failed | — | §11 |

## 13. Dependencies
`IF_Salt_O2C_Order_Enqueue` (prodhon në `salt.orders`); `IF_Salt_O2C_Event_In` (statusi i kthimit).

## 15. Open Points
- Kontrolli i duplikatit në S/4 (BD dedup / referenca CorrelationId) të konfigurohet.
- Vlerat JMS retry (5x/60min) të kalibrohen sipas SLA-së.
