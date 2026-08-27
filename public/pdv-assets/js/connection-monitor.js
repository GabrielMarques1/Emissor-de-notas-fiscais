/**
 * 🔥 CONNECTION MONITOR - PDV MULTI-TENANT
 * 
 * Monitora status de conexão e sincroniza dados
 */

class ConnectionMonitor {
    constructor() {
        this.isOnline = navigator.onLine;
        this.pingInterval = null;
        this.syncInterval = null;
        this.pingUrl = '/api/ping';
        this.callbacks = {
            online: [],
            offline: []
        };
        
        this.init();
    }

    /**
     * Inicializa o monitor
     */
    init() {
        // Listeners nativos do navegador
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());

        // Ping periódico (a cada 10 segundos)
        this.startPing();

        // Sincronização automática (a cada 30 segundos se online)
        this.startAutoSync();

        console.log('[ConnectionMonitor] Iniciado - Status:', this.isOnline ? 'ONLINE' : 'OFFLINE');
    }

    /**
     * Inicia ping periódico ao servidor
     */
    startPing() {
        this.pingInterval = setInterval(() => {
            this.checkConnection();
        }, 10000); // 10 segundos
    }

    /**
     * Verifica conexão com o servidor
     */
    async checkConnection() {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000); // 5s timeout

            const response = await fetch(this.pingUrl, {
                method: 'GET',
                cache: 'no-cache',
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (response.ok) {
                if (!this.isOnline) {
                    this.handleOnline();
                }
            } else {
                if (this.isOnline) {
                    this.handleOffline();
                }
            }
        } catch (error) {
            // Timeout ou erro de rede
            if (this.isOnline) {
                this.handleOffline();
            }
        }
    }

    /**
     * Handler quando fica online
     */
    handleOnline() {
        console.log('[ConnectionMonitor] STATUS: ONLINE');
        this.isOnline = true;

        // Atualizar UI
        this.updateUI(true);

        // Executar callbacks
        this.callbacks.online.forEach(cb => cb());

        // Sincronizar dados pendentes
        this.syncPendingData();
    }

    /**
     * Handler quando fica offline
     */
    handleOffline() {
        console.log('[ConnectionMonitor] STATUS: OFFLINE');
        this.isOnline = false;

        // Atualizar UI
        this.updateUI(false);

        // Executar callbacks
        this.callbacks.offline.forEach(cb => cb());
    }

    /**
     * Atualiza UI com badge de status
     */
    updateUI(online) {
        // Remover badge anterior se existir
        const existingBadge = document.getElementById('connection-status-badge');
        if (existingBadge) {
            existingBadge.remove();
        }

        if (!online) {
            // Criar badge de modo offline
            const badge = document.createElement('div');
            badge.id = 'connection-status-badge';
            badge.className = 'alert alert-warning mb-0 text-center';
            badge.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; z-index: 9999; border-radius: 0;';
            badge.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <strong>MODO OFFLINE</strong> - 
                Você está sem conexão com a internet. 
                Vendas serão sincronizadas automaticamente quando voltar online.
                <span id="outbox-counter" class="badge badge-danger ml-2">0 pendentes</span>
            `;

            document.body.insertBefore(badge, document.body.firstChild);

            // Atualizar contador de pendências
            this.updateOutboxCounter();
        } else {
            // Mostrar toast de reconexão
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Conexão Restaurada',
                    text: 'Sincronizando dados pendentes...',
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        }
    }

    /**
     * Atualiza contador de operações pendentes no outbox
     */
    async updateOutboxCounter() {
        if (!window.offlineManager || !window.offlineManager.initialized) {
            return;
        }

        try {
            const pendingItems = await window.offlineManager.getPendingOutbox();
            const counter = document.getElementById('outbox-counter');
            
            if (counter) {
                counter.textContent = `${pendingItems.length} pendentes`;
                
                if (pendingItems.length > 0) {
                    counter.classList.remove('badge-secondary');
                    counter.classList.add('badge-danger');
                } else {
                    counter.classList.remove('badge-danger');
                    counter.classList.add('badge-secondary');
                }
            }
        } catch (error) {
            console.error('[ConnectionMonitor] Erro ao atualizar contador:', error);
        }
    }

    /**
     * Sincroniza dados pendentes com o servidor
     */
    async syncPendingData() {
        if (!this.isOnline) {
            console.log('[ConnectionMonitor] Não é possível sincronizar - OFFLINE');
            return;
        }

        if (!window.offlineManager || !window.offlineManager.initialized) {
            console.log('[ConnectionMonitor] OfflineManager não inicializado');
            return;
        }

        try {
            console.log('[ConnectionMonitor] Iniciando sincronização...');

            const pendingItems = await window.offlineManager.getPendingOutbox();

            if (pendingItems.length === 0) {
                console.log('[ConnectionMonitor] Nenhum item pendente para sincronizar');
                return;
            }

            console.log(`[ConnectionMonitor] ${pendingItems.length} itens para sincronizar`);

            let synced = 0;
            let failed = 0;

            for (const item of pendingItems) {
                try {
                    // Enviar operação para o servidor
                    const response = await fetch('/api/sync/outbox', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(item.data)
                    });

                    if (response.ok) {
                        // Marcar como concluído
                        await window.offlineManager.markOutboxComplete(item.id);
                        synced++;
                    } else {
                        // Incrementar contador de retry
                        await window.offlineManager.incrementRetry(item.id);
                        failed++;
                        
                        // Se falhou mais de 5 vezes, logar erro
                        if (item.retry_count >= 5) {
                            console.error('[ConnectionMonitor] Item falhou 5+ vezes:', item);
                        }
                    }
                } catch (error) {
                    console.error('[ConnectionMonitor] Erro ao sincronizar item:', error);
                    await window.offlineManager.incrementRetry(item.id);
                    failed++;
                }

                // Pequeno delay entre requests
                await new Promise(resolve => setTimeout(resolve, 100));
            }

            console.log(`[ConnectionMonitor] Sincronização completa - Sucesso: ${synced}, Falhas: ${failed}`);

            // Atualizar contador
            this.updateOutboxCounter();

            // Notificar usuário
            if (synced > 0 && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Sincronização Completa',
                    text: `${synced} operação(ões) sincronizada(s) com sucesso!`,
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }

        } catch (error) {
            console.error('[ConnectionMonitor] Erro na sincronização:', error);
        }
    }

    /**
     * Inicia sincronização automática periódica
     */
    startAutoSync() {
        this.syncInterval = setInterval(() => {
            if (this.isOnline) {
                this.syncPendingData();
            }
        }, 30000); // 30 segundos
    }

    /**
     * Registra callback para evento de conexão
     */
    on(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event].push(callback);
        }
    }

    /**
     * Remove callback
     */
    off(event, callback) {
        if (this.callbacks[event]) {
            const index = this.callbacks[event].indexOf(callback);
            if (index > -1) {
                this.callbacks[event].splice(index, 1);
            }
        }
    }

    /**
     * Para o monitor (cleanup)
     */
    destroy() {
        if (this.pingInterval) {
            clearInterval(this.pingInterval);
        }
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }

        window.removeEventListener('online', this.handleOnline);
        window.removeEventListener('offline', this.handleOffline);
    }
}

// Exportar instância global
window.connectionMonitor = new ConnectionMonitor();

