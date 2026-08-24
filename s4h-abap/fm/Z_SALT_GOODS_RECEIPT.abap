*"* ===============================================================================
*"* Function Module  Z_SALT_GOODS_RECEIPT   (Function Group ZSALT_O2C, RFC)
*"* Poston Goods Receipt për një PO (BAPI_GOODSMVT_CREATE, movement 101).
*"* Rezultati (MAT_DOC) shkon si IDoc MBGMCR → IF_Salt_GR_In → ERP (rimbush stok).
*"* -------------------------------------------------------------------------------
*"* IMPORTING
*"*   VALUE(IV_PO_NUMBER) TYPE EBELN
*"*   VALUE(IV_PO_ITEM)   TYPE EBELP DEFAULT '00010'
*"*   VALUE(IV_MATNR)     TYPE MATNR
*"*   VALUE(IV_WERKS)     TYPE WERKS_D
*"*   VALUE(IV_LGORT)     TYPE LGORT_D DEFAULT '0001'
*"*   VALUE(IV_QTY)       TYPE ERFMG
*"*   VALUE(IV_MEINS)     TYPE ERFME  DEFAULT 'TO'
*"*   VALUE(IV_BATCH)     TYPE CHARG_D OPTIONAL
*"*   VALUE(IV_PSTDAT)    TYPE BUDAT  DEFAULT SY-DATUM
*"* EXPORTING
*"*   VALUE(EV_MAT_DOC)   TYPE MBLNR
*"*   VALUE(EV_MAT_YEAR)  TYPE MJAHR
*"* TABLES
*"*   ET_RETURN STRUCTURE BAPIRET2
*"* ===============================================================================
FUNCTION z_salt_goods_receipt.

  DATA: ls_header  TYPE bapi2017_gm_head_01,
        ls_code    TYPE bapi2017_gm_code,
        ls_headret TYPE bapi2017_gm_head_ret,
        lt_item    TYPE STANDARD TABLE OF bapi2017_gm_item_create,
        lt_return  TYPE STANDARD TABLE OF bapiret2.

  CLEAR: ev_mat_doc, ev_mat_year.

  ls_header-pstng_date = iv_pstdat.
  ls_header-doc_date   = sy-datum.
  ls_header-header_txt = |GR { iv_po_number }|.
  ls_code-gm_code      = '01'.                 " GR për Purchase Order

  APPEND VALUE bapi2017_gm_item_create(
      material   = |{ iv_matnr ALPHA = IN }|
      plant      = iv_werks
      stge_loc   = iv_lgort
      move_type  = '101'
      mvt_ind    = 'B'                          " GR për PO
      entry_qnt  = iv_qty
      entry_uom  = iv_meins
      po_number  = iv_po_number
      po_item    = iv_po_item
      batch      = iv_batch ) TO lt_item.

  CALL FUNCTION 'BAPI_GOODSMVT_CREATE'
    EXPORTING
      goodsmvt_header  = ls_header
      goodsmvt_code    = ls_code
    IMPORTING
      goodsmvt_headret = ls_headret
    TABLES
      goodsmvt_item    = lt_item
      return           = lt_return.

  et_return[] = lt_return.

  IF line_exists( lt_return[ type = 'E' ] ).
    CALL FUNCTION 'BAPI_TRANSACTION_ROLLBACK'.
  ELSE.
    CALL FUNCTION 'BAPI_TRANSACTION_COMMIT' EXPORTING wait = abap_true.
    ev_mat_doc  = ls_headret-mat_doc.
    ev_mat_year = ls_headret-doc_year.
  ENDIF.

ENDFUNCTION.
