<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // UF e Município (SP / São Paulo)
        $uf = $db->table('ufs')->where('uf','SP')->get()->getRowArray();
        if (! $uf) {
            $db->table('ufs')->insert(['codigo_uf'=>'35','estado'=>'SÃO PAULO','uf'=>'SP','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
            $ufId = (int) $db->insertID();
        } else { $ufId = (int) $uf['id_uf']; }

        $mun = $db->table('municipios')->where('municipio','SÃO PAULO')->get()->getRowArray();
        if (! $mun) {
            $db->table('municipios')->insert(['codigo'=>'3550308','municipio'=>'SÃO PAULO','id_uf'=>$ufId,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
            $munId = (int) $db->insertID();
        } else { $munId = (int) $mun['id_municipio']; }

        // Logins
        $loginEmp = $db->table('logins')->where('usuario','usuario_teste')->get()->getRowArray();
        if (! $loginEmp) {
            $db->table('logins')->insert(['usuario'=>'usuario_teste','senha'=>password_hash('teste123', PASSWORD_BCRYPT),'tipo'=>3,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
            $idLoginEmp = (int) $db->insertID();
        } else { $idLoginEmp = (int) $loginEmp['id_login']; }

        $loginCont = $db->table('logins')->where('usuario','contador_teste')->get()->getRowArray();
        if (! $loginCont) {
            $db->table('logins')->insert(['usuario'=>'contador_teste','senha'=>password_hash('teste123', PASSWORD_BCRYPT),'tipo'=>1,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
            $idLoginCont = (int) $db->insertID();
        } else { $idLoginCont = (int) $loginCont['id_login']; }

        // Contador
        $contador = $db->table('contadores')->where('id_login',$idLoginCont)->get()->getRowArray();
        if (! $contador) {
            $db->table('contadores')->insert([
                'status'=>'Ativo','nome'=>'Contador Teste','cnpj'=>'00000000000000','razao_social'=>'Contador Teste LTDA','nome_fantasia'=>'Contador Teste','ie'=>'ISENTO',
                'dia_do_pagamento'=>10,'logradouro'=>'Rua Teste','numero'=>'100','complemento'=>'Sala 1','bairro'=>'Centro','cep'=>'01000000','id_uf'=>$ufId,'id_municipio'=>$munId,
                'fixo'=>'1130000000','celular_1'=>'11990000000','celular_2'=>'11990000001','email'=>'contador@teste.com','id_login'=>$idLoginCont,
                'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
            ]);
            $idContador = (int) $db->insertID();
        } else { $idContador = (int) $contador['id_contador']; }

        // Empresa (homologação)
        $empresa = $db->table('empresas')->where('id_login',$idLoginEmp)->get()->getRowArray();
        if (! $empresa) {
            $db->table('empresas')->insert([
                'status'=>'Ativo','CNPJ'=>'00000000000000','xNome'=>'Empresa Teste LTDA','xFant'=>'Empresa Teste','IE'=>'ISENTO','dia_do_pagamento'=>10,
                'CEP'=>'01000000','xLgr'=>'Rua Y','nro'=>'200','xCpl'=>'Loja 1','xBairro'=>'Centro','fone'=>'1130000001','natOp'=>'VENDA','serie'=>'1','verProc'=>'1.0.0',
                'nNF_homologacao'=>'1','nNF_producao'=>'1','tpAmb_NFe'=>'2','nNFC_homologacao'=>'1','nNFC_producao'=>'1','tpAmb_NFCe'=>'2',
                'CSC_Id'=>'000001','CSC'=>'SEMCERTIFICADO','certificado'=>'dummy.pfx','senha_do_certificado'=>'1234',
                'id_login'=>$idLoginEmp,'id_contador'=>$idContador,'id_uf'=>$ufId,'id_municipio'=>$munId,
                'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
            ]);
        }
    }
}


