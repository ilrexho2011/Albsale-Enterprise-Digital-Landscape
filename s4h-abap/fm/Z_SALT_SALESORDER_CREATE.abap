*"* ==============================================================================
*"* Function Module  Z_SALT_SALESORDER_CREATE  (Function Group ZSALT_O2C, RFC)
*"* Krijon Sales Order (BAPI_SALESORDER_CREATEFROMDAT2). Alternativë sinkrone
*"* ndaj IDoc ORDERS05; ruan CorrelationId te PURCH_NO_C (BSTKD) për gjurmim.
*"* ------------------------------------------------------------------------------
*"* IMPORTING
*"*   VALUE(IV_ZINN)     TYPE ZSALT_ZINN
*"*   VALUE(IV_MATNR)    TYPE MATNR
*"*   VALUE(IV_QTY)      TYPE KWMENG
*"*   VALUE(IV_MEINS)    TYPE VRKME  DEFAULT 'TO'
*"*   VALUE(IV_REQ_DATE) TYPE EDATU  DEFAULT SY-DATUM
*"*   VALUE(IV_CORRID)   TYPE CHAR35
*"*   VALUE(IV_AUART)    TYPE AUART  DEFAULT 'TA'
*"*   VALUE(IV_VKORG)    TYPE VKORG  DEFAULT '1000'
*"*   VALUE(IV_VTWEG)    TYPE VTWEG  DEFAULT '10'
*"*   VALUE(IV_SPART)    TYPE SPART  DEFAULT '00'
*"* EXPORTING
*"*   VALUE(EV_SALESDOC) TYPE VBELN
*"* TABLES
*"*   ET_RETURN STRUCTURE BAPIRET2
*"* =============================================================================
FUNCTION z_salt_salesorder_create.

  DATA: ls_header   TYPE bapisdhd1,
        lt_partners TYPE STANDARD TABLE OF bapiparnr,
        lt_items    TYPE STANDARD TABLE OF bapisditm,
        lt_sched    TYPE STANDARD TABLE OF bapischdl,
        lt_return   TYPE STANDARD TABLE OF bapiret2,
        lv_kunnr    TYPE kunnr.

  CLEAR ev_salesdoc.

  lv_kunnr = zcl_salt_o2c=>map_zinn_to_kunnr( iv_zinn ).

  ls_header-doc_type   = iv_auart.
  ls_header-sales_org  = iv_vkorg.
  ls_header-distr_chan = iv_vtweg.
  ls_header-division   = iv_spart.
  ls_header-purch_no_c = iv_corrid.        " CorrelationId -> BSTKD (per gjurmim)

  APPEND VALUE bapiparnr(
      partn_role = 'AG' partn_numb = lv_kunnr ) TO lt_partners.

  APPEND VALUE bapisditm(
      itm_number = '000010'
      material   = |{ iv_matnr ALPHA = IN }|
      target_qty = iv_qty
      target_qu  = iv_meins ) TO lt_items.

  APPEND VALUE bapischdl(
      itm_number = '000010'
      sched_line = '0001'
      req_qty    = iv_qty
      req_date   = iv_req_date ) TO lt_sched.

  CALL FUNCTION 'BAPI_SALESORDER_CREATEFROMDAT2'
    EXPORTING
      order_header_in     = ls_header
    IMPORTING
      salesdocument       = ev_salesdoc
    TABLES
      return              = lt_return
      order_items_in      = lt_items
      order_partners      = lt_partners
      order_schedules_in  = lt_sched.

  et_return[] = lt_return.

  IF line_exists( lt_return[ type = 'E' ] ) OR ev_salesdoc IS INITIAL.
    CALL FUNCTION 'BAPI_TRANSACTION_ROLLBACK'.
  ELSE.
    CALL FUNCTION 'BAPI_TRANSACTION_COMMIT' EXPORTING wait = abap_true.
  ENDIF.

ENDFUNCTION.
