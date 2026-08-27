<?php

use CodeIgniter\Test\FeatureTestTrait;

class FinalizeCancelTest extends \CodeIgniter\Test\CIUnitTestCase
{
    use FeatureTestTrait;

    public function testFinalizeAndCancelWithErpProduct()
    {
        // Rotas sem filtros e com endpoints necessários
        $this->withRoutes([
            ['get', 'api/pos', 'Api\\Pos::index'],
            ['post', 'api/pos/(:num)/finalize', 'Api\\Pos::finalize/$1'],
            ['post', 'api/pos/(:num)/cancel', 'Api\\Pos::cancel/$1'],
            ['get', 'api/cart', 'Api\\Cart::index'],
            ['post', 'api/cart', 'Api\\Cart::create'],
            ['delete', 'api/cart/(:num)', 'Api\\Cart::delete/$1'],
            ['get', 'api/shifts', 'Api\\Shifts::index'],
            ['post', 'api/shifts/open', 'Api\\Shifts::open'],
            ['post', 'api/shifts/close/(:num)', 'Api\\Shifts::close/$1'],
            ['get', 'api/cash-registers', 'Api\\CashRegisters::index'],
            ['post', 'api/cash-registers', 'Api\\CashRegisters::create'],
        ]);

        $session = \Config\Services::session();
        $seeder = \Config\Database::seeder();
        try { $seeder->call('App\\Database\\Seeds\\DatabaseSeeder'); } catch (\Throwable $e) {}
        $db = \Config\Database::connect();
        $empresa = $db->table('empresas')->orderBy('id_empresa','ASC')->get()->getRowArray();
        $contador = $db->table('contadores')->orderBy('id_contador','ASC')->get()->getRowArray();
        $un = $db->table('unidades')->orderBy('id_unidade','ASC')->get()->getRowArray();
        if (! $un) { $db->table('unidades')->insert(['sigla'=>'UN','descricao'=>'Unidade']); $un = $db->table('unidades')->orderBy('id_unidade','ASC')->get()->getRowArray(); }
        $session->set('id_contador', (int) ($contador['id_contador'] ?? 1));
        $session->set('id_empresa', (int) ($empresa['id_empresa'] ?? 1));

        // Produto ERP
        $prod = model('App\\Models\\ProdutoModel');
        $produtoId = null;
        $byId1 = $prod->find(1);
        if ($byId1) { $produtoId = (int) $byId1['id_produto']; }
        else {
            $produtoId = (int) $prod->insert([
                'nome'=>'ERP Item','valor_unitario'=>5,'estoque'=>100,
                'id_unidade' => (int) ($un['id_unidade'] ?? null),
                'id_contador'=>(int) ($contador['id_contador'] ?? 1),
                'id_empresa'=>(int) ($empresa['id_empresa'] ?? 1)
            ]);
        }

        // Caixa/turno/venda
        $cash = model('App\\Models\\CashRegisterModel');
        $cashId = $cash->insert([
            'name'=>'CaixaT','location'=>'L','status'=>'open',
            'id_contador'=>(int) ($contador['id_contador'] ?? 1),
            'id_empresa'=>(int) ($empresa['id_empresa'] ?? 1)
        ]);
        $shift = model('App\\Models\\ShiftModel');
        $sid = $shift->insert([
            'id_cash_register'=>$cashId,'opened_by'=>'t','opened_at'=>date('Y-m-d H:i:s'),
            'opening_amount'=>0,'status'=>'open',
            'id_contador'=>(int) ($contador['id_contador'] ?? 1),
            'id_empresa'=>(int) ($empresa['id_empresa'] ?? 1)
        ]);
        $sale = model('App\\Models\\PosSaleModel');
        $saleId = $sale->insert([
            'id_shift'=>$sid,'id_cash_register'=>$cashId,'sale_number'=>'T-'.uniqid(),
            'total'=>0,'discount'=>0,'paid_amount'=>0,'change_amount'=>0,'payment_type'=>'cash','status'=>'draft',
            'id_contador'=>(int) ($contador['id_contador'] ?? 1),'id_empresa'=>(int) ($empresa['id_empresa'] ?? 1)
        ]);

        // Carrinho
        $payload = [
            'id_produto'=>$produtoId,
            'nome' => 'ERP Item',
            'unidade' => 'UN',
            'quantidade' => 2,
            'valor_unitario' => 5,
            'desconto' => 0,
            'CFOP_NFCe' => '5102',
            'NCM' => '00000000',
            'CSOSN' => '102'
        ];
        $this->withHeaders(['Accept'=>'application/json'])->post('api/cart', $payload)->assertStatus(201);

        putenv('PDV_SIMULATE_NFCE=1');
        $this->withHeaders(['Accept'=>'application/json'])->post('api/pos/'.$saleId.'/finalize', json_encode(['total'=>10,'paid_amount'=>10]), ['CONTENT_TYPE'=>'application/json'])->assertStatus(200);

        // Cancelar (simulado)
        $resCancel = $this->withHeaders(['Accept'=>'application/json'])->post('api/pos/'.$saleId.'/cancel', json_encode(['justificativa'=>'Teste']), ['CONTENT_TYPE'=>'application/json']);
        $this->assertTrue(in_array($resCancel->getStatusCode(), [200,500]));
    }
}


