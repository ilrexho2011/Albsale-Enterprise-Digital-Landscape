/*
 * setOrderHeaders.groovy — iFlow IF_Salt_O2C_Order_Out
 * Nxjerr CorrelationId nga payload-i kanonik dhe e vë si SAP_MessageProcessingLogID
 * dhe header X-Correlation-Id, që gjurmimi end-to-end (ERP -> CI -> S/4) të jetë i lidhur.
 * Nuk mban asnjë sekret; kredencialet trajtohen nga Security Material i adaptorëve.
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.xml.XmlSlurper

Message processData(Message message) {
    def body = message.getBody(java.lang.String) ?: ''
    def corr = ''
    try {
        def xml = new XmlSlurper().parseText(body)
        corr = xml.'**'.find { it.name() == 'CorrelationId' }?.text() ?: ''
    } catch (Exception e) {
        // lëmë corr bosh; log-u kryesor kapet nga MPL
    }
    if (corr) {
        message.setHeader('X-Correlation-Id', corr)
        message.setProperty('SAP_MessageProcessingLogID', corr)
        message.setProperty('corrId', corr)
    }
    // Metadata për MPL custom header (i dukshëm në monitoring)
    def mp = message.getProperty('SAP_MessageProcessingLog')
    if (mp != null) {
        mp.addCustomHeaderProperty('CorrelationId', corr)
        mp.addCustomHeaderProperty('Scenario', 'O2C-Order-Out')
    }
    return message
}
