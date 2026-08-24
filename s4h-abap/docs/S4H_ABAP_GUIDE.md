# S/4HANA 2023 — ABAP klasik & shërbimet për O2C (Albsale Vlora)

Qasja: **klasik** (SEGW + SE24/SE37 + IDoc), me **ripërdorim të API-ve standarde** dhe
kod custom **vetëm ku mungon**. Kjo dosje përmban artefaktet ABAP dhe udhëzuesit e krijimit.

---

## 0. Çfarë ripërdoret standard vs. çfarë ndërtohet custom

| Nevoja (iFlow) | Zgjidhja | ABAP custom? |
|---|---|---|
| Krijim Sales Order | IDoc **ORDERS05** inbound (standard) | Jo (opsional wrapper: `Z_SALT_SALESORDER_CREATE`) |
| Statuset O2C (ORDRSP/DELVRY03/INVOIC02) | IDoc outbound standard + output determination | Jo (enhancement opsional BSTKD) |
| EWM Goods Issue (SHPCON) | IDoc/output standard | Jo |
| Stock read (`IF_Salt_Stock_ATP_Query`) | **API_MATERIAL_STOCK_SRV** (standard) | Jo |
| Availability read (`IF_Salt_ATP_Check`) | aATP standard / **API_AVAILABILITY_INFORMATION_SRV** | Jo |
| A/R open items (`IF_Salt_Finance_Status`) | **API_OPLACCTGDOCITEMCUBE_SRV** (standard) | Jo |
| **aATP Confirm+Reserve** (`IF_Salt_ATP_Reserve`) | **CUSTOM** OData `ZSALT_ATP_SRV` | **Po** |
| Purchase Order create | IDoc **PORDCR** inbound (standard) | Jo (opsional wrapper: `Z_SALT_PO_CREATE`) |
| Goods Receipt | BAPI_GOODSMVT (standard) | Jo (opsional wrapper: `Z_SALT_GOODS_RECEIPT`) |

**Konkluzion:** i vetmi shërbim OData custom i domosdoshëm është **ZSALT_ATP_SRV**
(ConfirmAndReserve). Wrapper-at BAPI (`Z_SALT_*`) janë opsionalë — të dobishëm për **testim
sinkron** pa konfigurim të plotë IDoc, ose si alternativë sinkrone.

---

## 1. Objektet ABAP (rendi i krijimit)

Paketa: **ZSALT_O2C** (SE21/SE80). Function Group: **ZSALT_O2C** (SE37).

1. **DDIC** (`ddic/DDIC_OBJECTS.md`): data elements `ZSALT_ZINN`, `ZSALT_IDSO`,
   `ZSALT_RESV_ID`, `ZSALT_RESV_STAT`; tabelat `ZSALT_CUST_XREF`, `ZSALT_RESV_LOG`;
   struktura `ZSALT_S_ATP_RESULT`; table type `ZSALT_TT_RETURN`.
2. **Klasa** `ZCL_SALT_O2C` (SE24 / `class/ZCL_SALT_O2C.clas.abap`) — helper (map ZINN↔KUNNR,
   parse/make correlation, add_message).
3. **Function Modules** (SE37, RFC, `fm/*.abap`):
   `Z_SALT_ATP_CONFIRM_RESERVE`, `Z_SALT_PO_CREATE`, `Z_SALT_GOODS_RECEIPT`,
   `Z_SALT_SALESORDER_CREATE`.
4. **SEGW** `ZSALT_ATP_SRV` (`segw/ZSALT_ATP_SRV.model.md`) + redefino
   `ZCL_ZSALT_ATP_DPC_EXT` (`class/ZCL_ZSALT_ATP_DPC_EXT.clas.abap`).
5. **IDoc** (`idoc/correlation_id_enhancement.abap.md`) — verifikime + enhancement opsional.

> Çdo objekt në një transport request; aktivizo sipas radhës (DDIC → class → FM → SEGW).

---

## 2. Krijimi hap-pas-hapi

### 2.1 DDIC (SE11)
Krijo data elements dhe domains, pastaj tabelat/strukturat sipas `ddic/DDIC_OBJECTS.md`.
Aktivizo. (Për `ZSALT_CUST_XREF` fut disa rreshta test: ZINN↔KUNNR.)

