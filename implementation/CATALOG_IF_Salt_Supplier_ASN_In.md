# Integration Catalog: IF_Salt_Supplier_ASN_In
## 1. Executive Summary
- **Purpose:** Konfirmimi/ASN i furnitorit (ORDRSP) → përditëson PO në ERP.
- **Source:** S/4/Supplier (IDoc) · **Target:** ERP `receive_asn.php` · **Status:** v1.0.0
## 2. Source Map
| Evidence | Source |
|---|---|
| E1 | `ORDRSP_PO_to_Canonical.xsl`, `SupplierEvent.xsd` |
| E2 | ERP `receive_asn.php` (purchase_order status) |
## 4–8. Flow
1. IDoc `/salt/asn` (ORDERS05/ORDRSP me partner LF).
2. XSLT → SupplierEvent (PO_CONFIRMED/ASN/PO_REJECTED, namespace default).
3. Token + Groovy → HTTP → `receive_asn.php`.
## 9. Mapping
PoNumber/CorrelationId nga referenca 'PO-...'; E1EDKA1[PARVW=LF]→SupplierId; E1EDP01/E1EDP20→Items/ConfirmedDate.
## 15. Open Points
Qualifiers (IDDAT/QUALF) të konfirmohen; ASN i vërtetë (DESADV) me sasi të dërguara mund të zgjerohet.
