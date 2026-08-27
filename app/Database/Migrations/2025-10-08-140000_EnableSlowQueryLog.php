<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Habilitar Slow Query Log para profiling
 * 
 * IMPORTANTE: Usar apenas em desenvolvimento/staging
 */
class EnableSlowQueryLog extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Verificar se é ambiente de desenvolvimento
        if (ENVIRONMENT === 'production') {
            log_message('warning', 'Slow Query Log não habilitado em produção. Configure manualmente no my.cnf se necessário.');
            return;
        }
        
        // Habilitar slow query log (queries > 1 segundo)
        try {
            $db->query("SET GLOBAL slow_query_log = 'ON'");
            $db->query("SET GLOBAL long_query_time = 1");
            $db->query("SET GLOBAL log_queries_not_using_indexes = 'ON'");
            
            log_message('info', 'Slow Query Log habilitado com sucesso');
            log_message('info', 'Queries > 1s serão logadas');
            log_message('info', 'Queries sem índices serão logadas');
        } catch (\Exception $e) {
            log_message('error', 'Erro ao habilitar Slow Query Log: ' . $e->getMessage());
            log_message('info', 'Configure manualmente no MySQL: slow_query_log=ON, long_query_time=1');
        }
    }

    public function down()
    {
        if (ENVIRONMENT === 'production') {
            return;
        }
        
        $db = \Config\Database::connect();
        
        try {
            $db->query("SET GLOBAL slow_query_log = 'OFF'");
            $db->query("SET GLOBAL log_queries_not_using_indexes = 'OFF'");
            
            log_message('info', 'Slow Query Log desabilitado');
        } catch (\Exception $e) {
            log_message('error', 'Erro ao desabilitar Slow Query Log: ' . $e->getMessage());
        }
    }
}

