# Integration Catalog: IF_Salt_Monitoring_Collector

## 1. Executive Summary
- **Purpose:** Mbledh alertet e integrimit, i persiston te ERP dhe njofton Ops për ato kritike.
- **Business process:** Monitoring/Alerting (Phase 5). **Technology:** SAP CI/CPI + Mail.
- **Source:** Exception subprocess-et / skanim MPL · **Target:** ERP `monitor_alert.php` + Email · **Status:** v1.0.0

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `normalizeAlert.groovy` | Normalizim + severity (DLQ/dead → CRITICAL) |
| E2 | ERP `monitor_alert.php`, `integration_alert` | Persistim |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_Monitoring_Collector |
| package_name | AlbsaleVloraO2C |

## 4–8. Flow
1. HTTPS Sender `/salt/alert` pranon alertin JSON.
2. `normalizeAlert.groovy` cakton `alertSeverity/alertScenario` + token.
3. **Multicast:** shkruaj gjithmonë te ERP (`monitor_alert.php`); nëse `alertSeverity=CRITICAL` → **Email Ops**.

## 9. Data Contract
Hyrje: `{severity, scenario, correlationId, errorPhrase, messageId, timestamp}`.
Dalje ERP: rresht në `integration_alert`; Email: subjekt `[ALBSALE O2C] <severity>: <scenario>`.

## 10. Security
- Receiver ERP me `X-Inbound-Token`; SMTP me kredencial në Security Material.

## 11. Observability
- Dashboard-i (`dashboard.html`) shfaq numërimin e alerteve open (CRITICAL/WARNING).

## 15. Open Points
- Burimi i alerteve: exception subprocess POST vs skanim periodik MPL (OData Monitoring API).
- De-duplication/aggregation e alerteve të përsëritura.
