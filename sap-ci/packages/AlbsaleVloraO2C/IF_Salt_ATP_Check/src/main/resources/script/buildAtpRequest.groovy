/*
 * buildAtpRequest.groovy — IF_Salt_ATP_Check
 * Lexon JSON {material|saltcode, plant, quantity, date} nga ERP, e pastron (anti OData-injection)
 * dhe vendos properties p_material/p_plant/p_date/p_reqQty për query-n OData aATP.
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.json.JsonSlurper

Message processData(Message message) {
    def body = message.getBody(java.lang.String) ?: '{}'
    def j = [:]
    try { j = new JsonSlurper().parseText(body) } catch (Exception ignored) {}

    def mat   = ((j.material ?: j.saltcode ?: '') as String).replaceAll(/[^A-Za-z0-9_\-]/,'')
    def plant = ((j.plant ?: '1000') as String).replaceAll(/[^A-Za-z0-9]/,'')
    def qty   = 0.0
    try { qty = (j.quantity ?: 0) as BigDecimal } catch (Exception e) { qty = 0.0 }
    // data: pranon YYYY-MM-DD ose YYYYMMDD; default sot (kalohet nga CI si property nëse mungon)
    def date  = ((j.date ?: '') as String).replaceAll(/[^0-9\-]/,'')
    if (date ==~ /\d{8}/) date = date[0..3] + '-' + date[4..5] + '-' + date[6..7]
    if (!date) date = message.getProperty('today') ?: ''

    if (!mat) {
        message.setHeader('CamelHttpResponseCode', 400)
        message.setHeader('Content-Type','application/json')
        message.setBody('{"message":"material is required"}')
        throw new IllegalArgumentException('material is required')
    }
    message.setProperty('p_material', mat)
    message.setProperty('p_plant', plant)
    message.setProperty('p_date', date)
    message.setProperty('p_reqQty', qty.toString())
    return message
}
