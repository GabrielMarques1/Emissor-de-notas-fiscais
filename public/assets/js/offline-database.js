/**
 * Banco de Dados Offline no Navegador
 * Usa IndexedDB para armazenar dados offline por tenant
 */

class OfflineDatabase {
    constructor(tenantId) {
        this.tenantId = tenantId;
        this.dbName = `pdv_offline_${tenantId.replace(':', '_')}`;
        this.version = 1;
        this.db = null;
    }

    /**
     * Inicializa o banco IndexedDB
     */
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Store para vendas offline
                if (!db.objectStoreNames.contains('sales')) {
                    const salesStore = db.createObjectStore('sales', { keyPath: 'offline_id' });
                    salesStore.createIndex('status', 'status', { unique: false });
                    salesStore.createIndex('created_at', 'created_at', { unique: false });
                }

                // Store para itens de venda
                if (!db.objectStoreNames.contains('sale_items')) {
                    const itemsStore = db.createObjectStore('sale_items', { keyPath: 'offline_id' });
                    itemsStore.createIndex('sale_offline_id', 'sale_offline_id', { unique: false });
                }

                // Store para pagamentos
                if (!db.objectStoreNames.contains('payments')) {
                    const paymentsStore = db.createObjectStore('payments', { keyPath: 'offline_id' });
                    paymentsStore.createIndex('sale_offline_id', 'sale_offline_id', { unique: false });
                }

                // Store para clientes offline
                if (!db.objectStoreNames.contains('customers')) {
                    const customersStore = db.createObjectStore('customers', { keyPath: 'offline_id' });
                    customersStore.createIndex('cpf_cnpj', 'cpf_cnpj', { unique: false });
                }

