# Phase 2 — Reliable Async Messaging + ATP/Stock (OData)

Zgjeron rrjedhën O2C të Phase 1 me **dërgim të besueshëm asinkron** (JMS + retry +
dead-letter) dhe **kontroll disponueshmërie ATP/stock** nga S/4HANA përmes OData.

## 1. Pse Phase 2
Në Phase 1, `send_order.php` e thërriste CI-n **në mënyrë sinkrone**: nëse S/4 ose CI
ishin jashtë loje, porosia dështonte në çast. Phase 2 e shkëput ERP-në nga S/4:
porosia pranohet gjithmonë (202), ruhet në outbox, dhe dërgohet me **retry të garantuar**.
Gjithashtu, klienti mund të shohë **stokun/ATP-në real** përpara se të porosisë.

## 2. Arkitektura e mesazhimit të besueshëm

```
myorders.php
   │  (POST idso)
   ▼
enqueue_order.php ──► integration_outbox (PENDING)         [ERP: transactional outbox]
   │  202 Accepted                     ▲
   │                                   │ retry me backoff
   ▼                                   │
[HTTPS /salt/orders/async]        dispatch_outbox.php  ◄── cron çdo 1 min
   ▼                                   (PENDING/FAILED -> SENT/DEAD)
IF_Salt_O2C_Order_Enqueue ──► JMS queue 'salt.orders'      [CI: decoupling]
                                       │
                                       ▼
                    IF_Salt_O2C_Order_Consumer  (JMS retry 5x, backoff)
                                       │  XSLT canonical->ORDERS05
                                       ▼
                                   S/4HANA (IDoc)
                                       │  dështim i përhershëm
                                       ▼
                          Data Store 'salt_orders_dlq' (dead-letter) + alert
```

**Dy nivele mbrojtjeje (defense in depth):**
1. **ERP-anë:** outbox + `dispatch_outbox.php` me backoff eksponencial
   (30s → 60s → 120s → 240s → 480s; pas 6 përpjekjesh → `status=DEAD`). ~15.5 min dritare.
2. **CI-anë:** JMS retry (5x, exponential) + Data Store dead-letter për riproçesim manual.

Idempotenca ruhet nga `integration_outbox.correlation_id` UNIQUE dhe `CorrelationId`
i njëjtë përgjatë gjithë zinxhirit — ridërgimet nuk krijojnë porosi të dyfishta.

## 3. Kontrolli ATP/Stock (OData)

```
saltsearch / salesorder / myorders
   │  GET saltcode
   ▼
check_stock.php ──(cache TTL 300s? )──► stock_cache      [ERP cache]
   │ miss/refresh
   ▼
[HTTPS /salt/stock?material=..&plant=..]
   ▼
IF_Salt_Stock_ATP_Query
   │  OData Query (Request-Reply)
   ▼
S/4HANA · API_MATERIAL_STOCK_SRV (A_MatlStkInAcctMod)
   ▲
   └─ përgjigje -> JSON kanonik {material, plant, availableQuantity, unit, atpQuantity}
```

- Cache në `stock_cache` me TTL (default 300s) redukton thirrjet drejt S/4.
- Në rast që S/4/CI s'përgjigjen, `check_stock.php` kthen cache-n e vjetër (`stale:true`).
- ATP i thjeshtuar = sasia në magazinë; për **Advanced ATP** të vërtetë, ndrysho iFlow-in
  drejt OData-s së aATP (`API_AVAILABILITY_...`) — struktura mbetet e njëjtë.

## 4. Objektet e reja

### CI (paketa AlbsaleVloraO2C)
| iFlow | Rol | Sender | Receiver |
|---|---|---|---|
| IF_Salt_O2C_Order_Enqueue | Producer (202 + JMS write) | HTTPS `/salt/orders/async` | JMS `salt.orders` |
| IF_Salt_O2C_Order_Consumer | Consumer (JMS retry → IDoc) | JMS `salt.orders` | IDoc S/4 + Data Store DLQ |
| IF_Salt_Stock_ATP_Query | ATP/Stock (OData) | HTTPS `/salt/stock` | OData S/4 |

### ERP (dosja erp/)
| Skedar | Rol |
|---|---|
| `sql/03_phase2.sql` | outbox +backoff fields, `stock_cache` |
| `src/Lib/http.php` | helper cURL |
| `src/Lib/outbox.php` | logjika outbox (build/upsert/try_send + backoff/dead-letter) |
| `public/api/integration/enqueue_order.php` | pranon porosinë (async, 202) |
| `public/api/integration/dispatch_outbox.php` | worker cron me retry |
| `public/api/integration/check_stock.php` | ATP/stock me cache |

## 5. Deployment

### CI
1. Importo 3 iFlow-t e reja në paketën `AlbsaleVloraO2C`.
2. Krijo **JMS queue** `salt.orders` (Manage Message Queues) dhe **Data Store** `salt_orders_dlq`.
3. Konfiguro Externalized Parameters (shih `parameters.prop` të secilit) + Security Material
   `S4_IDOC_USER`, `S4_ODATA_USER`.
4. Aktivo aATP/OData: sigurohu që `API_MATERIAL_STOCK_SRV` është i publikuar në S/4 Gateway.

### ERP
1. Ngarko `sql/03_phase2.sql`.
2. Plotëso `.env`: `CPI_ORDERS_ASYNC_URL`, `CPI_STOCK_URL`, `OUTBOX_*`, `STOCK_CACHE_TTL`, `DISPATCH_TOKEN`.
3. Konfiguro cron për dispatcher-in (çdo minutë):
   ```cron
   * * * * * php /path/erp/public/api/integration/dispatch_outbox.php >> /var/log/salt_dispatch.log 2>&1
   ```
   (ose HTTP: `GET /erp/public/api/integration/dispatch_outbox.php?token=DISPATCH_TOKEN`)

## 6. Testet

### Reliable messaging
- **Happy path:** `POST enqueue_order.php {idso:12}` → 202, outbox `SENT` (ose `QUEUED→SENT`),
  JMS → consumer → IDoc në S/4.
- **S/4 jashtë loje:** fik receiver-in → outbox kalon `FAILED` me `next_attempt_at` që rritet
  (30→60→120s…); pas 6 përpjekjesh → `DEAD`; në CI, mesazhi shkon në `salt_orders_dlq`.
- **Idempotencë:** thirr enqueue dy herë me të njëjtin idso → një rresht i vetëm (UNIQUE corr).

### ATP/Stock
- `GET check_stock.php?saltcode=13455` → JSON me `available_qty`, ruhet në `stock_cache`.
- Thirrje e dytë brenda TTL → `cached:true` (pa thirrje S/4).
- S/4 jashtë loje → nëse ka cache → `stale:true`; ndryshe 502.

### Verifikime offline (në këtë repo)
- Backoff: 30/60/120/240/480s, pastaj DEAD — konfirmuar.
- Mapping OData→JSON kanonik: `MatlWrhsStkQtyInMatlBaseUnit`→`availableQuantity` — konfirmuar.
- Të gjithë skedarët PHP: `php -l` pa gabime; iFlw/XSL/XSD well-formed.

## 7. Hapat pas Phase 2
- Phase 3: IDoc DELVRY03/INVOIC02 të plota (delivery/billing).
- aATP i vërtetë + rezervim stoku.
- Alerting AEM mbi `salt_orders_dlq` dhe `order_status=DEAD`.
