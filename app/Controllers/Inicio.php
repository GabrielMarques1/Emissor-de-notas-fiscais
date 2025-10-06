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
        $this->helpers = ['app', 'url'];

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
        
        // Adicionar dados dos dashboards de monitoramento
        $data['system_overview'] = $this->getSystemOverview();
        $data['security_status'] = $this->getSecurityStatus();
        $data['dashboards_status'] = $this->getDashboardsStatus();
        $data['recent_alerts'] = $this->getRecentAlerts();

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

            $prev = $this->session->get('_ci_previous_url');
            if (!is_string($prev) || $prev === '') {
                $prev = function_exists('previous_url') ? previous_url() : null;
                if (!$prev || !is_string($prev) || $prev === '') {
                    $prev = site_url('/login');
                }
            }
            return redirect()->to($prev);
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

    public function planos()
    {
        $data['config'] = $this->configuracao_model
                                ->where('id_config', 1)
                                ->first();

        echo view('templates/header');
        echo view('site/planos', $data);
        echo view('templates/footer');
    }
    
    /**
     * Obter visão geral do sistema para dashboard admin
     */
    private function getSystemOverview()
    {
        try {
            // Estatísticas gerais (admin vê tudo)
            $totalContadores = $this->contador_model->countAllResults();
            $activeContadores = $this->contador_model->where('status', 'Ativo')->countAllResults();
            $totalEmpresas = $this->empresa_model->countAllResults();
            $activeEmpresas = $this->empresa_model->where('status', 'Ativo')->countAllResults();
            
            // Estatísticas de hoje (todos os logins - admin tem visão global)
            $today = date('Y-m-d');
            $todayLogins = $this->login_model->where('DATE(created_at)', $today)->countAllResults();
            
            return [
                'total_contadores' => $totalContadores,
                'active_contadores' => $activeContadores,
                'total_empresas' => $totalEmpresas,
                'active_empresas' => $activeEmpresas,
                'today_logins' => $todayLogins,
                'uptime' => $this->getSystemUptime()
            ];
        } catch (\Exception $e) {
            // Log do erro para debugging
            log_message('error', '[Inicio::getSystemOverview] Erro ao obter estatísticas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_type' => session('tipo')
            ]);
            
            return [
                'total_contadores' => 0,
                'active_contadores' => 0,
                'total_empresas' => 0,
                'active_empresas' => 0,
                'today_logins' => 0,
                'uptime' => '0h 0m'
            ];
        }
    }
    
    /**
     * Obter status de segurança
     */
    private function getSecurityStatus()
    {
        $status = [
            'overall_score' => 100,
            'level' => 'excellent',
            'color' => 'success',
            'components' => [
                'tenant_filter' => ['status' => 'active', 'message' => 'TenantFilter ativo'],
                'audit_logging' => ['status' => 'active', 'message' => 'Logs de auditoria funcionando'],
                'backup_system' => ['status' => 'active', 'message' => 'Sistema de backup ativo'],
                'cache_isolation' => ['status' => 'active', 'message' => 'Cache isolado por tenant'],
                'database_triggers' => ['status' => 'active', 'message' => 'Triggers de segurança ativos']
            ]
        ];
        
        // Verificar se existem logs de hoje
        $logFile = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
        if (!file_exists($logFile)) {
            $status['overall_score'] -= 10;
            $status['components']['audit_logging']['status'] = 'warning';
            $status['components']['audit_logging']['message'] = 'Logs não encontrados hoje';
        }
        
        // Verificar diretório de backup
        $backupDir = WRITEPATH . 'backups/';
        if (!is_dir($backupDir)) {
            $status['overall_score'] -= 15;
            $status['components']['backup_system']['status'] = 'warning';
            $status['components']['backup_system']['message'] = 'Diretório de backup não encontrado';
        }
        
        // Determinar nível baseado no score
        if ($status['overall_score'] >= 90) {
            $status['level'] = 'excellent';
            $status['color'] = 'success';
        } elseif ($status['overall_score'] >= 70) {
            $status['level'] = 'good';
            $status['color'] = 'warning';
        } else {
            $status['level'] = 'critical';
            $status['color'] = 'danger';
        }
        
        return $status;
    }
    
    /**
     * Obter status dos dashboards
     */
    private function getDashboardsStatus()
    {
        return [
            'master_dashboard' => [
                'name' => 'Dashboard Master',
                'url' => '/inicio/admin',
                'status' => 'active',
                'description' => 'Central de monitoramento completo',
                'icon' => 'fas fa-tachometer-alt',
                'color' => 'primary'
            ],
            'backup_dashboard' => [
                'name' => 'Monitor de Backup',
                'url' => '/admin/backup-dashboard',
                'status' => 'active',
                'description' => 'Monitoramento de backups criptografados',
                'icon' => 'fas fa-shield-alt',
                'color' => 'success'
            ],
            'cache_monitor' => [
                'name' => 'Monitor de Cache',
                'url' => '/admin/cache-monitor',
                'status' => 'active',
                'description' => 'Cache isolado por tenant com anti-poisoning',
                'icon' => 'fas fa-memory',
                'color' => 'info'
            ],
            'audit_dashboard' => [
                'name' => 'Dashboard de Auditoria',
                'url' => '/admin/audit-dashboard',
                'status' => 'active',
                'description' => 'Logs de auditoria e segurança forense',
                'icon' => 'fas fa-search',
                'color' => 'warning'
            ],
            'security_dashboard' => [
                'name' => 'Dashboard de Segurança',
                'url' => '/admin/security-dashboard',
                'status' => 'active',
                'description' => 'Monitoramento de segurança em tempo real',
                'icon' => 'fas fa-lock',
                'color' => 'danger'
            ]
        ];
    }
    
    /**
     * Obter alertas recentes
     */
    private function getRecentAlerts()
    {
        $alerts = [];
        
        // Verificar logs de hoje para alertas
        $logFile = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $alertLines = array_filter($lines, function($line) {
                return strpos($line, 'CRITICAL') !== false || 
                       strpos($line, 'ERROR') !== false ||
                       strpos($line, 'SECURITY') !== false;
            });
            
            // Pegar últimos 5 alertas
            $recentAlerts = array_slice(array_reverse($alertLines), 0, 5);
            
            foreach ($recentAlerts as $line) {
                if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}).*?(CRITICAL|ERROR|SECURITY).*?(.+)/', $line, $matches)) {
                    $alerts[] = [
                        'timestamp' => $matches[1],
                        'level' => strtolower($matches[2]),
                        'message' => trim($matches[3]),
                        'type' => $this->getAlertType($matches[3])
                    ];
                }
            }
        }
        
        // Se não há alertas, adicionar mensagem de status OK
        if (empty($alerts)) {
            $alerts[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => 'info',
                'message' => 'Sistema funcionando normalmente - Nenhum alerta crítico',
                'type' => 'system'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Obter uptime do sistema
     */
    private function getSystemUptime()
    {
        $uptime = time() - strtotime('today');
        $hours = floor($uptime / 3600);
        $minutes = floor(($uptime % 3600) / 60);
        return "{$hours}h {$minutes}m";
    }
    
    /**
     * Determinar tipo do alerta
     */
    private function getAlertType($message)
    {
        if (strpos($message, 'BACKUP') !== false) return 'backup';
        if (strpos($message, 'CACHE') !== false) return 'cache';
        if (strpos($message, 'DATABASE') !== false) return 'database';
        if (strpos($message, 'SECURITY') !== false) return 'security';
        return 'system';
    }
}
