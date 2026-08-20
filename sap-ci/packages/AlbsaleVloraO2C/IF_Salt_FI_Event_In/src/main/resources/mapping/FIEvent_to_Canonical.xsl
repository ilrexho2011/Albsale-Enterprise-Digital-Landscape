<?xml version="1.0" encoding="UTF-8"?>
<!--
  FIEvent_to_Canonical.xsl · Phase 4 (Finance / FI)
  Njoftim statusi financiar nga S/4 (IDoc FIDCCP02 / ose Event Mesh / CDS outbound) -> FinanceEvent kanonik.
  Burimi këtu modelohet si <FIStatus> me fusha standarde; përshtat XPath-et me formatin real.
  NotifType: FI_POSTED (posting i faturës) ose PAYMENT_CLEARED (clearing/pagesë). Namespace DEFAULT.
-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="urn:albsale:o2c:canonical:1.0">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

  <xsl:variable name="corr" select="normalize-space((//*[starts-with(normalize-space(.),'SALT-')])[1])"/>
  <xsl:variable name="type" select="normalize-space((//NotifType | //EventType)[1])"/>
  <xsl:variable name="isPayment" select="boolean($type='PAYMENT_CLEARED' or //ClearingDocument!='' )"/>

  <xsl:template name="fmtDate">
    <xsl:param name="d"/>
    <xsl:choose>
      <xsl:when test="string-length($d)=8"><xsl:value-of select="concat(substring($d,1,4),'-',substring($d,5,2),'-',substring($d,7,2))"/></xsl:when>
      <xsl:otherwise><xsl:value-of select="$d"/></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="/">
    <FinanceEvent>
      <Header>
        <EventType>
          <xsl:choose><xsl:when test="$isPayment">PAYMENT_CLEARED</xsl:when><xsl:otherwise>FI_POSTED</xsl:otherwise></xsl:choose>
        </EventType>
        <CorrelationId><xsl:value-of select="$corr"/></CorrelationId>
        <S4OrderId><xsl:value-of select="normalize-space((//SalesOrder | //SalesDocument)[1])"/></S4OrderId>
        <EventDate>
          <xsl:call-template name="fmtDate"><xsl:with-param name="d" select="normalize-space((//PostingDate | //ClearingDate)[1])"/></xsl:call-template>
        </EventDate>
        <Message><xsl:value-of select="normalize-space((//DocumentHeaderText | //Note)[1])"/></Message>
      </Header>
      <Reference>
        <CustomerRef><xsl:value-of select="normalize-space((//Customer | //Debtor)[1])"/></CustomerRef>
        <xsl:if test="contains($corr,'-')">
          <SaltOrderRef><xsl:value-of select="number(substring-before(substring-after(substring-after($corr,'-'),'-'),'-'))"/></SaltOrderRef>
        </xsl:if>
        <InvoiceNo><xsl:value-of select="normalize-space((//ReferenceDocument | //BillingDocument | //InvoiceNo)[1])"/></InvoiceNo>
      </Reference>

      <xsl:if test="not($isPayment)">
        <Accounting>
          <AccountingDoc><xsl:value-of select="normalize-space((//AccountingDocument)[1])"/></AccountingDoc>
          <CompanyCode><xsl:value-of select="normalize-space((//CompanyCode)[1])"/></CompanyCode>
          <FiscalYear><xsl:value-of select="normalize-space((//FiscalYear)[1])"/></FiscalYear>
          <PostingDate><xsl:call-template name="fmtDate"><xsl:with-param name="d" select="normalize-space((//PostingDate)[1])"/></xsl:call-template></PostingDate>
          <DocumentType><xsl:value-of select="normalize-space((//AccountingDocumentType | //DocumentType)[1])"/></DocumentType>
          <Amount><xsl:value-of select="normalize-space((//AmountInTransactionCurrency | //Amount)[1])"/></Amount>
          <Currency><xsl:value-of select="normalize-space((//TransactionCurrency | //Currency)[1])"/></Currency>
        </Accounting>
      </xsl:if>

      <xsl:if test="$isPayment">
        <Payment>
          <PaymentRef>
            <xsl:choose>
              <xsl:when test="normalize-space((//PaymentReference)[1])!=''"><xsl:value-of select="normalize-space((//PaymentReference)[1])"/></xsl:when>
              <xsl:otherwise><xsl:value-of select="normalize-space((//ClearingDocument)[1])"/></xsl:otherwise>
            </xsl:choose>
          </PaymentRef>
          <PaymentDate><xsl:call-template name="fmtDate"><xsl:with-param name="d" select="normalize-space((//ClearingDate | //PaymentDate)[1])"/></xsl:call-template></PaymentDate>
          <Amount><xsl:value-of select="normalize-space((//AmountPaid | //Amount)[1])"/></Amount>
          <Currency><xsl:value-of select="normalize-space((//Currency | //TransactionCurrency)[1])"/></Currency>
          <ClearingDoc><xsl:value-of select="normalize-space((//ClearingDocument)[1])"/></ClearingDoc>
          <ClearingStatus>CLEARED</ClearingStatus>
        </Payment>
      </xsl:if>
    </FinanceEvent>
  </xsl:template>
</xsl:stylesheet>
