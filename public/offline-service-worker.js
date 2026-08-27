/**
 * 🔥 SERVICE WORKER - PDV MULTI-TENANT OFFLINE
 * 
 * Funcionalidades:
 * - Cache de assets estáticos (CSS, JS, imagens)
 * - Estratégia: Cache-first com fallback para network
 * - Versionamento automático
 * - Isolamento por tenant
 */

const CACHE_VERSION = 'pdv-v1.0.0';
const CACHE_NAME = `pdv-cache-${CACHE_VERSION}`;

// Assets que SEMPRE devem ser cacheados
const STATIC_ASSETS = [
    '/theme/dist/css/adminlte.min.css',
    '/theme/plugins/fontawesome-free/css/all.min.css',
    '/theme/dist/js/adminlte.min.js',
    '/theme/plugins/jquery/jquery.min.js',
    '/theme/plugins/bootstrap/js/bootstrap.bundle.min.js',
    '/pdv-assets/js/pdv.js',
    '/pdv-assets/js/offline-manager.js',
    '/pdv-assets/js/connection-monitor.js',
    '/assets/img/logo.png',
    '/favicon.ico'
];

// Rotas da API que devem ser cacheadas (por tenant)
const CACHEABLE_API_ROUTES = [
    '/api/products',
    '/api/customers',
    '/api/pos/config'
];

/**
 * Evento: Instalação do Service Worker
 */
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Instalando...', CACHE_VERSION);
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Service Worker] Cacheando assets estáticos');
                return cache.addAll(STATIC_ASSETS.map(url => {
                    // Tentar cachear, mas não falhar se algum arquivo não existir
                    return fetch(url)
                        .then(response => {
                            if (response.ok) {
                                return cache.put(url, response);
                            }
                        })
                        .catch(() => {
                            console.warn('[Service Worker] Não foi possível cachear:', url);
                        });
                }));
            })
            .then(() => {
                console.log('[Service Worker] Instalação completa');
                return self.skipWaiting(); // Ativar imediatamente
            })
    );
});

/**
 * Evento: Ativação do Service Worker
 */
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Ativando...', CACHE_VERSION);
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                // Remover caches antigos
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME && cacheName.startsWith('pdv-cache-')) {
                            console.log('[Service Worker] Removendo cache antigo:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[Service Worker] Ativação completa');
                return self.clients.claim(); // Controlar páginas imediatamente
            })
    );
});

/**
 * Evento: Interceptação de requests
 */
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Ignorar requests de outros domínios
    if (url.origin !== location.origin) {
        return;
    }
    
    // Ignorar requests POST/PUT/DELETE (operações de escrita)
    if (request.method !== 'GET') {
        return;
    }
    
    // Estratégia: Cache-first para assets estáticos
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }
    
    // Estratégia: Network-first com cache fallback para API
    if (isApiRoute(url.pathname)) {
        event.respondWith(networkFirstWithCache(request));
        return;
    }
    
    // Outras requests: Network-only
    event.respondWith(fetch(request));
});

/**
 * Estratégia: Cache-first
 * Tenta buscar no cache primeiro, se não encontrar vai para network
 */
async function cacheFirst(request) {
    try {
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            console.log('[Service Worker] Cache HIT:', request.url);
            return cachedResponse;
        }
        
        console.log('[Service Worker] Cache MISS, buscando na network:', request.url);
        const networkResponse = await fetch(request);
        
        // Cachear apenas respostas bem-sucedidas
        if (networkResponse && networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.error('[Service Worker] Erro ao buscar recurso:', error);
        
        // Se estiver offline, tentar buscar no cache mesmo que tenha expirado
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Retornar página de erro offline
        return new Response('Recurso não disponível offline', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
                'Content-Type': 'text/plain'
            })
        });
    }
}

/**
 * Estratégia: Network-first com cache fallback
 * Tenta buscar na network primeiro, se falhar usa cache
 */
async function networkFirstWithCache(request) {
    try {
        const networkResponse = await fetch(request, {
            // Timeout de 5 segundos
            signal: AbortSignal.timeout(5000)
        });
        
        if (networkResponse && networkResponse.ok) {
            console.log('[Service Worker] Network HIT:', request.url);
            
            // Cachear resposta (com tenant no cache key)
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            
            return networkResponse;
        }
        
        throw new Error('Resposta não OK da network');
        
    } catch (error) {
        console.log('[Service Worker] Network FAIL, buscando no cache:', request.url);
        
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            // Adicionar header indicando que veio do cache
            const headers = new Headers(cachedResponse.headers);
            headers.append('X-From-Cache', 'true');
            
            return new Response(cachedResponse.body, {
                status: cachedResponse.status,
                statusText: cachedResponse.statusText,
                headers: headers
            });
        }
        
        // Sem cache disponível
        return new Response(JSON.stringify({
            error: 'Recurso não disponível offline',
            message: 'Conecte-se à internet para acessar este recurso'
        }), {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
                'Content-Type': 'application/json'
            })
        });
    }
}

/**
 * Verifica se é um asset estático
 */
function isStaticAsset(pathname) {
    return pathname.match(/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|eot|ico)$/i) ||
           pathname.includes('/theme/') ||
           pathname.includes('/assets/') ||
           pathname.includes('/pdv-assets/');
}

/**
 * Verifica se é uma rota da API cacheável
 */
function isApiRoute(pathname) {
    return CACHEABLE_API_ROUTES.some(route => pathname.startsWith(route));
}

/**
 * Mensagens do Service Worker
 */
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.delete(CACHE_NAME).then(() => {
                console.log('[Service Worker] Cache limpo');
                event.ports[0].postMessage({ success: true });
            })
        );
    }
    
    if (event.data && event.data.type === 'CACHE_SIZE') {
        event.waitUntil(
            caches.open(CACHE_NAME).then(cache => {
                return cache.keys().then(keys => {
                    event.ports[0].postMessage({ 
                        size: keys.length,
                        version: CACHE_VERSION 
                    });
                });
            })
        );
    }
});

console.log('[Service Worker] Carregado - Versão:', CACHE_VERSION);


