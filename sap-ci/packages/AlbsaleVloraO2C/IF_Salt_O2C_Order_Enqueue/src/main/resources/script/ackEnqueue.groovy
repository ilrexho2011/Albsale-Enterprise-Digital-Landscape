/*
 * ackEnqueue.groovy — IF_Salt_O2C_Order_Enqueue
 * Nxjerr CorrelationId nga porosia kanonike, e vë si JMS/MPL property, dhe kthen 202
 * (Accepted) te ERP-ja: porosia u vu në radhë, do të përpunohet asinkron.
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.xml.XmlSlurper

Message processData(Message message) {
    def body = message.getBody(java.lang.String) ?: ''
    def corr = ''
    try {
        corr = new XmlSlurper().parseText(body).'**'.find { it.name() == 'CorrelationId' }?.text() ?: ''
    } catch (Exception ignored) {}
    if (corr) {
        message.setProperty('SAP_MessageProcessingLogID', corr)
        message.setProperty('corrId', corr)
        message.setHeader('SAP_ApplicationID', corr)   // vlen si JMS message id
    }
    def mp = message.getProperty('SAP_MessageProcessingLog')
    if (mp != null) { mp.addCustomHeaderProperty('CorrelationId', corr); mp.addCustomHeaderProperty('Scenario','O2C-Order-Enqueue') }

    message.setHeader('CamelHttpResponseCode', 202)
    message.setHeader('Content-Type', 'application/json')
    message.setBody('{"message":"Order accepted and queued","correlationId":"' + corr + '"}')
    return message
}
