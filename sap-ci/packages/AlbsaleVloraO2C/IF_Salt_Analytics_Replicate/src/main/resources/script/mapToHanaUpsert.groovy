/*
 * mapToHanaUpsert.groovy — IF_Salt_Analytics_Replicate
 * Harton JSON-in delta të porosive nga ERP në XML-in batch të adaptorit JDBC (UPDATE_INSERT)
 * për FACT_O2C_ORDER dhe dimensionet (DIM_CUSTOMER, DIM_PRODUCT). Idempotent me çelës IDSO.
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.json.JsonSlurper
Message processData(Message message) {
    def raw = message.getBody(java.lang.String) ?: '{}'
    def rows = []
    try { rows = (new JsonSlurper().parseText(raw)).orders ?: [] } catch (Exception ignored) {}
    def esc = { v -> (v==null?'':v.toString()).replace('&','&amp;').replace('<','&lt;').replace('>','&gt;') }
    def sb = new StringBuilder('<root>')
    rows.each { o ->
        sb << '<Fact><dbTableName action="UPDATE_INSERT"><table>FACT_O2C_ORDER</table>'
        sb << '<access>'
        ['IDSO','ZINN','SALTCODE','TITLE','QUANTITY','VALUE','CURRENCY','ORDER_STATUS','WAREHOUSE_STATUS',
         'FI_STATUS','CONFIRMED_QTY','DELIVERY_NO','INVOICE_NO','GI_DATE','INVOICE_DATE','PAID_AMOUNT',
         'CREATED','UPDATED'].each { c -> sb << "<${c}>" << esc(o[c.toLowerCase()]) << "</${c}>" }
        sb << '</access>'
        sb << '<key1><IDSO>' << esc(o.idso) << '</IDSO></key1>'
        sb << '</dbTableName></Fact>'
        // dimensioni klient
        sb << '<Dim><dbTableName action="UPDATE_INSERT"><table>DIM_CUSTOMER</table>'
        sb << '<access><ZINN>' << esc(o.zinn) << '</ZINN><NAME>' << esc(o.customer_name) << '</NAME></access>'
        sb << '<key1><ZINN>' << esc(o.zinn) << '</ZINN></key1></dbTableName></Dim>'
    }
    sb << '</root>'
    message.setBody(sb.toString())
    message.setHeader('Content-Type','application/xml')
    return message
}
