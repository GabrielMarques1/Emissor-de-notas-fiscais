/**
 * Widget de Status Offline/Sincronização
 * Monitora conexão e exibe feedback visual para o usuário
 */

class OfflineStatusWidget {
    constructor() {
        this.isOnline = navigator.onLine;
        this.syncInProgress = false;
        this.pendingOps = 0;
        this.init();
    }

    init() {
        this.createWidget();
        this.attachEventListeners();
        this.startMonitoring();
    }

    createWidget() {
        const widget = document.createElement('div');
        widget.id = 'offline-status-widget';
        widget.className = 'offline-status-widget';
        widget.innerHTML = `
            <div class="status-indicator" id="status-indicator">
                <span class="status-icon"></span>
                <span class="status-text">Verificando conexão...</span>
            </div>
            <div class="sync-details" id="sync-details" style="display: none;">
                <div class="pending-count">
                    <strong id="pending-ops-count">0</strong> operações pendentes
                </div>
                <button id="sync-now-btn" class="btn-sync">Sincronizar Agora</button>
                <div class="last-sync" id="last-sync-time"></div>
            </div>
        `;
        document.body.appendChild(widget);
        
        // Adicionar estilos
        this.injectStyles();
    }

    injectStyles() {
        if (document.getElementById('offline-status-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'offline-status-styles';
        styles.textContent = `
            .offline-status-widget {
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 9999;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                padding: 10px 15px;
                min-width: 250px;
                transition: all 0.3s ease;
            }
            
            .status-indicator {
                display: flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
            }
            
            .status-icon {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                display: inline-block;
                animation: pulse 2s infinite;
            }
            
            .status-online .status-icon {
                background: #10b981;
                animation: none;
            }
            
            .status-offline .status-icon {
                background: #ef4444;
            }
            
            .status-syncing .status-icon {
                background: #f59e0b;
            }
            
            .status-text {
                font-size: 14px;
                font-weight: 500;
                color: #374151;
            }
            
            .sync-details {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid #e5e7eb;
            }
            
            .pending-count {
                margin-bottom: 10px;
                font-size: 13px;
                color: #6b7280;
            }
            
            .pending-count strong {
                color: #ef4444;
                font-size: 18px;
            }
            
            .btn-sync {
                width: 100%;
                padding: 8px;
                background: #3b82f6;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 500;
                transition: background 0.2s;
            }
            
            .btn-sync:hover {
                background: #2563eb;
            }
            
            .btn-sync:disabled {
                background: #9ca3af;
                cursor: not-allowed;
            }
            
            .last-sync {
                margin-top: 8px;
                font-size: 11px;
                color: #9ca3af;
                text-align: center;
            }
            
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
            
            .offline-banner {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #fef3c7;
                color: #92400e;
                padding: 12px;
                text-align: center;
                font-weight: 500;
                z-index: 9998;
                border-bottom: 2px solid #f59e0b;
            }
        `;
        document.head.appendChild(styles);
    }

    attachEventListeners() {
        // Eventos de conexão do navegador
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Click no indicador para expandir/recolher
        document.getElementById('status-indicator').addEventListener('click', () => {
            this.toggleDetails();
        });
        
        // Botão de sincronizar
        document.getElementById('sync-now-btn').addEventListener('click', () => {
            this.syncNow();
        });
    }

    startMonitoring() {
        // Verificar status inicial
        this.checkConnection();
        
        // Verificar a cada 30 segundos
        setInterval(() => {
            this.checkConnection();
            this.updateSyncStats();
        }, 30000);
        
        // Atualizar stats a cada 5 segundos
        setInterval(() => {
            this.updateSyncStats();
        }, 5000);
    }

    async checkConnection() {
        try {
            const response = await fetch('/api/health-check', {
                method: 'GET',
                cache: 'no-cache'
            });
            
            if (response.ok) {
                this.handleOnline();
            } else {
                this.handleOffline();
            }
        } catch (error) {
            this.handleOffline();
        }
    }

    handleOnline() {
        if (!this.isOnline) {
            console.log('[OfflineStatus] Conexão restaurada');
            this.isOnline = true;
            this.updateUI();
            this.removeBanner();
            
            // Iniciar sincronização automática
            setTimeout(() => this.syncNow(), 1000);
        }
    }

    handleOffline() {
        if (this.isOnline) {
            console.log('[OfflineStatus] Conexão perdida');
            this.isOnline = false;
            this.updateUI();
            this.showBanner();
        }
    }

    updateUI() {
        const indicator = document.getElementById('status-indicator');
        const statusText = indicator.querySelector('.status-text');
        
        indicator.className = 'status-indicator';
        
        if (this.syncInProgress) {
            indicator.classList.add('status-syncing');
            statusText.textContent = 'Sincronizando...';
        } else if (this.isOnline) {
            indicator.classList.add('status-online');
            statusText.textContent = 'Online';
        } else {
            indicator.classList.add('status-offline');
            statusText.textContent = 'Modo Offline';
        }
    }

    toggleDetails() {
        const details = document.getElementById('sync-details');
        if (details.style.display === 'none') {
            details.style.display = 'block';
            this.updateSyncStats();
        } else {
            details.style.display = 'none';
        }
    }

    async updateSyncStats() {
        try {
            const response = await fetch('/api/sync/stats');
            if (response.ok) {
                const data = await response.json();
                this.pendingOps = data.pending || 0;
                
                document.getElementById('pending-ops-count').textContent = this.pendingOps;
                
                if (data.last_sync) {
                    const lastSync = new Date(data.last_sync);
                    const diff = Math.floor((Date.now() - lastSync.getTime()) / 1000 / 60);
                    document.getElementById('last-sync-time').textContent = 
                        `Última sinc: há ${diff} min`;
                }
            }
        } catch (error) {
            console.error('[OfflineStatus] Erro ao buscar stats:', error);
        }
    }

    async syncNow() {
        if (this.syncInProgress || !this.isOnline) return;
        
        this.syncInProgress = true;
        this.updateUI();
        
        const btn = document.getElementById('sync-now-btn');
        btn.disabled = true;
        btn.textContent = 'Sincronizando...';
        
        try {
            const response = await fetch('/api/sync/execute', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (response.ok) {
                const result = await response.json();
                console.log('[OfflineStatus] Sincronização concluída:', result);
                this.showNotification('Sincronização concluída com sucesso!', 'success');
                this.updateSyncStats();
            } else {
                throw new Error('Erro na sincronização');
            }
        } catch (error) {
            console.error('[OfflineStatus] Erro na sincronização:', error);
            this.showNotification('Erro ao sincronizar. Tentando novamente...', 'error');
        } finally {
            this.syncInProgress = false;
            this.updateUI();
            btn.disabled = false;
            btn.textContent = 'Sincronizar Agora';
        }
    }

    showBanner() {
        if (document.getElementById('offline-banner')) return;
        
        const banner = document.createElement('div');
        banner.id = 'offline-banner';
        banner.className = 'offline-banner';
        banner.innerHTML = `
            <strong>⚠️ Modo Offline:</strong> 
            Sem conexão com a nuvem. Suas operações serão sincronizadas quando a conexão for restaurada.
        `;
        document.body.insertBefore(banner, document.body.firstChild);
    }

    removeBanner() {
        const banner = document.getElementById('offline-banner');
        if (banner) {
            banner.remove();
        }
    }

    showNotification(message, type = 'info') {
        // Integração com sistema de notificação existente
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            console.log(`[Notification ${type}]`, message);
        }
    }
}

// Inicializar widget quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.offlineStatus = new OfflineStatusWidget();
    });
} else {
    window.offlineStatus = new OfflineStatusWidget();
}
