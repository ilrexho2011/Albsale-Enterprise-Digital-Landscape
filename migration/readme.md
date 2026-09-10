# SAP Integration Suite — Import Guide (AlbsaleVloraO2C)

## Ky udhëzues shpjegon si vihen në punë 16 iFlow-t e projektit në SAP Cloud Integration.

---

1. Resurset janë 100% të ripërdorshme:
2. mapping-et (`\\\*.xsl`), skemat (`\\\*.xsd`),
3. skriptet Groovy (`\\\*.groovy`) dhe parametrat — i ngarkon drejtpërdrejt në një iFlow pa ndryshim.
`.iflw`-të janë blueprint besnikë, jo eksporte nga Web UI. U mungon seksioni i diagramit
(koordinatat BPMN shape/edge) dhe disa `componentVersion`/`cmdVariantUri` që i vë vetë mjeti.

Prandaj:
Disa tenant-e e pranojnë importin e ZIP-it dhe e hapin iFlow-in (pa layout, por të plotë).
Të tjerë e refuzojnë ose e hapin me komponentë të papozicionuar → duhet rirregullim.

Rekomandimi praktik: provo importin e ZIP-it (Rruga A). 
Nëse dështon për një iFlow, kalo te Rruga B (ndërto guaskën në Web UI dhe ngarko resurset) — të dyja të dokumentuara më poshtë.
---

1. Parakushtet në tenant (KRIJO KËTO NJË HERË)
Pa këto, iFlow-t importohen por nuk deploy-ohen:

Objekt	Emri	Përdoret nga

- Integration Package	`AlbsaleVloraO2C`	të gjitha iFlow-t
- JMS Queue	`salt.orders`	Order_Enqueue / Order_Consumer
- Data Store	`salt\\\_orders\\\_dlq`	Order_Consumer (dead-letter)
- Security Material (User Credentials)	`S4\\\_IDOC\\\_USER`	të gjithë IDoc receiver-at
- Security Material (User Credentials)	`S4\\\_ODATA\\\_USER`	të gjithë OData receiver-at
- Security Material (Secure Parameter)	`erp\\\_inbound\\\_token`	të gjithë endpoint-et INBOUND te ERP
- Security Material	`AEM\\\_BROKER\\\_CRED`	Event_Publish (AMQP)
Advanced Event Mesh	topik `albsale/o2c/\\\*`	Event_Publish
Mail (SMTP)	host + `ops\\\_email`	Monitoring_Collector
JDBC Data Source	`HANA\\\_CLOUD\\\_ANALYTICS`	Analytics_Replicate
OData services (S/4 Gateway, të publikuara)	`API\\\_MATERIAL\\\_STOCK\\\_SRV`, `API\\\_AVAILABILITY\\\_INFORMATION\\\_SRV`, `API\\\_OPLACCTGDOCITEMCUBE\\\_SRV`	Stock/ATP/Finance
aATP action	`ConfirmAndReserve`	ATP_Reserve
`erp\\\_inbound\\\_token` (CI) DUHET të jetë i njëjtë me `SALT\\\_INBOUND\\\_TOKEN` te `.env` i ERP-së.
---
2. Rruga A — Import i drejtpërdrejtë i ZIP-it (provë)
Integration Suite → Design → hap/krijo paketën `AlbsaleVloraO2C`.
Artifacts → Add → Integration Flow → Upload → zgjidh `sap-ci/import-zips/<iflow>.zip`.
Nëse hapet: Configure parametrat e jashtëm (shih §4), pastaj Save → Deploy.
Nëse importi dështon me gabim validimi/BPMN → kalo te Rruga B për atë iFlow.
> Këshillë: fillo me një iFlow të thjeshtë (p.sh. `IF\\\_Salt\\\_Stock\\\_ATP\\\_Query`) për ta parë si sillet importuesi yt.
---
3. Rruga B — Ndërto guaskën në Web UI + ngarko resurset (i sigurt)
Për çdo iFlow, hape `docs/CATALOG\\\_<iflow>.md` (seksioni §4–8 "Flow & Processing") dhe replikoje:
Krijo iFlow të ri në paketë me të njëjtin emër.
Sender/Receiver: shto pjesëmarrësit dhe adaptorët sipas tabelës në §5 më poshtë.
Hapat e procesit: shto Content Modifier / Router / Message Mapping / Script / Request-Reply
sipas rrjedhës në katalog.
Ngarko resurset (nga ZIP-i i po atij iFlow ose nga dosja):
Message Mapping (XSLT): shto një "XSLT Mapping" step → Upload `mapping/\\\*.xsl`.
Schemas: referoji `xsd/\\\*.xsd` aty ku duhen (mapping source/target).
Groovy: shto "Script" step → Upload `script/\\\*.groovy` (funksioni `processData`).
Externalized Parameters: vendos vlerat nga `src/main/resources/parameters.prop`.
Save → Deploy.
Resurset janë të njëjta si te Rruga A, thjesht i lidh manualisht — kjo është pjesa më e madhe e punës dhe është gati.
---
4. Externalized Parameters (nga `parameters.prop` i secilit)
Vendos vlerat reale të tenant-it/S4 para deploy-it. Shembuj kyç:
S4 IDoc: `s4\\\_idoc\\\_url`, `s4\\\_credential\\\_alias=S4\\\_IDOC\\\_USER`, `s4\\\_logical\\\_system=ZS4CLNT100`
Org SD (Order_Out/Consumer): `sales\\\_org=1000`, `distr\\\_channel=10`, `division=00`, `order\\\_type=TA`
Org MM (PO_Send): `purch\\\_org=1000`, `purch\\\_group=001`, `po\\\_type=NB`
ERP endpoints (INBOUND): `erp\\\_receive\\\_event\\\_url`, `erp\\\_warehouse\\\_url`, `erp\\\_finance\\\_url`,
`erp\\\_asn\\\_url`, `erp\\\_gr\\\_url`, `erp\\\_monitor\\\_url`, `erp\\\_extract\\\_url` + `erp\\\_inbound\\\_token`
OData: `s4\\\_odata\\\_stock\\\_url`, `s4\\\_odata\\\_atp\\\_url`, `s4\\\_odata\\\_ar\\\_url`, `s4\\\_odata\\\_credential=S4\\\_ODATA\\\_USER`
AEM/HANA/Mail: `aem\\\_credential`, `hana\\\_jdbc\\\_alias`, `smtp\\\_host`, `ops\\\_email`
---
5. Përmbledhje adaptorësh & rrugësh (16 iFlow)
iFlow	Sender	Receiver	Endpoint / Objekt

