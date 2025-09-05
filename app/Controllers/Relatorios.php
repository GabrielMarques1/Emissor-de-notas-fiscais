<?php

namespace App\Controllers;

use App\Models\ContadorModel;
use App\Models\PagamentoModel;
use App\Models\EmpresaModel;
use CodeIgniter\Controller;

class Relatorios extends Controller
{
    private $tipo = 2;

    private $session;
    private $id_contador;

    private $contador_model;
    private $pagamento_model;
    private $empresa_model;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');

        $this->contador_model  = new ContadorModel();
        $this->pagamento_model = new PagamentoModel();
        $this->empresa_model   = new EmpresaModel();
    }

    public function empresas()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = '3';

        $data['titulo'] = [
            'modulo' => 'Relação de Empresas',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "Relatório", 'rota'   => "", 'active' => true]
        ];
        
        $data['empresas'] = $this->empresa_model
            ->where('id_contador', $this->id_contador)
            ->join('ufs', 'empresas.id_uf = ufs.id_uf')
            ->join('municipios', 'empresas.id_municipio = municipios.id_municipio')
            ->findAll();

        $this->session->setFlashdata(
            'alert',
            [
                'type'  => 'success',
                'title' => 'Relatório gerado com sucesso!'
            ]
        );

        echo view('templates/header');
        echo view('relatorios/empresas', $data);
        echo view('templates/footer');
    }

    public function pagamentos()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($this->tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = '4';

        $data['titulo'] = [
            'modulo' => 'Relação de Pagamentos',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/contador", 'active' => false],
            ['titulo' => "Relatório", 'rota'   => "", 'active' => true]
        ];

        $data['empresas'] = $this->empresa_model
                                ->where('id_contador', $this->id_contador)
                                ->findAll();
        
        $dados = $this->request
                        ->getvar();

        // Verifica se o usuário escolheu uma data inicio e uma data final
        if(isset($dados['data_inicio']) && $dados['data_final'] && $dados['id_empresa'] != 0) :
            
            // Pega todos os pagamentos de uma determinada data inicio e final
            $data['pagamentos'] = $this->empresa_model
                                    ->where('pagamentos_da_empresa.id_contador', $this->id_contador)
                                    ->where('pagamentos_da_empresa.id_empresa', $dados['id_empresa'])
                                    ->join('pagamentos_da_empresa', 'pagamentos_da_empresa.id_empresa = empresas.id_empresa')
                                    ->findAll();
            
            $data['data_inicio'] = $dados['data_inicio'];
            $data['data_final']  = $dados['data_final'];
            $data['id_empresa']  = $dados['id_empresa'];

            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'success',
                    'title' => 'Relatório gerado com sucesso!'
                ]
            );

        elseif(isset($dados['data_inicio']) && $dados['data_final']) :
            
            // Pega todos os pagamentos de uma determinada data inicio e final
            $data['pagamentos'] = $this->empresa_model
                                    ->where('pagamentos_da_empresa.id_contador', $this->id_contador)
                                    ->join('pagamentos_da_empresa', 'pagamentos_da_empresa.id_empresa = empresas.id_empresa')
                                    ->findAll();
            
            $data['data_inicio'] = $dados['data_inicio'];
            $data['data_final'] = $dados['data_final'];

            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'success',
                    'title' => 'Relatório gerado com sucesso!'
                ]
            );

        endif;

        echo view('templates/header');
        echo view('relatorios/pagamentos', $data);
        echo view('templates/footer');
    }

    public function contadores()
    {
        $tipo = $this->session->get('tipo');
        
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso($tipo)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = '3';

        $data['titulo'] = [
            'modulo' => 'Relação de Contadores',
            'icone'  => 'fa fa-user-circle'
        ];

        $data['caminhos'] = [
            ['titulo' => "Início", 'rota' => "/inicio/admin", 'active' => false],
            ['titulo' => "Relatório", 'rota'   => "", 'active' => true]
        ];

        $contadores = $this->contador_model
                            ->select('id_contador, status, nome, nome_fantasia, cnpj, status')
                            ->findAll();

        $contagem = [];

        foreach($contadores as $contador) :
            $cont = $this->empresa_model
                        ->selectCount('id_empresa')
                        ->where('id_contador', $contador['id_contador'])
                        ->first()['id_empresa'];

            $empresas = $this->empresa_model
                            ->where('id_contador', $contador['id_contador'])
                            ->findAll();

            $somatorio_pagamentos = 0;

            foreach($empresas as $empresa) :
                $dados = $this->request->getvar();

                if(isset($dados['data_inicio']) && isset($dados['data_final'])) :
                    $somatorio_pagamentos += $this->pagamento_model
                                                ->selectSum('valor')
                                                ->where('id_empresa', $empresa['id_empresa'])
                                                ->where('data_do_pagamento >=', $dados['data_inicio'])
                                                ->where('data_do_pagamento <=', $dados['data_final'])
                                                ->first()['valor'];

                    $data['data_inicio'] = $dados['data_inicio'];
                    $data['data_final']  = $dados['data_final'];
                else:
                    $somatorio_pagamentos += $this->pagamento_model
                                                ->selectSum('valor')
                                                ->where('id_empresa', $empresa['id_empresa'])
                                                ->first()['valor'];
                endif;

            endforeach;
            
            $contagem[] = [
                'contador'             => $contador['nome'],
                'nome_fantasia'        => $contador['nome_fantasia'],
                'cnpj'                 => $contador['cnpj'],
                'status'               => $contador['status'],
                'somatorio_pagamentos' => $somatorio_pagamentos,
                'qtd_empresas'  => $cont
            ]; 
        endforeach;

        $data['contagens'] = $contagem;

        echo view('templates/header');
        echo view('relatorios/contadores', $data);
        echo view('templates/footer');
    }
}