<?php

namespace App\Controllers;

use App\Models\ConfiguracaoModel;
use App\Models\EmpresaModel;
use App\Models\ContadorModel;
use App\Models\LoginModel;

use CodeIgniter\Controller;

class Login extends Controller
{
    private $session;
    private $id_contador;
    private $id_empresa;

    private $configuracao_model;
    private $empresa_model;
    private $contador_model;
    private $login_model;

    function __construct()
    {
        $this->session = session();
        $this->id_contador = $this->session->get('id_contador');
        $this->id_empresa  = $this->session->get('id_empresa');

        $this->configuracao_model = new ConfiguracaoModel();
        $this->empresa_model      = new EmpresaModel();
        $this->contador_model     = new ContadorModel();
        $this->login_model        = new LoginModel();
    }

    public function index()
    {
        $data['config'] = $this->configuracao_model
                                ->where('id_config', 1)
                                ->first();

        echo view('login/index', $data);
    }

    public function autenticar()
    {
        // Throttle por IP: 5 tentativas por minuto
        $throttler = service('throttler');
        $ipKey = 'login-' . $this->request->getIPAddress();
        if (!$throttler->check($ipKey, 5, MINUTE)) {
            $this->session->setFlashdata(
                'alert',
                [
                    'type'  => 'error',
                    'title' => 'Muitas tentativas. Tente novamente em instantes.'
                ]
            );
            return redirect()->to('/login');
        }
        $dados = $this->request
                        ->getvar();

        $login = $this->login_model
                    ->where('usuario', $dados['usuario'])
                    ->first();

        // Verifica senha (suporta senhas antigas em texto e novas com hash)
        $senhaValida = false;
        if ($login) {
            $info = password_get_info($login['senha']);
            if (!empty($info['algo'])) {
                $senhaValida = password_verify($dados['senha'], $login['senha']);
            } else {
                $senhaValida = hash_equals((string) $login['senha'], (string) $dados['senha']);
            }
        }

        if(!empty($login) && $senhaValida) :
            // Upgrade de senha: se armazenada sem hash, re-hash agora
            $info = password_get_info($login['senha']);
            if (empty($info['algo'])) {
                $this->login_model
                    ->where('id_login', $login['id_login'])
                    ->set('senha', password_hash($dados['senha'], PASSWORD_DEFAULT))
                    ->update();
            }
            $config = $this->configuracao_model
                                ->where('id_config', 1)
                                ->first();

            // Redireciona
            if($login['tipo'] == 1) :
                // Alerta de succeso de autenticação
                $this->session->setFlashdata(
                    'alert',
                    [
                        'type'  => 'success',
                        'title' => 'Login realizado com sucesso!'
                    ]
                );
                
                // Insere variáveis na sessão
                $this->session->regenerate();
                $this->session->set('id_login', $login['id_login']);
                $this->session->set('xFant', "NxSistemas");
                $this->session->set('xApp', $config['nome_do_app']);
                $this->session->set('usuario', $login['usuario']);
                $this->session->set('tipo', $login['tipo']);
                
                return redirect()->to('/inicio/admin');
            
            elseif($login['tipo'] == 2) :
                // Caso o tipo for 2 (Contador) então pega o id e coloca também na sessão
                $contador = $this->contador_model
                                ->where('id_login', $login['id_login'])
                                ->first();

                $this->session->regenerate();
                $this->session
                    ->set('id_contador', $contador['id_contador']);

                $this->session
                    ->set('status', $contador['status']);

                // Alerta de succeso de autenticação
                $this->session->setFlashdata(
                    'alert',
                    [
                        'type'  => 'success',
                        'title' => 'Login realizado com sucesso!'
                    ]
                );
                
                // Insere variáveis na sessão
                $this->session->set('xFant', $contador['nome_fantasia']);
                $this->session->set('xApp', $config['nome_do_app']);
                $this->session->set('id_login', $login['id_login']);
                $this->session->set('usuario', $login['usuario']);
                $this->session->set('tipo', $login['tipo']);

                return redirect()->to('/inicio/contador');

            elseif($login['tipo'] == 3) :
                // Caso o tipo for 3 (Empresa/Gerente) então pega o id_empresa e valida acesso
                $empresa = $this->empresa_model
                                ->where('id_login', $login['id_login'])
                                ->first();

                // Pega os dados do contador
                $contador = $this->contador_model
                                ->where('id_contador', $empresa['id_contador'])
                                ->first();

                $this->session->regenerate();
                $this->session->set('id_empresa', $empresa['id_empresa']);
                $this->session->set('id_contador', $empresa['id_contador']);

                // Alerta de succeso de autenticação
                $this->session->setFlashdata(
                    'alert',
                    [
                        'type'  => 'success',
                        'title' => 'Login realizado com sucesso!'
                    ]
                );

                // Insere variáveis na sessão
                $this->session->set('xFant', $empresa['xFant']);
                $this->session->set('xApp', $config['nome_do_app']);
                $this->session->set('id_login', $login['id_login']);
                $this->session->set('usuario', $login['usuario']);
                $this->session->set('tipo', $login['tipo']);

                return redirect()->to('/painel/empresa');
            
            elseif($login['tipo'] == 4) :
                // Tipo 4 não deve usar este login - redireciona para login PDV
                $this->session->setFlashdata(
                    'alert',
                    [
                        'type'  => 'warning',
                        'title' => 'Use o login específico do PDV'
                    ]
                );
                return redirect()->to('/index.php/login-pdv');
            endif;

        else :
            
            // Informa que os dados estão errados
            $this->session->setFlashdata(
                'alert', 
                [
                    'type'  => 'error',
                    'title' => 'Usuário ou senha incorretos!'
                ]
            );

            // Retorna para o login
            return redirect()->to("/login");

        endif;
    }

    public function logout()
    {
        $this->session
            ->destroy();

        return redirect()->to('/login');
    }

    public function verificaUsuario()
    {
        $dados = $this->request
                        ->getvar();

        $usuario = $this->login_model
                        ->where('usuario', $dados['usuario'])
                        ->first();
        
        if(!empty($usuario)) :
            echo 1;
        else:
            echo 0;
        endif;
    }

    public function teste(){
        echo md5('soueu123!$%C0');
        echo "<br>";
        echo md5('soueu123!$%C0');
    }
}