IF_Salt_O2C_Order_Out	HTTPS `/salt/orders`	IDoc → S/4	ORDERS05

IF_Salt_O2C_Order_Enqueue	HTTPS `/salt/orders/async`	JMS `salt.orders`	202

IF_Salt_O2C_Order_Consumer	JMS `salt.orders`	IDoc → S/4 (+DataStore DLQ)	retry 5×

IF_Salt_O2C_Event_In	IDoc `/salt/events`	HTTP → `receive\\\_event.php`	router MESTYP

IF_Salt_Stock_ATP_Query	HTTPS `/salt/stock`	OData → S/4	API_MATERIAL_STOCK_SRV

IF_Salt_ATP_Check	HTTPS `/salt/atp`	OData → S/4	API_AVAILABILITY_INFORMATION_SRV

IF_Salt_EWM_Event_In	IDoc `/salt/ewm`	HTTP → `receive\\\_warehouse.php`	SHPCON

IF_Salt_FI_Event_In	HTTPS `/salt/fi`	HTTP → `receive\\\_finance.php`	FI event

IF_Salt_Finance_Status	HTTPS `/salt/finance`	OData → S/4	API_OPLACCTGDOCITEMCUBE_SRV

IF_Salt_Event_Publish	HTTPS `/salt/event/publish`	AMQP → AEM	topik `albsale/o2c/\\\*`

IF_Salt_Monitoring_Collector	HTTPS `/salt/alert`	HTTP → ERP + Mail	`monitor\\\_alert.php`

IF_Salt_Analytics_Replicate	Timer (15 min)	HTTP → ERP; JDBC → HANA	`extract.php`

IF_Salt_PO_Send	HTTPS `/salt/po`	IDoc → S/4 MM	PORDCR

IF_Salt_Supplier_ASN_In	IDoc `/salt/asn`	HTTP → `receive\\\_asn.php`	ORDRSP (LF)

IF_Salt_GR_In	IDoc `/salt/gr`	HTTP → `receive\\\_goodsreceipt.php`	MBGMCR

IF_Salt_ATP_Reserve	HTTPS `/salt/atp/reserve`	OData action → S/4	ConfirmAndReserve

---
6. Renditja e rekomanduar e deploy-it
Bërthama O2C: Order_Out → Event_In → (test një porosi end-to-end).
Besueshmëria: Order_Enqueue + Order_Consumer (kërko JMS+DataStore).
Availability: Stock_ATP_Query → ATP_Check → ATP_Reserve.
Fulfillment/Finance: EWM_Event_In → FI_Event_In → Finance_Status.
Procurement: PO_Send → Supplier_ASN_In → GR_In.
Platforma: Event_Publish → Monitoring_Collector → Analytics_Replicate.
---
7. Verifikim pas deploy-it
Runtime → Manage Integration Content: iFlow = Started.
Monitor Message Processing (MPL): kërko header `CorrelationId = SALT-<ZINN>-<idso>-<rand>`.
Testo një thirrje nga ERP (p.sh. `myorders.php` → "An SAP senden") dhe ndiq rrjedhën në MPL.
> Nëse një `.iflw` s'importohet, kjo NUK është humbje: katalogu + resurset e ZIP-it e bëjnë
> rikrijimin në Web UI çështje minutash për iFlow.
