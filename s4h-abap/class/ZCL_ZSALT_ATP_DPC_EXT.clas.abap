*"* ===================================================================
*"* Class ZCL_ZSALT_ATP_DPC_EXT  (gjenerohet nga SEGW; redefino EXECUTE_ACTION)
*"* Implementon Function Import 'ConfirmAndReserve' → FM Z_SALT_ATP_CONFIRM_RESERVE.
*"* Vetëm metoda e redefinuar tregohet këtu; pjesa tjetër e klasës gjenerohet.
*"* ===================================================================
CLASS zcl_zsalt_atp_dpc_ext DEFINITION
  PUBLIC
  INHERITING FROM zcl_zsalt_atp_dpc
  CREATE PUBLIC.

  PUBLIC SECTION.
    METHODS /iwbep/if_mgw_appl_srv_runtime~execute_action REDEFINITION.
ENDCLASS.


CLASS zcl_zsalt_atp_dpc_ext IMPLEMENTATION.

  METHOD /iwbep/if_mgw_appl_srv_runtime~execute_action.

    DATA: ls_result TYPE zsalt_s_atp_result,
          lt_return TYPE bapiret2_t,
          lv_matnr  TYPE matnr,
          lv_werks  TYPE werks_d,
          lv_qty    TYPE meng13,
          lv_date   TYPE datum,
          lv_idso   TYPE numc10.

    IF iv_action_name <> 'ConfirmAndReserve'.
      super->/iwbep/if_mgw_appl_srv_runtime~execute_action(
        EXPORTING iv_action_name = iv_action_name
                  it_parameter    = it_parameter
                  io_tech_request_context = io_tech_request_context
        IMPORTING er_data         = er_data ).
      RETURN.
    ENDIF.

    " --- Lexo parametrat e function import-it ---
    LOOP AT it_parameter INTO DATA(ls_p).
      CASE ls_p-name.
        WHEN 'Material'.          lv_matnr = |{ ls_p-value ALPHA = IN }|.
        WHEN 'Plant'.             lv_werks = ls_p-value.
        WHEN 'RequestedQuantity'. lv_qty   = ls_p-value.
        WHEN 'RequestedDate'.     lv_date  = ls_p-value(8).   " YYYYMMDD
        WHEN 'Idso'.              lv_idso  = |{ ls_p-value ALPHA = IN }|.
      ENDCASE.
    ENDLOOP.

    IF lv_werks IS INITIAL. lv_werks = '1000'. ENDIF.
    IF lv_date  IS INITIAL. lv_date  = sy-datum. ENDIF.

    " --- Thirr logjikën aATP ---
    CALL FUNCTION 'Z_SALT_ATP_CONFIRM_RESERVE'
      EXPORTING
        iv_matnr    = lv_matnr
        iv_werks    = lv_werks
        iv_req_qty  = lv_qty
        iv_req_date = lv_date
        iv_idso     = lv_idso
      IMPORTING
        ev_resv_id   = ls_result-reservationid
        ev_conf_qty  = ls_result-confirmedquantity
        ev_conf_date = ls_result-confirmeddate
        ev_back_qty  = ls_result-backorderqty
        ev_fully     = ls_result-fullyconfirmed
      TABLES
        et_return    = lt_return.

    ls_result-material          = lv_matnr.
    ls_result-plant             = lv_werks.
    ls_result-requestedquantity = lv_qty.
    ls_result-requesteddate     = lv_date.
    ls_result-unit              = 'TO'.

    " Nëse ka error, ktheje si mesazh biznesi OData
    IF line_exists( lt_return[ type = 'E' ] ).
      DATA(ls_err) = lt_return[ type = 'E' ].
      RAISE EXCEPTION TYPE /iwbep/cx_mgw_busi_exception
        EXPORTING textid = /iwbep/cx_mgw_busi_exception=>business_error
                  message = ls_err-message.
    ENDIF.

    " Kthe entitetin
    copy_data_to_ref( EXPORTING is_data = ls_result
                      CHANGING  cr_data = er_data ).

  ENDMETHOD.

ENDCLASS.
