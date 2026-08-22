/*
 * readFinanceRequest.groovy — IF_Salt_Finance_Status
 * Lexon ?customer=.. nga query, pastron (anti OData-injection) dhe vendos property p_customer.
 */
import com.sap.gateway.ip.core.customdev.util.Message
Message processData(Message message) {
    def q = (message.getHeader('CamelHttpQuery', String) ?: '')
    def params = [:]
    q.split('&').each { p -> def kv = p.split('=',2); if (kv.length==2) params[kv[0]] = java.net.URLDecoder.decode(kv[1],'UTF-8') }
    def cust = (params['customer'] ?: params['zinn'] ?: '').replaceAll(/[^A-Za-z0-9_\-]/,'')
    if (!cust) {
        message.setHeader('CamelHttpResponseCode', 400)
        message.setHeader('Content-Type','application/json')
        message.setBody('{"message":"customer is required"}')
        throw new IllegalArgumentException('customer is required')
    }
    message.setProperty('p_customer', cust)
    return message
}
