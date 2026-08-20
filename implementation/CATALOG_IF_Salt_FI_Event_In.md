# Integration Catalog: IF_Salt_FI_Event_In

## 1. Executive Summary
- **Purpose:** Merr ngjarjet financiare (FI posting / clearing pagese) nga S/4 dhe përditëson ERP.
- **Business process:** O2C — Finance (Phase 4). **Technology:** SAP CI/CPI. **Priority:** P2. **Status:** v1.0.0.
- **Source:** SAP S/4 FI (event/IDoc) · **Target:** ERP `receive_finance.php`

## 2. Source Map
| Evidence | Source | Usage |
|---|---|---|
| E1 | `FIEvent_to_Canonical.xsl` | Mapping FI_POSTED / PAYMENT_CLEARED |
| E2 | `FinanceEvent.xsd` | Kontrata |
| E3 | ERP `receive_finance.php`, finance_document/payment | Persistim |

## 3. Interface Identity
| Field | Value |
|---|---|
| integration_flow_id | IF_Salt_FI_Event_In |
| package_name | AlbsaleVloraO2C |
| source_system | SAP S/4HANA FI |
| target_system | Custom ERP |

## 4–8. Flow
1. HTTPS Sender `/salt/fi` pranon njoftimin financiar.
2. XSLT → FinanceEvent; EventType = PAYMENT_CLEARED nëse ka ClearingDocument, ndryshe FI_POSTED.
3. Content Modifier token + Groovy + HTTP Receiver → `receive_finance.php`.

## 9. Data Contract & Mapping
| FI event | Canonical |
|---|---|
| AccountingDocument, CompanyCode, FiscalYear, PostingDate, Amount, Currency | Accounting/* |
| ClearingDocument, ClearingDate, PaymentReference, AmountPaid | Payment/* |
| ReferenceDocument/BillingDocument | Reference/InvoiceNo |
| DocumentHeaderText/Note (SALT-...) | CorrelationId (echo) |

## 10. Security
- Sender Client Certificate; Receiver me `X-Inbound-Token`.

## 11. Error Handling & Observability
- `receive_finance.php` transaksional; finance_document idempotent (PK acc_doc+cc+fy).
- PAYMENT_CLEARED shënon faturën përkatëse si CLEARED.

## 15. Open Points
- Burimi real mund të jetë IDoc FIDCCP02/ACC_DOCUMENT ose Event Mesh; përshtat XPath-et.
- Mapping-u i EventType bazohet në praninë e ClearingDocument.
