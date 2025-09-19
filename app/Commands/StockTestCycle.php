<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class StockTestCycle extends BaseCommand
{
    protected $group       = 'Stock';
    protected $name        = 'stock:test-cycle';
    protected $description = 'Cria produto, executa venda simulada no PDV e exibe estoque antes/depois.';

    public function run(array $params)
    {
        CLI::write('Iniciando ciclo de teste de estoque...', 'yellow');

        // Seeds básicos
        $seeder = \Config\Database::seeder();
        try { $seeder->call('App\\Database\\Seeds\\DatabaseSeeder'); } catch (\Throwable $e) {}

        $db = \Config\Database::connect();

        // Empresa/Contador
        $empresa = $db->table('empresas')->orderBy('id_empresa','ASC')->get()->getRowArray();
        if (! $empresa) { CLI::error('Nenhuma empresa encontrada.'); return; }
        $contador = $db->table('contadores')->where('id_contador', (int) $empresa['id_contador'])->get()->getRowArray();
        if (! $contador) { CLI::error('Contador não encontrado.'); return; }

        // Sessão mínima
        $session = \Config\Services::session();
        $session->set('id_empresa', (int) $empresa['id_empresa']);
        $session->set('id_contador', (int) $contador['id_contador']);
        $session->set('tipo', 3);

        // Produto de teste com estoque
        // Garante uma unidade válida
        $unidId = 0;
        try {
            $un = $db->table('unidades')->orderBy('id_unidade','ASC')->get()->getRowArray();
            if (! $un) {
                $db->table('unidades')->insert(['sigla'=>'UN','descricao'=>'Unidade']);
                $unidId = (int) $db->insertID();
            } else {
                $unidId = (int) $un['id_unidade'];
            }
        } catch (\Throwable $e) {}
        $prodModel = new \App\Models\ProdutoModel();
        $idProduto = (int) ($params[0] ?? 0);
        if ($idProduto <= 0) {
            $exist = $db->table('produtos')->where('id_empresa', (int) $empresa['id_empresa'])->orderBy('id_produto','ASC')->get()->getRowArray();
            if ($exist) { $idProduto = (int) $exist['id_produto']; }
        }
        if ($idProduto <= 0) {
            $idProduto = (int) $prodModel->insert([
                'nome' => 'Produto Estoque Teste',
                'codigo_de_barras' => 'SEM GTIN',
                'valor_unitario' => 10.00,
                'estoque' => 100.0000,
                'id_unidade' => $unidId ?: null,
                'CFOP_NFCe' => '5102',
                'CFOP_NFe' => '5102',
                'CFOP_Externo' => '6102',
                'NCM' => '00000000',
                'CSOSN' => '102',
                'id_contador' => (int) $contador['id_contador'],
                'id_empresa' => (int) $empresa['id_empresa'],
            ]);
        } else {
            // Garante estoque inicial
            $db->table('produtos')->where('id_produto', $idProduto)->update(['estoque' => 100.0000]);
        }

        $prod = $prodModel->find($idProduto);
        $estoqueAntes = (float) ($prod['estoque'] ?? 0);
        CLI::write('Estoque ANTES: ' . number_format($estoqueAntes, 4, ',', '.'), 'yellow');

        // Garantir caixa/turno aberto
        $cashModel = new \App\Models\CashRegisterModel();
        $cash = $cashModel->first();
        if (! $cash) {
            $cashId = $cashModel->insert(['name'=>'Caixa Test','location'=>'Loja','status'=>'open','id_contador'=>(int)$contador['id_contador'],'id_empresa'=>(int)$empresa['id_empresa']]);
            $cash = $cashModel->find($cashId);
        }
        $shiftModel = new \App\Models\ShiftModel();
        $shift = $shiftModel->where('id_cash_register', (int) ($cash->id_cash_register ?? $cash['id_cash_register']))->orderBy('id_shift','DESC')->first();
        if (! $shift || (is_array($shift)?$shift['status']:$shift->status) !== 'open') {
            $sid = $shiftModel->insert(['id_cash_register'=>(int) ($cash->id_cash_register ?? $cash['id_cash_register']),'opened_by'=>'cli','opened_at'=>date('Y-m-d H:i:s'),'opening_amount'=>0,'status'=>'open','id_contador'=>(int)$contador['id_contador'],'id_empresa'=>(int)$empresa['id_empresa']]);
            $shift = $shiftModel->find($sid);
        }

        // Criar venda
        $saleModel = new \App\Models\PosSaleModel();
        $saleId = $saleModel->insert([
            'id_shift' => (int) (is_array($shift)?$shift['id_shift']:$shift->id_shift),
            'id_cash_register' => (int) ($cash->id_cash_register ?? $cash['id_cash_register']),
            'sale_number' => 'STK-' . time(),
            'total' => 0,
            'discount' => 0,
            'paid_amount' => 0,
            'change_amount' => 0,
            'payment_type' => 'cash',
            'status' => 'draft',
            'id_contador' => (int) $contador['id_contador'],
            'id_empresa' => (int) $empresa['id_empresa'],
        ]);

        // Limpa carrinho e adiciona item vinculado ao produto ERP
        $cart = new \App\Models\ProdutoProvisorioModel();
        $cart->where('id_contador', (int) $contador['id_contador'])->where('id_empresa', (int) $empresa['id_empresa'])->delete();
        $cart->insert([
            'id_produto' => $idProduto,
            'nome' => $prod['nome'] ?? 'Produto',
            'codigo_de_barras' => $prod['codigo_de_barras'] ?? 'SEM GTIN',
            'unidade' => 'UN',
            'quantidade' => 3,
            'valor_unitario' => $prod['valor_unitario'] ?? 10.00,
            'desconto' => 0,
            'CFOP_NFe' => $prod['CFOP_NFe'] ?? '5102',
            'CFOP_NFCe' => $prod['CFOP_NFCe'] ?? '5102',
            'CFOP_Externo' => $prod['CFOP_Externo'] ?? '6102',
            'NCM' => $prod['NCM'] ?? '00000000',
            'CSOSN' => $prod['CSOSN'] ?? '102',
            'id_contador' => (int) $contador['id_contador'],
            'id_empresa' => (int) $empresa['id_empresa'],
        ]);

        // Finalizar (simulado)
        putenv('PDV_SIMULATE_NFCE=1');
        $controller = new \App\Controllers\Api\Pos();
        $request = \Config\Services::request();
        $responseSvc = \Config\Services::response();
        $logger = \Config\Services::logger();
        $controller->initController($request, $responseSvc, $logger);
        $resp = $controller->finalize($saleId);
        if (is_object($resp) && method_exists($resp, 'getStatusCode') && $resp->getStatusCode() !== 200) {
            CLI::error('Finalização retornou HTTP ' . $resp->getStatusCode());
        }

        // Estoque depois (após baixa)
        $prod2 = $prodModel->find($idProduto);
        $estoqueDepois = (float) ($prod2['estoque'] ?? 0);
        CLI::write('Estoque DEPOIS: ' . number_format($estoqueDepois, 4, ',', '.'), 'green');
        CLI::write('Baixa esperada: 3. Diferença: ' . number_format($estoqueAntes - $estoqueDepois, 4, ',', '.'), 'yellow');

        // Cancelar para estornar
        try {
            $controller2 = new \App\Controllers\Api\Pos();
            $controller2->initController($request, $responseSvc, $logger);
            // forçar simulação de cancelamento sem SEFAZ: se não houver NFC-e real, cancel() marcará como cancelled com estorno financeiro e estoque via itens
            $resCancel = $controller2->cancel($saleId);
            if (is_object($resCancel) && method_exists($resCancel, 'getStatusCode')) {
                CLI::write('Cancel HTTP: ' . $resCancel->getStatusCode(), 'yellow');
            }
        } catch (\Throwable $e) {}

        $prod3 = $prodModel->find($idProduto);
        $estoqueFinal = (float) ($prod3['estoque'] ?? 0);
        CLI::write('Estoque APÓS ESTORNO: ' . number_format($estoqueFinal, 4, ',', '.'), 'green');
    }
}


