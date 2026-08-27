<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\CaixaSessaoModel;

class SyncCaixaSessoes extends BaseCommand
{
    protected $group       = 'Debug';
    protected $name        = 'sync:caixa-sessoes';
    protected $description = 'Sincroniza shifts abertos com sessões de caixa';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $caixaModel = new CaixaSessaoModel();

        CLI::write('=== SINCRONIZANDO SHIFTS COM CAIXA_SESSOES ===', 'yellow');

        // Busca shifts abertos que não têm sessão de caixa correspondente
        $openShifts = $db->query("
            SELECT s.* FROM shifts s 
            LEFT JOIN caixa_sessoes c ON (s.id_contador = c.id_contador AND s.id_empresa = c.id_empresa AND c.status = 'aberto')
            WHERE s.status = 'open' AND c.id IS NULL
        ")->getResultArray();

        if (empty($openShifts)) {
            CLI::write('Todos os shifts abertos já têm sessões de caixa correspondentes.', 'green');
            return;
        }

        CLI::write('Shifts sem sessão de caixa encontrados: ' . count($openShifts), 'red');

        foreach ($openShifts as $shift) {
            CLI::write("Criando sessão de caixa para Shift ID: {$shift['id_shift']} (Empresa: {$shift['id_empresa']}, Contador: {$shift['id_contador']})");
            
            try {
                // Criar sessão de caixa manualmente
                $sessaoData = [
                    'id_usuario_abertura' => 1, // usuário padrão
                    'data_abertura' => $shift['opened_at'],
                    'valor_inicial' => (float) $shift['opening_amount'],
                    'id_contador' => (int) $shift['id_contador'],
                    'id_empresa' => (int) $shift['id_empresa'],
                    'status' => 'aberto'
                ];

                $db->table('caixa_sessoes')->insert($sessaoData);
                CLI::write("✓ Sessão criada com sucesso", 'green');
            } catch (\Exception $e) {
                CLI::write("✗ Erro ao criar sessão: " . $e->getMessage(), 'red');
            }
        }

        CLI::newLine();
        CLI::write('Sincronização concluída!', 'green');
    }
}
