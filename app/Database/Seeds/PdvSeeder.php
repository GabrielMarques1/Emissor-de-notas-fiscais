<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PdvSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Descobrir IDs válidos de empresa e contador para respeitar FKs
        $empresa = $db->table('empresas')->limit(1)->get()->getRowArray();
        $contador = $db->table('contadores')->limit(1)->get()->getRowArray();

        if (!$empresa || !$contador) {
            // Sem dados mestres, não semear para evitar violar FKs
            echo "[PdvSeeder] Nenhuma empresa/contador encontrado. Pulei o seed.\n";
            return;
        }

        $idEmpresa  = (int) $empresa['id_empresa'];
        $idContador = (int) $contador['id_contador'];

        // Cash Register
        $db->table('cash_registers')->insert([
            'name' => 'Caixa Principal',
            'location' => 'Loja 1',
            'status' => 'open',
            'id_contador' => $idContador,
            'id_empresa'  => $idEmpresa,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $idCash = (int) $db->insertID();

        // Shift
        $db->table('shifts')->insert([
            'id_cash_register' => $idCash,
            'opened_by' => 'admin',
            'opened_at' => date('Y-m-d H:i:s'),
            'opening_amount' => 0,
            'status' => 'open',
            'id_contador' => $idContador,
            'id_empresa'  => $idEmpresa,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}


