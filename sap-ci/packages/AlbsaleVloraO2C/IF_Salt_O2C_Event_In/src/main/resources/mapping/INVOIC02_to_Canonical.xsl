<?xml version="1.0" encoding="UTF-8"?>
<!--
  INVOIC02_to_Canonical.xsl · Phase 3 (Faturë e plotë)
  IDoc INVOIC02 -> event kanonik me header fature + shuma + artikuj. Namespace DEFAULT.
  Segmentet: E1EDK01 (header/valuta/nr), E1EDK03 (data), E1EDS01 (shuma totale),
             E1EDP01 (item) + E1EDP19 (material) + E1EDP26 (vlera) + E1EDP04 (taksë).
  SHËNIM: qualifiers (IDDAT, SUMID, QUALF) përshtaten me metadata reale të S/4.
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="urn:albsale:o2c:canonical:1.0">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

  <xsl:variable name="corr" select="normalize-space((//*[starts-with(normalize-space(.),'SALT-')])[1])"/>
  <xsl:variable name="zinn" select="normalize-space((//E1EDKA1[PARVW='AG']/PARTN | //E1EDKA1[PARVW='RG']/PARTN)[1])"/>

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
        <DocumentType>INVOIC</DocumentType>
        <CorrelationId><xsl:value-of select="$corr"/></CorrelationId>
        <S4OrderId><xsl:value-of select="normalize-space((//E1EDK02[QUALF='001']/BELNR)[1])"/></S4OrderId>
        <EventDate><xsl:call-template name="fmtDate"><xsl:with-param name="d" select="normalize-space((//E1EDK03[IDDAT='015']/DATUM)[1])"/></xsl:call-template></EventDate>
        <Message><xsl:value-of select="normalize-space((//E1EDKT2/TDLINE)[1])"/></Message>
      </Header>
      <Reference>
        <CustomerRef><xsl:value-of select="$zinn"/></CustomerRef>
        <xsl:if test="contains($corr,'-')">
          <SaltOrderRef><xsl:value-of select="number(substring-before(substring-after(substring-after($corr,'-'),'-'),'-'))"/></SaltOrderRef>
        </xsl:if>
      </Reference>
      <Invoice>
        <InvoiceNo><xsl:value-of select="normalize-space((//E1EDK01/BELNR)[1])"/></InvoiceNo>
        <InvoiceDate><xsl:call-template name="fmtDate"><xsl:with-param name="d" select="normalize-space((//E1EDK03[IDDAT='015']/DATUM)[1])"/></xsl:call-template></InvoiceDate>
        <DueDate><xsl:call-template name="fmtDate"><xsl:with-param name="d" select="normalize-space((//E1EDK03[IDDAT='012']/DATUM)[1])"/></xsl:call-template></DueDate>
        <Currency><xsl:value-of select="normalize-space((//E1EDK01/CURCY)[1])"/></Currency>
        <NetAmount><xsl:value-of select="normalize-space((//E1EDS01[SUMID='011']/SUMME)[1])"/></NetAmount>
        <TaxAmount><xsl:value-of select="normalize-space((//E1EDS01[SUMID='012']/SUMME)[1])"/></TaxAmount>
        <GrossAmount><xsl:value-of select="normalize-space((//E1EDS01[SUMID='010']/SUMME)[1])"/></GrossAmount>
        <Items>
          <xsl:for-each select="//E1EDP01">
            <Item>
              <LineNo><xsl:value-of select="normalize-space(POSEX)"/></LineNo>
              <ProductRef><xsl:value-of select="normalize-space((E1EDP19[QUALF='002']/IDTNR | E1EDP19/IDTNR)[1])"/></ProductRef>
              <Description><xsl:value-of select="normalize-space((E1EDP19/KTEXT)[1])"/></Description>
              <Quantity><xsl:value-of select="normalize-space(MENGE)"/></Quantity>
              <Unit><xsl:value-of select="normalize-space(MENEE)"/></Unit>
              <NetValue><xsl:value-of select="normalize-space((E1EDP26[QUALF='003']/BETRG | E1EDP26/BETRG)[1])"/></NetValue>
              <TaxRate><xsl:value-of select="normalize-space((E1EDP04/MSATZ)[1])"/></TaxRate>
            </Item>
          </xsl:for-each>
        </Items>
      </Invoice>
    </OrderEvent>
  </xsl:template>
</xsl:stylesheet>
