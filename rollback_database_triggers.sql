-- ROLLBACK DOS TRIGGERS DE PROTEÇÃO
-- Gerado automaticamente em 2025-10-05 23:58:44

DROP TRIGGER IF EXISTS trg_pos_sales_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_pos_sales_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_pos_sales_delete_audit;
DROP TRIGGER IF EXISTS trg_pos_sale_items_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_pos_sale_items_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_pos_sale_items_delete_audit;
DROP TRIGGER IF EXISTS trg_pos_sale_payments_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_pos_sale_payments_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_pos_sale_payments_delete_audit;
DROP TRIGGER IF EXISTS trg_produtos_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_produtos_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_produtos_delete_audit;
DROP TRIGGER IF EXISTS trg_clientes_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_clientes_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_clientes_delete_audit;
DROP TRIGGER IF EXISTS trg_fornecedores_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_fornecedores_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_fornecedores_delete_audit;
DROP TRIGGER IF EXISTS trg_cash_registers_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_cash_registers_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_cash_registers_delete_audit;
DROP TRIGGER IF EXISTS trg_cash_movements_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_cash_movements_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_cash_movements_delete_audit;
DROP TRIGGER IF EXISTS trg_inventory_movements_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_inventory_movements_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_inventory_movements_delete_audit;
DROP TRIGGER IF EXISTS trg_shifts_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_shifts_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_shifts_delete_audit;
DROP TRIGGER IF EXISTS trg_empresas_insert_tenant_validation;
DROP TRIGGER IF EXISTS trg_empresas_update_tenant_protection;
DROP TRIGGER IF EXISTS trg_empresas_delete_audit;