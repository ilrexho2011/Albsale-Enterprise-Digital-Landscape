# Phase 5 — API Management + AEM Monitoring + HANA Cloud Analytics

Kurorëzon landscape-in: ekspozon API-t në mënyrë të sigurt (APIM), monitoron integrimin
me alerte/evente (AEM), dhe replikon O2C në HANA Cloud për **analitikë & dashboard**.

## 1. Tri shtyllat

### A) SAP API Management (APIM)
API proxy **AlbsaleSaltAPI** (`apim/`) fronton endpoint-et O2C te CI me politika:
- **Spike-Arrest** (20 req/s), **Verify-API-Key** (`X-Apikey`), **Quota** (10k/ditë),
  **Remove-API-Key-Header**, **CORS** i kontrolluar.
- **OpenAPI 3.0** (`openapi/albsale-salt-api.yaml`) për Developer Portal; **API Product** me scopes.
- Backend me **OAuth2 client credentials** drejt CI (KVM, jo sekret në proxy).

Rrugët publike: `POST /orders`, `POST /atp`, `GET /stock`, `GET /finance`
(base path `/albsale/o2c/v1`).

### B) AEM — Advanced Event Mesh + Monitoring
- `IF_Salt_Event_Publish` — publikon çdo event O2C në **topik Event Mesh**
  `albsale/o2c/{eventType}` (AMQP) për konsum event-driven (analitikë/monitorim real-time).
- `IF_Salt_Monitoring_Collector` — mbledh **alertet** e integrimit (nga exception subprocess-et
  ose skanim MPL), i shkruan te ERP (`monitor_alert.php`) dhe dërgon **email** për ato **CRITICAL**.

### C) HANA Cloud Analytics
- `IF_Salt_Analytics_Replicate` — në orar (**Timer 15 min**) tërheq delta-n O2C nga ERP
  (`api/analytics/extract.php`) dhe e **upsert**-on në HANA Cloud (JDBC) në **star schema**.
- `hana/sql/`: `FACT_O2C_ORDER` + dimensionet + **KPI views** (funnel, revenue, cycle time,
  on-time, DSO/A/R, top products, headline).
- **Dashboard** (`erp/public/dashboard.html`): KPI cards + status funnel + umsatz + top produkte
  + monitorim alertesh; light/dark; lexon `api/analytics/kpis.php` kur hostohet, ndryshe mostër.

## 2. Arkitektura

```
Partnerë B2B ──HTTPS+APIKey──► SAP API Management (AlbsaleSaltAPI)
                                  │ Spike/Quota/Key/CORS + OAuth2
                                  ▼
                          SAP Cloud Integration (iFlow-t 1–4)
                                  │
         ┌────────────────────────┼───────────────────────────┐
         ▼                        ▼                            ▼
  IF_Salt_Event_Publish    IF_Salt_Monitoring_Collector   IF_Salt_Analytics_Replicate
   → AEM topic               → ERP monitor_alert            → HANA Cloud (JDBC)
   albsale/o2c/*             + Email (CRITICAL)             FACT_O2C_ORDER + KPI views
                                                                   │
                                                          dashboard.html / kpis.php
```

## 3. Objektet e reja

### APIM (`apim/`)
- `AlbsaleSaltAPI/apiproxy/` — proxy + target + 5 policy.
- `openapi/albsale-salt-api.yaml`, `products/albsale-o2c-product.json`, `README.md`.

### CI (paketa AlbsaleVloraO2C)
| iFlow | Rol | Adapter |
|---|---|---|
| IF_Salt_Event_Publish | Event O2C → AEM topic | HTTPS → AMQP |
| IF_Salt_Monitoring_Collector | Alerte → ERP + Email | HTTPS → HTTP + Mail |
| IF_Salt_Analytics_Replicate | Delta ERP → HANA (JDBC) | Timer → HTTP/JDBC |

### HANA Cloud (`hana/sql/`)
- `01_analytics_schema.sql` — star schema (fact + dim).
- `02_kpi_views.sql` — 7 KPI views.

### ERP (dosja erp/)
| Skedar | Rol |
|---|---|
| `sql/06_phase5.sql` | `integration_alert` + KPI views lokale (mirror) |
| `public/api/analytics/kpis.php` | KPI JSON për dashboard |
| `public/api/analytics/extract.php` | delta O2C për HANA (token) |
| `public/api/integration/monitor_alert.php` | pranon alertet (token) |
| `public/dashboard.html` | dashboard analitik/monitorim (light/dark) |
| `src/Config/integration.php`, `.env` | CPI_EVENT_PUBLISH_URL, CPI_ALERT_URL |

## 4. Deployment

### APIM
1. Importo `AlbsaleSaltAPI` në API Portal; krijo KVM `albsale.cpi.oauth` + variabël `apim.allowed.origin`.
2. Krijo API Product nga JSON; publiko OpenAPI në Developer Portal; krijo App → API Key.

### AEM / Event Mesh
1. Krijo instancë Advanced Event Mesh; queue/topic `albsale/o2c/*`; Security Material `AEM_BROKER_CRED`.
2. Konfiguro Mail (SMTP) + `ops_email` për alertet CRITICAL.
3. Lidh exception subprocess-et e iFlow-ve që POST-ojnë te `/salt/alert`.

### HANA Cloud
1. Ekzekuto `hana/sql/01_*` dhe `02_*` (schema ALBSALE_ANALYTICS).
2. Krijo JDBC Data Source `HANA_CLOUD_ANALYTICS` në CI; aktivo `IF_Salt_Analytics_Replicate` (Timer).

### ERP
1. Ngarko `sql/06_phase5.sql`.
2. Plotëso `.env`: `CPI_EVENT_PUBLISH_URL`, `CPI_ALERT_URL`.
3. Hap `dashboard.html` (nën `erp/public/`) — merr KPI live nga `api/analytics/kpis.php`.

## 5. Testet
- **APIM:** thirrje pa `X-Apikey` → 401; mbi kuotë → 429; me key valide → 2xx te CI.
- **AEM:** simulo dështim → alert POST te `/salt/alert` → rresht në `integration_alert`; CRITICAL → email.
- **HANA:** ekzekuto replicate → rreshta në `FACT_O2C_ORDER`; `V_KPI_HEADLINE` kthen totalet.
- **Dashboard:** hap `dashboard.html` → KPI + charts; verifikuar light/dark (screenshot).

### Verifikime offline (në këtë repo)
- APIM proxy/policies + OpenAPI + product: well-formed / valid (xmllint + YAML + JSON parse).
- iFlw well-formed; PHP `php -l` pa gabime; dashboard i renderuar (light+dark) pa probleme layout-i.

## 6. Statusi i landscape-it
E gjithë rrjedha O2C (Faza 1–4) tani është e **ekspozuar sigurt** (APIM), **e monitoruar** (AEM),
dhe **e matshme** (HANA KPI). Custom ERP shërben si kanal B2B/B2C; SAP S/4HANA është System of Record.

## 7. Zgjerime të mëtejshme (opsionale)
- Event Mesh consumers për push real-time te dashboard (WebSocket).
- SAP Analytics Cloud mbi HANA views; predictive replenishment; SAP Build Process Automation.
- Procurement (PO/GR); aATP me rezervim.
