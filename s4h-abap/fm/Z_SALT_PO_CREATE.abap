*"* =====================================================================================
*"* Function Module  Z_SALT_PO_CREATE   (Function Group ZSALT_O2C, RFC)
*"* Krijon Purchase Order (BAPI_PO_CREATE1). Alternativë sinkrone ndaj IDoc PORDCR.
*"* -------------------------------------------------------------------------------------
*"* IMPORTING
*"*   VALUE(IV_LIFNR)   TYPE LIFNR
*"*   VALUE(IV_BUKRS)   TYPE BUKRS   DEFAULT '1000'
*"*   VALUE(IV_EKORG)   TYPE EKORG   DEFAULT '1000'
*"*   VALUE(IV_EKGRP)   TYPE BKGRP   DEFAULT '001'
*"*   VALUE(IV_BSART)   TYPE ESART   DEFAULT 'NB'
*"*   VALUE(IV_MATNR)   TYPE MATNR
*"*   VALUE(IV_WERKS)   TYPE EWERK
*"*   VALUE(IV_QTY)     TYPE BSTMG
*"*   VALUE(IV_MEINS)   TYPE BSTME   DEFAULT 'TO'
*"*   VALUE(IV_NETPR)   TYPE BPREI   OPTIONAL
*"*   VALUE(IV_DELDATE) TYPE EINDT
*"*   VALUE(IV_WAERS)   TYPE WAERS   DEFAULT 'EUR'
*"* EXPORTING
*"*   VALUE(EV_PO_NUMBER) TYPE EBELN
*"* TABLES
*"*   ET_RETURN STRUCTURE BAPIRET2
*"* ====================================================================================
FUNCTION z_salt_po_create.

  DATA: ls_header  TYPE bapimepoheader,
        ls_headerx TYPE bapimepoheaderx,
        lt_item    TYPE STANDARD TABLE OF bapimepoitem,
        lt_itemx   TYPE STANDARD TABLE OF bapimepoitemx,
        lt_sched   TYPE STANDARD TABLE OF bapimeposchedule,
        lt_schedx  TYPE STANDARD TABLE OF bapimeposchedulx,
        lt_return  TYPE STANDARD TABLE OF bapiret2.

  CLEAR ev_po_number.

  ls_header-comp_code  = iv_bukrs.
  ls_header-doc_type   = iv_bsart.
  ls_header-vendor     = |{ iv_lifnr ALPHA = IN }|.
  ls_header-purch_org  = iv_ekorg.
  ls_header-pur_group  = iv_ekgrp.
  ls_header-currency   = iv_waers.
  ls_header-doc_date   = sy-datum.
  ls_headerx-comp_code = abap_true.
  ls_headerx-doc_type  = abap_true.
  ls_headerx-vendor    = abap_true.
  ls_headerx-purch_org = abap_true.
  ls_headerx-pur_group = abap_true.
  ls_headerx-currency  = abap_true.
  ls_headerx-doc_date  = abap_true.

  APPEND VALUE bapimepoitem(
      po_item   = '00010'
      material  = |{ iv_matnr ALPHA = IN }|
      plant     = iv_werks
      quantity  = iv_qty
      po_unit   = iv_meins
      net_price = iv_netpr ) TO lt_item.
  APPEND VALUE bapimepoitemx(
      po_item   = '00010' po_itemx = abap_true
      material  = abap_true plant = abap_true quantity = abap_true
      po_unit   = abap_true net_price = abap_true ) TO lt_itemx.

  APPEND VALUE bapimeposchedule(
      po_item = '00010' sched_line = '0001'
      delivery_date = iv_deldate quantity = iv_qty ) TO lt_sched.
  APPEND VALUE bapimeposchedulx(
      po_item = '00010' sched_line = '0001' po_itemx = abap_true
      sched_linex = abap_true delivery_date = abap_true quantity = abap_true ) TO lt_schedx.

  CALL FUNCTION 'BAPI_PO_CREATE1'
    EXPORTING
      poheader         = ls_header
      poheaderx        = ls_headerx
    IMPORTING
      exppurchaseorder = ev_po_number
    TABLES
      return           = lt_return
      poitem           = lt_item
      poitemx          = lt_itemx
      poschedule       = lt_sched
      poschedulex      = lt_schedx.

  et_return[] = lt_return.

  IF line_exists( lt_return[ type = 'E' ] ) OR ev_po_number IS INITIAL.
    CALL FUNCTION 'BAPI_TRANSACTION_ROLLBACK'.
  ELSE.
    CALL FUNCTION 'BAPI_TRANSACTION_COMMIT' EXPORTING wait = abap_true.
  ENDIF.

ENDFUNCTION.
