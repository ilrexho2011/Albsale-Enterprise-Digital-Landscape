*"* ===================================================================
*"* Function Module  Z_SALT_ATP_CONFIRM_RESERVE   (Function Group ZSALT_O2C)
*"* Processing type: Remote-Enabled Module (RFC)
*"* Qëllimi: aATP check + rezervim (logjik) për një material/plant/datë.
*"* Thirret nga OData ZSALT_ATP_SRV (ConfirmAndReserve) → IF_Salt_ATP_Reserve.
*"* -------------------------------------------------------------------
*"* Interface (defino në SE37):
*"*  IMPORTING
*"*    VALUE(IV_MATNR)    TYPE MATNR
*"*    VALUE(IV_WERKS)    TYPE WERKS_D
*"*    VALUE(IV_REQ_QTY)  TYPE MENG13
*"*    VALUE(IV_REQ_DATE) TYPE DATUM
*"*    VALUE(IV_MEINS)    TYPE MEINS      OPTIONAL
*"*    VALUE(IV_IDSO)     TYPE NUMC10     OPTIONAL
*"*    VALUE(IV_CHECK_RULE) TYPE CHECK_RULE DEFAULT 'A'
*"*  EXPORTING
*"*    VALUE(EV_RESV_ID)   TYPE ZSALT_RESV_ID
*"*    VALUE(EV_CONF_QTY)  TYPE MENG13
*"*    VALUE(EV_CONF_DATE) TYPE DATUM
*"*    VALUE(EV_BACK_QTY)  TYPE MENG13
*"*    VALUE(EV_FULLY)     TYPE ABAP_BOOL
*"*  TABLES
*"*    ET_RETURN STRUCTURE BAPIRET2
*"* ===================================================================
FUNCTION z_salt_atp_confirm_reserve.

  DATA: lt_wmdvsx TYPE STANDARD TABLE OF bapiwmdvs,
        lt_wmdvex TYPE STANDARD TABLE OF bapiwmdve,
        ls_wmdvsx TYPE bapiwmdvs,
        lv_unit   TYPE meins,
        lv_avail  TYPE meng13,
        lv_conf   TYPE meng13.

  CLEAR: ev_resv_id, ev_conf_qty, ev_conf_date, ev_back_qty, ev_fully.

  lv_unit = COND #( WHEN iv_meins IS NOT INITIAL THEN iv_meins ELSE 'TO' ).

  " 1) Kërkesa për ATP check (një pozicion)
  ls_wmdvsx-reqtime = iv_req_date.       " data e kërkuar
  ls_wmdvsx-requir  = iv_req_qty.        " sasia e kërkuar
  APPEND ls_wmdvsx TO lt_wmdvsx.

  " 2) ATP check standard
  CALL FUNCTION 'BAPI_MATERIAL_AVAILABILITY'
    EXPORTING
      plant        = iv_werks
      material     = iv_matnr
      unit         = lv_unit
      check_rule   = iv_check_rule
    IMPORTING
      av_qty_plant = lv_avail
    TABLES
      wmdvsx       = lt_wmdvsx
      wmdvex       = lt_wmdvex.

  " 3) Sasia e konfirmuar = COM_QTY nga rezultati; fallback = min(kërkesa, disponibël)
  READ TABLE lt_wmdvex INTO DATA(ls_ex) INDEX 1.
  IF sy-subrc = 0 AND ls_ex-com_qty IS NOT INITIAL.
    lv_conf = ls_ex-com_qty.
  ELSE.
    lv_conf = COND #( WHEN lv_avail < iv_req_qty THEN lv_avail ELSE iv_req_qty ).
  ENDIF.
  " Data e konfirmimit: data e kërkuar (për datë dinamike, zgjero me schedule-in e aATP).
  ev_conf_date = iv_req_date.

  IF lv_conf > iv_req_qty.
    lv_conf = iv_req_qty.
  ENDIF.
  ev_conf_qty = lv_conf.
  ev_back_qty = COND #( WHEN iv_req_qty > lv_conf THEN iv_req_qty - lv_conf ELSE 0 ).
  ev_fully    = COND #( WHEN ev_back_qty = 0 THEN abap_true ELSE abap_false ).

  " 4) Gjenero ID rezervimi dhe logo (rezervim logjik; për RESB reale shih shënimin)
  ev_resv_id = |RES-{ iv_idso }-{ sy-datum }{ sy-uzeit }|.

  INSERT zsalt_resv_log FROM @( VALUE #(
      mandt     = sy-mandt
      resv_id   = ev_resv_id
      idso      = iv_idso
      matnr     = iv_matnr
      werks     = iv_werks
      req_qty   = iv_req_qty
      conf_qty  = ev_conf_qty
      back_qty  = ev_back_qty
      meins     = lv_unit
      conf_date = ev_conf_date
      status    = COND #( WHEN ev_back_qty > 0 THEN 'BACKORDER' ELSE 'RESERVED' )
      created_at = |{ sy-datum }{ sy-uzeit }0000000| ) ).
  COMMIT WORK.

  zcl_salt_o2c=>add_message(
    EXPORTING iv_type = 'S'
              iv_message = |aATP: confirmed { ev_conf_qty } / requested { iv_req_qty }, backorder { ev_back_qty }|
    CHANGING  ct_return = et_return[] ).

  " ---- SHËNIM: rezervim REAL në MM (opsional) ----
  " Për një rezervim fizik (RESB) përdor BAPI_RESERVATION_CREATE1 me
  " movement type të përshtatshëm dhe vendos EV_RESV_ID = numri i rezervimit të kthyer.
  " Për aATP me BOP (Backorder Processing) të vërtetë, thirr metodat e aATP
  " (p.sh. klasa /SCMB/CL_ATP_* ose OData e aATP) sipas konfigurimit tënd.

ENDFUNCTION.
