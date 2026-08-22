<?xml version="1.0" encoding="UTF-8"?>
<!--
  Canonical_to_PORDCR.xsl · Phase 6
  Harton PurchaseOrder kanonike -> IDoc PORDCR05 (krijim Purchase Order në S/4HANA MM).
  Parametrat organizativë (EKORG/EKGRP/BSART/RCVPRN) kalohen nga iFlow.
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:o2c="urn:albsale:o2c:canonical:1.0" exclude-result-prefixes="o2c">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
  <xsl:param name="p_rcvprn" select="'ZS4CLNT100'"/>
  <xsl:param name="p_sndprn" select="'ALBSALE_SALT'"/>
  <xsl:param name="p_bsart"  select="'NB'"/>
  <xsl:param name="p_ekorg"  select="'1000'"/>
  <xsl:param name="p_ekgrp"  select="'001'"/>

  <xsl:template name="d"><xsl:param name="v"/><xsl:value-of select="translate($v,'-','')"/></xsl:template>

  <xsl:template match="/o2c:PurchaseOrder">
    <PORDCR05>
      <IDOC BEGIN="1">
        <EDI_DC40 SEGMENT="1">
          <MESTYP>PORDCR</MESTYP><IDOCTYP>PORDCR05</IDOCTYP>
          <SNDPRN><xsl:value-of select="$p_sndprn"/></SNDPRN>
          <RCVPRN><xsl:value-of select="$p_rcvprn"/></RCVPRN>
        </EDI_DC40>
        <E1PORDCR5 SEGMENT="1">
          <BSART><xsl:value-of select="$p_bsart"/></BSART>
          <LIFNR><xsl:value-of select="o2c:Header/o2c:SupplierId"/></LIFNR>
          <EKORG><xsl:value-of select="$p_ekorg"/></EKORG>
          <EKGRP><xsl:value-of select="$p_ekgrp"/></EKGRP>
          <WAERS><xsl:value-of select="o2c:Header/o2c:Currency"/></WAERS>
          <E1BPEKKOA><REF_DOC><xsl:value-of select="o2c:Header/o2c:PoNumber"/></REF_DOC></E1BPEKKOA>
          <xsl:for-each select="o2c:Lines/o2c:Line">
            <E1BPEKPOC SEGMENT="1">
              <PO_ITEM><xsl:value-of select="o2c:LineNo"/></PO_ITEM>
              <MATERIAL><xsl:value-of select="o2c:ProductRef"/></MATERIAL>
              <QUANTITY><xsl:value-of select="o2c:Quantity"/></QUANTITY>
              <PO_UNIT><xsl:value-of select="o2c:Unit"/></PO_UNIT>
              <NET_PRICE><xsl:value-of select="o2c:Price"/></NET_PRICE>
              <PLANT><xsl:value-of select="../../o2c:Header/o2c:Plant"/></PLANT>
              <DELIV_DATE><xsl:call-template name="d"><xsl:with-param name="v" select="o2c:DeliveryDate"/></xsl:call-template></DELIV_DATE>
            </E1BPEKPOC>
          </xsl:for-each>
        </E1PORDCR5>
      </IDOC>
    </PORDCR05>
  </xsl:template>
</xsl:stylesheet>
