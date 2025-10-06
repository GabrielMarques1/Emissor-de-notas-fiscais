/**
 * Gerenciador de Sincronização Offline
 * Sincroniza dados do IndexedDB com o servidor
 */

class OfflineSyncManager {
    constructor(tenantId) {
        this.tenantId = tenantId;
        this.offlineDB = new OfflineDatabase(tenantId);
        this.syncInProgress = false;
        this.autoSyncInterval = null;
    }

    /**
     * Inicializa o gerenciador
     */
    async init() {
        await this.offlineDB.init();
        this.startAutoSync();
        this.attachEventListeners();
    }

    /**
     * Inicia sincronização automática
     */
    startAutoSync() {
        // Tentar sincronizar a cada 30 segundos quando online
        this.autoSyncInterval = setInterval(async () => {
            if (navigator.onLine && !this.syncInProgress) {
                await this.syncPendingOperations();
            }
        }, 30000);
    }

    /**
     * Para sincronização automática
     */
    stopAutoSync() {
        if (this.autoSyncInterval) {
            clearInterval(this.autoSyncInterval);
            this.autoSyncInterval = null;
        }
    }

    /**
     * Anexa event listeners
     */
    attachEventListeners() {
        // Sincronizar quando voltar online
        window.addEventListener('online', () => {
            console.log('[OfflineSync] Conexão restaurada, iniciando sincronização');
            setTimeout(() => this.syncPendingOperations(), 1000);
        });

        // Parar sync quando ficar offline
        window.addEventListener('offline', () => {
            console.log('[OfflineSync] Conexão perdida, pausando sincronização');
            this.syncInProgress = false;
        });
    }

