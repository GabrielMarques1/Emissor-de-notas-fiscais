<?php

namespace App\Libraries;

use App\Models\NFeModel;
use CodeIgniter\Database\BaseConnection;

class HybridPersistenceService
{
    /**
     * Salva dados da nota em nuvem (default) e também no banco local (local_backup).
     *
     * Fluxo:
     * - Tenta salvar na nuvem; se ok, tenta salvar também localmente.
     * - Se a nuvem falhar, registra erro e ainda tenta salvar localmente.
     * - Retorna true se pelo menos uma das operações (nuvem ou local) for bem-sucedida.
     *
     * @param array $invoiceData
     * @param string $modelClass Classe do Model (ex.: App\Models\NFeModel ou App\Models\NFCeModel)
     * @return bool
     */
    public function saveInvoice(array $invoiceData, string $modelClass = NFeModel::class): bool
    {
        $cloudSucceeded = false;
        $localSucceeded = false;

        // 1) Tenta salvar na nuvem (conexão default)
        try {
            /** @var \CodeIgniter\Model $cloudModel */
            $cloudModel = new $modelClass();
            $insertResult = $cloudModel->insert($invoiceData);
            if ($insertResult !== false) {
                $cloudSucceeded = true;
            }
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            log_message('error', 'Falha ao salvar na nuvem: {message}', ['message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            log_message('error', 'Erro inesperado na gravação em nuvem: {message}', ['message' => $e->getMessage()]);
        }

        // 2) Tenta salvar no banco local (local_backup)
        try {
            /** @var BaseConnection $localDb */
            $localDb = \Config\Database::connect('local_backup');
            /** @var \CodeIgniter\Model $localModel */
            $localModel = new $modelClass($localDb);
            $insertLocal = $localModel->insert($invoiceData);
            if ($insertLocal !== false) {
                $localSucceeded = true;
            }
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            log_message('error', 'Falha ao salvar no banco local: {message}', ['message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            log_message('error', 'Erro inesperado na gravação local: {message}', ['message' => $e->getMessage()]);
        }

        return $cloudSucceeded || $localSucceeded;
    }
}


