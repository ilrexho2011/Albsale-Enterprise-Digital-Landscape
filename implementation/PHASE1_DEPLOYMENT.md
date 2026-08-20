# Phase 1 — Deployment & Test Guide (ERP ↔ SAP CI)

Paketa: **AlbsaleVloraO2C** · iFlows: `IF_Salt_O2C_Order_Out`, `IF_Salt_O2C_Event_In`.

## 1. Parakushtet
- SAP Integration Suite (Cloud Integration) i aktivizuar; roli `ESBMessaging.send`.
- S/4HANA me partner profile / logical system për IDoc ORDERS05 (dalës drejt CI) dhe
  ORDRSP/DELVRY03/INVOIC02 (dalës nga S/4).
- Custom ERP i vendosur (dosja `erp/`), i arritshëm nga CI mbi HTTPS.

## 2. Import në tenant
1. Në Integration Suite → **Design** → krijo/hap paketën `AlbsaleVloraO2C`.
2. Importo të dy iFlow-t (struktura `src/main/resources/...` është layout-i standard i CPI).
   Nëse importi i drejtpërdrejtë i `.iflw` nuk pranohet nga tenant-i yt, krijo iFlow bosh
   me të njëjtin emër dhe shto komponentët sipas `docs/CATALOG_*.md` (§8), pastaj ngarko
   resurset (`xsd/`, `mapping/`, `script/`) si Resources.

## 3. Konfigurimi i parametrave (Externalized)
`IF_Salt_O2C_Order_Out` (shih `parameters.prop`):

| Parametër | Shembull | Kuptimi |
|---|---|---|
| `erp_logical_system` | ALBSALE_SALT | SNDPRN |
| `s4_logical_system` | ZS4CLNT100 | RCVPRN |
| `s4_idoc_url` | https://s4host:44300/sap/bc/idoc_xml | Endpoint IDoc |
| `s4_credential_alias` | S4_IDOC_USER | Security Material |
| `sales_org` / `distr_channel` / `division` / `order_type` | 1000 / 10 / 00 / TA | Org. SD |

`IF_Salt_O2C_Event_In`:

| Parametër | Shembull | Kuptimi |
|---|---|---|
| `erp_receive_event_url` | https://erp-host/erp/public/api/integration/receive_event.php | Endpoint ERP |
| `erp_inbound_token` | _(Secure Parameter)_ | = `SALT_INBOUND_TOKEN` në `.env` të ERP |

> **E rëndësishme:** `erp_inbound_token` në CI DUHET të jetë i njëjtë me `SALT_INBOUND_TOKEN`
> te `.env` i ERP-së — ndryshe `receive_event.php` kthen 401.

## 4. Security Material
- Krijo **User Credentials** `S4_IDOC_USER` (për IDoc receiver).
- Krijo **Secure Parameter** `erp_inbound_token`.
- Sender HTTPS: Client Certificate (rekomandohet) ose OAuth; shmang Basic në prod.

## 5. Testet (negative + positive)

### 5.1 Order Out (ERP → S/4)
- Në portal `myorders.php` zgjidh një klient, kliko **An SAP senden** (ose:
  `POST /erp/public/api/integration/send_order.php` me `{"idso":12}`).
- Prit: `salesorder.order_status = SENT`, rresht në `integration_outbox` = SENT,
  IDoc ORDERS05 i marrë në S/4 (WE02), Sales Order i krijuar.
- Negative: fik S/4 → prit HTTP 502 nga send_order, `integration_outbox.status = FAILED`.

### 5.2 Event In (S/4 → ERP)
- Trigger ORDRSP nga S/4 (ose simulo POST XML te `receive_event.php` me header
  `X-Inbound-Token`). Prit: `order_status = CONFIRMED`, rresht në `order_status_history`.
- Negative: token i gabuar → prit HTTP 401 `Invalid inbound token`.

### 5.3 Mostra të validuara (offline)
Mapping-et janë testuar me lxml/PHP në këtë repo:
- Canonical ORDER (nga `canonical.php`) → **valid** vs `OrderCanonical_v1.xsd`;
  → XSLT → **valid** vs `ORDERS05_subset.xsd`.
- IDoc ORDRSP/DESADV/INVOIC → XSLT → **valid** vs `EventCanonical_v1.xsd`;
  `receive_event.php` lexon saktë `DocumentType/CorrelationId/S4OrderId/ZINN/idso` + fushat specifike.

## 6. Gjurmimi end-to-end
Çelësi i korrelacionit është `CorrelationId = SALT-<ZINN>-<idso>-<rand>`, i pranishëm në:
ERP `integration_outbox.correlation_id` → MPL custom header në të dy iFlow-t → E1EDK01/BELNR
në IDoc → i echo-uar në ORDRSP → `order_status_history.correlation_id`.

## 7. Hapat pas Phase 1
- Phase 2: retry asinkron (JMS/Data Store) + OData ATP/stock.
- Phase 3: IDoc DELVRY03/INVOIC02 të plota.
- Value mapping ZINN↔KUNNR nëse numrat ndryshojnë nga S/4.
