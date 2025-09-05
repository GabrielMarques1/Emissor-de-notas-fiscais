<?php

namespace App\Database\Seeds;

class AutoInsert extends \CodeIgniter\Database\Seeder
{
    public function run()
    {
        // Configurações
        $this->db->table('configuracoes')->insert([
            'nome_do_app' => 'xFiscal',
            'mensagem_suporte' => 'Nosso suporte funciona de segunda a sábado das 08:00 as 18:00 pelo WhatsApp',
            'contato_suporte' => '5563992127726',
            'contato_suporte_formatado' => '(63) 9 9212-7726',
            'outras_opcoes_de_pagamento' => "
<b>OPÇÕES DE PAGAMENTO:</b> <br>
PIX: 34.229.323/00001-73 <br>
<hr>

<b>Banco do Brasil</b> <br>
Agência: 2781-2 <br>
Conta: 36275-9 <br>
<hr>

PEDRO ARLINDO DE MOURA JUNIOR <br>
017.585.281-25

<hr>

<b>OBS:</b> Caso seja feito pagamento por PIX, TRANSFERÊNCIA e DEPÓSITO enviar comprovante no WhatsApp 63992069830
            ",
        ]);

        // Unidades
        $this->db->table('unidades')->insert([
            'unidade'   => 'PC',
            'descricao' => 'PACOTE'
        ]);

        // id 1
        $this->db->table('logins')->insert([
            'usuario' => 'pedro',
            'senha'   => '123',
            'tipo'    => 1
        ]);
        
        // id 2
        $this->db->table('logins')->insert([
            'usuario' => 'luzia',
            'senha'   => '123',
            'tipo'    => 2
        ]);

        // id 3
        $this->db->table('logins')->insert([
            'usuario' => 'comercialjp',
            'senha'   => '123',
            'tipo'    => 3
        ]);

        $this->db->table('contadores')->insert([
            'status'        => "Ativo",
            'nome'          => "LUZIA",
            'cnpj'          => "",
            'razao_social'  => "MK CONTABILIDADE ESCRITORIOS LTDA",
            'nome_fantasia' => "MK CONT.",
            'ie'            => "",
            'logradouro'    => "",
            'numero'        => "",
            'complemento'   => "",
            'bairro'        => "",
            'cep'           => "",
            'id_uf'         => 17,
            'id_municipio'  => 1,
            'fixo'          => "",
            'celular_1'     => "",
            'celular_2'     => "",
            'email'         => "",
            'id_login'      => 2,
        ]);

        $this->db->table('empresas')->insert([
            'status'               => "Ativo",
            'CNPJ'                 => "07296786000185",
            'xNome'                => "PEDRO ARLINDO DE MOURA - ME",
            'xFant'                => "COMERCIAL JP",
            'IE'                   => "294846921",
            'CEP'                  => "77610000",
            'xLgr'                 => "DIOCLECI RIBEIRO DE SOUSA",
            'nro'                  => "S/N",
            'xCpl'                 => "AEROPORTO",
            'xBairro'              => "AEROPORTO",
            // 'cMun'                 => "1715101",
            // 'xMun'                 => "Palmas TO",
            // 'UF'                   => "TO",
            'fone'                 => "63992000000",
            // 'cUF'                  => "17",
            'natOp'                => "VENDA DE MERCADORIAS",
            'serie'                => "1",
            // 'cMunFG'               => "1715101",
            'verProc'              => "V1.0.1",
            'nNF_homologacao'      => "0",
            'nNF_producao'         => "0",
            'tpAmb_NFe'            => "2",
            'nNFC_homologacao'     => "0",
            'nNFC_producao'        => "0",
            'tpAmb_NFCe'           => "2",
            'CSC_Id'               => "000001",
            'CSC'                  => "90EC2A75-934A-4F16-89C3-52931F244F80",
            'certificado'          => "arquivo_comercial_jp.pfx",
            'senha_do_certificado' => "tiao1378",
            'id_login'             => 3,
            'id_contador'          => 1,
            'id_uf'                => 17, // id_uf
            'id_municipio'         => 1 // id_municipio 399=Palmas
        ]);

        $this->db->table('clientes')->insert([
            'tipo'                => "1",
            'nome'                => "PEDRO ARLINDO DE MOURA JUNIOR",
            'cpf'                 => "01758528125",
            'cnpj'                => "",
            'razao_social'        => "",
            'ie'                  => "",
            'logradouro'          => "AV. CONTORNO",
            'numero'              => 83,
            'complemento'         => "RUA C CASA 83",
            'bairro'              => "SANTA BARBARA",
            'cep'                 => "77060334",
            'id_uf'               => 17,
            'id_municipio'        => 1, // 399=Palmas
            'id_contador'         => 1,
            'id_empresa'          => 1
        ]);

        $this->db->table('produtos')->insert([
            'nome'             => "GLP 13KG",
            'codigo_de_barras' => "0",
            'valor_unitario'   => 72.5,
            'CFOP_NFe'         => "5403",
            'CFOP_NFCe'        => "5102",
            'CFOP_Externo'     => "6104",
            'NCM'              => "27111910",
            'CSOSN'            => "103",
            'id_unidade'       => 1,
            'id_contador'      => 1,
            'id_empresa'       => 1
        ]);
    }
}
