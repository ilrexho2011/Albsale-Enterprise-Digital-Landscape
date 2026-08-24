# CorrelationId në IDoc-t (ben gjurmimin end-to-end)

Gjithë gjurmimi mbështetet te `CorrelationId = SALT-<ZINN>-<idso>-<rand>`, i cili udhëton
si **referencë e blerësit (BSTKD)** përgjatë IDoc-ve. Në shumicën e rasteve kjo është
**standarde** — më poshtë ku të verifikosh dhe një enhancement opsional nëse mungon.

## 1. INBOUND ORDERS05 → Sales Order (automatik)
`send_order.php`/CI e vendos CorrelationId te `E1EDK01/BELNR` (dhe `E1EDK02 QUALF=001`).
Përpunimi standard `IDOC_INPUT_ORDERS` e mapon te **VBKD-BSTKD** (numri i porosisë së klientit).
→ Asnjë kod. Verifiko te WE20 (partner profile) + inbound process code `ORDE`.

## 2. OUTBOUND ORDRSP / DELVRY03 / INVOIC02 (zakonisht automatik)
Output-i standard i SD e përfshin `BSTKD` te `E1EDK02 QUALF=001 / BELNR`. ERP-ja (mapping-et
`*_to_Canonical.xsl`) e nxjerr CorrelationId nga fusha që fillon me `SALT-`.
→ Verifiko output determination (NACE) + partner profile outbound.

## 3. Enhancement opsional — sigurimi i BSTKD në outbound
Nëse për ndonjë skenar BSTKD nuk del në IDoc, përdor **customer-exit** të IDoc-ut:
- ORDERS/ORDRSP: `EXIT_SAPLVEDC_002` (outbound) — enhancement projekt SMOD `VSV00001`.
- Delivery (DESADV): `EXIT_SAPLV56K_002`.
- Invoice (INVOIC): `EXIT_SAPLVEDF_002`.

Shembull (outbound ORDRSP — sigurohu që E1EDK02/BSTKD është i mbushur):

```abap
" Include ZXVEDCU02 (EXIT_SAPLVEDC_002), për segmentet e IDoc-ut dalës.
DATA: ls_e1edk02 TYPE e1edk02.
FIELD-SYMBOLS <fs_edidd> TYPE edidd.

" Sigurohu që ekziston E1EDK02 me QUALF='001' dhe BELNR = BSTKD i porosisë.
READ TABLE int_edidd ASSIGNING <fs_edidd>
     WITH KEY segnam = 'E1EDK02'.
IF sy-subrc <> 0 AND xvbak-bstnk IS NOT INITIAL.
  CLEAR ls_e1edk02.
  ls_e1edk02-qualf = '001'.
  ls_e1edk02-belnr = xvbak-bstnk.        " referenca e blerësit = CorrelationId
  APPEND VALUE edidd( segnam = 'E1EDK02'
                      sdata  = ls_e1edk02 ) TO int_edidd.
ENDIF.
```

## 4. MBGMCR (Goods Receipt) → GR
IDoc-u i GR-së mban `PO_NUMBER` (numrin e PO-së sonë `PO-<...>`). Mapping-u `MBGMCR_to_GR.xsl`
e lexon dhe ERP-ja e lidh me PO-në. → Asnjë kod; sigurohu që outbound MBGMCR është aktiv (te
konfigurimi i output-it të lëvizjes së mallit ose një job që e gjeneron).

> Këshillë: mbaj gjithnjë të njëjtin çelës (BSTKD) nga porosia deri te fatura — kështu
> `receive_event.php` gjen `idso` nga `SALT-<zinn>-<idso>-<rand>` pa varësi nga numrat e S/4.
```
