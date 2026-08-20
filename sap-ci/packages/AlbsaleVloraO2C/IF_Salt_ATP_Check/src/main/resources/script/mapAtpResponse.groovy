/*
 * mapAtpResponse.groovy — IF_Salt_ATP_Check
 * Harton përgjigjen aATP (Availability Information, e renditur sipas datës) në JSON:
 * {material, plant, requestedQuantity, requestedDate, availableQuantity, confirmedQuantity,
 *  confirmedDate, shortfall, fullyConfirmed}. confirmedQuantity = min(kërkesa, disponibël).
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.json.JsonSlurper
import groovy.json.JsonOutput

Message processData(Message message) {
    def raw   = message.getBody(java.lang.String) ?: '{}'
    def mat   = message.getProperty('p_material') ?: ''
    def plant = message.getProperty('p_plant') ?: ''
    def reqQty = (message.getProperty('p_reqQty') ?: '0') as BigDecimal
    def reqDate = message.getProperty('p_date') ?: ''

    BigDecimal available = 0.0
    String confDate = ''
    String unit = ''
    try {
        def j = new JsonSlurper().parseText(raw)
        def rows = j?.d?.results ?: (j?.value ?: [])
        // shuma kumulative deri sa të mbulohet kërkesa -> data e konfirmimit
        BigDecimal cum = 0.0
        for (r in rows) {
            def q = (r?.AvailableQuantity ?: 0) as BigDecimal
            cum += q
            if (!unit) unit = r?.BaseUnit ?: ''
            if (cum >= reqQty && !confDate) { confDate = (r?.AvailabilityDate ?: '') as String }
        }
        available = cum
    } catch (Exception e) {
        message.setHeader('CamelHttpResponseCode', 502)
    }

    def confirmed = (available < reqQty) ? available : reqQty
    def shortfall = (reqQty > confirmed) ? (reqQty - confirmed) : 0.0
    if (!confDate && confirmed >= reqQty) confDate = reqDate

    def out = [
        material: mat, plant: plant,
        requestedQuantity: reqQty, requestedDate: reqDate,
        availableQuantity: available, confirmedQuantity: confirmed,
        confirmedDate: confDate, shortfall: shortfall,
        fullyConfirmed: (shortfall == 0.0),
        source: 'S4_aATP_AvailabilityInformation'
    ]
    message.setHeader('Content-Type','application/json')
    message.setBody(JsonOutput.toJson(out))
    return message
}
