---
paths:
  - 'resources/views/**'
---

# Views

## Idioma

El proyecto se desarrolla en español. Todo texto visible agregado a una vista debe usar `__()` y contar con su traducción en los archivos de idioma correspondientes; antes de finalizar una vista nueva, valida que no queden textos visibles sin traducir.

## Componentes visuales reutilizables
Construye los patrones visuales repetidos como componentes Blade. Usa Livewire cuando exista estado o interacción dependiente del servidor, y Alpine para interacciones locales de la interfaz.

## Fechas y notificaciones

Los campos de fecha deben usar `data-datepicker` para activar Air Datepicker. Las alertas y notificaciones visibles deben usar SweetAlert2 mediante `Swal` o `Toast`; no se deben crear mensajes paralelos con otra librería.

## Blades solo de presentación
Las vistas Blade no deben contener código PHP ni lógica de aplicación. Reciben datos preparados por controllers/services y se limitan a presentar la información.
