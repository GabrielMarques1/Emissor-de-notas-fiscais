-- Backup do Tenant 1:3
-- Data: 2025-10-06 01:28:21
-- Tipo: Completo

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `pos_sales`;
CREATE TABLE `pos_sales` (
  `id_pos_sale` int(9) unsigned NOT NULL AUTO_INCREMENT,
  `id_shift` int(9) unsigned NOT NULL,
  `id_caixa_sessao` int(11) DEFAULT NULL,
  `id_cash_register` int(9) unsigned NOT NULL,
  `sale_number` varchar(32) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `total_discount` decimal(10,2) DEFAULT 0.00 COMMENT 'Total de descontos aplicados',
  `discount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  `payment_type` varchar(16) NOT NULL,
  `is_multi_payment` tinyint(1) DEFAULT 0 COMMENT 'Indica se usa múltiplas formas de pagamento',
  `total_paid` decimal(10,2) DEFAULT NULL COMMENT 'Soma dos pagamentos (validação)',
  `id_tef_transaction` int(11) unsigned DEFAULT NULL COMMENT 'FK para tef_transactions',
  `id_pix_transaction` int(11) unsigned DEFAULT NULL COMMENT 'FK para pix_transactions',
  `id_cliente` int(11) DEFAULT NULL,
  `notes` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL,
  `is_suspended` tinyint(1) DEFAULT 0 COMMENT 'Venda está suspensa (pausada)',
  `suspended_at` datetime DEFAULT NULL COMMENT 'Data/hora de suspensão',
  `suspended_by` int(11) DEFAULT NULL COMMENT 'ID do operador que suspendeu',
  `suspended_reason` varchar(255) DEFAULT NULL COMMENT 'Motivo da suspensão',
  `resumed_at` datetime DEFAULT NULL COMMENT 'Data/hora de retomada',
  `resumed_by` int(11) DEFAULT NULL COMMENT 'ID do operador que retomou',
  `suspension_expires_at` datetime DEFAULT NULL COMMENT 'Suspensão expira automaticamente após X horas',
  `id_contador` int(9) NOT NULL,
  `id_empresa` int(9) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime NOT NULL,
  `id_nfce` int(9) DEFAULT NULL,
  `chave_nfce` varchar(55) DEFAULT NULL,
  PRIMARY KEY (`id_pos_sale`),
  UNIQUE KEY `pos_sales_empresa_sale_unique` (`id_empresa`,`sale_number`),
  KEY `id_shift` (`id_shift`),
  KEY `id_cash_register` (`id_cash_register`),
  KEY `id_contador` (`id_contador`),
  KEY `id_empresa` (`id_empresa`),
  KEY `status` (`status`),
  KEY `payment_type` (`payment_type`),
  KEY `idx_is_suspended` (`is_suspended`,`id_contador`,`id_empresa`),
  KEY `idx_suspended_at` (`suspended_at`),
  KEY `idx_suspension_expires` (`suspension_expires_at`),
  KEY `idx_pos_sales_tenant_date` (`id_empresa`,`id_contador`,`created_at`,`status`),
  KEY `idx_pos_sales_sale_number` (`sale_number`,`id_empresa`),
  KEY `idx_pos_sales_cliente` (`id_cliente`,`id_empresa`,`status`),
  KEY `idx_pos_sales_tenant_status_date` (`id_contador`,`id_empresa`,`status`,`created_at`),
  KEY `idx_pos_sales_tenant_optimized` (`id_contador`,`id_empresa`,`status`,`created_at`),
  CONSTRAINT `pos_sales_id_contador_foreign` FOREIGN KEY (`id_contador`) REFERENCES `contadores` (`id_contador`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pos_sales_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pos_sales_id_shift_foreign` FOREIGN KEY (`id_shift`) REFERENCES `shifts` (`id_shift`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dados da tabela pos_sales
INSERT INTO `pos_sales` (`id_pos_sale`, `id_shift`, `id_caixa_sessao`, `id_cash_register`, `sale_number`, `total`, `total_discount`, `discount`, `paid_amount`, `change_amount`, `payment_type`, `is_multi_payment`, `total_paid`, `id_tef_transaction`, `id_pix_transaction`, `id_cliente`, `notes`, `status`, `is_suspended`, `suspended_at`, `suspended_by`, `suspended_reason`, `resumed_at`, `resumed_by`, `suspension_expires_at`, `id_contador`, `id_empresa`, `created_at`, `updated_at`, `deleted_at`, `id_nfce`, `chave_nfce`) VALUES ('1', '1', NULL, '1', 'PDV-1758835107', '0.00', '0.00', '0.00', '0.00', '0.00', 'cash', '0', NULL, NULL, NULL, NULL, '', 'draft', '0', NULL, NULL, NULL, NULL, NULL, NULL, '1', '3', '2025-09-25 21:18:27', '2025-09-25 21:18:27', '0000-00-00 00:00:00', NULL, NULL);
INSERT INTO `pos_sales` (`id_pos_sale`, `id_shift`, `id_caixa_sessao`, `id_cash_register`, `sale_number`, `total`, `total_discount`, `discount`, `paid_amount`, `change_amount`, `payment_type`, `is_multi_payment`, `total_paid`, `id_tef_transaction`, `id_pix_transaction`, `id_cliente`, `notes`, `status`, `is_suspended`, `suspended_at`, `suspended_by`, `suspended_reason`, `resumed_at`, `resumed_by`, `suspension_expires_at`, `id_contador`, `id_empresa`, `created_at`, `updated_at`, `deleted_at`, `id_nfce`, `chave_nfce`) VALUES ('2', '2', '2', '1', 'PDV-1759376970', '1.00', '0.00', '0.00', '1.00', '0.00', 'cash', '0', NULL, NULL, NULL, NULL, '', 'finalized', '0', NULL, NULL, NULL, NULL, NULL, NULL, '1', '3', '2025-10-02 03:49:30', '2025-10-02 03:50:31', '0000-00-00 00:00:00', NULL, NULL);
INSERT INTO `pos_sales` (`id_pos_sale`, `id_shift`, `id_caixa_sessao`, `id_cash_register`, `sale_number`, `total`, `total_discount`, `discount`, `paid_amount`, `change_amount`, `payment_type`, `is_multi_payment`, `total_paid`, `id_tef_transaction`, `id_pix_transaction`, `id_cliente`, `notes`, `status`, `is_suspended`, `suspended_at`, `suspended_by`, `suspended_reason`, `resumed_at`, `resumed_by`, `suspension_expires_at`, `id_contador`, `id_empresa`, `created_at`, `updated_at`, `deleted_at`, `id_nfce`, `chave_nfce`) VALUES ('3', '2', '2', '1', 'PDV-1759377056', '1.00', '0.00', '0.00', '1.00', '0.00', 'debit', '0', NULL, NULL, NULL, NULL, '', 'finalized', '0', NULL, NULL, NULL, NULL, NULL, NULL, '1', '3', '2025-10-02 03:50:56', '2025-10-02 13:02:53', '0000-00-00 00:00:00', NULL, NULL);
INSERT INTO `pos_sales` (`id_pos_sale`, `id_shift`, `id_caixa_sessao`, `id_cash_register`, `sale_number`, `total`, `total_discount`, `discount`, `paid_amount`, `change_amount`, `payment_type`, `is_multi_payment`, `total_paid`, `id_tef_transaction`, `id_pix_transaction`, `id_cliente`, `notes`, `status`, `is_suspended`, `suspended_at`, `suspended_by`, `suspended_reason`, `resumed_at`, `resumed_by`, `suspension_expires_at`, `id_contador`, `id_empresa`, `created_at`, `updated_at`, `deleted_at`, `id_nfce`, `chave_nfce`) VALUES ('4', '2', NULL, '1', 'PDV-1759411048', '0.00', '0.00', '0.00', '0.00', '0.00', 'cash', '0', NULL, NULL, NULL, NULL, '', 'draft', '0', NULL, NULL, NULL, NULL, NULL, NULL, '1', '3', '2025-10-02 13:17:28', '2025-10-02 13:17:28', '0000-00-00 00:00:00', NULL, NULL);

DROP TABLE IF EXISTS `pos_sale_items`;
CREATE TABLE `pos_sale_items` (
  `id_item` int(9) unsigned NOT NULL AUTO_INCREMENT,
  `id_pos_sale` int(9) unsigned NOT NULL,
  `id_contador` int(9) unsigned DEFAULT NULL,
  `id_empresa` int(9) unsigned DEFAULT NULL,
  `id_produto` int(11) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `codigo_de_barras` varchar(32) DEFAULT NULL,
  `unidade` varchar(10) DEFAULT NULL,
  `quantidade` decimal(10,3) NOT NULL DEFAULT 1.000,
  `valor_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `desconto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `CFOP_NFe` varchar(4) DEFAULT NULL,
  `CFOP_NFCe` varchar(4) DEFAULT NULL,
  `CFOP_Externo` varchar(4) DEFAULT NULL,
  `NCM` varchar(8) DEFAULT NULL,
  `CSOSN` varchar(4) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id_item`),
  KEY `id_pos_sale` (`id_pos_sale`),
  KEY `idx_pos_sale_items_tenant` (`id_empresa`,`id_contador`),
  KEY `idx_pos_sale_items_sale_tenant` (`id_pos_sale`,`id_empresa`,`id_contador`),
  KEY `idx_pos_sale_items_tenant_sale` (`id_contador`,`id_empresa`,`id_pos_sale`),
  KEY `idx_pos_sale_items_tenant_optimized` (`id_contador`,`id_empresa`,`id_pos_sale`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dados da tabela pos_sale_items
INSERT INTO `pos_sale_items` (`id_item`, `id_pos_sale`, `id_contador`, `id_empresa`, `id_produto`, `nome`, `codigo_de_barras`, `unidade`, `quantidade`, `valor_unitario`, `desconto`, `CFOP_NFe`, `CFOP_NFCe`, `CFOP_Externo`, `NCM`, `CSOSN`, `created_at`, `updated_at`) VALUES ('1', '2', '1', '3', '27', 'IPHONE ', '123', 'UN', '1.000', '1.00', '0.00', '5403', '5102', '6104', '12312323', '103', '2025-10-02 03:50:31', '2025-10-02 03:50:31');

DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
  `id_produto` int(9) NOT NULL AUTO_INCREMENT,
  `nome` varchar(128) NOT NULL,
  `codigo_de_barras` varchar(13) NOT NULL,
  `valor_unitario` double NOT NULL,
  `CFOP_NFe` varchar(4) NOT NULL,
  `CFOP_NFCe` varchar(4) NOT NULL,
  `CFOP_Externo` varchar(4) NOT NULL,
  `NCM` varchar(8) NOT NULL,
  `CSOSN` varchar(3) NOT NULL,
  `id_unidade` int(11) NOT NULL,
  `id_contador` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime NOT NULL,
  `estoque` decimal(10,3) DEFAULT 0.000,
  `estoque_minimo` decimal(12,3) DEFAULT 0.000,
  PRIMARY KEY (`id_produto`),
  KEY `produtos_id_unidade_foreign` (`id_unidade`),
  KEY `produtos_id_contador_foreign` (`id_contador`),
  KEY `produtos_id_empresa_foreign` (`id_empresa`),
  KEY `idx_produtos_updated_at` (`updated_at`),
  KEY `idx_produtos_barcode` (`codigo_de_barras`,`id_empresa`,`id_contador`),
  KEY `idx_produtos_tenant_estoque` (`id_contador`,`id_empresa`,`estoque`,`estoque_minimo`),
  CONSTRAINT `produtos_id_contador_foreign` FOREIGN KEY (`id_contador`) REFERENCES `contadores` (`id_contador`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `produtos_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `produtos_id_unidade_foreign` FOREIGN KEY (`id_unidade`) REFERENCES `unidades` (`id_unidade`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dados da tabela produtos
INSERT INTO `produtos` (`id_produto`, `nome`, `codigo_de_barras`, `valor_unitario`, `CFOP_NFe`, `CFOP_NFCe`, `CFOP_Externo`, `NCM`, `CSOSN`, `id_unidade`, `id_contador`, `id_empresa`, `created_at`, `updated_at`, `deleted_at`, `estoque`, `estoque_minimo`) VALUES ('27', 'IPHONE ', '123', '1', '5403', '5102', '6104', '12312323', '103', '1', '1', '3', '2025-10-02 03:50:10', '2025-10-02 03:50:10', '0000-00-00 00:00:00', '3.000', '0.000');

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id_cliente` int(9) NOT NULL AUTO_INCREMENT,
  `tipo` int(11) NOT NULL,
  `nome` varchar(128) NOT NULL,
  `cpf` varchar(11) NOT NULL,
  `cnpj` varchar(14) NOT NULL,
  `razao_social` varchar(128) NOT NULL,
  `isento` int(11) NOT NULL,
  `ie` varchar(128) NOT NULL,
  `logradouro` varchar(128) NOT NULL,
  `numero` varchar(9) NOT NULL,
  `complemento` varchar(128) NOT NULL,
  `bairro` varchar(128) NOT NULL,
  `cep` varchar(8) NOT NULL,
  `fone` varchar(16) NOT NULL,
  `id_uf` int(11) NOT NULL,
  `id_municipio` int(11) NOT NULL,
  `id_contador` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime NOT NULL,
  PRIMARY KEY (`id_cliente`),
  KEY `clientes_id_uf_foreign` (`id_uf`),
  KEY `clientes_id_municipio_foreign` (`id_municipio`),
  KEY `clientes_id_contador_foreign` (`id_contador`),
  KEY `clientes_id_empresa_foreign` (`id_empresa`),
  KEY `idx_clientes_updated_at` (`updated_at`),
  KEY `idx_clientes_cpf` (`cpf`,`id_empresa`),
  KEY `idx_clientes_cnpj` (`cnpj`,`id_empresa`),
  CONSTRAINT `clientes_id_contador_foreign` FOREIGN KEY (`id_contador`) REFERENCES `contadores` (`id_contador`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `clientes_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `clientes_id_municipio_foreign` FOREIGN KEY (`id_municipio`) REFERENCES `municipios` (`id_municipio`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `clientes_id_uf_foreign` FOREIGN KEY (`id_uf`) REFERENCES `ufs` (`id_uf`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `fornecedores`;
CREATE TABLE `fornecedores` (
  `id_fornecedor` int(9) NOT NULL AUTO_INCREMENT,
  `tipo` int(11) NOT NULL,
  `nome` varchar(128) NOT NULL,
  `cpf` varchar(11) NOT NULL,
  `cnpj` varchar(14) NOT NULL,
  `razao_social` varchar(128) NOT NULL,
  `isento` int(11) NOT NULL,
  `ie` varchar(128) NOT NULL,
  `logradouro` varchar(128) NOT NULL,
  `numero` varchar(9) NOT NULL,
  `complemento` varchar(128) NOT NULL,
  `bairro` varchar(128) NOT NULL,
  `cep` varchar(8) NOT NULL,
  `id_uf` int(11) NOT NULL,
  `id_municipio` int(11) NOT NULL,
  `id_contador` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime NOT NULL,
  PRIMARY KEY (`id_fornecedor`),
  KEY `fornecedores_id_uf_foreign` (`id_uf`),
  KEY `fornecedores_id_municipio_foreign` (`id_municipio`),
  KEY `fornecedores_id_contador_foreign` (`id_contador`),
  KEY `fornecedores_id_empresa_foreign` (`id_empresa`),
  KEY `idx_fornecedores_updated_at` (`updated_at`),
  KEY `idx_fornecedores_tenant_cnpj` (`id_contador`,`id_empresa`,`cnpj`),
  CONSTRAINT `fornecedores_id_contador_foreign` FOREIGN KEY (`id_contador`) REFERENCES `contadores` (`id_contador`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fornecedores_id_empresa_foreign` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fornecedores_id_municipio_foreign` FOREIGN KEY (`id_municipio`) REFERENCES `municipios` (`id_municipio`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fornecedores_id_uf_foreign` FOREIGN KEY (`id_uf`) REFERENCES `ufs` (`id_uf`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

