---
paths:
  - 'resources/js/**'
---

# Js

## JavaScript bajo demanda
Gestiona JavaScript mediante Vite y agrega librerías sólo cuando una funcionalidad real las necesite. Prefiere módulos diferidos para componentes que no forman parte de la carga inicial.

## Fechas y notificaciones

Usa Air Datepicker para todos los campos de fecha del proyecto y marca sus elementos con `data-datepicker`; conserva la configuración regional en español y el formato `dd/MM/yyyy`. Usa SweetAlert2 para alertas, confirmaciones y mensajes tipo toast; utiliza `window.Toast` para toasts y `window.Swal` para diálogos completos. No introduzcas otra librería para estos casos.
