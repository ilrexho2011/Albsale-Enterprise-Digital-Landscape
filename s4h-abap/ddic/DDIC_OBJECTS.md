# DDIC — Objektet fillestare (SE11)

Paketa (development class): **ZSALT_O2C** · Transport layer sipas sistemit · Namespace: `Z`.

## 1. Tabela transparente `ZSALT_CUST_XREF` — ZINN ↔ KUNNR
Harta e klientit të ERP-së (ZINN) me numrin e klientit të S/4 (KUNNR). Përdoret nga
`ZCL_SALT_O2C=>map_zinn_to_kunnr`.

| Fusha | Key | Data element | Tipi | Përshkrim |
|---|---|---|---|---|
| MANDT | X | MANDT | CLNT 3 | Klienti |
| ZINN | X | ZSALT_ZINN (char30) | CHAR 30 | Kodi ZINN i ERP-së |
| KUNNR |  | KUNNR | CHAR 10 | Sold-to (KNA1) |
| VKORG |  | VKORG | CHAR 4 | Sales org |
| VTWEG |  | VTWEG | CHAR 2 | Distribution channel |
| SPART |  | SPART | CHAR 2 | Division |
| CREATED_AT |  | TIMESTAMPL | DEC 21,7 | Kohë krijimi |

> Krijo data element `ZSALT_ZINN` (CHAR 30) me domain `CHAR30`.

## 2. Tabela transparente `ZSALT_RESV_LOG` — log i rezervimeve aATP
| Fusha | Key | Data element | Tipi |
|---|---|---|---|
| MANDT | X | MANDT | CLNT 3 |
| RESV_ID | X | ZSALT_RESV_ID (char30) | CHAR 30 |
| IDSO |  | ZSALT_IDSO (char10) | CHAR 10 |
| MATNR |  | MATNR | CHAR 40 |
| WERKS |  | WERKS_D | CHAR 4 |
| REQ_QTY |  | MENG13 | QUAN 13,3 |
| CONF_QTY |  | MENG13 | QUAN 13,3 |
| BACK_QTY |  | MENG13 | QUAN 13,3 |
| MEINS |  | MEINS | UNIT 3 |
| CONF_DATE |  | DATUM | DATS 8 |
| STATUS |  | ZSALT_RESV_STAT (char10) | CHAR 10 |  ("RESERVED/BACKORDER/FULFILLED")
| CREATED_AT |  | TIMESTAMPL | DEC 21,7 |

## 3. Struktura për OData (SE11) `ZSALT_S_ATP_RESULT`
Rezultati i `ConfirmAndReserve` (i mapuar në entitetin OData AtpResult).

| Fusha | Data element | Tipi |
|---|---|---|
| MATERIAL | MATNR | CHAR 40 |
| PLANT | WERKS_D | CHAR 4 |
| REQUESTEDQUANTITY | MENG13 | QUAN 13,3 |
| REQUESTEDDATE | DATUM | DATS 8 |
| CONFIRMEDQUANTITY | MENG13 | QUAN 13,3 |
| CONFIRMEDDATE | DATUM | DATS 8 |
| BACKORDERQTY | MENG13 | QUAN 13,3 |
| RESERVATIONID | ZSALT_RESV_ID | CHAR 30 |
| FULLYCONFIRMED | CHAR1 (abap_bool) | CHAR 1 |
| UNIT | MEINS | UNIT 3 |

Krijo edhe **table type** `ZSALT_TT_RETURN` = STANDARD TABLE OF `BAPIRET2` (për mesazhet).
