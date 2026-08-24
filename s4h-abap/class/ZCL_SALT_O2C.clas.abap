*"* ===================================================================
*"* Class ZCL_SALT_O2C  ·  Paketa ZSALT_O2C
*"* Metoda ndihmëse të përbashkëta për integrimin O2C (Albsale Vlora).
*"* Krijo në SE24 ose ADT. Të gjitha metodat statike (CLASS-METHODS).
*"* ===================================================================
CLASS zcl_salt_o2c DEFINITION
  PUBLIC
  FINAL
  CREATE PUBLIC.

  PUBLIC SECTION.
    TYPES ty_zinn TYPE c LENGTH 30.

    "! Mapon ZINN (ERP) -> KUNNR (S/4). Fallback: kthen vetë ZINN nëse s'ka xref.
    CLASS-METHODS map_zinn_to_kunnr
      IMPORTING iv_zinn         TYPE clike
      RETURNING VALUE(rv_kunnr) TYPE kunnr.

    "! Mapon KUNNR -> ZINN. Fallback: kthen KUNNR.
    CLASS-METHODS map_kunnr_to_zinn
      IMPORTING iv_kunnr       TYPE kunnr
      RETURNING VALUE(rv_zinn) TYPE ty_zinn.

    "! Parson CorrelationId 'SALT-<zinn>-<idso>-<rand>'.
    CLASS-METHODS parse_correlation
      IMPORTING iv_corr TYPE clike
      EXPORTING ev_zinn TYPE ty_zinn
                ev_idso TYPE numc10.

    "! Ndërton CorrelationId (pa random-in, që e shton ERP-ja).
    CLASS-METHODS make_correlation
      IMPORTING iv_zinn        TYPE clike
                iv_idso        TYPE numc10
      RETURNING VALUE(rv_corr) TYPE string.

    "! Shton një rresht BAPIRET2 te tabela e mesazheve.
    CLASS-METHODS add_message
      IMPORTING iv_type    TYPE bapi_mtype DEFAULT 'S'
                iv_id      TYPE symsgid   DEFAULT 'ZSALT_O2C'
                iv_number  TYPE symsgno   DEFAULT '000'
                iv_message TYPE clike
      CHANGING  ct_return  TYPE bapiret2_t.
ENDCLASS.


CLASS zcl_salt_o2c IMPLEMENTATION.

  METHOD map_zinn_to_kunnr.
    DATA lv_kunnr TYPE kunnr.
    SELECT SINGLE kunnr FROM zsalt_cust_xref
      WHERE zinn = @iv_zinn INTO @lv_kunnr.
    IF sy-subrc = 0 AND lv_kunnr IS NOT INITIAL.
      rv_kunnr = lv_kunnr.
      RETURN.
    ENDIF.
    " Fallback: nëse ZINN është numër klienti me zero-padding, konvertoje.
    rv_kunnr = |{ iv_zinn ALPHA = IN }|.
  ENDMETHOD.

  METHOD map_kunnr_to_zinn.
    DATA lv_zinn TYPE ty_zinn.
    SELECT SINGLE zinn FROM zsalt_cust_xref
      WHERE kunnr = @iv_kunnr INTO @lv_zinn.
    IF sy-subrc = 0 AND lv_zinn IS NOT INITIAL.
      rv_zinn = lv_zinn.
    ELSE.
      rv_zinn = |{ iv_kunnr ALPHA = OUT }|.
    ENDIF.
  ENDMETHOD.

  METHOD parse_correlation.
    " Format: SALT-<zinn>-<idso>-<rand>
    DATA lt_parts TYPE STANDARD TABLE OF string.
    CLEAR: ev_zinn, ev_idso.
    SPLIT iv_corr AT '-' INTO TABLE lt_parts.
    " parts: [1]=SALT [2]=zinn [3]=idso(6 shifra) [4]=rand
    IF lines( lt_parts ) >= 3.
      ev_zinn = VALUE #( lt_parts[ 2 ] OPTIONAL ).
      DATA(lv_idso) = VALUE string( lt_parts[ 3 ] OPTIONAL ).
      ev_idso = |{ lv_idso ALPHA = IN }|.
    ENDIF.
  ENDMETHOD.

  METHOD make_correlation.
    rv_corr = |SALT-{ iv_zinn }-{ iv_idso }|.
  ENDMETHOD.

  METHOD add_message.
    APPEND VALUE bapiret2(
      type       = iv_type
      id         = iv_id
      number     = iv_number
      message    = iv_message
      message_v1 = iv_message ) TO ct_return.
  ENDMETHOD.

ENDCLASS.