### 2.2 Klasa ZCL_SALT_O2C (SE24 ose ADT)
Kopjo `class/ZCL_SALT_O2C.clas.abap`. Aktivizo.

### 2.3 Function Modules (SE37)
Për secilin: krijo FM në function group `ZSALT_O2C`, **Remote-Enabled**, defino interface-in
nga koment-blloku në krye të skedarit, ngjit trupin `FUNCTION...ENDFUNCTION`, aktivizo.

### 2.4 OData (SEGW) — shih `segw/ZSALT_ATP_SRV.model.md`
Ndërto modelin, gjenero, redefino `EXECUTE_ACTION`, regjistro te `/IWFND/MAINT_SERVICE`.

---

## 3. Konfigurimi IDoc/ALE (pa kod)

- **RFC destinations & ports:** SM59 (drejt CI ose reverse-proxy), WE21 (port XML/HTTP).
- **Logical systems & partner profiles:** BD54, WE20 (partner LS = `ALBSALE_SALT`/`ZS4CLNT100`).
  - Inbound: ORDERS05 (process code ORDE), PORDCR (PORD/ME10).
  - Outbound: ORDRSP, DELVRY03, INVOIC02, SHPCON, MBGMCR.
- **Output determination:** NACE për SD (ORDRSP/DELVRY/INVOIC) me medium **6 (EDI)**.
- **Portet e CI:** `/salt/orders`, `/salt/events`, `/salt/ewm`, `/salt/fi`, `/salt/asn`,
  `/salt/gr` — të lidhura me marrësit HTTP/IDoc në iFlow-t përkatëse.

---

## 4. Lidhja me iFlow-t (parametrat OData/S4)

| iFlow | Parametër CI | Vlera në S/4 |
|---|---|---|
| Stock_ATP_Query | `s4_odata_stock_url` | `/sap/opu/odata/sap/API_MATERIAL_STOCK_SRV` |
| ATP_Check | `s4_odata_atp_url` | aATP / `API_AVAILABILITY_INFORMATION_SRV` |
| Finance_Status | `s4_odata_ar_url` | `/sap/opu/odata/sap/API_OPLACCTGDOCITEMCUBE_SRV` |
| **ATP_Reserve** | `s4_odata_atp_url` | **`/sap/opu/odata/sap/ZSALT_ATP_SRV`** (custom) |
| Order_Out / PO_Send | `s4_idoc_url` | `/sap/bc/idoc_xml` (ose SOAP IDoc) |

Kredencialet: `S4_ODATA_USER` / `S4_IDOC_USER` (Security Material në CI) → përdorues teknik
në S/4 me autorizimet përkatëse (OData: `/IWFND/`, IDoc: `B_ALE_*`, BAPI: objektet e MM/SD).

---

## 5. Testet në S/4

1. **ATP reserve:** `/IWFND/GW_CLIENT` → POST `ConfirmAndReserve` → verifiko `ZSALT_RESV_LOG`.
2. **PO create:** `SE37 → Z_SALT_PO_CREATE` (Test/Execute) → ME23N.
3. **Goods Receipt:** `Z_SALT_GOODS_RECEIPT` → MB03; pastaj sigurohu që del MBGMCR outbound (WE05).
4. **Sales order:** `Z_SALT_SALESORDER_CREATE` → VA03; kontrollo VBKD-BSTKD = CorrelationId.
5. **IDoc:** WE19/WE02/WE05 për të ndjekur inbound/outbound.

---

## 6. Autorizimet minimale të përdoruesit teknik
- OData: `S_SERVICE`, `/IWFND/*` (`/IWFND/RT`).
- IDoc/ALE: `B_ALE_RECV`, `S_IDOCDEFT`.
- SD: `V_VBAK_AAT`, `V_VBAK_VKO`. MM: `M_BEST_*` (PO), `M_MSEG_*` (GR).
- Custom: `S_RFC` për function group `ZSALT_O2C`.

> Parim: përdorues teknik i dedikuar, autorizime minimale, jo SAP_ALL.
