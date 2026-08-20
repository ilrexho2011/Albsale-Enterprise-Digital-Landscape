# Integration Catalog: IF_Salt_PO_Send
## 1. Executive Summary
- **Purpose:** Krijon Purchase Order në S/4 MM nga riblerja e ERP-së (IDoc PORDCR).
- **Source:** ERP `reorder_check.php` · **Target:** S/4HANA MM · **Tech:** SAP CI/CPI · **Status:** v1.0.0
## 2. Source Map
| Evidence | Source |
|---|---|
| E1 | `Canonical_to_PORDCR.xsl`, `PurchaseOrder.xsd`, `PORDCR_subset.xsd` |
| E2 | ERP `src/Lib/procurement.php` (build_po_canonical) |
## 4–8. Flow
1. HTTPS `/salt/po` pranon PurchaseOrder kanonike.
2. Content Modifier: purch_org/group/po_type (Externalized).
3. XSLT → IDoc PORDCR05; IDoc receiver → S/4.
## 9. Mapping
Header→E1PORDCR5 (LIFNR/EKORG/EKGRP/WAERS/REF_DOC); Line→E1BPEKPOC (PO_ITEM/MATERIAL/QUANTITY/PO_UNIT/NET_PRICE/DELIV_DATE).
## 10. Security
Sender Client Certificate; IDoc receiver Security Material `{{s4_credential_alias}}`.
## 15. Open Points
BSART/EKORG/EKGRP placeholder (NB/1000/001) — konfirmo me MM customizing. Alternativë: EDIFACT ORDERS (850) drejt furnitorit.
