# SEGW OData Service — ZSALT_ATP_SRV (Advanced ATP Reserve)

Ndërto në transaksionin **SEGW** (SAP Gateway Service Builder). Ky është i vetmi
shërbim OData custom që na duhet (aATP ConfirmAndReserve). Të tjerët ripërdoren prej sherbimeve standarde:
stock → `API_MATERIAL_STOCK_SRV`, A/R → `API_OPLACCTGDOCITEMCUBE_SRV`, availability → aATP standard.

## 1. Krijo projektin
- SEGW → **Create Project** → `ZSALT_ATP_SRV`, paketa `ZSALT_O2C`.

## 2. Data Model
### Entity Type: `AtpResult` (ABAP structure: `ZSALT_S_ATP_RESULT`)
Import nga DDIC structure ose krijo properties manualisht:

| Property | Edm type | Key | ABAP |
|---|---|---|---|
| Material | Edm.String |  | MATERIAL |
| Plant | Edm.String |  | PLANT |
| RequestedQuantity | Edm.Decimal |  | REQUESTEDQUANTITY |
| RequestedDate | Edm.DateTime |  | REQUESTEDDATE |
| ConfirmedQuantity | Edm.Decimal |  | CONFIRMEDQUANTITY |
| ConfirmedDate | Edm.DateTime |  | CONFIRMEDDATE |
| BackorderQty | Edm.Decimal |  | BACKORDERQTY |
| ReservationID | Edm.String | ✔ | RESERVATIONID |
| FullyConfirmed | Edm.Boolean |  | FULLYCONFIRMED |
| Unit | Edm.String |  | UNIT |

- Entity Set: **AtpResultSet** (nga AtpResult).

### Function Import: `ConfirmAndReserve`
- Return Entity Set: `AtpResultSet` · Return: **Entity** · HTTP Method: **POST**.
- Parameters:

| Name | Edm type | Nullable |
|---|---|---|
| Material | Edm.String | false |
| Plant | Edm.String | true |
| RequestedQuantity | Edm.Decimal | false |
| RequestedDate | Edm.DateTime | true |
| Idso | Edm.String | true |

## 3. Gjenero runtime artifacts
- **Generate** → klasat: MPC `ZCL_ZSALT_ATP_MPC` + `_EXT`, DPC `ZCL_ZSALT_ATP_DPC` + `_EXT`,
  Model `ZSALT_ATP_MDL`, Service `ZSALT_ATP_SRV`.
- Implemento `EXECUTE_ACTION` te **ZCL_ZSALT_ATP_DPC_EXT** (shih `class/ZCL_ZSALT_ATP_DPC_EXT.clas.abap`).

## 4. Regjistro & aktivizo
- `/IWFND/MAINT_SERVICE` → **Add Service** → System Alias `LOCAL` → zgjidh `ZSALT_ATP_SRV` → Add.
- Testo te `/IWFND/GW_CLIENT`:
  `POST /sap/opu/odata/sap/ZSALT_ATP_SRV/ConfirmAndReserve?Material='13455'&Plant='1000'&RequestedQuantity=50&RequestedDate=datetime'2026-09-01T00:00:00'`

## 5. Lidhja me CI
Në `IF_Salt_ATP_Reserve`, vendos `s4_odata_atp_url` =
`https://<s4host>/sap/opu/odata/sap/ZSALT_ATP_SRV` dhe resourcePath = `ConfirmAndReserve`.
