# SAP API Management — AlbsaleSaltAPI

Fronton API-t publike O2C (te SAP Cloud Integration) me siguri, kufizim dhe analitikë.

## Përmbajtja
- `AlbsaleSaltAPI/apiproxy/` — API proxy (Apigee/SAP APIM format): proxy + target + policies.
- `openapi/albsale-salt-api.yaml` — kontrata OpenAPI 3.0 (portali i zhvilluesit).
- `products/albsale-o2c-product.json` — API Product (quota, scopes, approval manual).

## Politikat (PreFlow request)
1. **Spike-Arrest** — 20 kërkesa/sekondë për client.
2. **Verify-API-Key** — header `X-Apikey` (nga developer app).
3. **Quota-Business** — 10000/ditë sipas produktit.
4. **Remove-API-Key-Header** — s'e përcjell çelësin te backend-i.
- Response: **CORS** i kontrolluar (origjina nga variabla `apim.allowed.origin`).

## Base path & backend
- Publik: `https://api.albsalevlora.al/albsale/o2c/v1`
- Backend (target): SAP CI `.../http/salt` me OAuth2 client credentials (ref `albsale.cpi.oauth` në KVM).

## Rrugët (routes)
| Metoda | Path | Backend CI |
|---|---|---|
| POST | /orders | /http/salt/orders/async |
| POST | /atp | /http/salt/atp |
| GET | /stock | /http/salt/stock |
| GET | /finance | /http/salt/finance |

## Deployment
1. Importo bundle-in `AlbsaleSaltAPI` në SAP APIM (API Portal).
2. Krijo KVM `albsale.cpi.oauth` (client credentials te CI); variabël `apim.allowed.origin`.
3. Krijo API Product nga `products/albsale-o2c-product.json`; publiko OpenAPI në Developer Portal.
4. Krijo Developer + App → merr API Key → testo me header `X-Apikey`.

> Analitika: APIM regjistron latency/traffic/error rate për çdo route (Analytics dashboard).
> MOS vendos sekrete në proxy; përdor KVM + Secure Store.
