<?xml version="1.0" encoding="UTF-8"?>
<!--
  DELVRY03_to_Canonical.xsl · Phase 3 (DESADV/Delivery i plotë)
  IDoc DELVRY03 -> event kanonik me header dërgese + artikuj. Namespace DEFAULT në dalje.
  Segmentet standarde: E1EDL20 (header), E1EDT13 (data), E1ADRM1 (partnerë), E1EDL24 (item).
  SHËNIM: qualifiers (PARVW, IDDAT/QUALF) përshtaten me metadata reale të S/4.
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="urn:albsale:o2c:canonical:1.0">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

  <xsl:variable name="corr" select="normalize-space((//*[starts-with(normalize-space(.),'SALT-')])[1])"/>
  <xsl:variable name="zinn" select="normalize-space((//E1ADRM1[PARVW='AG']/PARTNER_ID | //E1EDKA1[PARVW='AG']/PARTN)[1])"/>

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
        <DocumentType>DESADV</DocumentType>
        <CorrelationId><xsl:value-of select="$corr"/></CorrelationId>
        <S4OrderId><xsl:value-of select="normalize-space((//E1EDL20/BSTNR | //E1EDL41/BSTNR)[1])"/></S4OrderId>
        <EventDate><xsl:call-template name="fmtDate"><xsl:with-param name="d" select="normalize-space((//E1EDT13/NTANF)[1])"/></xsl:call-template></EventDate>
        <Message><xsl:value-of select="normalize-space((//E1EDL20/LIFEX)[1])"/></Message>
      </Header>
      <Reference>
        <CustomerRef><xsl:value-of select="$zinn"/></CustomerRef>
        <xsl:if test="contains($corr,'-')">
          <SaltOrderRef><xsl:value-of select="number(substring-before(substring-after(substring-after($corr,'-'),'-'),'-'))"/></SaltOrderRef>
        </xsl:if>
      </Reference>
      <Despatch>
        <DeliveryNo><xsl:value-of select="normalize-space((//E1EDL20/VBELN)[1])"/></DeliveryNo>
        <DeliveryDate>
          <xsl:call-template name="fmtDate"><xsl:with-param name="d" select="normalize-space((//E1EDT13[QUALF='007']/NTANF | //E1EDT13/NTANF)[1])"/></xsl:call-template>
        </DeliveryDate>
        <Incoterms><xsl:value-of select="normalize-space((//E1EDL20/INCO1)[1])"/></Incoterms>
        <Carrier><xsl:value-of select="normalize-space((//E1ADRM1[PARVW='SP']/NAME1)[1])"/></Carrier>
        <TrackingNo><xsl:value-of select="normalize-space((//E1EDL20/TRAID | //E1EDL20/BOLNR)[1])"/></TrackingNo>
        <ShipToParty><xsl:value-of select="normalize-space((//E1ADRM1[PARVW='WE']/PARTNER_ID | //E1EDKA1[PARVW='WE']/PARTN)[1])"/></ShipToParty>
        <TotalGrossWeight><xsl:value-of select="normalize-space((//E1EDL20/BTGEW)[1])"/></TotalGrossWeight>
        <WeightUnit><xsl:value-of select="normalize-space((//E1EDL20/GEWEI)[1])"/></WeightUnit>
        <Items>
          <xsl:for-each select="//E1EDL24">
            <Item>
              <LineNo><xsl:value-of select="normalize-space(POSNR)"/></LineNo>
              <ProductRef><xsl:value-of select="normalize-space(MATNR)"/></ProductRef>
              <Description><xsl:value-of select="normalize-space(ARKTX)"/></Description>
              <DeliveredQuantity><xsl:value-of select="normalize-space(LFIMG)"/></DeliveredQuantity>
              <Unit><xsl:value-of select="normalize-space(VRKME)"/></Unit>
              <Batch><xsl:value-of select="normalize-space((E1EDL25/CHARG | CHARG)[1])"/></Batch>
            </Item>
          </xsl:for-each>
        </Items>
      </Despatch>
    </OrderEvent>
  </xsl:template>
</xsl:stylesheet>
