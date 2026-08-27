<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateTestSession extends BaseCommand
{
    protected $group       = 'Debug';
    protected $name        = 'create:test-session';
    protected $description = 'Cria uma sessão de caixa e shift de teste';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        CLI::write('=== CRIANDO SESSÃO DE TESTE ===', 'yellow');

        try {
            // Criar shift
            $shiftData = [
                'id_cash_register' => 1,
                'opened_by' => 'test_user',
                'opened_at' => date('Y-m-d H:i:s'),
                'opening_amount' => 100.00,
                'status' => 'open',
                'id_contador' => 1,
                'id_empresa' => 5,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $db->table('shifts')->insert($shiftData);
            $shiftId = $db->insertID();
            CLI::write("✓ Shift criado com ID: $shiftId", 'green');

            // Criar sessão de caixa
            $sessaoData = [
                'id_usuario_abertura' => 1,
                'data_abertura' => date('Y-m-d H:i:s'),
                'valor_inicial' => 100.00,
                'id_contador' => 1,
                'id_empresa' => 5,
                'status' => 'aberto'
            ];

            $db->table('caixa_sessoes')->insert($sessaoData);
            $sessaoId = $db->insertID();
            CLI::write("✓ Sessão de caixa criada com ID: $sessaoId", 'green');

            CLI::newLine();
            CLI::write("Agora você pode testar o fechamento do shift ID: $shiftId", 'cyan');
            
        } catch (\Exception $e) {
            CLI::write("✗ Erro: " . $e->getMessage(), 'red');
        }
    }
}
