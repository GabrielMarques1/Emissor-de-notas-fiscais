/**
 * 🔥 OFFLINE MANAGER - PDV MULTI-TENANT
 * 
 * Gerencia dados offline usando IndexedDB
 * Garante isolamento por tenant (id_empresa + id_contador)
 */

class OfflineManager {
    constructor() {
        this.dbName = 'PDV_MultiTenant';
        this.dbVersion = 1;
        this.db = null;
        this.tenantKey = null;
        this.initialized = false;
    }

    /**
     * Inicializa o IndexedDB
     */
    async init(idEmpresa, idContador) {
        if (this.initialized && this.tenantKey === `${idEmpresa}_${idContador}`) {
            return;
        }

        this.tenantKey = `${idEmpresa}_${idContador}`;

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = () => {
                console.error('[OfflineManager] Erro ao abrir IndexedDB:', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                this.initialized = true;
                console.log('[OfflineManager] IndexedDB inicializado para tenant:', this.tenantKey);
                resolve();
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Store para produtos
                if (!db.objectStoreNames.contains('produtos')) {
                    const produtosStore = db.createObjectStore('produtos', { keyPath: 'id' });
                    produtosStore.createIndex('tenant', 'tenant', { unique: false });
                    produtosStore.createIndex('codigo_de_barras', 'codigo_de_barras', { unique: false });
                    produtosStore.createIndex('updated_at', 'updated_at', { unique: false });
                }

                // Store para clientes
                if (!db.objectStoreNames.contains('clientes')) {
                    const clientesStore = db.createObjectStore('clientes', { keyPath: 'id' });
                    clientesStore.createIndex('tenant', 'tenant', { unique: false });
                    clientesStore.createIndex('cpf', 'cpf', { unique: false });
                    clientesStore.createIndex('cnpj', 'cnpj', { unique: false });
                }

                // Store para configurações
                if (!db.objectStoreNames.contains('config')) {
                    db.createObjectStore('config', { keyPath: 'key' });
                }

                // Store para operações pendentes (outbox)
                if (!db.objectStoreNames.contains('outbox')) {
                    const outboxStore = db.createObjectStore('outbox', { keyPath: 'id', autoIncrement: true });
                    outboxStore.createIndex('tenant', 'tenant', { unique: false });
                    outboxStore.createIndex('created_at', 'created_at', { unique: false });
                    outboxStore.createIndex('status', 'status', { unique: false });
                }

                console.log('[OfflineManager] Estrutura do banco criada');
            };
        });
    }

    /**
     * Salva produtos no cache offline (com isolamento tenant)
     */
    async saveProdutos(produtos) {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        const tx = this.db.transaction(['produtos'], 'readwrite');
        const store = tx.objectStore('produtos');

        // Limpar produtos antigos deste tenant
        await this.clearProdutos();

        // Salvar novos produtos com tenant
        for (const produto of produtos) {
            store.put({
                ...produto,
                id: `${this.tenantKey}_${produto.id_produto}`,
                tenant: this.tenantKey,
                updated_at: Date.now()
            });
        }

        return new Promise((resolve, reject) => {
            tx.oncomplete = () => {
                console.log(`[OfflineManager] ${produtos.length} produtos salvos offline`);
                resolve();
            };
            tx.onerror = () => reject(tx.error);
        });
    }

    /**
     * Busca produtos offline (apenas do tenant atual)
     */
    async getProdutos(limit = 100) {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['produtos'], 'readonly');
            const store = tx.objectStore('produtos');
            const index = store.index('tenant');
            const request = index.getAll(this.tenantKey, limit);

            request.onsuccess = () => {
                const produtos = request.result.map(p => {
                    const { tenant, updated_at, ...produto } = p;
                    return produto;
                });
                console.log(`[OfflineManager] ${produtos.length} produtos carregados do cache`);
                resolve(produtos);
            };

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Busca produto por código de barras (apenas do tenant atual)
     */
    async getProdutoByBarcode(barcode) {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['produtos'], 'readonly');
            const store = tx.objectStore('produtos');
            const index = store.index('codigo_de_barras');
            const request = index.getAll(barcode);

            request.onsuccess = () => {
                // Filtrar pelo tenant (pois index não é unique)
                const produtos = request.result.filter(p => p.tenant === this.tenantKey);
                
                if (produtos.length > 0) {
                    const { tenant, updated_at, ...produto } = produtos[0];
                    resolve(produto);
                } else {
                    resolve(null);
                }
            };

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Limpa produtos do tenant atual
     */
    async clearProdutos() {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['produtos'], 'readwrite');
            const store = tx.objectStore('produtos');
            const index = store.index('tenant');
            const request = index.openCursor(this.tenantKey);

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    cursor.delete();
                    cursor.continue();
                }
            };

            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    /**
     * Salva clientes no cache offline
     */
    async saveClientes(clientes) {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        const tx = this.db.transaction(['clientes'], 'readwrite');
        const store = tx.objectStore('clientes');

        await this.clearClientes();

        for (const cliente of clientes) {
            store.put({
                ...cliente,
                id: `${this.tenantKey}_${cliente.id_cliente}`,
                tenant: this.tenantKey
            });
        }

        return new Promise((resolve, reject) => {
            tx.oncomplete = () => {
                console.log(`[OfflineManager] ${clientes.length} clientes salvos offline`);
                resolve();
            };
            tx.onerror = () => reject(tx.error);
        });
    }

    /**
     * Busca clientes offline
     */
    async getClientes(limit = 100) {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['clientes'], 'readonly');
            const store = tx.objectStore('clientes');
            const index = store.index('tenant');
            const request = index.getAll(this.tenantKey, limit);

            request.onsuccess = () => {
                const clientes = request.result.map(c => {
                    const { tenant, ...cliente } = c;
                    return cliente;
                });
                resolve(clientes);
            };

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Limpa clientes do tenant atual
     */
    async clearClientes() {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['clientes'], 'readwrite');
            const store = tx.objectStore('clientes');
            const index = store.index('tenant');
            const request = index.openCursor(this.tenantKey);

            request.onsucomplete = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    cursor.delete();
                    cursor.continue();
                }
            };

            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    /**
     * Adiciona operação pendente ao outbox
     */
    async addToOutbox(operation, data) {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['outbox'], 'readwrite');
            const store = tx.objectStore('outbox');

            const outboxItem = {
                tenant: this.tenantKey,
                operation,
                data,
                status: 'pending',
                created_at: Date.now(),
                retry_count: 0
            };

            const request = store.add(outboxItem);

            request.onsuccess = () => {
                console.log('[OfflineManager] Operação adicionada ao outbox:', operation);
                resolve(request.result);
            };

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Busca operações pendentes do outbox
     */
    async getPendingOutbox(limit = 50) {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['outbox'], 'readonly');
            const store = tx.objectStore('outbox');
            const index = store.index('status');
            const request = index.getAll('pending', limit);

            request.onsuccess = () => {
                // Filtrar pelo tenant atual
                const items = request.result.filter(item => item.tenant === this.tenantKey);
                resolve(items);
            };

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Marca operação do outbox como concluída
     */
    async markOutboxComplete(id) {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['outbox'], 'readwrite');
            const store = tx.objectStore('outbox');
            const request = store.delete(id);

            request.onsuccess = () => {
                console.log('[OfflineManager] Operação do outbox concluída:', id);
                resolve();
            };

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Incrementa contador de retry de uma operação
     */
    async incrementRetry(id) {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['outbox'], 'readwrite');
            const store = tx.objectStore('outbox');
            const getRequest = store.get(id);

            getRequest.onsuccess = () => {
                const item = getRequest.result;
                if (item) {
                    item.retry_count++;
                    item.last_retry = Date.now();
                    
                    const putRequest = store.put(item);
                    putRequest.onsuccess = () => resolve();
                    putRequest.onerror = () => reject(putRequest.error);
                } else {
                    resolve();
                }
            };

            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Limpa todo o cache (para trocar de tenant)
     */
    async clearAll() {
        if (!this.db) return;

        const stores = ['produtos', 'clientes', 'config', 'outbox'];
        
        for (const storeName of stores) {
            await new Promise((resolve, reject) => {
                const tx = this.db.transaction([storeName], 'readwrite');
                const store = tx.objectStore(storeName);
                const request = store.clear();

                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        }

        console.log('[OfflineManager] Cache limpo completamente');
    }

    /**
     * Retorna estatísticas do cache
     */
    async getStats() {
        if (!this.db) throw new Error('IndexedDB não inicializado');

        const stats = {
            tenant: this.tenantKey,
            produtos: 0,
            clientes: 0,
            outbox_pending: 0
        };

        // Contar produtos
        stats.produtos = await new Promise((resolve) => {
            const tx = this.db.transaction(['produtos'], 'readonly');
            const store = tx.objectStore('produtos');
            const index = store.index('tenant');
            const request = index.count(this.tenantKey);
            request.onsuccess = () => resolve(request.result);
        });

        // Contar clientes
        stats.clientes = await new Promise((resolve) => {
            const tx = this.db.transaction(['clientes'], 'readonly');
            const store = tx.objectStore('clientes');
            const index = store.index('tenant');
            const request = index.count(this.tenantKey);
            request.onsuccess = () => resolve(request.result);
        });

        // Contar outbox pendente
        const outboxItems = await this.getPendingOutbox();
        stats.outbox_pending = outboxItems.length;

        return stats;
    }
}

// Exportar instância global (singleton por tenant)
window.offlineManager = new OfflineManager();


