<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use App\Models\ProdutoModel;
use App\Models\EmpresaModel;
use App\Models\ContadorModel;
use App\Models\LoginModel;

use CodeIgniter\Controller;

set_time_limit(0);

class Testes extends Controller
{  
    private $cliente_model;
    private $produto_model; 
    private $empresa_model; 
    private $contador_model;
    private $login_model;

    public function __construct()
    {
        $this->cliente_model  = new ClienteModel();
        $this->produto_model  = new ProdutoModel();
        $this->empresa_model  = new EmpresaModel();
        $this->contador_model = new ContadorModel();
        $this->login_model    = new LoginModel();
    }

    public function index()
    {
        // // Cadastra o administrador geral
        // $this->login_model
        //     ->insert([
        //         'usuario' => "pedro",
        //         'senha' => "123",
        //         'tipo' => "1",
        //     ]);
        // // ---------------------------- //

        $cont = 35;
        $aux = 1; // Responsável por não deixar repetir o login da empresa

        for($i=0; $i < $cont; $i++) :
            // Cadastra o login do contador
            $id_login_contador = $this->login_model
                                    ->insert([
                                        'usuario' => "contador$i",
                                        'senha' => "123",
                                        'tipo' => "2",
                                    ]);
            
            // Cadastra o contador
            $id_contador = $this->contador_model
                                ->insert([
                                    'status' => "Ativo",
                                    'nome' => "Contador $i",
                                    'cnpj' => "",
                                    'razao_social' => "Contador $i - ME",
                                    'nome_fantasia' => "Cont $i",
                                    'ie' => "",
                                    'logradouro' => "Av. Contorno",
                                    'numero' => "S/N",
                                    'complemento' => "Rua 1 Quadra 2 Lote 3",
                                    'bairro' => "Santa barbara",
                                    'cep' => "77060334",
                                    'id_uf' => 17,
                                    'id_municipio' => 399,
                                    'fixo' => "",
                                    'celular_1' => "63992000000",
                                    'celular_2' => "",
                                    'email' => "",
                                    'id_login' => $id_login_contador,
                                ]);
            
            // Cadastra as empresas
            $cont_empresa = 25;
            for($i_empresa=0; $i_empresa < $cont_empresa; $i_empresa++) :
                
                // Cadastra login da empresa
                $id_login_empresa = $this->login_model
                                ->insert([
                                    'usuario' => "empresa$aux",
                                    'senha' => "123",
                                    'tipo' => "3",
                                ]);
                
                // Cadastra empresa
                $id_empresa = $this->empresa_model
                                    ->insert([
                                        'status' => "Ativo",
                                        'CNPJ' => "",
                                        'xNome' => "Empresa $aux - ME",
                                        'xFant' => "Empresa $aux",
                                        'IE' => "",
                                        'dia_do_pagamento' => "",
                                        'CEP' => "77060334",
                                        'xLgr' => "Av. Contorno",
                                        'nro' => "83",
                                        'xCpl' => "Rua C Casa 83",
                                        'xBairro' => "Santa Barbara",
                                        'fone' => "",
                                        'natOp' => "",
                                        'serie' => "",
                                        'verProc' => "",
                                        'nNF_homologacao' => "",
                                        'nNF_producao' => "",
                                        'tpAmb_NFe' => "",
                                        'nNFC_homologacao' => "",
                                        'nNFC_producao' => "",
                                        'tpAmb_NFCe' => "",
                                        'CSC_Id' => "",
                                        'CSC' => "",
                                        'certificado' => "",
                                        'senha_do_certificado' => "",
                                        'id_login' => $id_login_empresa,
                                        'id_contador' => $id_contador,
                                        'id_uf' => 17,
                                        'id_municipio' => 399,
                                    ]);
                
                // Cadastra os produtos
                $cont_produto = 250;
                for($i=0; $i < $cont_produto; $i++) :
                    $this->produto_model
                        ->insert([
                            'nome' => "Produto $i",
                            'codigo_de_barras' => "",
                            'valor_unitario' => '129.9',
                            'CFOP_NFe' => "5403",
                            'CFOP_NFCe' => "5102",
                            'CFOP_Externo' => "6104",
                            'NCM' => "27101911",
                            'id_unidade' => 1,
                            'id_contador' => $id_contador,
                            'id_empresa' => $id_empresa,
                        ]);
                endfor;

                // Cadastra os clientes
                $cont_cliente = 100;
                for($i=0; $i < $cont_cliente; $i++) :
                    $this->produto_model
                        ->insert([
                            'tipo' => 1,
                            'nome' => "Cliente $i",
                            'cpf' => "01758528125",
                            'cnpj' => "",
                            'razao_social' => "",
                            'isento' => 1,
                            'ie' => "",
                            'logradouro' => "Av. Contorno",
                            'numero' => "83",
                            'complemento' => "Rua C Casa 83",
                            'bairro' => "Santa Barbara",
                            'cep' => "77060334",
                            'id_uf' => 17,
                            'id_municipio' => 399,
                            'id_contador' => $id_contador,
                            'id_empresa' => $id_empresa,
                        ]);
                endfor;

                $aux += 1;

            endfor;
        endfor;
    }
}
