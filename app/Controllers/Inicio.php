<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;
use App\Models\NFCeModel;
use App\Models\NFeModel;
use App\Models\ContadorModel;
use App\Models\EmpresaModel;
use App\Models\LoginModel;

use CodeIgniter\Controller;

class Inicio extends Controller
{
    private $link = '1';

    private $session;
    private $id_contador;
    private $id_empresa;

    private $configuracao_model;
    private $nfce_model;
    private $nfe_model;
    private $contador_model;
    private $empresa_model;
    private $login_model;

    function __construct()
    {
        $this->helpers = ['app'];

        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');

        $this->configuracao_model = new ConfiguracaoModel();
        $this->nfce_model         = new NFCeModel();
        $this->nfe_model          = new NFeModel();
        $this->contador_model     = new ContadorModel();
        $this->empresa_model      = new EmpresaModel();
        $this->login_model        = new LoginModel();
    }

    public function admin()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso(1)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        echo view('templates/header');
        echo view('start/admin', $data);
        echo view('templates/footer');
    }

    public function contador()
    {
        // Aqui a forma de verificação é diferente para mostrar uma mensagem para o contador
        // realizar o pagamento e não permitir que outros acessse essa função sem autorização

        if($this->session->get('tipo') != 2) :
            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'error',
                    'title' => 'Você não tem permissão de acessar essa funcionalidade!'
                ]
            );

            return redirect()->to($this->session->get('_ci_previous_url')) ;
        endif;

        $data['link'] = $this->link;

        $data['dados_do_contador'] = $this->contador_model
                                        ->where('id_contador', $this->id_contador)
                                        ->first();

        $data['config'] = $this->configuracao_model
                                ->where('id_config', 1)
                                ->first();

        echo view('templates/header');
        echo view('start/contador', $data);
        echo view('templates/footer');
    }

    public function emissor()
    {
        // Verifica se o usuário tem permissão de acessar essa url  
        if($retorno = verificaPermissaoDeAcesso(3)) :
            return redirect()->to($retorno);
        endif;

        $data['link'] = $this->link;

        // Pega primeiro o contador e depois a empresa desse contador, para não perder o desempenho na consulta.
        $data['dados_da_empresa'] = $this->empresa_model
                                        ->where('id_contador', $this->id_contador)
                                        ->where('id_empresa', $this->id_empresa)
                                        ->first();

        // Total de NFe de entrada
        $data['total_nfe_entrada_emitidas'] = count($this->nfe_model
                                                    ->where('id_contador', $this->id_contador)
                                                    ->where('id_empresa', $this->id_empresa)
                                                    ->where('tipo', 1) // 1=Entrada
                                                    ->findAll());

        // Total de NFe de entrada
        $data['total_nfe_saidas_emitidas'] = count($this->nfe_model
                                                    ->where('id_contador', $this->id_contador)
                                                    ->where('id_empresa', $this->id_empresa)
                                                    ->where('tipo', 2) // 2=Saída
                                                    ->findAll());

        // Total de NFe de entrada
        $data['total_nfe_devolucao_emitidas'] = count($this->nfe_model
                                                    ->where('id_contador', $this->id_contador)
                                                    ->where('id_empresa', $this->id_empresa)
                                                    ->where('tipo', 3) // 3=Devolução
                                                    ->findAll());

        // Total de NFCe emitidas
        $data['total_nfce_emitidas'] = count($this->nfce_model
                                            ->where('id_contador', $this->id_contador)
                                            ->where('id_empresa', $this->id_empresa)
                                            ->findAll());

        $meses = [
            [
                'mes'  => '01',
                'nome' => 'Jan'
            ],
            [
                'mes'  => '02',
                'nome' => 'Fev'
            ],
            [
                'mes'  => '03',
                'nome' => 'Mar'
            ],
            [
                'mes'  => '04',
                'nome' => 'Abr'
            ],
            [
                'mes'  => '05',
                'nome' => 'Mai'
            ],
            [
                'mes'  => '06',
                'nome' => 'Jun'
            ],
            [
                'mes'  => '07',
                'nome' => 'Jul'
            ],
            [
                'mes'  => '08',
                'nome' => 'Ago'
            ],
            [
                'mes'  => '09',
                'nome' => 'Set'
            ],
            [
                'mes'  => '10',
                'nome' => 'Out'
            ],
            [
                'mes'  => '11',
                'nome' => 'Nov'
            ],
            [
                'mes'  => '12',
                'nome' => 'Dez'
            ],
        ];

        // Valores do gráfico de quantidade de notas geradas       
        foreach($meses as $mes) :
            $qtd_de_notas_nfe = null;
            $qtd_de_notas_nfce = null;

            $data_inicio = date('Y')."-{$mes['mes']}-01";
            $data_final  = date('Y')."-{$mes['mes']}-31";

            $notas_nfe = $this->nfe_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->where('data >=', $data_inicio)
                                ->where('data <=', $data_final)
                                ->findAll();

            $notas_nfce = $this->nfce_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->where('data >=', $data_inicio)
                                ->where('data <=', $data_final)
                                ->findAll();

            $array_qtd_de_notas_geradas[] = [
                'mes' => $mes['nome'],
                'qtd' => count($notas_nfe) + count($notas_nfce)
            ];
        endforeach;

        // Valores do gráfico de valor total das notas geradas        
        foreach($meses as $mes) :
            $qtd_de_notas_nfe = null;
            $qtd_de_notas_nfce = null;

            $data_inicio = date('Y')."-{$mes['mes']}-01";
            $data_final  = date('Y')."-{$mes['mes']}-31";

            $notas_nfe = $this->nfe_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->where('data >=', $data_inicio)
                                ->where('data <=', $data_final)
                                ->selectSum('valor_da_nota')
                                ->findAll()[0]['valor_da_nota'];

            $notas_nfce = $this->nfce_model
                                ->where('id_contador', $this->id_contador)
                                ->where('id_empresa', $this->id_empresa)
                                ->where('data >=', $data_inicio)
                                ->where('data <=', $data_final)
                                ->selectSum('valor_da_nota')
                                ->findAll()[0]['valor_da_nota'];

            $array_valor_total_notas_geradas[] = [
                'mes'   => $mes['nome'],
                'valor' => $notas_nfe + $notas_nfce
            ];
        endforeach;

        // ------ //

        $data['array_qtd_de_notas_geradas']      = $array_qtd_de_notas_geradas;
        $data['array_valor_total_notas_geradas'] = $array_valor_total_notas_geradas;

        echo view('templates/header');
        echo view('start/emissor', $data);
        echo view('templates/footer');
    }
}
