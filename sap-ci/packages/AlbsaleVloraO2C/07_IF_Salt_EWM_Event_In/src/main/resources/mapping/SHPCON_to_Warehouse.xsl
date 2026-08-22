<?xml version="1.0" encoding="UTF-8"?>
<!--
  SHPCON_to_Warehouse.xsl · Phase 4 (EWM / Warehouse)
  IDoc SHPCON (DELVRY03 basic, konfirmim dërgese/goods issue nga EWM) -> WarehouseEvent kanonik.
  EventType: GOODS_ISSUED nëse ka datë goods issue (WADAT_IST), përndryshe PICKED.
  Segmentet: E1EDL20 (VBELN/LGNUM/BSTNR), E1EDT13 (datë), E1EDL24 (item/pick qty),
             E1EDL37 (Handling Unit). Namespace DEFAULT në dalje.
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="urn:albsale:o2c:canonical:1.0">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

  <xsl:variable name="corr" select="normalize-space((//*[starts-with(normalize-space(.),'SALT-')])[1])"/>
  <xsl:variable name="giDate" select="normalize-space((//E1EDT13[QUALF='006']/NTANF | //E1EDT13[QUALF='007']/NTANF | //E1EDT13/NTANF)[1])"/>
  <xsl:variable name="zinn" select="normalize-space((//E1ADRM1[PARVW='AG']/PARTNER_ID | //E1EDKA1[PARVW='AG']/PARTN)[1])"/>

  <xsl:template name="fmtDate">
    <xsl:param name="d"/>
    <xsl:choose>
      <xsl:when test="string-length($d)=8"><xsl:value-of select="concat(substring($d,1,4),'-',substring($d,5,2),'-',substring($d,7,2))"/></xsl:when>
      <xsl:otherwise><xsl:value-of select="$d"/></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="/">
    <WarehouseEvent>
      <Header>
        <EventType>
          <xsl:choose>
            <xsl:when test="string-length($giDate)&gt;0">GOODS_ISSUED</xsl:when>
            <xsl:otherwise>PICKED</xsl:otherwise>
          </xsl:choose>
        </EventType>
        <CorrelationId><xsl:value-of select="$corr"/></CorrelationId>
        <DeliveryNo><xsl:value-of select="normalize-space((//E1EDL20/VBELN)[1])"/></DeliveryNo>
        <S4OrderId><xsl:value-of select="normalize-space((//E1EDL20/BSTNR | //E1EDL41/BSTNR)[1])"/></S4OrderId>
        <Warehouse><xsl:value-of select="normalize-space((//E1EDL20/LGNUM | //E1EDL22/LSTEL)[1])"/></Warehouse>
        <EventDate><xsl:call-template name="fmtDate"><xsl:with-param name="d" select="$giDate"/></xsl:call-template></EventDate>
        <Message><xsl:value-of select="normalize-space((//E1EDL20/LIFEX)[1])"/></Message>
      </Header>
      <Reference>
        <CustomerRef><xsl:value-of select="$zinn"/></CustomerRef>
        <xsl:if test="contains($corr,'-')">
          <SaltOrderRef><xsl:value-of select="number(substring-before(substring-after(substring-after($corr,'-'),'-'),'-'))"/></SaltOrderRef>
        </xsl:if>
      </Reference>
      <xsl:if test="string-length($giDate)&gt;0">
        <GoodsIssue>
          <MovementType>601</MovementType>
          <GIDate><xsl:call-template name="fmtDate"><xsl:with-param name="d" select="$giDate"/></xsl:call-template></GIDate>
          <TotalQuantity><xsl:value-of select="sum(//E1EDL24/LGMNG[.!=''] | //E1EDL24/LFIMG[.!=''])"/></TotalQuantity>
          <Unit><xsl:value-of select="normalize-space((//E1EDL24/VRKME | //E1EDL24/MEINS)[1])"/></Unit>
        </GoodsIssue>
      </xsl:if>
      <xsl:if test="//E1EDL37">
        <HandlingUnits>
          <xsl:for-each select="//E1EDL37">
            <HandlingUnit>
              <HuId><xsl:value-of select="normalize-space(EXIDV)"/></HuId>
              <PackMaterial><xsl:value-of select="normalize-space(VHILM)"/></PackMaterial>
              <GrossWeight><xsl:value-of select="normalize-space(BRGEW)"/></GrossWeight>
              <WeightUnit><xsl:value-of select="normalize-space(GEWEI)"/></WeightUnit>
              <TrackingNo><xsl:value-of select="normalize-space((VENUM | ../E1EDL20/TRAID)[1])"/></TrackingNo>
            </HandlingUnit>
          </xsl:for-each>
        </HandlingUnits>
      </xsl:if>
      <xsl:if test="//E1EDL24">
        <Tasks>
          <xsl:for-each select="//E1EDL24">
            <Task>
              <TaskId><xsl:value-of select="normalize-space(POSNR)"/></TaskId>
              <ProductRef><xsl:value-of select="normalize-space(MATNR)"/></ProductRef>
              <PickedQuantity><xsl:value-of select="normalize-space((LGMNG | LFIMG)[1])"/></PickedQuantity>
              <Unit><xsl:value-of select="normalize-space((VRKME | MEINS)[1])"/></Unit>
              <SourceBin><xsl:value-of select="normalize-space((E1EDL43/VBELN | VGBEL)[1])"/></SourceBin>
              <DestBin><xsl:value-of select="normalize-space(WERKS)"/></DestBin>
              <Status>CONFIRMED</Status>
            </Task>
          </xsl:for-each>
        </Tasks>
      </xsl:if>
    </WarehouseEvent>
  </xsl:template>
</xsl:stylesheet>
