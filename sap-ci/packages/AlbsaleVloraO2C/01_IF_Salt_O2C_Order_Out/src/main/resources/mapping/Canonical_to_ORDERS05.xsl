<?xml version="1.0" encoding="UTF-8"?>
<!--
  Canonical_to_ORDERS05.xsl
  Harton porosinë kanonike (urn:albsale:o2c:canonical:1.0 / OrderCreate)
  në IDoc ORDERS05 (message type ORDERS) për krijim Sales Order në S/4HANA.

  Parametrat organizativë kalohen nga iFlow (Content Modifier -> exchange properties):
    p_sndprn  logical system i dërguesit (ERP)      -> EDI_DC40/SNDPRN
    p_rcvprn  logical system i marrësit (S/4)        -> EDI_DC40/RCVPRN
    p_auart   lloji i porosisë (p.sh. TA/OR)         -> E1EDK14 QUALF 012
    p_vkorg   Sales Organization                     -> E1EDK14 QUALF 008
    p_vtweg   Distribution Channel                   -> E1EDK14 QUALF 007
    p_spart   Division                               -> E1EDK14 QUALF 006
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:o2c="urn:albsale:o2c:canonical:1.0"
                exclude-result-prefixes="o2c">

  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

  <xsl:param name="p_sndprn" select="'ALBSALE_SALT'"/>
  <xsl:param name="p_rcvprn" select="'ZS4CLNT100'"/>
  <xsl:param name="p_auart"  select="'TA'"/>
  <xsl:param name="p_vkorg"  select="'1000'"/>
  <xsl:param name="p_vtweg"  select="'10'"/>
  <xsl:param name="p_spart"  select="'00'"/>

  <xsl:template match="/o2c:OrderCreate">
    <ORDERS05>
      <IDOC BEGIN="1">
        <EDI_DC40 SEGMENT="1">
          <TABNAM>EDI_DC40</TABNAM>
          <DIRECT>2</DIRECT>
          <IDOCTYP>ORDERS05</IDOCTYP>
          <MESTYP>ORDERS</MESTYP>
          <SNDPRT>LS</SNDPRT>
          <SNDPRN><xsl:value-of select="$p_sndprn"/></SNDPRN>
          <RCVPRT>LS</RCVPRT>
          <RCVPRN><xsl:value-of select="$p_rcvprn"/></RCVPRN>
        </EDI_DC40>

        <E1EDK01 SEGMENT="1">
          <CURCY><xsl:value-of select="o2c:Summary/o2c:Currency"/></CURCY>
          <!-- Referenca e blerësit = CorrelationId (gjurmim end-to-end) -->
          <BELNR><xsl:value-of select="o2c:Header/o2c:CorrelationId"/></BELNR>
          <BSART><xsl:value-of select="$p_auart"/></BSART>
        </E1EDK01>

        <!-- Kualifikuesit organizativë -->
        <E1EDK14 SEGMENT="1"><QUALF>012</QUALF><ORGID><xsl:value-of select="$p_auart"/></ORGID></E1EDK14>
        <E1EDK14 SEGMENT="1"><QUALF>008</QUALF><ORGID><xsl:value-of select="$p_vkorg"/></ORGID></E1EDK14>
        <E1EDK14 SEGMENT="1"><QUALF>007</QUALF><ORGID><xsl:value-of select="$p_vtweg"/></ORGID></E1EDK14>
        <E1EDK14 SEGMENT="1"><QUALF>006</QUALF><ORGID><xsl:value-of select="$p_spart"/></ORGID></E1EDK14>

        <!-- Partneri Sold-to (AG) = ZINN i klientit -->
        <E1EDKA1 SEGMENT="1">
          <PARVW>AG</PARVW>
          <PARTN><xsl:value-of select="o2c:Buyer/o2c:CustomerRef"/></PARTN>
        </E1EDKA1>

        <!-- Pozicionet -->
        <xsl:for-each select="o2c:Lines/o2c:Line">
          <E1EDP01 SEGMENT="1">
            <POSEX><xsl:value-of select="o2c:LineNo"/></POSEX>
            <MENGE><xsl:value-of select="o2c:Quantity"/></MENGE>
            <MENEE><xsl:value-of select="o2c:Unit"/></MENEE>
            <VPREI><xsl:value-of select="o2c:LineValue"/></VPREI>
            <E1EDP19 SEGMENT="1">
              <QUALF>002</QUALF>
              <IDTNR><xsl:value-of select="o2c:ProductRef"/></IDTNR>
              <KTEXT><xsl:value-of select="o2c:Description"/></KTEXT>
            </E1EDP19>
          </E1EDP01>
        </xsl:for-each>
      </IDOC>
    </ORDERS05>
  </xsl:template>

</xsl:stylesheet>
