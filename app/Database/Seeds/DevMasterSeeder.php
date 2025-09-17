<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DevMasterSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Criar UF e Município mínimos se não existirem
        $uf = $db->table('ufs')->where('uf', 'SP')->get()->getRowArray();
        if (!$uf) {
            $db->table('ufs')->insert(['estado' => 'SÃO PAULO', 'uf' => 'SP', 'codigo_uf' => 35, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            $ufId = (int) $db->insertID();
        } else {
            $ufId = (int) $uf['id_uf'];
        }

        $mun = $db->table('municipios')->where('municipio', 'SÃO PAULO')->get()->getRowArray();
        if (!$mun) {
            $db->table('municipios')->insert(['codigo' => 3550308, 'municipio' => 'SÃO PAULO', 'id_uf' => $ufId, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            $munId = (int) $db->insertID();
        } else {
            $munId = (int) $mun['id_municipio'];
        }

        // Criar login de empresa (tipo 3) e de contador (tipo 1) se não existirem
        $loginEmpresa = $db->table('logins')->where('usuario', 'empresa_demo')->get()->getRowArray();
        if (!$loginEmpresa) {
            $db->table('logins')->insert([
                'usuario' => 'empresa_demo',
                'senha' => password_hash('senha123', PASSWORD_BCRYPT),
                'tipo' => 3,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $idLoginEmpresa = (int) $db->insertID();
        } else {
            $idLoginEmpresa = (int) $loginEmpresa['id_login'];
        }

        $loginContador = $db->table('logins')->where('usuario', 'contador_demo')->get()->getRowArray();
        if (!$loginContador) {
            $db->table('logins')->insert([
                'usuario' => 'contador_demo',
                'senha' => password_hash('senha123', PASSWORD_BCRYPT),
                'tipo' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $idLoginContador = (int) $db->insertID();
        } else {
            $idLoginContador = (int) $loginContador['id_login'];
        }

        // Criar Contador
        $contador = $db->table('contadores')->where('id_login', $idLoginContador)->get()->getRowArray();
        if (!$contador) {
            $db->table('contadores')->insert([
                'status' => 'Ativo',
                'nome' => 'Contador Demo',
                'cnpj' => '00000000000000',
                'razao_social' => 'Contador Demo LTDA',
                'nome_fantasia' => 'Contador Demo',
                'ie' => 'ISENTO',
                'dia_do_pagamento' => 10,
                'logradouro' => 'Rua X',
                'numero' => '100',
                'complemento' => 'Sala 1',
                'bairro' => 'Centro',
                'cep' => '01000000',
                'id_uf' => $ufId,
                'id_municipio' => $munId,
                'fixo' => '1130000000',
                'celular_1' => '11990000000',
                'celular_2' => '11990000001',
                'email' => 'contador@demo.com',
                'id_login' => $idLoginContador,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $idContador = (int) $db->insertID();
        } else {
            $idContador = (int) $contador['id_contador'];
        }

        // Criar Empresa (garantindo id_uf/id_municipio e campos NFC-e)
        $empresa = $db->table('empresas')->where('id_login', $idLoginEmpresa)->get()->getRowArray();
        if (!$empresa) {
            $db->table('empresas')->insert([
                'status' => 'Ativo',
                'CNPJ' => '00000000000000',
                'xNome' => 'Empresa Demo LTDA',
                'xFant' => 'Empresa Demo',
                'IE' => 'ISENTO',
                'dia_do_pagamento' => 10,
                'CEP' => '01000000',
                'xLgr' => 'Rua Y',
                'nro' => '200',
                'xCpl' => 'Loja 1',
                'xBairro' => 'Centro',
                'fone' => '1130000001',
                'natOp' => 'VENDA',
                'serie' => '1',
                'verProc' => '1.0.0',
                'nNF_homologacao' => '1',
                'nNF_producao' => '1',
                'tpAmb_NFe' => '2',
                'nNFC_homologacao' => '1',
                'nNFC_producao' => '1',
                'tpAmb_NFCe' => '2',
                'CSC_Id' => '000001',
                'CSC' => 'SEMCERTIFICADO',
                'certificado' => 'dummy.pfx',
                'senha_do_certificado' => '1234',
                'id_login' => $idLoginEmpresa,
                'id_contador' => $idContador,
                'id_uf' => $ufId,
                'id_municipio' => $munId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Garantir consistência mínima se já existir
            $updates = [];
            foreach (['id_uf' => $ufId, 'id_municipio' => $munId] as $k => $v) {
                if (empty($empresa[$k])) $updates[$k] = $v;
            }
            foreach (['tpAmb_NFCe' => '2','CSC_Id' => '000001','CSC' => 'SEMCERTIFICADO'] as $k => $v) {
                if (empty($empresa[$k])) $updates[$k] = $v;
            }
            if ($updates) {
                $db->table('empresas')->where('id_empresa', $empresa['id_empresa'])->update($updates);
            }
        }
    }
}


