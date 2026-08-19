# SAP Cloud Integration — Phase 1 (O2C: ERP ↔ SAP CI ↔ S/4HANA)

Paketa e integrimit **AlbsaleVloraO2C** që lidh Custom ERP-në me SAP S/4HANA sipas
roadmap-it (Phase 1). Përmban dy iFlow-t, kontratat XSD, mapping-et XSLT dhe Groovy.

```
sap-ci/packages/AlbsaleVloraO2C/
├── IF_Salt_O2C_Order_Out/   # ERP -> CI -> S/4 (IDoc ORDERS05)
│   └── src/main/resources/{scenarioflows,mapping,xsd,script}, parameters.*
└── IF_Salt_O2C_Event_In/    # S/4 (ORDRSP/DESADV/INVOIC) -> CI -> ERP receive_event.php
    └── src/main/resources/{scenarioflows,mapping,xsd,script}, parameters.*
```

**Rrjedha:** `myorders.php → send_order.php → [HTTPS] → IF_Salt_O2C_Order_Out →
XSLT canonical→ORDERS05 → [IDoc] → S/4HANA` dhe kthimi
`S/4HANA IDoc → IF_Salt_O2C_Event_In → XSLT IDoc→canonical → [HTTP] → receive_event.php`.

Dokumentacioni i plotë: `../docs/CATALOG_IF_Salt_O2C_Order_Out.md`,
`../docs/CATALOG_IF_Salt_O2C_Event_In.md`, `../docs/PHASE1_DEPLOYMENT.md`.

> Çelësi i gjurmimit end-to-end: `CorrelationId = SALT-<ZINN>-<idso>-<rand>`.
> `erp_inbound_token` në CI = `SALT_INBOUND_TOKEN` te `.env` i ERP-së.
