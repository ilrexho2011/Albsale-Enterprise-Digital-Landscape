/*
 * setInboundContext.groovy — iFlow IF_Salt_O2C_Event_In
 * Përgatit thirrjen drejt ERP-së: vendos header-in X-Inbound-Token nga një
 * Externalized Parameter / Security Material (JURË i hardkoduar) dhe Content-Type XML.
 * Token-i lexohet nga property {{erp_inbound_token}} që lidhet me Secure Parameter.
 */
import com.sap.gateway.ip.core.customdev.util.Message

Message processData(Message message) {
    def token = message.getProperty('erp_inbound_token') ?: ''
    message.setHeader('X-Inbound-Token', token)
    message.setHeader('Content-Type', 'application/xml')

    // Korrelacioni për MPL
    def corr = ''
    try {
        def body = message.getBody(java.lang.String) ?: ''
        def m = (body =~ /<[^:>]*:?CorrelationId>([^<]*)</)
        if (m.find()) corr = m.group(1)
    } catch (Exception e) {}
    if (corr) message.setProperty('SAP_MessageProcessingLogID', corr)

    def mp = message.getProperty('SAP_MessageProcessingLog')
    if (mp != null) {
        mp.addCustomHeaderProperty('CorrelationId', corr)
        mp.addCustomHeaderProperty('Scenario', 'O2C-Event-In')
    }
    return message
}
