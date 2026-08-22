<?xml version="1.0" encoding="UTF-8"?>
<!--
  ORDRSP_PO_to_Canonical.xsl · Phase 6
  Konfirmimi i PO nga furnitori (IDoc ORDERS05/ORDRSP me partner LF) -> SupplierEvent kanonik.
  Namespace DEFAULT në dalje. CorrelationId/PoNumber nga referenca (fillon me 'PO-').
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="urn:albsale:o2c:canonical:1.0">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
  <xsl:variable name="po" select="normalize-space((//*[starts-with(normalize-space(.),'PO-')])[1])"/>
  <xsl:variable name="supplier" select="normalize-space((//E1EDKA1[PARVW='LF']/PARTN | //E1EDKA1[PARVW='LI']/PARTN)[1])"/>

  <xsl:template name="fd"><xsl:param name="d"/>
    <xsl:choose><xsl:when test="string-length($d)=8"><xsl:value-of select="concat(substring($d,1,4),'-',substring($d,5,2),'-',substring($d,7,2))"/></xsl:when>
    <xsl:otherwise><xsl:value-of select="$d"/></xsl:otherwise></xsl:choose></xsl:template>

  <xsl:template match="/">
    <SupplierEvent>
      <Header>
        <EventType>
          <xsl:choose>
            <xsl:when test="normalize-space((//EDI_DC40/MESTYP)[1])='DESADV'">ASN</xsl:when>
            <xsl:when test="//E1EDK01/ACTION='REJECT' or normalize-space((//BSART)[1])='REJ'">PO_REJECTED</xsl:when>
            <xsl:otherwise>PO_CONFIRMED</xsl:otherwise>
          </xsl:choose>
        </EventType>
        <PoNumber><xsl:value-of select="$po"/></PoNumber>
        <SupplierId><xsl:value-of select="$supplier"/></SupplierId>
        <CorrelationId><xsl:value-of select="$po"/></CorrelationId>
        <ConfirmationNo><xsl:value-of select="normalize-space((//E1EDK02[QUALF='002']/BELNR)[1])"/></ConfirmationNo>
        <DeliveryDate><xsl:call-template name="fd"><xsl:with-param name="d" select="normalize-space((//E1EDK03[IDDAT='002']/DATUM | //E1EDP20/EDATU)[1])"/></xsl:call-template></DeliveryDate>
        <EventDate><xsl:call-template name="fd"><xsl:with-param name="d" select="normalize-space((//E1EDK03[IDDAT='022']/DATUM)[1])"/></xsl:call-template></EventDate>
      </Header>
      <Items>
        <xsl:for-each select="//E1EDP01">
          <Item>
            <LineNo><xsl:value-of select="normalize-space(POSEX)"/></LineNo>
            <ProductRef><xsl:value-of select="normalize-space((E1EDP19[QUALF='002']/IDTNR | E1EDP19/IDTNR)[1])"/></ProductRef>
            <ConfirmedQuantity><xsl:value-of select="normalize-space(MENGE)"/></ConfirmedQuantity>
            <Unit><xsl:value-of select="normalize-space(MENEE)"/></Unit>
            <ConfirmedDate><xsl:call-template name="fd"><xsl:with-param name="d" select="normalize-space((E1EDP20/EDATU)[1])"/></xsl:call-template></ConfirmedDate>
          </Item>
        </xsl:for-each>
      </Items>
    </SupplierEvent>
  </xsl:template>
</xsl:stylesheet>
