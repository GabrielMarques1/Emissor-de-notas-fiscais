<?php

namespace App\Libraries;

use Config\Database;

/**
 * Otimizador de Queries - Elimina N+1 e implementa Eager Loading
 * Reduz drasticamente o número de queries executadas
 */
class QueryOptimizer
{
    private $db;
    private $queryLog = [];
    private $enableLogging = false;
    
    public function __construct()
    {
        $this->db = Database::connect();
    }
    
    /**
     * Habilita logging de queries para análise
     */
    public function enableQueryLogging(): void
    {
        $this->enableLogging = true;
        $this->queryLog = [];
    }
    
    /**
     * Carrega vendas com itens e pagamentos (Eager Loading)
     * Elimina N+1 queries
     */
    public function getSalesWithDetails(int $idContador, int $idEmpresa, array $filters = [], int $limit = 50): array
    {
        $startTime = microtime(true);
        
        // Query principal otimizada com JOINs
        $builder = $this->db->table('pos_sales ps')
            ->select('
                ps.*,
                c.nome as cliente_nome,
                c.cpf_cnpj as cliente_documento,
                u.nome as usuario_nome,
                COUNT(DISTINCT psi.id) as total_items,
                COUNT(DISTINCT psp.id) as total_payments,
                COALESCE(SUM(psi.quantity * psi.price), 0) as calculated_total
            ')
            ->join('clientes c', 'c.id_cliente = ps.customer_id', 'left')
            ->join('usuarios u', 'u.id = ps.user_id', 'left')
            ->join('pos_sale_items psi', 'psi.id_pos_sale = ps.id_pos_sale', 'left')
            ->join('pos_sale_payments psp', 'psp.id_pos_sale = ps.id_pos_sale', 'left')
            ->where('ps.id_contador', $idContador)
            ->where('ps.id_empresa', $idEmpresa)
            ->groupBy('ps.id_pos_sale');
        
        // Aplicar filtros
        if (!empty($filters['status'])) {
            $builder->where('ps.status', $filters['status']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder->where('DATE(ps.created_at) >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $builder->where('DATE(ps.created_at) <=', $filters['date_to']);
        }
        
        if (!empty($filters['customer_id'])) {
            $builder->where('ps.customer_id', $filters['customer_id']);
        }
        
        $sales = $builder->limit($limit)->get()->getResultArray();
        
        if (empty($sales)) {
            return ['data' => [], 'meta' => ['query_time' => microtime(true) - $startTime, 'queries_count' => 1]];
        }
        
        // Extrair IDs das vendas para carregar detalhes
        $saleIds = array_column($sales, 'id_pos_sale');
        
        // Carregar todos os itens de uma vez (elimina N+1)
        $items = $this->db->table('pos_sale_items psi')
            ->select('
                psi.*,
                p.nome as produto_nome,
                p.codigo as produto_codigo,
                p.unidade as produto_unidade,
                p.categoria_id
            ')
            ->join('produtos p', 'p.id_produto = psi.product_id', 'left')
            ->whereIn('psi.id_pos_sale', $saleIds)
            ->where('psi.id_contador', $idContador)
            ->where('psi.id_empresa', $idEmpresa)
            ->orderBy('psi.id_pos_sale, psi.id')
            ->get()
            ->getResultArray();
        
        // Carregar todos os pagamentos de uma vez (elimina N+1)
        $payments = $this->db->table('pos_sale_payments psp')
            ->whereIn('psp.id_pos_sale', $saleIds)
            ->where('psp.id_contador', $idContador)
            ->where('psp.id_empresa', $idEmpresa)
            ->orderBy('psp.id_pos_sale, psp.id')
            ->get()
            ->getResultArray();
        
        // Agrupar itens e pagamentos por venda
        $itemsBySale = [];
        foreach ($items as $item) {
            $itemsBySale[$item['id_pos_sale']][] = $item;
        }
        
        $paymentsBySale = [];
        foreach ($payments as $payment) {
            $paymentsBySale[$payment['id_pos_sale']][] = $payment;
        }
        
        // Associar dados às vendas
        foreach ($sales as &$sale) {
            $saleId = $sale['id_pos_sale'];
            $sale['items'] = $itemsBySale[$saleId] ?? [];
            $sale['payments'] = $paymentsBySale[$saleId] ?? [];
        }
        
        $queryTime = microtime(true) - $startTime;
        
        return [
            'data' => $sales,
            'meta' => [
                'query_time' => $queryTime,
                'queries_count' => 3, // Apenas 3 queries vs N+1
                'records_count' => count($sales)
            ]
        ];
    }
    
    /**
     * Carrega produtos com categorias e fornecedores (Eager Loading)
     */
    public function getProductsWithRelations(int $idContador, int $idEmpresa, array $filters = [], int $limit = 50): array
    {
        $startTime = microtime(true);
        
        $builder = $this->db->table('produtos p')
            ->select('
                p.*,
                c.nome as categoria_nome,
                c.descricao as categoria_descricao,
                f.razao_social as fornecedor_nome,
                f.cnpj as fornecedor_cnpj,
                CASE 
                    WHEN p.estoque <= p.estoque_minimo THEN "low"
                    WHEN p.estoque <= p.estoque_minimo * 2 THEN "medium"
                    ELSE "ok"
                END as stock_status
            ')
            ->join('categorias c', 'c.id_categoria = p.categoria_id', 'left')
            ->join('fornecedores f', 'f.id_fornecedor = p.fornecedor_id', 'left')
            ->where('p.id_contador', $idContador)
            ->where('p.id_empresa', $idEmpresa);
        
        // Aplicar filtros
        if (!empty($filters['categoria_id'])) {
            $builder->where('p.categoria_id', $filters['categoria_id']);
        }
        
        if (!empty($filters['fornecedor_id'])) {
            $builder->where('p.fornecedor_id', $filters['fornecedor_id']);
        }
        
        if (!empty($filters['status'])) {
            $builder->where('p.status', $filters['status']);
        }
        
        if (!empty($filters['low_stock'])) {
            $builder->where('p.estoque <=', 'p.estoque_minimo', false);
        }
        
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('p.nome', $filters['search'])
                ->orLike('p.codigo', $filters['search'])
                ->orLike('p.codigo_barras', $filters['search'])
                ->groupEnd();
        }
        
        $products = $builder->limit($limit)->get()->getResultArray();
        
        $queryTime = microtime(true) - $startTime;
        
        return [
            'data' => $products,
            'meta' => [
                'query_time' => $queryTime,
                'queries_count' => 1, // Apenas 1 query com JOINs
                'records_count' => count($products)
            ]
        ];
    }
    
    /**
     * Dashboard otimizado com uma única query complexa
     */
    public function getDashboardData(int $idContador, int $idEmpresa): array
    {
        $startTime = microtime(true);
        
        // Query complexa que busca tudo de uma vez
        $dashboardData = $this->db->query("
            SELECT 
                -- Vendas de hoje
                (SELECT COALESCE(SUM(total), 0) 
                 FROM pos_sales 
                 WHERE id_contador = ? AND id_empresa = ? 
                 AND DATE(created_at) = CURDATE() 
                 AND status = 'completed') as sales_today_total,
                
                (SELECT COUNT(*) 
                 FROM pos_sales 
                 WHERE id_contador = ? AND id_empresa = ? 
                 AND DATE(created_at) = CURDATE() 
                 AND status = 'completed') as sales_today_count,
                
                -- Vendas do mês
                (SELECT COALESCE(SUM(total), 0) 
                 FROM pos_sales 
                 WHERE id_contador = ? AND id_empresa = ? 
                 AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') 
                 AND status = 'completed') as sales_month_total,
                
                (SELECT COUNT(*) 
                 FROM pos_sales 
                 WHERE id_contador = ? AND id_empresa = ? 
                 AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') 
                 AND status = 'completed') as sales_month_count,
                
                -- Produtos com estoque baixo
                (SELECT COUNT(*) 
                 FROM produtos 
                 WHERE id_contador = ? AND id_empresa = ? 
                 AND estoque <= estoque_minimo 
                 AND status = 'ativo') as low_stock_count,
                
                -- Total de produtos ativos
                (SELECT COUNT(*) 
                 FROM produtos 
                 WHERE id_contador = ? AND id_empresa = ? 
                 AND status = 'ativo') as total_products,
                
                -- Total de clientes ativos
                (SELECT COUNT(*) 
                 FROM clientes 
                 WHERE id_contador = ? AND id_empresa = ? 
                 AND status = 'ativo') as total_customers,
                
                -- Caixa aberto
                (SELECT cr.id 
                 FROM cash_registers cr 
                 WHERE cr.id_contador = ? AND cr.id_empresa = ? 
                 AND cr.status = 'open' 
                 LIMIT 1) as open_cash_register
        ", [
            $idContador, $idEmpresa, // sales_today_total
            $idContador, $idEmpresa, // sales_today_count
            $idContador, $idEmpresa, // sales_month_total
            $idContador, $idEmpresa, // sales_month_count
            $idContador, $idEmpresa, // low_stock_count
            $idContador, $idEmpresa, // total_products
            $idContador, $idEmpresa, // total_customers
            $idContador, $idEmpresa  // open_cash_register
        ])->getRowArray();
        
        // Top produtos vendidos (query separada otimizada)
        $topProducts = $this->db->query("
            SELECT 
                p.nome as product_name,
                p.codigo as product_code,
                SUM(psi.quantity) as total_quantity,
                SUM(psi.quantity * psi.price) as total_revenue,
                COUNT(DISTINCT psi.id_pos_sale) as sales_count
            FROM pos_sale_items psi
            JOIN pos_sales ps ON ps.id_pos_sale = psi.id_pos_sale
            JOIN produtos p ON p.id_produto = psi.product_id
            WHERE psi.id_contador = ? AND psi.id_empresa = ?
            AND ps.status = 'completed'
            AND DATE_FORMAT(ps.created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
            GROUP BY psi.product_id, p.nome, p.codigo
            ORDER BY total_quantity DESC
            LIMIT 10
        ", [$idContador, $idEmpresa])->getResultArray();
        
        $queryTime = microtime(true) - $startTime;
        
        return [
            'summary' => $dashboardData,
            'top_products' => $topProducts,
            'meta' => [
                'query_time' => $queryTime,
                'queries_count' => 2, // Apenas 2 queries complexas vs múltiplas simples
                'generated_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    /**
     * Relatório de vendas otimizado com agregações
     */
    public function getSalesReport(int $idContador, int $idEmpresa, string $dateFrom, string $dateTo, string $groupBy = 'day'): array
    {
        $startTime = microtime(true);
        
        // Determinar formato de agrupamento
        $dateFormat = match($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d'
        };
        
        // Query otimizada com agregações
        $salesData = $this->db->query("
            SELECT 
                DATE_FORMAT(ps.created_at, ?) as period,
                COUNT(ps.id_pos_sale) as sales_count,
                SUM(ps.total) as total_revenue,
                AVG(ps.total) as avg_sale_value,
                SUM(psi.quantity) as total_items_sold,
                COUNT(DISTINCT ps.customer_id) as unique_customers,
                COUNT(DISTINCT ps.user_id) as active_users
            FROM pos_sales ps
            LEFT JOIN pos_sale_items psi ON psi.id_pos_sale = ps.id_pos_sale 
                AND psi.id_contador = ps.id_contador 
                AND psi.id_empresa = ps.id_empresa
            WHERE ps.id_contador = ? 
            AND ps.id_empresa = ?
            AND ps.status = 'completed'
            AND DATE(ps.created_at) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(ps.created_at, ?)
            ORDER BY period ASC
        ", [$dateFormat, $idContador, $idEmpresa, $dateFrom, $dateTo, $dateFormat])->getResultArray();
        
        // Métodos de pagamento mais usados no período
        $paymentMethods = $this->db->query("
            SELECT 
                psp.method,
                COUNT(*) as usage_count,
                SUM(psp.amount) as total_amount,
                AVG(psp.amount) as avg_amount
            FROM pos_sale_payments psp
            JOIN pos_sales ps ON ps.id_pos_sale = psp.id_pos_sale
            WHERE psp.id_contador = ? 
            AND psp.id_empresa = ?
            AND ps.status = 'completed'
            AND DATE(ps.created_at) BETWEEN ? AND ?
            GROUP BY psp.method
            ORDER BY usage_count DESC
        ", [$idContador, $idEmpresa, $dateFrom, $dateTo])->getResultArray();
        
        $queryTime = microtime(true) - $startTime;
        
        return [
            'sales_data' => $salesData,
            'payment_methods' => $paymentMethods,
            'period' => ['from' => $dateFrom, 'to' => $dateTo, 'group_by' => $groupBy],
            'meta' => [
                'query_time' => $queryTime,
                'queries_count' => 2,
                'records_count' => count($salesData)
            ]
        ];
    }
    
    /**
     * Busca otimizada de produtos com full-text search
     */
    public function searchProducts(int $idContador, int $idEmpresa, string $search, int $limit = 20): array
    {
        $startTime = microtime(true);
        
        // Query otimizada com MATCH AGAINST para full-text search
        $products = $this->db->query("
            SELECT 
                p.*,
                c.nome as categoria_nome,
                MATCH(p.nome, p.descricao) AGAINST(? IN BOOLEAN MODE) as relevance_score
            FROM produtos p
            LEFT JOIN categorias c ON c.id_categoria = p.categoria_id 
                AND c.id_contador = p.id_contador 
                AND c.id_empresa = p.id_empresa
            WHERE p.id_contador = ? 
            AND p.id_empresa = ?
            AND p.status = 'ativo'
            AND (
                MATCH(p.nome, p.descricao) AGAINST(? IN BOOLEAN MODE)
                OR p.codigo LIKE ?
                OR p.codigo_barras LIKE ?
            )
            ORDER BY relevance_score DESC, p.nome ASC
            LIMIT ?
        ", [
            $search,
            $idContador, 
            $idEmpresa,
            $search,
            "%{$search}%",
            "%{$search}%",
            $limit
        ])->getResultArray();
        
        $queryTime = microtime(true) - $startTime;
        
        return [
            'data' => $products,
            'search_term' => $search,
            'meta' => [
                'query_time' => $queryTime,
                'queries_count' => 1,
                'records_count' => count($products)
            ]
        ];
    }
    
    /**
     * Retorna log de queries executadas
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
    
    /**
     * Analisa performance de uma query específica
     */
    public function analyzeQuery(string $sql, array $params = []): array
    {
        $startTime = microtime(true);
        
        // Executar EXPLAIN
        $explainSql = "EXPLAIN " . $sql;
        $explain = $this->db->query($explainSql, $params)->getResultArray();
        
        // Executar query real para medir tempo
        $result = $this->db->query($sql, $params);
        $executionTime = microtime(true) - $startTime;
        
        return [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
            'rows_returned' => $result->getNumRows(),
            'explain' => $explain,
            'recommendations' => $this->generateQueryRecommendations($explain)
        ];
    }
    
    /**
     * Gera recomendações baseadas no EXPLAIN
     */
    private function generateQueryRecommendations(array $explain): array
    {
        $recommendations = [];
        
        foreach ($explain as $row) {
            // Verificar table scan
            if ($row['type'] === 'ALL') {
                $recommendations[] = "Table scan detected on '{$row['table']}' - consider adding appropriate index";
            }
            
            // Verificar uso de índices
            if (empty($row['key'])) {
                $recommendations[] = "No index used for table '{$row['table']}' - add index on WHERE/JOIN columns";
            }
            
            // Verificar número de rows examinadas
            if (isset($row['rows']) && $row['rows'] > 1000) {
                $recommendations[] = "High row count ({$row['rows']}) examined on '{$row['table']}' - optimize WHERE conditions";
            }
            
            // Verificar filesort
            if (isset($row['Extra']) && strpos($row['Extra'], 'Using filesort') !== false) {
                $recommendations[] = "Filesort detected on '{$row['table']}' - consider adding index for ORDER BY";
            }
            
            // Verificar temporary table
            if (isset($row['Extra']) && strpos($row['Extra'], 'Using temporary') !== false) {
                $recommendations[] = "Temporary table used for '{$row['table']}' - optimize GROUP BY/ORDER BY";
            }
        }
        
        return $recommendations;
    }
}
