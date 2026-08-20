/*
 * deriveTopic.groovy — IF_Salt_Event_Publish
 * Nxjerr eventType nga eventi kanonik dhe cakton header.eventTopic (i vogël) për AMQP topic
 * albsale/o2c/<eventType>. Ruan CorrelationId për MPL.
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.xml.XmlSlurper
Message processData(Message message) {
    def body = message.getBody(java.lang.String) ?: ''
    def et = 'unknown'; def corr = ''
    try {
        def x = new XmlSlurper().parseText(body)
        et = (x.'**'.find { it.name()=='DocumentType' || it.name()=='EventType' }?.text() ?: 'unknown').toLowerCase()
        corr = x.'**'.find { it.name()=='CorrelationId' }?.text() ?: ''
    } catch (Exception ignored) {}
    message.setHeader('eventTopic', et.replaceAll(/[^a-z0-9_]/,''))
    if (corr) { message.setProperty('SAP_MessageProcessingLogID', corr); message.setHeader('X-Correlation-Id', corr) }
    def mp = message.getProperty('SAP_MessageProcessingLog')
    if (mp != null) { mp.addCustomHeaderProperty('CorrelationId', corr); mp.addCustomHeaderProperty('Topic', 'albsale/o2c/'+et) }
    return message
}
