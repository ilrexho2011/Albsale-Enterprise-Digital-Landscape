/*
 * mapFinanceResponse.groovy — IF_Salt_Finance_Status
 * Harton A/R open items (OData) në JSON: {customer, openItemCount, totalOpen, currency, items:[...]}.
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.json.JsonSlurper
import groovy.json.JsonOutput
Message processData(Message message) {
    def raw = message.getBody(java.lang.String) ?: '{}'
    def cust = message.getProperty('p_customer') ?: ''
    def items = []; BigDecimal total = 0.0; String cur = ''
    try {
        def j = new JsonSlurper().parseText(raw)
        def rows = j?.d?.results ?: (j?.value ?: [])
        rows.each { r ->
            def amt = (r?.AmountInCompanyCodeCurrency ?: 0) as BigDecimal
            total += amt; if (!cur) cur = r?.CompanyCodeCurrency ?: ''
            items << [accountingDoc: r?.AccountingDocument, invoiceNo: r?.BillingDocument,
                      amount: amt, dueDate: (r?.NetDueDate ?: ''), cleared: (r?.IsCleared ?: false)]
        }
    } catch (Exception e) { message.setHeader('CamelHttpResponseCode', 502) }
    def out = [customer: cust, openItemCount: items.size(), totalOpen: total, currency: cur,
               items: items, source: 'S4_A_OperationalAcctgDocItem']
    message.setHeader('Content-Type','application/json')
    message.setBody(JsonOutput.toJson(out))
    return message
}
