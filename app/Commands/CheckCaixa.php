<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckCaixa extends BaseCommand
{
    protected $group       = 'Debug';
    protected $name        = 'check:caixa';
    protected $description = 'Verifica estado das sessões de caixa e shifts';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        CLI::write('=== VERIFICANDO CAIXA_SESSOES ===', 'yellow');
        $query = $db->query("SELECT * FROM caixa_sessoes WHERE status = 'aberto'");
        $results = $query->getResultArray();

        if (empty($results)) {
            CLI::write('Nenhuma sessão de caixa aberta encontrada.', 'red');
        } else {
            CLI::write('Sessões abertas encontradas:', 'green');
            foreach ($results as $row) {
                CLI::write("ID: {$row['id']}, Status: {$row['status']}, Contador: {$row['id_contador']}, Empresa: {$row['id_empresa']}");
            }
        }

        CLI::newLine();
        CLI::write('=== VERIFICANDO SHIFTS ABERTOS ===', 'yellow');
        $query2 = $db->query("SELECT id_shift, status, id_contador, id_empresa FROM shifts WHERE status = 'open'");
        $shifts = $query2->getResultArray();

        if (empty($shifts)) {
            CLI::write('Nenhum shift aberto encontrado.', 'red');
        } else {
            CLI::write('Shifts abertos encontrados:', 'green');
            foreach ($shifts as $shift) {
                CLI::write("ID: {$shift['id_shift']}, Status: {$shift['status']}, Contador: {$shift['id_contador']}, Empresa: {$shift['id_empresa']}");
            }
        }
    }
}
