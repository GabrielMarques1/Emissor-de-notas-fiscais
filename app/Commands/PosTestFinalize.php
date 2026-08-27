<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

use App\Models\CashRegisterModel;
use App\Models\ShiftModel;
use App\Models\PosSaleModel;
use App\Models\ProdutoProvisorioModel;

class PosTestFinalize extends BaseCommand
{
    protected $group       = 'POS';
    protected $name        = 'pos:test-finalize';
    protected $description = 'Fluxo end-to-end: abre turno, cria venda, adiciona itens provisórios e chama finalize (emissão NFC-e).';

    public function run(array $params)
    {
        CLI::write('Iniciando teste do fluxo PDV → finalize', 'yellow');

        // 1) Seeds mestres
        $seeder = \Config\Database::seeder();
        try {
            $seeder->call('App\\Database\\Seeds\\DatabaseSeeder');
            CLI::write('Seeds executados (DatabaseSeeder).', 'green');
        } catch (\Throwable $e) {
            CLI::error('Falha ao rodar seeds: ' . $e->getMessage());
            return;
        }

        // 2) Descobrir empresa/contador criados pelo seeder (pareados)
        $db = \Config\Database::connect();
        $empresa = $db->table('empresas')->orderBy('id_empresa','ASC')->get()->getRowArray();
        if (! $empresa) {
            CLI::error('Nenhuma empresa encontrada após seed.');
            return;
        }
        $contador = $db->table('contadores')->where('id_contador', (int) $empresa['id_contador'])->get()->getRowArray();
        if (! $empresa || ! $contador) {
            CLI::error('Empresa/Contador não encontrados.');
            return;
        }

        // 3) Injetar sessão mínima necessária
        $session = \Config\Services::session();
        $session->set('id_empresa', (int) $empresa['id_empresa']);
        $session->set('id_contador', (int) $contador['id_contador']);

        // 4) Garantir caixa e turno abertos
        $cashModel = new CashRegisterModel();
        $cash = $cashModel->first();
        if (! $cash) {
            $cashId = $cashModel->insert([
                'name' => 'Caixa CLI',
                'location' => 'Loja CLI',
                'status' => 'open',
                'id_contador' => (int) $contador['id_contador'],
                'id_empresa' => (int) $empresa['id_empresa'],
            ]);
            $cash = $cashModel->find($cashId);
        }

        $shiftModel = new ShiftModel();
        $shift = $shiftModel->where('id_cash_register', (int) ($cash->id_cash_register ?? ($cash['id_cash_register'] ?? 0)))
                            ->orderBy('id_shift', 'DESC')->first();
        $shiftStatus = is_array($shift) ? ($shift['status'] ?? '') : (string) ($shift->status ?? '');
        $shiftIdVal = is_array($shift) ? ((int) ($shift['id_shift'] ?? 0)) : (int) ($shift->id_shift ?? 0);
        if (! $shift || $shiftStatus !== 'open') {
            $shiftId = $shiftModel->insert([
                'id_cash_register' => (int) ($cash->id_cash_register ?? $cash['id_cash_register']),
                'opened_by' => 'cli',
                'opened_at' => date('Y-m-d H:i:s'),
                'opening_amount' => 0,
                'status' => 'open',
                'id_contador' => (int) $contador['id_contador'],
                'id_empresa' => (int) $empresa['id_empresa'],
            ]);
            $shift = $shiftModel->find($shiftId);
            $shiftIdVal = (int) ($shift->id_shift ?? 0);
            $shiftStatus = (string) ($shift->status ?? '');
        }
        CLI::write('Turno OK: #' . $shiftIdVal . ' (status=' . $shiftStatus . ')', 'green');

        // 5) Criar venda POS
        $saleModel = new PosSaleModel();
        $saleId = $saleModel->insert([
            'id_shift' => $shiftIdVal,
            'id_cash_register' => (int) ($cash->id_cash_register ?? $cash['id_cash_register']),
            'sale_number' => 'CLI-' . time(),
            'total' => 0,
            'discount' => 0,
            'paid_amount' => 0,
            'change_amount' => 0,
            'payment_type' => 'cash',
            'status' => 'draft',
            'id_contador' => (int) $contador['id_contador'],
            'id_empresa' => (int) $empresa['id_empresa'],
        ]);
        CLI::write('Venda criada: #' . $saleId, 'green');

        // 6) Popular itens provisórios
        $cart = new ProdutoProvisorioModel();
        $cart->insert([
            'nome' => 'Produto Teste 1',
            'codigo_de_barras' => 'SEM GTIN',
            'unidade' => 'UN',
            'quantidade' => 2,
            'valor_unitario' => 10.00,
            'desconto' => 0,
            'CFOP_NFe' => '5102',
            'CFOP_NFCe' => '5102',
            'CFOP_Externo' => '6102',
            'NCM' => '12345678',
            'CSOSN' => '102',
            'id_contador' => (int) $contador['id_contador'],
            'id_empresa' => (int) $empresa['id_empresa'],
        ]);
        $cart->insert([
            'nome' => 'Produto Teste 2',
            'codigo_de_barras' => 'SEM GTIN',
            'unidade' => 'UN',
            'quantidade' => 1,
            'valor_unitario' => 25.50,
            'desconto' => 0,
            'CFOP_NFe' => '5102',
            'CFOP_NFCe' => '5102',
            'CFOP_Externo' => '6102',
            'NCM' => '12345678',
            'CSOSN' => '102',
            'id_contador' => (int) $contador['id_contador'],
            'id_empresa' => (int) $empresa['id_empresa'],
        ]);
        CLI::write('Itens provisórios adicionados.', 'green');

        // 7) Chamar controller finalize (inicializa request/response/logger)
        $controller = new \App\Controllers\Api\Pos();
        $request = \Config\Services::request();
        $responseSvc = \Config\Services::response();
        $logger = \Config\Services::logger();
        $controller->initController($request, $responseSvc, $logger);
        $response = $controller->finalize($saleId);

        if (is_object($response) && method_exists($response, 'getStatusCode')) {
            CLI::write('HTTP ' . $response->getStatusCode(), $response->getStatusCode() >= 400 ? 'red' : 'green');
            CLI::write($response->getBody());
        } else {
            CLI::write('Resposta sem objeto HTTP. Resultado:', 'yellow');
            CLI::write(print_r($response, true));
        }
    }
}


