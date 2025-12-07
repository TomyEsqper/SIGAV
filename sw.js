const CACHE_NAME = 'sigav-inspector-v1.0.0';
const STATIC_CACHE = 'sigav-static-v1.0.0';
const DYNAMIC_CACHE = 'sigav-dynamic-v1.0.0';

// Archivos estáticos para cachear
const STATIC_FILES = [
  '/inspector/',
  '/inspector/index.php',
  '/inspector/extintor_fecha.php',
  '/inspector/camaras/',
  '/inspector/camaras/index.php',
  '/inspector/offline.html',
  '/assets/css/admin.css',
  '/inspector/qr-scanner.umd.min.js',
  '/inspector/qr-scanner-worker.min.js',
  '/manifest.json',
  // CSS y JS externos
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

// Archivos dinámicos que se cachean bajo demanda
const DYNAMIC_FILES = [
  '/inspector/alistamiento.php',
  '/inspector/camaras/iniciar.php',
  '/inspector/buscar_vehiculo_id.php',
  '/inspector/camaras/api/crear_inspeccion.php',
  '/inspector/camaras/api/guardar_checklist.php',
  '/config/database.php',
  '/admin/'
];

// Instalación del Service Worker
self.addEventListener('install', event => {
  console.log('Service Worker: Instalando...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('Service Worker: Cacheando archivos estáticos');
        return cache.addAll(STATIC_FILES);
      })
      .catch(err => {
        console.error('Error al cachear archivos estáticos:', err);
      })
  );
  
  // Forzar activación inmediata
  self.skipWaiting();
});

// Activación del Service Worker
self.addEventListener('activate', event => {
  console.log('Service Worker: Activando...');
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          // Eliminar caches antiguos
          if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
            console.log('Service Worker: Eliminando cache antiguo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  
  // Tomar control inmediato de todas las páginas
  self.clients.claim();
});

// Interceptar peticiones de red
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Interceptar navegaciones de documentos solo para Cámaras
  if (request.mode === 'navigate' || request.destination === 'document') {
    if (url.pathname.startsWith('/inspector/camaras')) {
      event.respondWith(networkFirst(request));
      return; // manejar Cámaras con Network First + fallback offline
    }
    return; // permitir que el navegador maneje otras navegaciones normalmente
  }

  // Solo manejar peticiones HTTP/HTTPS
  if (!request.url.startsWith('http')) {
    return;
  }

  // Estrategia Cache First para archivos estáticos
  if (isStaticFile(request.url)) {
    event.respondWith(cacheFirst(request));
  }
  // Estrategia Network First para contenido dinámico (excluir .php genérico)
  else if (isDynamicFile(request.url)) {
    event.respondWith(networkFirst(request));
  }
  // Estrategia Stale While Revalidate para otros recursos
  else {
    event.respondWith(staleWhileRevalidate(request));
  }
});

// Verificar si es un archivo estático
function isStaticFile(url) {
  return STATIC_FILES.some(file => url.includes(file)) ||
         url.includes('.css') ||
         url.includes('.js') ||
         url.includes('.png') ||
         url.includes('.jpg') ||
         url.includes('.svg') ||
         url.includes('.ico');
}

// Verificar si es un archivo dinámico
function isDynamicFile(url) {
  // Solo tratar como dinámicos los paths definidos explícitamente
  return DYNAMIC_FILES.some(file => url.includes(file));
}

// Estrategia Cache First
async function cacheFirst(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    const networkResponse = await fetch(request);
    
    // Cachear la respuesta si es exitosa
    if (networkResponse.status === 200) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('Cache First falló:', error);
    
    // Retornar página offline si está disponible
    if (request.destination === 'document') {
      return caches.match('/inspector/offline.html') || 
             new Response('Sin conexión', { status: 503 });
    }
    
    return new Response('Recurso no disponible', { status: 503 });
  }
}

// Estrategia Network First
async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request);
    
    // Cachear respuestas exitosas
    if (networkResponse.status === 200) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('Network First falló:', error);
    
    // Intentar obtener de cache
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Página offline para documentos
    if (request.destination === 'document') {
      return caches.match('/inspector/offline.html') ||
             new Response('Sin conexión - Contenido no disponible', { status: 503 });
    }
    
    return new Response('Recurso no disponible offline', { status: 503 });
  }
}

