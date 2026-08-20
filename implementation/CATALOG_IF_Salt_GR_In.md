# Integration Catalog: IF_Salt_GR_In
## 1. Executive Summary
- **Purpose:** Goods Receipt (MBGMCR) → rimbush stokun në ERP dhe nis BOP.
- **Source:** S/4 (IDoc MBGMCR) · **Target:** ERP `receive_goodsreceipt.php` · **Status:** v1.0.0
## 2. Source Map
| Evidence | Source |
|---|---|
| E1 | `MBGMCR_to_GR.xsl`, `GoodsReceipt.xsd` |
| E2 | ERP `receive_goodsreceipt.php` + `bop_reconfirm` |
## 4–8. Flow
1. IDoc `/salt/gr` (MBGMCR — BAPI Goods Movement).
2. XSLT → GoodsReceipt (MAT_DOC, PO_NUMBER, items MATERIAL/ENTRY_QNT/BATCH).
3. Token + Groovy → HTTP → `receive_goodsreceipt.php` (transaksional): stock += qty, on_order -=, BOP FIFO.
## 9. Mapping
E1BP2017_GM_HEAD_RET/MAT_DOC→MaterialDocument; E1BP2017_GM_ITEM_CREATE→Items (MATERIAL/ENTRY_QNT/PO_NUMBER/BATCH/STGE_LOC).
## 11. Error Handling
Transaksional (rollback); idempotencë me MAT_DOC (rekomandohet unique guard në prod).
## 15. Open Points
Movement types përveç 101 (102 storno) të trajtohen; batch management opsional.
