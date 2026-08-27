<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class Diagnostics extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        helper('app');
        $session = session();
        $ids = function_exists('resolve_tenant_ids') ? resolve_tenant_ids() : [0,0];
        [$idContadorResolved, $idEmpresaResolved] = $ids;

        $data = [
            'env' => defined('ENVIRONMENT') ? ENVIRONMENT : null,
            'ci_version' => defined('CI_VERSION') ? CI_VERSION : null,
            'session' => [
                'id_contador' => $session->get('id_contador'),
                'id_empresa' => $session->get('id_empresa'),
                'tipo' => $session->get('tipo'),
            ],
            'resolved_tenant' => [
                'id_contador' => $idContadorResolved,
                'id_empresa' => $idEmpresaResolved,
            ],
        ];

        try {
            $db = \Config\Database::connect();
            $counts = [];
            $counts['cash_registers'] = (int) $db->table('cash_registers')->countAllResults();
            $counts['open_shifts'] = (int) $db->table('shifts')->where('status','open')->countAllResults();
            $counts['products_total'] = (int) $db->table('produtos')->countAllResults();
            if ($idEmpresaResolved) {
                $counts['products_by_empresa'] = (int) $db->table('produtos')->where('id_empresa', $idEmpresaResolved)->countAllResults();
            }
            $data['counts'] = $counts;
        } catch (\Throwable $e) {
            $data['counts_error'] = $e->getMessage();
        }

        // Últimas linhas do log mais recente
        try {
            $lastLog = $this->getLastLogTail(300);
            $data['log_tail'] = $lastLog;
        } catch (\Throwable $e) {
            $data['log_error'] = $e->getMessage();
        }

        return $this->respond($data);
    }

    public function logs()
    {
        $lines = (int) ($this->request->getGet('lines') ?? 300);
        $lines = max(50, min(2000, $lines));
        try {
            return $this->respond([
                'lines' => $lines,
                'tail' => $this->getLastLogTail($lines),
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    private function getLastLogTail(int $lines): string
    {
        $logDir = WRITEPATH . 'logs';
        if (!is_dir($logDir)) return '';
        $files = glob($logDir . DIRECTORY_SEPARATOR . 'log-*.log');
        if (!$files) return '';
        rsort($files);
        $file = $files[0];
        $content = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $slice = array_slice($content, -$lines);
        return implode("\n", $slice);
    }
}


