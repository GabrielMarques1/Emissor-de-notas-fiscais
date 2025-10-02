<?php

namespace App\Filters;

use App\Models\EmpresaModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PdvAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Log inicial detalhado
        $uri = $request->getUri()->getPath();
        log_message('debug', '=== PdvAccessFilter INÍCIO === URI: ' . $uri);
        
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'testing') {
            log_message('debug', 'PdvAccessFilter - Ambiente de teste, pulando validação');
            return null;
        }

        $session = session();
        $tipo = (int) ($session->get('tipo') ?? 0);
        $idEmpresa = (int) ($session->get('id_empresa') ?? 0);
        $idContador = (int) ($session->get('id_contador') ?? 0);
        $usuario = $session->get('usuario');
        
        // Log detalhado da sessão
        log_message('debug', 'PdvAccessFilter - Dados da sessão:');
        log_message('debug', '  - tipo: ' . $tipo);
        log_message('debug', '  - id_empresa: ' . $idEmpresa);
        log_message('debug', '  - id_contador: ' . $idContador);
        log_message('debug', '  - usuario: ' . ($usuario ?? 'null'));
        log_message('debug', '  - session_id: ' . session_id());
        
        // Autocorreção de sessão APENAS se usuário já estiver logado mas faltar dados específicos
        if (($idEmpresa <= 0) && $usuario && function_exists('resolve_tenant_ids')) {
            log_message('debug', 'PdvAccessFilter - Tentando autocorreção de sessão...');
            [$idContadorResolved,$idEmpResolved] = resolve_tenant_ids();
            log_message('debug', 'PdvAccessFilter - resolve_tenant_ids retornou: contador=' . $idContadorResolved . ', empresa=' . $idEmpResolved);
            
            if ($idContadorResolved > 0 && $idEmpResolved > 0) {
                if ($session->get('id_contador') === null) { 
                    $session->set('id_contador', $idContadorResolved);
                    log_message('debug', 'PdvAccessFilter - id_contador definido: ' . $idContadorResolved);
                }
                if ($idEmpresa <= 0) { 
                    $session->set('id_empresa', $idEmpResolved); 
                    $idEmpresa = $idEmpResolved;
                    log_message('debug', 'PdvAccessFilter - id_empresa definido: ' . $idEmpResolved);
                }
                log_message('info', 'PdvAccessFilter - Sessão autocorrigida para empresa: ' . $idEmpResolved);
            } else {
                log_message('error', 'PdvAccessFilter - resolve_tenant_ids não retornou dados válidos');
            }
        }

        $acceptsJson = strpos((string) ($request->getHeaderLine('Accept') ?? ''), 'application/json') !== false;
        $path = '/' . trim((string) $request->getUri()->getPath(), '/');
        $isApi = (bool) preg_match('#(^|/)api(\/|$)#i', $path);
        $wantsJson = $acceptsJson || $isApi;

        // Validação de tipo e empresa
        $tipoValido = in_array($tipo, [1,3,4], true);
        log_message('debug', 'PdvAccessFilter - Validação de acesso:');
        log_message('debug', '  - tipos permitidos: [1,3,4]');
        log_message('debug', '  - tipo do usuário: ' . $tipo . ' (válido: ' . ($tipoValido ? 'SIM' : 'NÃO') . ')');
        log_message('debug', '  - id_empresa: ' . $idEmpresa . ' (válido: ' . ($idEmpresa > 0 ? 'SIM' : 'NÃO') . ')');
        log_message('debug', '  - wants JSON: ' . ($wantsJson ? 'SIM' : 'NÃO'));
        
        if (! $tipoValido || $idEmpresa <= 0) {
            log_message('error', 'PdvAccessFilter - ACESSO NEGADO!');
            log_message('error', '  - Motivo: ' . (!$tipoValido ? 'Tipo inválido' : 'ID empresa inválido'));
            
            $resp = ['error' => 'Não autenticado ou perfil inválido.'];
            // Para API, nunca redireciona: responde JSON 401
            if ($wantsJson) {
                log_message('debug', 'PdvAccessFilter - Retornando JSON 401');
                return service('response')->setStatusCode(401)->setJSON($resp);
            }
            log_message('debug', 'PdvAccessFilter - Redirecionando para /login-pdv');
            return redirect()->to('/index.php/login-pdv');
        }

        log_message('debug', 'PdvAccessFilter - Verificando empresa no banco...');
        
        try {
            $empresaModel = new EmpresaModel();
            $empresa = $empresaModel->find($idEmpresa);
            
            if (!$empresa) {
                log_message('error', 'PdvAccessFilter - EMPRESA NÃO ENCONTRADA no banco!');
                log_message('error', '  - ID procurado: ' . $idEmpresa);
                
                $resp = ['error' => 'Sessão inválida.'];
                if ($wantsJson) {
                    log_message('debug', 'PdvAccessFilter - Retornando JSON 401 (empresa não encontrada)');
                    return service('response')->setStatusCode(401)->setJSON($resp);
                }
                log_message('debug', 'PdvAccessFilter - Redirecionando para /login-pdv (empresa não encontrada)');
                return redirect()->to('/index.php/login-pdv');
            }
            
            log_message('debug', 'PdvAccessFilter - Empresa encontrada: ' . $empresa['xFant'] . ' (ID: ' . $empresa['id_empresa'] . ')');
            log_message('debug', '=== PdvAccessFilter SUCESSO === Acesso liberado para ' . $usuario);
            
        } catch (\Exception $e) {
            log_message('error', 'PdvAccessFilter - ERRO ao verificar empresa: ' . $e->getMessage());
            log_message('error', '  - Arquivo: ' . $e->getFile() . ':' . $e->getLine());
            
            $resp = ['error' => 'Erro interno do sistema.'];
            if ($wantsJson) {
                return service('response')->setStatusCode(500)->setJSON($resp);
            }
            return redirect()->to('/index.php/login-pdv');
        }

        // Assinatura pode ser verificada por outro filtro (subscription)
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}


