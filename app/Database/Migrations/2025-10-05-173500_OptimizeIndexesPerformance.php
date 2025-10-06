<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration para Otimização de Performance
 * Cria índices compostos otimizados para multi-tenant
 */
class OptimizeIndexesPerformance extends Migration
{
    public function up()
    {
        // Índices para pos_sales (vendas)
        $this->createIndexIfNotExists('pos_sales', 'idx_pos_sales_tenant_status_date', [
            'id_contador', 'id_empresa', 'status', 'created_at'
        ]);
        
        $this->createIndexIfNotExists('pos_sales', 'idx_pos_sales_tenant_customer', [
            'id_contador', 'id_empresa', 'customer_id'
        ]);
        
        $this->createIndexIfNotExists('pos_sales', 'idx_pos_sales_tenant_user_date', [
            'id_contador', 'id_empresa', 'user_id', 'created_at'
        ]);
        
        // Índices para pos_sale_items (itens de venda)
        $this->createIndexIfNotExists('pos_sale_items', 'idx_pos_sale_items_tenant_sale', [
            'id_contador', 'id_empresa', 'id_pos_sale'
        ]);
        
        $this->createIndexIfNotExists('pos_sale_items', 'idx_pos_sale_items_tenant_product', [
            'id_contador', 'id_empresa', 'product_id'
        ]);
        
        // Índices para pos_sale_payments (pagamentos)
        $this->createIndexIfNotExists('pos_sale_payments', 'idx_pos_sale_payments_tenant_sale', [
            'id_contador', 'id_empresa', 'id_pos_sale'
        ]);
        
        $this->createIndexIfNotExists('pos_sale_payments', 'idx_pos_sale_payments_tenant_method', [
            'id_contador', 'id_empresa', 'method', 'status'
        ]);
        
        // Índices para produtos
        $this->createIndexIfNotExists('produtos', 'idx_produtos_tenant_status_categoria', [
            'id_contador', 'id_empresa', 'status', 'categoria_id'
        ]);
        
        $this->createIndexIfNotExists('produtos', 'idx_produtos_tenant_codigo', [
            'id_contador', 'id_empresa', 'codigo'
        ]);
        
        $this->createIndexIfNotExists('produtos', 'idx_produtos_tenant_barcode', [
            'id_contador', 'id_empresa', 'codigo_barras'
        ]);
        
        $this->createIndexIfNotExists('produtos', 'idx_produtos_tenant_estoque', [
            'id_contador', 'id_empresa', 'estoque', 'estoque_minimo'
        ]);
        
        // Índice para busca full-text em produtos
        $this->createFullTextIndexIfNotExists('produtos', 'idx_produtos_fulltext', [
            'nome', 'descricao'
        ]);
        
        // Índices para clientes
        $this->createIndexIfNotExists('clientes', 'idx_clientes_tenant_documento', [
            'id_contador', 'id_empresa', 'cpf_cnpj'
        ]);
        
        $this->createIndexIfNotExists('clientes', 'idx_clientes_tenant_status', [
            'id_contador', 'id_empresa', 'status'
        ]);
        
        // Índices para fornecedores
        $this->createIndexIfNotExists('fornecedores', 'idx_fornecedores_tenant_cnpj', [
            'id_contador', 'id_empresa', 'cnpj'
        ]);
        
        // Índices para configurações
        $this->createIndexIfNotExists('configuracoes', 'idx_configuracoes_tenant_chave', [
            'id_contador', 'id_empresa', 'chave'
        ]);
        
        // Índices para cash_registers (caixas)
        $this->createIndexIfNotExists('cash_registers', 'idx_cash_registers_tenant_status', [
            'id_contador', 'id_empresa', 'status'
        ]);
        
        // Índices para cash_movements (movimentações de caixa)
        $this->createIndexIfNotExists('cash_movements', 'idx_cash_movements_tenant_register_date', [
            'id_contador', 'id_empresa', 'cash_register_id', 'created_at'
        ]);
        
        // Índices para inventory_movements (movimentações de estoque)
        $this->createIndexIfNotExists('inventory_movements', 'idx_inventory_movements_tenant_product_date', [
            'id_contador', 'id_empresa', 'product_id', 'created_at'
        ]);
        
        // Índices para empresas
        $this->createIndexIfNotExists('empresas', 'idx_empresas_contador_status', [
            'id_contador', 'status'
        ]);
        
        // Índices para usuários
        $this->createIndexIfNotExists('usuarios', 'idx_usuarios_tenant_email', [
            'id_contador', 'id_empresa', 'email'
        ]);
        
        $this->createIndexIfNotExists('usuarios', 'idx_usuarios_tenant_status', [
            'id_contador', 'id_empresa', 'status'
        ]);
        
        // Índices para categorias
        $this->createIndexIfNotExists('categorias', 'idx_categorias_tenant_status', [
            'id_contador', 'id_empresa', 'status'
        ]);
        
        echo "Índices de performance criados com sucesso!\n";
    }

