/*
 * logAndRaise.groovy — Exception Subprocess i IF_Salt_O2C_Order_Out
 * Logon detajet e gabimit në MPL (pa sekrete) dhe ndërton një përgjigje JSON
 * që kthehet te ERP-ja (send_order.php pret status jo-2xx për të shënuar FAILED).
 */
import com.sap.gateway.ip.core.customdev.util.Message

Message processData(Message message) {
    def ex   = message.getProperty('CamelExceptionCaught')
    def corr = message.getProperty('corrId') ?: ''
    def msg  = ex ? ex.getMessage() : 'Unknown error'

    def log = messageLogFactory.getMessageLog(message)
    if (log != null) {
        log.addAttachmentAsString('ErrorDetail', msg.toString(), 'text/plain')
        log.setStringProperty('CorrelationId', corr.toString())
        log.setStringProperty('Scenario', 'O2C-Order-Out')
    }
    message.setHeader('CamelHttpResponseCode', 502)
    message.setHeader('Content-Type', 'application/json')
    message.setBody('{"message":"Mapping/Delivery to S/4 failed","correlationId":"' + corr + '"}')
    return message
}