// Estrategia Stale While Revalidate
async function staleWhileRevalidate(request) {
  const cache = await caches.open(DYNAMIC_CACHE);
  const cachedResponse = await cache.match(request);
  
  // Actualizar cache en segundo plano
  const fetchPromise = fetch(request).then(networkResponse => {
    if (networkResponse.status === 200) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  }).catch(error => {
    console.error('Stale While Revalidate falló:', error);
    return cachedResponse;
  });
  
  // Retornar cache inmediatamente si está disponible
  return cachedResponse || fetchPromise;
}

// Manejo de mensajes desde la aplicación
self.addEventListener('message', event => {
  const { type, payload } = event.data;
  
  switch (type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
      
    case 'CACHE_URLS':
      if (payload && payload.urls) {
        cacheUrls(payload.urls);
      }
      break;
      
    case 'CLEAR_CACHE':
      clearAllCaches();
      break;
      
    case 'GET_CACHE_SIZE':
      getCacheSize().then(size => {
        event.ports[0].postMessage({ type: 'CACHE_SIZE', size });
      });
      break;
  }
});

// Cachear URLs específicas
async function cacheUrls(urls) {
  try {
    const cache = await caches.open(DYNAMIC_CACHE);
    await cache.addAll(urls);
    console.log('URLs cacheadas exitosamente:', urls);
  } catch (error) {
    console.error('Error al cachear URLs:', error);
  }
}

// Limpiar todos los caches
async function clearAllCaches() {
  try {
    const cacheNames = await caches.keys();
    await Promise.all(cacheNames.map(name => caches.delete(name)));
    console.log('Todos los caches eliminados');
  } catch (error) {
    console.error('Error al limpiar caches:', error);
  }
}

// Obtener tamaño del cache
async function getCacheSize() {
  try {
    const cacheNames = await caches.keys();
    let totalSize = 0;
    
    for (const name of cacheNames) {
      const cache = await caches.open(name);
      const keys = await cache.keys();
      
      for (const request of keys) {
        const response = await cache.match(request);
        if (response) {
          const blob = await response.blob();
          totalSize += blob.size;
        }
      }
    }
    
    return totalSize;
  } catch (error) {
    console.error('Error al calcular tamaño del cache:', error);
    return 0;
  }
}

// Sincronización en segundo plano
self.addEventListener('sync', event => {
  console.log('Background Sync:', event.tag);
  
  switch (event.tag) {
    case 'sync-alistamientos':
      event.waitUntil(syncAlistamientos());
      break;
      
    case 'sync-photos':
      event.waitUntil(syncPhotos());
      break;
  }
});

// Sincronizar alistamientos pendientes
async function syncAlistamientos() {
  try {
    // Aquí iría la lógica para sincronizar alistamientos offline
    console.log('Sincronizando alistamientos...');
    
    // Obtener datos pendientes del IndexedDB
    // Enviar al servidor
    // Marcar como sincronizado
    
  } catch (error) {
    console.error('Error en sincronización de alistamientos:', error);
  }
}

// Sincronizar fotos pendientes
async function syncPhotos() {
  try {
    console.log('Sincronizando fotos...');
    
    // Lógica similar para fotos
    
  } catch (error) {
    console.error('Error en sincronización de fotos:', error);
  }
}

// Notificaciones push
self.addEventListener('push', event => {
  if (!event.data) return;
  
  const data = event.data.json();
  const options = {
    body: data.body || 'Nueva notificación de SIGAV',
    icon: '/assets/icons/icon-192x192.png',
    badge: '/assets/icons/badge-72x72.png',
    vibrate: [100, 50, 100],
    data: data.data || {},
    actions: [
      {
        action: 'open',
        title: 'Abrir',
        icon: '/assets/icons/open-icon.png'
      },
      {
        action: 'close',
        title: 'Cerrar',
        icon: '/assets/icons/close-icon.png'
      }
    ],
    requireInteraction: true,
    tag: data.tag || 'sigav-notification'
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'SIGAV Inspector', options)
  );
});

// Manejo de clics en notificaciones
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  const { action, data } = event;
  
  if (action === 'close') {
    return;
  }
  
  // Abrir la aplicación
  event.waitUntil(
    clients.matchAll({ type: 'window' }).then(clientList => {
      // Si ya hay una ventana abierta, enfocarla
      for (const client of clientList) {
        if (client.url.includes('/inspector/') && 'focus' in client) {
          return client.focus();
        }
      }
      
      // Si no hay ventana abierta, abrir una nueva
      if (clients.openWindow) {
        const url = data?.url || '/inspector/';
        return clients.openWindow(url);
      }
    })
  );
});

console.log('Service Worker: Cargado exitosamente');