    public function down()
    {
        // Remover índices criados (opcional)
        $indexes = [
            'pos_sales' => [
                'idx_pos_sales_tenant_status_date',
                'idx_pos_sales_tenant_customer',
                'idx_pos_sales_tenant_user_date'
            ],
            'pos_sale_items' => [
                'idx_pos_sale_items_tenant_sale',
                'idx_pos_sale_items_tenant_product'
            ],
            'pos_sale_payments' => [
                'idx_pos_sale_payments_tenant_sale',
                'idx_pos_sale_payments_tenant_method'
            ],
            'produtos' => [
                'idx_produtos_tenant_status_categoria',
                'idx_produtos_tenant_codigo',
                'idx_produtos_tenant_barcode',
                'idx_produtos_tenant_estoque',
                'idx_produtos_fulltext'
            ],
            'clientes' => [
                'idx_clientes_tenant_documento',
                'idx_clientes_tenant_status'
            ],
            'fornecedores' => [
                'idx_fornecedores_tenant_cnpj'
            ],
            'configuracoes' => [
                'idx_configuracoes_tenant_chave'
            ],
            'cash_registers' => [
                'idx_cash_registers_tenant_status'
            ],
            'cash_movements' => [
                'idx_cash_movements_tenant_register_date'
            ],
            'inventory_movements' => [
                'idx_inventory_movements_tenant_product_date'
            ],
            'empresas' => [
                'idx_empresas_contador_status'
            ],
            'usuarios' => [
                'idx_usuarios_tenant_email',
                'idx_usuarios_tenant_status'
            ],
            'categorias' => [
                'idx_categorias_tenant_status'
            ]
        ];
        
        foreach ($indexes as $table => $tableIndexes) {
            if ($this->db->tableExists($table)) {
                foreach ($tableIndexes as $indexName) {
                    $this->dropIndexIfExists($table, $indexName);
                }
            }
        }
    }
    
    /**
     * Cria índice se não existir
     */
    private function createIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        if (!$this->db->tableExists($table)) {
            echo "Tabela {$table} não existe, pulando índice {$indexName}\n";
            return;
        }
        
        try {
            // Verificar se índice já existe
            $existingIndexes = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'")->getResultArray();
            
            if (empty($existingIndexes)) {
                $columnList = implode(', ', $columns);
                $sql = "CREATE INDEX {$indexName} ON {$table} ({$columnList})";
                $this->db->query($sql);
                echo "✓ Índice {$indexName} criado na tabela {$table}\n";
            } else {
                echo "- Índice {$indexName} já existe na tabela {$table}\n";
            }
            
        } catch (\Throwable $e) {
            echo "✗ Erro ao criar índice {$indexName} na tabela {$table}: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Cria índice full-text se não existir
     */
    private function createFullTextIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        if (!$this->db->tableExists($table)) {
            echo "Tabela {$table} não existe, pulando índice full-text {$indexName}\n";
            return;
        }
        
        try {
            // Verificar se índice já existe
            $existingIndexes = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'")->getResultArray();
            
            if (empty($existingIndexes)) {
                $columnList = implode(', ', $columns);
                $sql = "CREATE FULLTEXT INDEX {$indexName} ON {$table} ({$columnList})";
                $this->db->query($sql);
                echo "✓ Índice full-text {$indexName} criado na tabela {$table}\n";
            } else {
                echo "- Índice full-text {$indexName} já existe na tabela {$table}\n";
            }
            
        } catch (\Throwable $e) {
            echo "✗ Erro ao criar índice full-text {$indexName} na tabela {$table}: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Remove índice se existir
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            $existingIndexes = $this->db->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'")->getResultArray();
            
            if (!empty($existingIndexes)) {
                $sql = "DROP INDEX {$indexName} ON {$table}";
                $this->db->query($sql);
                echo "✓ Índice {$indexName} removido da tabela {$table}\n";
            }
            
        } catch (\Throwable $e) {
            echo "✗ Erro ao remover índice {$indexName} da tabela {$table}: " . $e->getMessage() . "\n";
        }
    }
}
