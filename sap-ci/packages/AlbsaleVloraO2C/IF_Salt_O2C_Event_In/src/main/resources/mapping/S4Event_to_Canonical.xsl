<?xml version="1.0" encoding="UTF-8"?>
<!--
  S4Event_to_Canonical.xsl
  Harton IDoc-un dalës të statusit nga S/4HANA në eventin kanonik O2C që POST-ohet
  te receive_event.php. Elementet dalëse janë në NAMESPACE DEFAULT
  (xmlns="urn:albsale:o2c:canonical:1.0") — pikërisht ashtu si e pret SimpleXML
  te receive_event.php ($xml->Header->DocumentType). MOS përdor prefiks (o2c:) në dalje,
  sepse SimpleXML pa regjistrim namespace-i nuk i lexon fëmijët e prefiksuar.

  Mbulon: ORDRSP (konfirmim), DESADV (delivery/DELVRY03), INVOIC (INVOIC02).
  XPath-et e fushave duhen përshtatur me metadata-n reale të IDoc-ëve të S/4.
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="urn:albsale:o2c:canonical:1.0">

  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

  <xsl:variable name="mestyp" select="normalize-space((//EDI_DC40/MESTYP)[1])"/>
  <xsl:variable name="corr" select="normalize-space((//E1EDK02[QUALF='001']/BELNR | //E1EDK01/BELNR)[1])"/>
  <xsl:variable name="s4order" select="normalize-space((//E1EDK02[QUALF='002']/BELNR | //E1EDL20/VBELN)[1])"/>
  <xsl:variable name="zinn" select="normalize-space((//E1EDKA1[PARVW='AG']/PARTN)[1])"/>

  <xsl:template match="/">
    <OrderEvent>
      <Header>
        <DocumentType>
          <xsl:choose>
            <xsl:when test="$mestyp='ORDRSP'">ORDRSP</xsl:when>
            <xsl:when test="$mestyp='DESADV'">DESADV</xsl:when>
            <xsl:when test="$mestyp='INVOIC'">INVOIC</xsl:when>
            <xsl:otherwise>REJECT</xsl:otherwise>
          </xsl:choose>
        </DocumentType>
        <CorrelationId><xsl:value-of select="$corr"/></CorrelationId>
        <S4OrderId><xsl:value-of select="$s4order"/></S4OrderId>
        <Message><xsl:value-of select="normalize-space((//E1EDKT2/TDLINE)[1])"/></Message>
      </Header>

      <Reference>
        <CustomerRef><xsl:value-of select="$zinn"/></CustomerRef>
        <xsl:if test="contains($corr,'-')">
          <SaltOrderRef>
            <xsl:value-of select="number(substring-before(substring-after(substring-after($corr,'-'),'-'),'-'))"/>
          </SaltOrderRef>
        </xsl:if>
      </Reference>

      <xsl:if test="$mestyp='ORDRSP'">
        <Confirmation>
          <ConfirmedQuantity><xsl:value-of select="normalize-space((//E1EDP01/MENGE)[1])"/></ConfirmedQuantity>
        </Confirmation>
      </xsl:if>

      <xsl:if test="$mestyp='DESADV'">
        <Despatch>
          <DeliveryNo><xsl:value-of select="normalize-space((//E1EDL20/VBELN | //E1EDT13/VBELN)[1])"/></DeliveryNo>
        </Despatch>
      </xsl:if>

      <xsl:if test="$mestyp='INVOIC'">
        <Invoice>
          <InvoiceNo><xsl:value-of select="normalize-space((//E1EDK01/BELNR)[1])"/></InvoiceNo>
        </Invoice>
      </xsl:if>
    </OrderEvent>
  </xsl:template>

</xsl:stylesheet>
