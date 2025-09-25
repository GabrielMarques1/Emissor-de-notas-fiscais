<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CaixaSessaoModel;

class Caixa extends ResourceController
{
    protected $format = 'json';

    // GET /api/caixa/status
    public function status()
    {
        try {
            $session = session();
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            $model = new CaixaSessaoModel();
            $openId = $model->findOpenSessionId($idContador, $idEmpresa);
            $current = null;
            if ($openId) {
                $current = $model->find((int) $openId);
            } else {
                // Última sessão (fechada) para referência
                $current = $model->asArray()
                    ->where('id_contador', $idContador ?: 0)
                    ->where('id_empresa',  $idEmpresa  ?: 0)
                    ->orderBy('id', 'DESC')
                    ->first();
            }
            return $this->respond(['open' => (bool) $openId, 'session' => $current]);
        } catch (\Throwable $e) {
            return $this->failServerError('Falha ao consultar status do caixa');
        }
    }

    // POST /api/caixa/abrir
    public function abrir()
    {
		try {
			$payload = $this->request->getJSON(true) ?? $this->request->getPost();
			$valorInicialRaw = (string) ($payload['valor_inicial'] ?? '0');
			$valorInicial = round($this->normalizaValor($valorInicialRaw), 2);

			$session = session();
			$idUsuario = (int) ($session->get('id_usuario') ?? 0);
			$idContador = (int) ($session->get('id_contador') ?? 0);
			$idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
			if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
				[$idContador,$idEmpresa] = resolve_tenant_ids();
			}

			$model = new CaixaSessaoModel();
			// Se já existir sessão aberta, responde 409
			$openId = $model->findOpenSessionId($idContador, $idEmpresa);
			if ($openId) {
				return $this->failResourceExists('Já existe um caixa aberto. Feche o caixa atual antes de abrir um novo.');
			}
			$created = $model->abrirSessao($idUsuario, $valorInicial, $idContador, $idEmpresa);
			if ($created === false) {
				return $this->failServerError('Falha ao abrir caixa');
			}
			return $this->respondCreated($created);
		} catch (\Throwable $e) {
			log_message('error', '[Caixa::abrir] ' . $e->getMessage());
			return $this->failServerError('Falha ao abrir caixa');
		}
    }

    private function normalizaValor(string $raw): float
    {
        $raw = trim($raw);
        if ($raw === '') return 0.0;
        $norm = str_replace(['.', ' '], '', $raw);
        $norm = str_replace(',', '.', $norm);
        return is_numeric($norm) ? (float) $norm : 0.0;
    }

    // POST /api/caixa/fechar
    public function fechar()
    {
        // Adiciona logs visíveis em produção para verificação de entrada
        log_message('critical', 'ATENÇÃO: O MÉTODO Caixa::fechar() FOI EXECUTADO.');
        log_message('debug', '==================================================');
        log_message('debug', '[Caixa::fechar] INICIANDO PROCESSO DE FECHAMENTO.');

            try {
            $payload = $this->request->getJSON(true) ?? $this->request->getPost();
            log_message('debug', '[Caixa::fechar] Payload recebido: ' . json_encode($payload));

            $contadoRaw = (string) ($payload['valor_final_contado_dinheiro'] ?? ($payload['closing_amount'] ?? '0'));
            $valorContado = round($this->normalizaValor($contadoRaw), 2);
            log_message('debug', "[Caixa::fechar] Valor contado normalizado: {$valorContado}");

            $session = session();
            $idUsuario = (int) ($session->get('id_usuario') ?? 0);
            $idContador = (int) ($session->get('id_contador') ?? 0);
            $idEmpresa  = (int) ($session->get('id_empresa') ?? 0);
            if (($idContador === 0 || $idEmpresa === 0) && function_exists('resolve_tenant_ids')) {
                [$idContador,$idEmpresa] = resolve_tenant_ids();
            }
            log_message('debug', "[Caixa::fechar] IDs de tenant: Contador={$idContador}, Empresa={$idEmpresa}");

			try {
				$model = new \App\Models\CaixaSessaoModel();
				$openId = $model->findOpenSessionId($idContador, $idEmpresa);
				if (! $openId) {
					return $this->failNotFound('Nenhum caixa aberto encontrado para fechar.');
				}
				$closed = $model->fecharSessao((int) $openId, $idUsuario, (float) $valorContado);
				if ($closed === false) {
					return $this->failServerError('Falha ao fechar o caixa.');
				}
				log_message('info', '[Caixa::fechar] Fechado via model centralizado | id_caixa_sessao={id}', ['id' => $closed['id'] ?? null]);
				return $this->respond($closed);
			} catch (\Throwable $e) {
				log_message('error', '[Caixa::fechar] Falha no model centralizado: ' . $e->getMessage());
				return $this->failServerError('Erro inesperado ao fechar caixa');
			}

        } catch (\Throwable $e) {
            log_message('error', '[Caixa::fechar] EXCEÇÃO CAPTURADA: ' . $e->getMessage() . ' no arquivo ' . $e->getFile() . ' na linha ' . $e->getLine());
            return $this->failServerError('Erro inesperado ao fechar caixa: ' . $e->getMessage());
        }
    }
}

