<?php

use CodeIgniter\Test\FeatureTestTrait;

class PosApiTest extends \CodeIgniter\Test\CIUnitTestCase
{
    use FeatureTestTrait;

    public function testIndexResponds()
    {
        // Rotas sem filtros para ambiente de teste
        $this->withRoutes([
            ['get', 'api/pos', 'Api\\Pos::index'],
        ]);

        $result = $this->withHeaders(['Accept' => 'application/json'])->get('api/pos');

        $result->assertStatus(200);
    }

    public function testCartCreateAndFinalizeSimulated()
    {
        // Rotas sem filtros para ambiente de teste
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

        // Seed e sessão mínima com IDs válidos
        $seeder = \Config\Database::seeder();
        try { $seeder->call('App\\Database\\Seeds\\DatabaseSeeder'); } catch (\Throwable $e) {}
        $db = \Config\Database::connect();
        $empresa = $db->table('empresas')->orderBy('id_empresa','ASC')->get()->getRowArray();
        $contador = $db->table('contadores')->orderBy('id_contador','ASC')->get()->getRowArray();
        $session = \Config\Services::session();
        $sessionData = ['id_contador'=>(int) ($contador['id_contador'] ?? 1), 'id_empresa'=>(int) ($empresa['id_empresa'] ?? 1)];
        $session->set('id_contador', $sessionData['id_contador']);
        $session->set('id_empresa', $sessionData['id_empresa']);

        // Criar caixa e turno aberto
        $cash = model('App\\Models\\CashRegisterModel');
        $cashId = $cash->insert([
            'name'=>'Caixa Test','location'=>'Loja','status'=>'open',
            'id_contador'=>$sessionData['id_contador'],
            'id_empresa'=>$sessionData['id_empresa']
        ]);
        $shift = model('App\\Models\\ShiftModel');
        $sid = $shift->insert([
            'id_cash_register'=>$cashId,'opened_by'=>'test','opened_at'=>date('Y-m-d H:i:s'),
            'opening_amount'=>0,'status'=>'open',
            'id_contador'=>$sessionData['id_contador'],
            'id_empresa'=>$sessionData['id_empresa']
        ]);

        // Criar venda draft (sale_number único para evitar conflito de índice)
        $sale = model('App\\Models\\PosSaleModel');
        $saleId = $sale->insert([
            'id_shift'=>$sid,'id_cash_register'=>$cashId,'sale_number'=>'T-'.uniqid(),
            'total'=>0,'discount'=>0,'paid_amount'=>0,'change_amount'=>0,'payment_type'=>'cash','status'=>'draft',
            'id_contador'=>$sessionData['id_contador'],'id_empresa'=>$sessionData['id_empresa']
        ]);

        // Adicionar item ao carrinho
        $payload = [
            'nome' => 'Teste',
            'unidade' => 'UN',
            'quantidade' => 1,
            'valor_unitario' => 10,
            'desconto' => 0,
            'CFOP_NFCe' => '5102',
            'NCM' => '00000000',
            'CSOSN' => '102'
        ];
        $res = $this->withSession($sessionData)->withHeaders(['Accept'=>'application/json'])->post('api/cart', $payload);
        $res->assertStatus(201);

        // Finalizar (simulado)
        putenv('PDV_SIMULATE_NFCE=1');
        $final = $this->withSession($sessionData)->withHeaders(['Accept'=>'application/json'])->post('api/pos/'.$saleId.'/finalize', json_encode(['total'=>10,'paid_amount'=>10,'payment_type'=>'cash']), ['CONTENT_TYPE' => 'application/json']);
        $final->assertStatus(200);
    }
}