                // Store para operações pendentes
                if (!db.objectStoreNames.contains('pending_operations')) {
                    const opsStore = db.createObjectStore('pending_operations', { keyPath: 'id' });
                    opsStore.createIndex('operation_type', 'operation_type', { unique: false });
                    opsStore.createIndex('created_at', 'created_at', { unique: false });
                }
            };
        });
    }

    /**
     * Gera ID único para operações offline
     */
    generateOfflineId() {
        return `offline_${this.tenantId}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Salva venda offline
     */
    async saveSaleOffline(saleData) {
        if (!this.db) await this.init();

        const offlineId = this.generateOfflineId();
        const sale = {
            offline_id: offlineId,
            tenant_id: this.tenantId,
            ...saleData,
            status: 'pending_sync',
            created_at: new Date().toISOString(),
            synced: false
        };

        const transaction = this.db.transaction(['sales'], 'readwrite');
        const store = transaction.objectStore('sales');
        
        await new Promise((resolve, reject) => {
            const request = store.add(sale);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });

        // Registrar operação pendente
        await this.addPendingOperation('create_sale', {
            offline_id: offlineId,
            data: sale
        });

        return offlineId;
    }

    /**
     * Salva itens da venda offline
     */
    async saveSaleItemsOffline(saleOfflineId, items) {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['sale_items'], 'readwrite');
        const store = transaction.objectStore('sale_items');

        for (const item of items) {
            const offlineItem = {
                offline_id: this.generateOfflineId(),
                sale_offline_id: saleOfflineId,
                tenant_id: this.tenantId,
                ...item,
                created_at: new Date().toISOString()
            };

            await new Promise((resolve, reject) => {
                const request = store.add(offlineItem);
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }
    }

    /**
     * Salva pagamentos offline
     */
    async savePaymentsOffline(saleOfflineId, payments) {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['payments'], 'readwrite');
        const store = transaction.objectStore('payments');

        for (const payment of payments) {
            const offlinePayment = {
                offline_id: this.generateOfflineId(),
                sale_offline_id: saleOfflineId,
                tenant_id: this.tenantId,
                ...payment,
                created_at: new Date().toISOString()
            };

            await new Promise((resolve, reject) => {
                const request = store.add(offlinePayment);
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }
    }

    /**
     * Adiciona operação pendente de sincronização
     */
    async addPendingOperation(operationType, data) {
        if (!this.db) await this.init();

        const operation = {
            id: this.generateOfflineId(),
            tenant_id: this.tenantId,
            operation_type: operationType,
            data: data,
            created_at: new Date().toISOString(),
            retry_count: 0,
            status: 'pending'
        };

        const transaction = this.db.transaction(['pending_operations'], 'readwrite');
        const store = transaction.objectStore('pending_operations');

        return new Promise((resolve, reject) => {
            const request = store.add(operation);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Busca operações pendentes de sincronização
     */
    async getPendingOperations() {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['pending_operations'], 'readonly');
        const store = transaction.objectStore('pending_operations');

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => {
                const operations = request.result.filter(op => 
                    op.tenant_id === this.tenantId && 
                    op.status === 'pending' &&
                    op.retry_count < 5
                );
                resolve(operations);
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Marca operação como sincronizada
     */
    async markOperationSynced(operationId) {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['pending_operations'], 'readwrite');
        const store = transaction.objectStore('pending_operations');

        const getRequest = store.get(operationId);
        
        return new Promise((resolve, reject) => {
            getRequest.onsuccess = () => {
                const operation = getRequest.result;
                if (operation) {
                    operation.status = 'synced';
                    operation.synced_at = new Date().toISOString();
                    
                    const putRequest = store.put(operation);
                    putRequest.onsuccess = () => resolve(true);
                    putRequest.onerror = () => reject(putRequest.error);
                } else {
                    resolve(false);
                }
            };
            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Incrementa retry de operação falhada
     */
    async incrementOperationRetry(operationId, errorMessage) {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['pending_operations'], 'readwrite');
        const store = transaction.objectStore('pending_operations');

        const getRequest = store.get(operationId);
        
        return new Promise((resolve, reject) => {
            getRequest.onsuccess = () => {
                const operation = getRequest.result;
                if (operation) {
                    operation.retry_count = (operation.retry_count || 0) + 1;
                    operation.last_error = errorMessage;
                    operation.last_retry_at = new Date().toISOString();
                    
                    if (operation.retry_count >= 5) {
                        operation.status = 'failed';
                    }
                    
                    const putRequest = store.put(operation);
                    putRequest.onsuccess = () => resolve(true);
                    putRequest.onerror = () => reject(putRequest.error);
                } else {
                    resolve(false);
                }
            };
            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Busca vendas offline não sincronizadas
     */
    async getUnsyncedSales() {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['sales'], 'readonly');
        const store = transaction.objectStore('sales');

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => {
                const sales = request.result.filter(sale => 
                    sale.tenant_id === this.tenantId && 
                    !sale.synced
                );
                resolve(sales);
            };
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Limpa dados antigos (mais de 30 dias)
     */
    async cleanOldData() {
        if (!this.db) await this.init();

        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        const cutoffDate = thirtyDaysAgo.toISOString();

        const stores = ['sales', 'sale_items', 'payments', 'pending_operations'];
        
        for (const storeName of stores) {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore('sales');
            const index = store.index('created_at');
            
            const range = IDBKeyRange.upperBound(cutoffDate);
            const request = index.openCursor(range);
            
            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    const record = cursor.value;
                    if (record.synced || record.status === 'synced') {
                        cursor.delete();
                    }
                    cursor.continue();
                }
            };
        }
    }

    /**
     * Estatísticas do banco offline
     */
    async getStats() {
        if (!this.db) await this.init();

        const stats = {
            tenant_id: this.tenantId,
            pending_sales: 0,
            pending_operations: 0,
            total_size_estimate: 0,
            last_sync: null
        };

        // Contar vendas pendentes
        const salesTransaction = this.db.transaction(['sales'], 'readonly');
        const salesStore = salesTransaction.objectStore('sales');
        
        const salesRequest = salesStore.getAll();
        salesRequest.onsuccess = () => {
            stats.pending_sales = salesRequest.result.filter(s => !s.synced).length;
        };

        // Contar operações pendentes
        const opsTransaction = this.db.transaction(['pending_operations'], 'readonly');
        const opsStore = opsTransaction.objectStore('pending_operations');
        
        const opsRequest = opsStore.getAll();
        opsRequest.onsuccess = () => {
            const pending = opsRequest.result.filter(op => op.status === 'pending');
            stats.pending_operations = pending.length;
            
            // Última sincronização
            const synced = opsRequest.result.filter(op => op.status === 'synced');
            if (synced.length > 0) {
                stats.last_sync = Math.max(...synced.map(op => new Date(op.synced_at).getTime()));
                stats.last_sync = new Date(stats.last_sync).toISOString();
            }
        };

        return stats;
    }
}

// Exportar para uso global
window.OfflineDatabase = OfflineDatabase;
