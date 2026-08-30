---
paths:
  - 'app/**'
  - 'routes/**'
  - 'config/**'
  - 'database/**'
---

# Paquetes backend oficiales del proyecto

## Imágenes

Usa Intervention Image para cargar, transformar y exportar imágenes. Valida tipo, tamaño y dimensiones antes de procesar archivos y usa almacenamiento configurado mediante `Storage`.

## Roles y permisos

Usa Spatie Laravel Permission para roles y permisos. Autoriza con permisos o políticas en los límites de la aplicación; no implementes comprobaciones manuales de nombres de rol dispersas.

## Tokens

Usa Laravel Sanctum para tokens personales y autenticación de API. No agregues Passport ni implementes tokens propios salvo una decisión explícita del equipo.

## AWS

Usa el AWS SDK oficial para integraciones AWS y la abstracción `Storage` de Laravel para archivos S3. Las credenciales deben provenir de variables de entorno/configuración y nunca de código fuente.

## Rutas delegan en controllers
Toda ruta debe apuntar a un método de un controller. No usar closures ni lógica de aplicación directamente en los archivos de rutas.

## Organización de rutas por audiencia y versión
Organiza las rutas web en archivos públicos y privados, y las APIs bajo api/v1 separadas en public, consumers, internal y webhooks. Versiona las APIs y carga los subarchivos desde los archivos principales routes/web.php y routes/api.php.
