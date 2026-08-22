<?xml version="1.0" encoding="UTF-8"?>
<!--
  MBGMCR_to_GR.xsl · Phase 6
  Goods Receipt nga S/4 (IDoc MBGMCR — Goods Movement Create, BAPI struktura) -> GoodsReceipt kanonik.
  Segmentet: E1BP2017_GM_HEAD_01 (header), E1BP2017_GM_HEAD_RET (MAT_DOC), E1BP2017_GM_ITEM_CREATE (items).
  Namespace DEFAULT në dalje.
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="urn:albsale:o2c:canonical:1.0">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
  <xsl:variable name="po" select="normalize-space((//*[starts-with(normalize-space(.),'PO-')])[1])"/>

  <xsl:template name="fd"><xsl:param name="d"/>
    <xsl:choose><xsl:when test="string-length($d)=8"><xsl:value-of select="concat(substring($d,1,4),'-',substring($d,5,2),'-',substring($d,7,2))"/></xsl:when>
    <xsl:otherwise><xsl:value-of select="$d"/></xsl:otherwise></xsl:choose></xsl:template>

  <xsl:template match="/">
    <GoodsReceipt>
      <Header>
        <MaterialDocument><xsl:value-of select="normalize-space((//E1BP2017_GM_HEAD_RET/MAT_DOC | //MAT_DOC)[1])"/></MaterialDocument>
        <PoNumber>
          <xsl:choose>
            <xsl:when test="$po != ''"><xsl:value-of select="$po"/></xsl:when>
            <xsl:otherwise><xsl:value-of select="normalize-space((//E1BP2017_GM_ITEM_CREATE/PO_NUMBER)[1])"/></xsl:otherwise>
          </xsl:choose>
        </PoNumber>
        <CorrelationId><xsl:value-of select="$po"/></CorrelationId>
        <MovementType><xsl:value-of select="normalize-space((//E1BP2017_GM_ITEM_CREATE/MOVE_TYPE)[1])"/></MovementType>
        <PostingDate><xsl:call-template name="fd"><xsl:with-param name="d" select="normalize-space((//E1BP2017_GM_HEAD_01/PSTNG_DATE)[1])"/></xsl:call-template></PostingDate>
        <Plant><xsl:value-of select="normalize-space((//E1BP2017_GM_ITEM_CREATE/PLANT)[1])"/></Plant>
      </Header>
      <Items>
        <xsl:for-each select="//E1BP2017_GM_ITEM_CREATE">
          <Item>
            <LineNo><xsl:value-of select="normalize-space(PO_ITEM)"/></LineNo>
            <ProductRef><xsl:value-of select="normalize-space(MATERIAL)"/></ProductRef>
            <ReceivedQuantity><xsl:value-of select="normalize-space(ENTRY_QNT)"/></ReceivedQuantity>
            <Unit><xsl:value-of select="normalize-space((ENTRY_UOM | PO_PR_UOM)[1])"/></Unit>
            <Batch><xsl:value-of select="normalize-space(BATCH)"/></Batch>
            <StorageLocation><xsl:value-of select="normalize-space(STGE_LOC)"/></StorageLocation>
          </Item>
        </xsl:for-each>
      </Items>
    </GoodsReceipt>
  </xsl:template>
</xsl:stylesheet>
