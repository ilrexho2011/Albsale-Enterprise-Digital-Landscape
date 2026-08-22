/*
 * buildReserve.groovy — IF_Salt_ATP_Reserve
 * Lexon JSON {saltcode, quantity, date, plant, idso} nga ERP, pastron dhe ndërton
 * body-n e OData action ConfirmAndReserve (aATP/BOP). Ruajmë p_reqQty për llogaritjen e backorder.
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.json.JsonSlurper
import groovy.json.JsonOutput
Message processData(Message message) {
    def j = [:]
    try { j = new JsonSlurper().parse(message.getBody(java.io.Reader.class)) } catch(e){}
    def mat=((j.material?:j.saltcode?:'') as String).replaceAll(/[^A-Za-z0-9_\-]/,'')
    def plant=((j.plant?:'1000') as String).replaceAll(/[^A-Za-z0-9]/,'')
    def qty; try{qty=(j.quantity?:0) as BigDecimal}catch(e){qty=0.0}
    def date=((j.date?:'') as String).replaceAll(/[^0-9\-]/,'')
    def idso=((j.idso?:'') as String).replaceAll(/[^0-9]/,'')
    if(!mat||qty<=0){ message.setHeader('CamelHttpResponseCode',400); message.setHeader('Content-Type','application/json')
        message.setBody('{"message":"material and quantity are required"}'); throw new IllegalArgumentException('bad request') }
    message.setProperty('p_material',mat); message.setProperty('p_plant',plant)
    message.setProperty('p_reqQty',qty.toString()); message.setProperty('p_date',date); message.setProperty('p_idso',idso)
    // body i action-it (OData V2 function import merr parametra në URL; për V4 action -> JSON body)
    message.setBody(JsonOutput.toJson([Material:mat, Plant:plant, RequestedQuantity:qty, RequestedDate:date, Mode:'CONFIRM_RESERVE']))
    message.setHeader('Content-Type','application/json')
    return message
}
