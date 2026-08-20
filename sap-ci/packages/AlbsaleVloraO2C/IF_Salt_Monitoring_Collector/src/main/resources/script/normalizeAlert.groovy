/*
 * normalizeAlert.groovy — IF_Salt_Monitoring_Collector
 * Normalizon alertin (JSON) dhe cakton header-at alertSeverity/alertScenario/correlationId.
 * Severity default = WARNING; DLQ/dead-letter -> CRITICAL.
 */
import com.sap.gateway.ip.core.customdev.util.Message
import groovy.json.JsonSlurper
import groovy.json.JsonOutput
Message processData(Message message) {
    def raw = message.getBody(java.lang.String) ?: '{}'
    def j = [:]
    try { j = new JsonSlurper().parseText(raw) } catch (Exception ignored) {}
    def scenario = (j.scenario ?: j.iflow ?: 'unknown').toString()
    def sev = (j.severity ?: (scenario.toLowerCase().contains('dlq') || scenario.toLowerCase().contains('dead') ? 'CRITICAL' : 'WARNING')).toString().toUpperCase()
    def corr = (j.correlationId ?: '').toString()
    message.setHeader('alertSeverity', sev)
    message.setHeader('alertScenario', scenario)
    message.setHeader('X-Inbound-Token', message.getProperty('erp_inbound_token') ?: '')
    def norm = [severity: sev, scenario: scenario, correlationId: corr,
                errorPhrase: (j.errorPhrase ?: j.message ?: ''), messageId: (j.messageId ?: ''),
                ts: (j.timestamp ?: '')]
    message.setHeader('Content-Type','application/json')
    message.setBody(JsonOutput.toJson(norm))
    return message
}
