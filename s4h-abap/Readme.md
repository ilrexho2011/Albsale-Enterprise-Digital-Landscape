# s4h-abap — Shërbimet & kodi ABAP në S/4HANA 2023 (klasik)

Ana e **S/4HANA** e landscape-it O2C: shërbimet OData (SEGW), Function Modules (SE37),
klasat (SE24) dhe konfigurimi IDoc/ALE. Qasje **klasike**, me **ripërdorim të API-ve
standarde** dhe kod custom vetëm ku mungon (aATP ConfirmAndReserve).

## Struktura
```
s4h-abap/
├── docs/S4H_ABAP_GUIDE.md        # udhëzuesi kryesor (rendi, krijimi, konfigurimi, testet)
├── ddic/DDIC_OBJECTS.md          # tabela/strukturat/data elements (SE11)
├── class/
│   ├── ZCL_SALT_O2C.clas.abap            # helper (ZINN↔KUNNR, correlation, messages)
│   └── ZCL_ZSALT_ATP_DPC_EXT.clas.abap   # OData DPC_EXT: ConfirmAndReserve
├── fm/
│   ├── Z_SALT_ATP_CONFIRM_RESERVE.abap   # aATP check + rezervim (custom kyç)
│   ├── Z_SALT_PO_CREATE.abap             # BAPI_PO_CREATE1 (opsional/test)
│   ├── Z_SALT_GOODS_RECEIPT.abap         # BAPI_GOODSMVT_CREATE (GR 101)
│   └── Z_SALT_SALESORDER_CREATE.abap     # BAPI_SALESORDER_CREATEFROMDAT2
├── segw/ZSALT_ATP_SRV.model.md   # modeli OData custom + function import
└── idoc/correlation_id_enhancement.abap.md  # BSTKD/CorrelationId nëpër IDoc
```

## Fillon procesi i krijimit këtu
1. Lexohet `docs/S4H_ABAP_GUIDE.md` (§0 tabela reuse-vs-custom, §1 rendi i krijimit).
2. Krijo DDIC → ZCL_SALT_O2C → Function Modules → SEGW ZSALT_ATP_SRV.
3. Konfiguro IDoc/ALE (WE20/BD54/NACE) dhe lidh URL-të e OData/S4 me iFlow-t.

## Shënime
- I vetmi OData custom i domosdoshëm: **ZSALT_ATP_SRV** (ConfirmAndReserve). Pjesa tjetër standard.
- Wrapper-at `Z_SALT_*` janë RFC-enabled — të dobishëm për testim sinkron pa IDoc të plotë.
- Emrat e fushave të BAPI-ve mund të ndryshojnë pak sipas release/EHP — konfirmo në SE37/BAPI Explorer.
- Paketa `ZSALT_O2C`; përdorues teknik i dedikuar me autorizime minimale (jo SAP_ALL).

