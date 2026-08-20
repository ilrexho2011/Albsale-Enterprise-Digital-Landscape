/*
 * notifyDeadLetter.groovy — IF_Salt_O2C_Order_Consumer (exception subprocess)
 * Pas shterimit të retry-t, logon në MPL detajet dhe shënon mesazhin si dead-letter.
 * Payload-i ruhet në Data Store 'salt_orders_dlq' (hapi pararendës) për riproçesim manual.
 */
import com.sap.gateway.ip.core.customdev.util.Message

Message processData(Message message) {
    def ex   = message.getProperty('CamelExceptionCaught')
    def corr = message.getProperty('corrId') ?: (message.getHeader('SAP_ApplicationID', String) ?: '')
    def msg  = ex ? ex.getMessage() : 'Max retries exhausted'

    def log = messageLogFactory.getMessageLog(message)
    if (log != null) {
        log.addAttachmentAsString('DeadLetterReason', msg.toString(), 'text/plain')
        log.setStringProperty('CorrelationId', corr.toString())
        log.setStringProperty('Scenario', 'O2C-Order-Consumer-DLQ')
        log.setStringProperty('Status', 'DEAD_LETTER')
    }
    // Sinjal për alerting (AEM/monitoring e kap nga statusi FAILED + kjo property)
    message.setProperty('deadLetter', 'true')
    return message
}
