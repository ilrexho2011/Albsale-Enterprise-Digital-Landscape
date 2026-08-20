# Integration Catalog: IF_Salt_Event_Publish

## 1. Executive Summary
- **Purpose:** Publikon eventet O2C në Advanced Event Mesh për konsum event-driven.
- **Business process:** O2C — event streaming (Phase 5). **Technology:** SAP CI/CPI + AEM.
- **Source:** ERP/CI (event kanonik) · **Target:** AEM topic `albsale/o2c/{eventType}` · **Status:** v1.0.0

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `deriveTopic.groovy` | Nxjerr eventType → topic + MPL |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_Event_Publish |
| package_name | AlbsaleVloraO2C |
| target | AEM (AMQP) topic albsale/o2c/* |

## 4–8. Flow
1. HTTPS Sender `/salt/event/publish` pranon eventin kanonik.
2. `deriveTopic.groovy` cakton `header.eventTopic` (nga DocumentType/EventType) + CorrelationId në MPL.
3. AMQP Receiver publikon në topik `albsale/o2c/${header.eventTopic}`.

## 10. Security
- Sender Client Certificate; AEM me Security Material `{{aem_credential}}`.

## 11. Observability
- MPL custom header `Topic`, `CorrelationId`. Konsumatorët (monitorim/analitikë) abonohen në topik.

## 15. Open Points
- Skema e topikëve (per event vs per aggregate) të finalizohet; retention në AEM.
