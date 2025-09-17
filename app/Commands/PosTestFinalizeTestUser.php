<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\CashRegisterModel;
use App\Models\ShiftModel;
use App\Models\PosSaleModel;
use App\Models\ProdutoProvisorioModel;

class PosTestFinalizeTestUser extends BaseCommand
{
    protected $group       = 'POS';
    protected $name        = 'pos:test-finalize-testuser';
    protected $description = 'Fluxo completo PDV (teste) para a empresa do usuário usuario_teste e contador_teste.';

    public function run(array $params)
    {
        CLI::write('Iniciando fluxo PDV (TestUser)', 'yellow');

        // Garantir seeds do TestUser
        $seeder = \Config\Database::seeder();
        try { $seeder->call('App\\Database\\Seeds\\TestUserSeeder'); } catch (\Throwable $e) {}

        $db = \Config\Database::connect();
        $loginEmp = $db->table('logins')->where('usuario','usuario_teste')->get()->getRowArray();
        $loginCont = $db->table('logins')->where('usuario','contador_teste')->get()->getRowArray();
        if (! $loginEmp || ! $loginCont) { CLI::error('Logins de teste não encontrados.'); return; }
        $empresa = $db->table('empresas')->where('id_login', (int) $loginEmp['id_login'])->get()->getRowArray();
        $contador = $db->table('contadores')->where('id_login', (int) $loginCont['id_login'])->get()->getRowArray();
        if (! $empresa || ! $contador) { CLI::error('Empresa/Contador de teste não encontrados.'); return; }

        // Sessão do test user
        $session = \Config\Services::session();
        $session->set('id_empresa', (int) $empresa['id_empresa']);
        $session->set('id_contador', (int) $contador['id_contador']);

        // Caixa e turno
        $cashModel = new CashRegisterModel();
        $cash = $cashModel->where('id_empresa', (int) $empresa['id_empresa'])->first();
        if (! $cash) {
            $cashId = $cashModel->insert(['name'=>'Caixa Teste','location'=>'Loja Teste','status'=>'open','id_contador'=>(int)$contador['id_contador'],'id_empresa'=>(int)$empresa['id_empresa']]);
            $cash = $cashModel->find($cashId);
        }
        $shiftModel = new ShiftModel();
        $shift = $shiftModel->where('id_cash_register', (int) ($cash->id_cash_register ?? $cash['id_cash_register']))->orderBy('id_shift','DESC')->first();
        if (! $shift || (is_array($shift)?$shift['status']:$shift->status) !== 'open') {
            $sid = $shiftModel->insert(['id_cash_register'=>(int) ($cash->id_cash_register ?? $cash['id_cash_register']),'opened_by'=>'cli','opened_at'=>date('Y-m-d H:i:s'),'opening_amount'=>0,'status'=>'open','id_contador'=>(int)$contador['id_contador'],'id_empresa'=>(int)$empresa['id_empresa']]);
            $shift = $shiftModel->find($sid);
        }
        CLI::write('Turno: #' . (is_array($shift)?$shift['id_shift']:$shift->id_shift) . ' (open)', 'green');

        // Venda
        $saleModel = new PosSaleModel();
        $saleId = $saleModel->insert([
            'id_shift' => (int) (is_array($shift)?$shift['id_shift']:$shift->id_shift),
            'id_cash_register' => (int) ($cash->id_cash_register ?? $cash['id_cash_register']),
            'sale_number' => 'TEST-' . time(),
            'total' => 0,
            'discount' => 0,
            'paid_amount' => 0,
            'change_amount' => 0,
            'payment_type' => 'cash',
            'status' => 'draft',
            'id_contador' => (int) $contador['id_contador'],
            'id_empresa' => (int) $empresa['id_empresa'],
        ]);
        CLI::write('Venda: #' . $saleId, 'green');

        // Itens provisórios
        $cart = new ProdutoProvisorioModel();
        $cart->insert([
            'nome'=>'Item Teste A','codigo_de_barras'=>'SEM GTIN','unidade'=>'UN','quantidade'=>1,'valor_unitario'=>19.90,'desconto'=>0,
            'CFOP_NFe'=>'5102','CFOP_NFCe'=>'5102','CFOP_Externo'=>'6102','NCM'=>'12345678','CSOSN'=>'102','id_contador'=>(int)$contador['id_contador'],'id_empresa'=>(int)$empresa['id_empresa']
        ]);
        $cart->insert([
            'nome'=>'Item Teste B','codigo_de_barras'=>'SEM GTIN','unidade'=>'UN','quantidade'=>2,'valor_unitario'=>9.50,'desconto'=>0,
            'CFOP_NFe'=>'5102','CFOP_NFCe'=>'5102','CFOP_Externo'=>'6102','NCM'=>'12345678','CSOSN'=>'102','id_contador'=>(int)$contador['id_contador'],'id_empresa'=>(int)$empresa['id_empresa']
        ]);
        CLI::write('Itens provisórios adicionados.', 'green');

        // Finalize
        $controller = new \App\Controllers\Api\Pos();
        $request = \Config\Services::request();
        $responseSvc = \Config\Services::response();
        $logger = \Config\Services::logger();
        $controller->initController($request, $responseSvc, $logger);
        $response = $controller->finalize($saleId);
        if (is_object($response) && method_exists($response,'getStatusCode')) {
            CLI::write('HTTP ' . $response->getStatusCode(), $response->getStatusCode() >= 400 ? 'red' : 'green');
            CLI::write($response->getBody());
        } else {
            CLI::write(print_r($response,true));
        }
    }
}



