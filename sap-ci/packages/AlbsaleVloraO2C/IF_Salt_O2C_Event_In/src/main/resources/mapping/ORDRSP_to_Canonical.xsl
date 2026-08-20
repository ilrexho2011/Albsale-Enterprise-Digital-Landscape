<?xml version="1.0" encoding="UTF-8"?>
<!--
  ORDRSP_to_Canonical.xsl · Phase 3
  Konfirmimi i porosisë (ORDRSP) -> event kanonik. Namespace DEFAULT në dalje.
  CorrelationId nxirret nga fusha që fillon me 'SALT-' (referenca jonë e echo-uar).
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="urn:albsale:o2c:canonical:1.0">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

  <xsl:variable name="corr" select="normalize-space((//*[starts-with(normalize-space(.),'SALT-')])[1])"/>
  <xsl:variable name="s4order" select="normalize-space((//E1EDK02[QUALF='002']/BELNR | //E1EDK01/BELNR)[1])"/>
  <xsl:variable name="zinn" select="normalize-space((//E1EDKA1[PARVW='AG']/PARTN)[1])"/>

  <xsl:template name="fmtDate">
    <xsl:param name="d"/>
    <xsl:choose>
      <xsl:when test="string-length($d)=8">
        <xsl:value-of select="concat(substring($d,1,4),'-',substring($d,5,2),'-',substring($d,7,2))"/>
      </xsl:when>
      <xsl:otherwise><xsl:value-of select="$d"/></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="/">
    <OrderEvent>
      <Header>
        <DocumentType>ORDRSP</DocumentType>
        <CorrelationId><xsl:value-of select="$corr"/></CorrelationId>
        <S4OrderId><xsl:value-of select="$s4order"/></S4OrderId>
        <EventDate><xsl:call-template name="fmtDate"><xsl:with-param name="d" select="normalize-space((//E1EDK03[IDDAT='022']/DATUM)[1])"/></xsl:call-template></EventDate>
        <Message><xsl:value-of select="normalize-space((//E1EDKT2/TDLINE)[1])"/></Message>
      </Header>
      <Reference>
        <CustomerRef><xsl:value-of select="$zinn"/></CustomerRef>
        <xsl:if test="contains($corr,'-')">
          <SaltOrderRef><xsl:value-of select="number(substring-before(substring-after(substring-after($corr,'-'),'-'),'-'))"/></SaltOrderRef>
        </xsl:if>
      </Reference>
      <Confirmation>
        <ConfirmedQuantity><xsl:value-of select="normalize-space((//E1EDP01/MENGE)[1])"/></ConfirmedQuantity>
        <ConfirmedDate>
          <xsl:call-template name="fmtDate">
            <xsl:with-param name="d" select="normalize-space((//E1EDP01/E1EDP20/EDATU | //E1EDP20/EDATU)[1])"/>
          </xsl:call-template>
        </ConfirmedDate>
      </Confirmation>
    </OrderEvent>
  </xsl:template>
</xsl:stylesheet>
