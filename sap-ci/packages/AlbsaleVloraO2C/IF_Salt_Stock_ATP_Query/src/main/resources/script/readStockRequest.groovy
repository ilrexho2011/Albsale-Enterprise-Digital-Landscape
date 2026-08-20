/*
 * readStockRequest.groovy — IF_Salt_Stock_ATP_Query
 * Lexon query params nga ERP (?material=..&plant=..), i pastron dhe i vë si properties
 * p_material / p_plant që përdoren në query-n OData. Nëse plant mungon, merr default nga param.
 */
import com.sap.gateway.ip.core.customdev.util.Message

Message processData(Message message) {
    def material = (message.getHeader('CamelHttpQuery', String) ?: '')
    def params = [:]
    material.split('&').each { p -> def kv = p.split('=',2); if (kv.length==2) params[kv[0]] = java.net.URLDecoder.decode(kv[1],'UTF-8') }
    def mat = (params['material'] ?: '').replaceAll(/[^A-Za-z0-9_\-]/,'')   // pastrim (anti-injection OData)
    def plant = (params['plant'] ?: message.getProperty('default_plant') ?: '1000').replaceAll(/[^A-Za-z0-9]/,'')
    if (!mat) {
        message.setHeader('CamelHttpResponseCode', 400)
        message.setHeader('Content-Type','application/json')
        message.setBody('{"message":"material is required"}')
        throw new IllegalArgumentException('material is required')
    }
    message.setProperty('p_material', mat)
    message.setProperty('p_plant', plant)
    return message
}
