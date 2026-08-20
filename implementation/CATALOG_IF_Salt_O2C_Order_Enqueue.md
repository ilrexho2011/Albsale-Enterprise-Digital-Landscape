# Integration Catalog: IF_Salt_O2C_Order_Enqueue

## 1. Executive Summary
- **Purpose:** Pranon porosinë kanonike nga ERP dhe e vë në radhë JMS (decoupling async).
- **Business process:** O2C — Phase 2 (reliable messaging, producer).
- **Source:** Custom ERP `enqueue_order.php` · **Target:** JMS queue `salt.orders`
- **Technology:** SAP CI/CPI · **Priority:** P2 · **Status:** Design v1.0.0

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | ERP `enqueue_order.php` / `src/Lib/outbox.php` | Prodhon & POST-on payload-in |
| E2 | `ackEnqueue.groovy` | 202 + JMS/MPL correlation |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_O2C_Order_Enqueue |
| package_name | AlbsaleVloraO2C |
| source_system | Custom ERP (ALBSALE_SALT) |
| target | JMS `salt.orders` |

## 4. Business Context
Shkëput ERP-në nga disponueshmëria e S/4: porosia pranohet gjithmonë (202) dhe përpunohet
më vonë nga `IF_Salt_O2C_Order_Consumer`. Kjo redukton dështimet e drejtpërdrejta te klienti.

## 5–8. Flow & Processing
1. HTTPS Sender `/salt/orders/async` pranon XML-in kanonik ORDERS.
2. `ackEnqueue.groovy` nxjerr `CorrelationId`, e vë si JMS message id + MPL header, kthen **202**.
3. JMS Receiver shkruan mesazhin në radhën `salt.orders`.

## 9. Data Contract
Hyrje: `OrderCanonical_v1.xsd` (urn:albsale:o2c:canonical:1.0). Dalje: i njëjti payload në JMS.

## 10. Security
- Sender: HTTPS Client Certificate, rol `ESBMessaging.send`.
- Asnjë transformim sekreti; JMS brenda tenant-it.

## 11. Error Handling & Observability
- Nëse shkrimi në JMS dështon, ERP e mban rreshtin `PENDING` në outbox dhe `dispatch_outbox.php` riprovon.
- MPL custom header: `CorrelationId`, `Scenario=O2C-Order-Enqueue`.

## 13. Dependencies
`IF_Salt_O2C_Order_Consumer` (konsumon `salt.orders`); ERP `integration_outbox`.

## 15. Open Points
- Autentikimi Sender të jetë Client Certificate në prod (jo Basic).
