/*
 * mapStockResponse.groovy — IF_Salt_Stock_ATP_Query
 * Harton përgjigjen OData (JSON nga API_MATERIAL_STOCK_SRV) në JSON kanonik të thjeshtë
 * që konsumon ERP: {material, plant, availableQuantity, unit, atpQuantity, source}.
 * ATP i thjeshtuar = sasia në magazinë; për ATP të vërtetë përdor Advanced ATP OData.
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.json.JsonSlurper
import groovy.json.JsonOutput

Message processData(Message message) {
    def raw = message.getBody(java.lang.String) ?: '{}'
    def mat = message.getProperty('p_material') ?: ''
    def plant = message.getProperty('p_plant') ?: ''
    def qty = 0.0; def unit = ''
    try {
        def j = new JsonSlurper().parseText(raw)
        def results = j?.d?.results ?: (j?.value ?: [])
        if (results) {
            def r = results[0]
            qty  = (r?.MatlWrhsStkQtyInMatlBaseUnit ?: r?.MatlWrhsStkQtyInMatlBaseUnit ?: 0) as BigDecimal
            unit = r?.MaterialBaseUnit ?: ''
        }
    } catch (Exception e) {
        message.setHeader('CamelHttpResponseCode', 502)
    }
    def out = [material: mat, plant: plant, availableQuantity: qty, unit: unit,
               atpQuantity: qty, source: 'S4_API_MATERIAL_STOCK_SRV']
    message.setHeader('Content-Type','application/json')
    message.setBody(JsonOutput.toJson(out))
    return message
}
