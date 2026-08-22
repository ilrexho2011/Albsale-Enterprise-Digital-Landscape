/*
 * mapReserve.groovy — IF_Salt_ATP_Reserve
 * Harton përgjigjen e aATP action -> JSON kanonik për ERP:
 * {reservationId, confirmedQuantity, confirmedDate, backorderQty, fullyConfirmed}.
 * backorderQty = kërkesa - konfirmimi (do të mbulohet nga procurement/BOP).
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.json.JsonSlurper
import groovy.json.JsonOutput
Message processData(Message message) {
    def reqQty=(message.getProperty('p_reqQty')?:'0') as BigDecimal
    def reqDate=message.getProperty('p_date')?:''
    def resId=''; def conf=0.0; def confDate=reqDate
    try{
        def d=new JsonSlurper().parse(message.getBody(java.io.Reader.class))
        def r=d?.d ?: d?.value ?: d
        if(r instanceof List) r=r[0]
        resId=(r?.ReservationID ?: r?.ReservationId ?: r?.SalesOrderReservation ?: '') as String
        conf=(r?.ConfirmedQuantity ?: r?.ConfdQuantity ?: 0) as BigDecimal
        confDate=(r?.ConfirmedDate ?: reqDate) as String
    }catch(e){ message.setHeader('CamelHttpResponseCode',502) }
    if(conf>reqQty) conf=reqQty
    def back = (reqQty>conf) ? (reqQty-conf) : 0.0
    def out=[reservationId:resId, requestedQuantity:reqQty, confirmedQuantity:conf,
             confirmedDate:confDate, backorderQty:back, fullyConfirmed:(back==0.0),
             idso:(message.getProperty('p_idso')?:''), source:'S4_aATP_BOP']
    message.setHeader('Content-Type','application/json'); message.setBody(JsonOutput.toJson(out))
    return message
}