    /**
     * Cria venda offline
     */
    async createSaleOffline(saleData, items, payments) {
        try {
            console.log('[OfflineSync] Criando venda offline', saleData);

            // Salvar venda
            const saleOfflineId = await this.offlineDB.saveSaleOffline(saleData);

            // Salvar itens
            if (items && items.length > 0) {
                await this.offlineDB.saveSaleItemsOffline(saleOfflineId, items);
            }

            // Salvar pagamentos
            if (payments && payments.length > 0) {
                await this.offlineDB.savePaymentsOffline(saleOfflineId, payments);
            }

            // Tentar sincronizar imediatamente se online
            if (navigator.onLine) {
                setTimeout(() => this.syncPendingOperations(), 500);
            }

            return {
                success: true,
                offline_id: saleOfflineId,
                message: 'Venda salva offline. Será sincronizada quando houver conexão.'
            };

        } catch (error) {
            console.error('[OfflineSync] Erro ao criar venda offline:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Sincroniza operações pendentes
     */
    async syncPendingOperations() {
        if (this.syncInProgress || !navigator.onLine) {
            return;
        }

        this.syncInProgress = true;

        try {
            console.log('[OfflineSync] Iniciando sincronização de operações pendentes');

            const pendingOps = await this.offlineDB.getPendingOperations();
            console.log(`[OfflineSync] ${pendingOps.length} operações pendentes`);

            let syncedCount = 0;
            let failedCount = 0;

            for (const operation of pendingOps) {
                try {
                    const result = await this.syncOperation(operation);
                    
                    if (result.success) {
                        await this.offlineDB.markOperationSynced(operation.id);
                        syncedCount++;
                        console.log(`[OfflineSync] Operação ${operation.id} sincronizada`);
                    } else {
                        await this.offlineDB.incrementOperationRetry(operation.id, result.error);
                        failedCount++;
                        console.warn(`[OfflineSync] Falha na operação ${operation.id}:`, result.error);
                    }

                } catch (error) {
                    await this.offlineDB.incrementOperationRetry(operation.id, error.message);
                    failedCount++;
                    console.error(`[OfflineSync] Erro na operação ${operation.id}:`, error);
                }
            }

            console.log(`[OfflineSync] Sincronização concluída: ${syncedCount} sucesso, ${failedCount} falhas`);

            // Notificar interface
            this.notifyUI({
                type: 'sync_complete',
                synced: syncedCount,
                failed: failedCount,
                total: pendingOps.length
            });

            // Limpar dados antigos
            await this.offlineDB.cleanOldData();

        } catch (error) {
            console.error('[OfflineSync] Erro na sincronização:', error);
        } finally {
            this.syncInProgress = false;
        }
    }

    /**
     * Sincroniza uma operação específica
     */
    async syncOperation(operation) {
        const { operation_type, data } = operation;

        switch (operation_type) {
            case 'create_sale':
                return await this.syncSale(data);
            
            case 'create_customer':
                return await this.syncCustomer(data);
            
            default:
                return {
                    success: false,
                    error: `Tipo de operação não suportado: ${operation_type}`
                };
        }
    }

    /**
     * Sincroniza venda com o servidor
     */
    async syncSale(saleData) {
        try {
            const response = await fetch('/api/pos/sales', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    ...saleData.data,
                    offline_id: saleData.offline_id,
                    created_offline: true
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                return {
                    success: true,
                    server_id: result.id
                };
            } else {
                return {
                    success: false,
                    error: result.message || 'Erro desconhecido do servidor'
                };
            }

        } catch (error) {
            return {
                success: false,
                error: `Erro de rede: ${error.message}`
            };
        }
    }

    /**
     * Sincroniza cliente com o servidor
     */
    async syncCustomer(customerData) {
        try {
            const response = await fetch('/api/customers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    ...customerData.data,
                    offline_id: customerData.offline_id,
                    created_offline: true
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                return {
                    success: true,
                    server_id: result.id
                };
            } else {
                return {
                    success: false,
                    error: result.message || 'Erro desconhecido do servidor'
                };
            }

        } catch (error) {
            return {
                success: false,
                error: `Erro de rede: ${error.message}`
            };
        }
    }

    /**
     * Força sincronização manual
     */
    async forcSync() {
        if (this.syncInProgress) {
            console.log('[OfflineSync] Sincronização já em andamento');
            return;
        }

        if (!navigator.onLine) {
            this.notifyUI({
                type: 'error',
                message: 'Sem conexão com a internet'
            });
            return;
        }

        await this.syncPendingOperations();
    }

    /**
     * Retorna estatísticas offline
     */
    async getStats() {
        return await this.offlineDB.getStats();
    }

    /**
     * Notifica a interface sobre eventos
     */
    notifyUI(event) {
        // Disparar evento customizado
        window.dispatchEvent(new CustomEvent('offline-sync-event', {
            detail: event
        }));

        // Integração com toastr se disponível
        if (typeof toastr !== 'undefined') {
            switch (event.type) {
                case 'sync_complete':
                    if (event.synced > 0) {
                        toastr.success(`${event.synced} operações sincronizadas com sucesso!`);
                    }
                    if (event.failed > 0) {
                        toastr.warning(`${event.failed} operações falharam na sincronização`);
                    }
                    break;
                
                case 'error':
                    toastr.error(event.message);
                    break;
            }
        }
    }

    /**
     * Limpa todos os dados offline (usar com cuidado)
     */
    async clearAllOfflineData() {
        if (confirm('Tem certeza que deseja limpar todos os dados offline? Dados não sincronizados serão perdidos!')) {
            try {
                await this.offlineDB.db.close();
                await new Promise((resolve, reject) => {
                    const deleteRequest = indexedDB.deleteDatabase(this.offlineDB.dbName);
                    deleteRequest.onsuccess = () => resolve();
                    deleteRequest.onerror = () => reject(deleteRequest.error);
                });
                
                console.log('[OfflineSync] Dados offline limpos');
                this.notifyUI({
                    type: 'info',
                    message: 'Dados offline limpos com sucesso'
                });
                
                // Reinicializar
                await this.offlineDB.init();
                
            } catch (error) {
                console.error('[OfflineSync] Erro ao limpar dados:', error);
                this.notifyUI({
                    type: 'error',
                    message: 'Erro ao limpar dados offline'
                });
            }
        }
    }
}

// Exportar para uso global
window.OfflineSyncManager = OfflineSyncManager;
