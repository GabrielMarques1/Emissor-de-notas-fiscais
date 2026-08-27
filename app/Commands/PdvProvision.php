<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PdvProvision extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'pdv:provision';
    protected $description = 'Garante recursos mínimos do PDV para todas as empresas (cria Caixa Principal se ausente).';

    public function run(array $params)
    {
        $empresaModel = new \App\Models\EmpresaModel();
        $cashModel    = new \App\Models\CashRegisterModel();

        $empresas = $empresaModel->asArray()->findAll();
        $created = 0; $skipped = 0;
        foreach ($empresas as $emp) {
            $idEmpresa  = (int) ($emp['id_empresa'] ?? 0);
            $idContador = (int) ($emp['id_contador'] ?? 0);
            if ($idEmpresa <= 0 || $idContador <= 0) { continue; }
            $exists = $cashModel->asArray()
                ->where('id_empresa', $idEmpresa)
                ->where('id_contador', $idContador)
                ->first();
            if ($exists) { $skipped++; continue; }
            $ok = $cashModel->insert([
                'id_contador' => $idContador,
                'id_empresa'  => $idEmpresa,
                'name'        => 'Caixa Principal',
                'location'    => 'Frente de Loja',
                'status'      => 'closed',
            ]);
            if ($ok) { $created++; CLI::write("Empresa {$idEmpresa}: Caixa Principal criado.", 'green'); }
        }
        CLI::write("Provisionamento concluído. Criados: {$created}. Já existentes: {$skipped}.", 'green');
    }
}


