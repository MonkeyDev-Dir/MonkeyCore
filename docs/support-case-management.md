# Guía de trabajo: Mesa de trabajo

## Propósito y alcance

Guía de trabajo funcional y técnica para organizar la construcción incremental del módulo interno “Mesa de trabajo”. El módulo permitirá registrar y gestionar soporte, integraciones, planificación y otros trabajos de producto o desarrollo; no se limitará a resolver incidentes. Orienta las actividades, revisiones y entregas; no autoriza por sí sola la implementación. Cada fase debe ser revisable, funcional, comprobable y desplegable por sí misma.

## Visión del producto

La Mesa de trabajo busca combinar la profundidad y trazabilidad de Jira con la velocidad, claridad y facilidad de uso de Trello. Será una herramienta interna para desarrolladores y creadores de producto que permita gestionar soporte, integraciones, planificación y evolución del producto sin convertir cada actualización en un proceso pesado.

Principios de experiencia que deben guiar todas las fases:

- Cambiar rápidamente la información principal de un caso desde la bandeja o el detalle, con la menor cantidad posible de pasos.
- Agregar comentarios y actualizaciones de forma inmediata, conservando autor, fecha, visibilidad e historial.
- Facilitar la incorporación de fragmentos de código en las actualizaciones, manteniendo formato legible y opción de copiar.
- Agregar imágenes de manera simple mediante selección, arrastre o pegado cuando sea posible, con vista previa y asociación clara al caso o actualización.
- Usar etiquetas, colores, estados y responsables para comprender el trabajo de un vistazo.
- Mantener la trazabilidad y los permisos sin sacrificar una experiencia rápida y amigable.
- Preferir interacciones directas, edición contextual y respuestas ágiles para las operaciones frecuentes.

La profundidad funcional se incorporará progresivamente, pero cada nueva capacidad debe conservar esta sensación de rapidez y sencillez. Las APIs, integraciones externas y automatizaciones quedan fuera de la fase 1 y no deben condicionar la experiencia interna inicial.

## Cómo trabajar en este módulo

- Usar nombres en inglés para código, tablas, clases, rutas, permisos, eventos y enums; la interfaz puede estar en español y sus textos visibles deben usar `__()`.
- Mantener controladores delgados, validación con Form Requests, lógica y consultas en Services y Actions para operaciones de negocio bien delimitadas.
- Ubicar futuras APIs bajo `Api/V1`, separadas por audiencia (`Public`, `Consumers`, `Internal`, `Webhooks`) y con rutas delegadas a controllers.
- Aplicar Policies/Gates y permisos de Spatie Laravel Permission; los filtros de interfaz nunca sustituyen la autorización.
- La interfaz objetivo es Blade + Livewire + Alpine.js + Bootstrap. No introducir Tailwind/TallStackUI en este módulo ni cambiar el sistema visual global.
- No añadir dependencias ni cambiar el sistema visual global sin aprobación. Mantener la lógica fuera de Livewire, controllers y vistas; seguir Pest y ejecutar Pint sobre cambios PHP.

## Diseño de trabajo del dominio

El nombre visible del módulo será “Mesa de trabajo” y el nombre técnico de la entidad será `WorkItem`. La entidad representará un elemento de trabajo de la Mesa de trabajo. Debe contener:

- Identificador interno no predecible mediante UUID y un código público legible, estable y no enumerable con formato `MKY-{aa}{secuencia de 6 dígitos}`. La secuencia inicia en `000001`, se reinicia cada año y debe ser única dentro del año.
- Cliente creado en el sistema y proyecto vinculado al cliente son obligatorios para los casos de tipo `Support`. Los demás tipos podrán representar trabajos internos sin cliente o proyecto, según su configuración.
- Solicitante interno de la plataforma. Los contactos externos quedan fuera de la fase 1.
- Uno o varios usuarios responsables. Solo pueden seleccionarse usuarios administrativos de la plataforma o usuarios que sean miembros del proyecto. El modelo no debe quedar limitado a `assigned_agent_id`; debe existir una relación de asignación múltiple. No se definirá un responsable principal; la coordinación del equipo ocurrirá fuera del sistema.
- Origen controlado: `Internal`, `Api`, `Portal`, `Email` y canales futuros.
- Tipo y categoría administrables en BD. La fase 1 los cargará mediante seeders; el mantenimiento quedará reservado para superadministradores en una fase posterior.
- Etiquetas visuales tipo Trello, con nombre, color y una referencia de duración estimada para facilitar la lectura rápida del tablero. Cada caso podrá tener múltiples etiquetas y los usuarios autorizados podrán agregar o quitar las etiquetas necesarias de forma sencilla. Su diseño y mantenimiento quedan fuera de la fase 1.
- Prioridad basada inicialmente en los valores enum `Low`, `Medium`, `High` y `Critical`. La matriz impacto/urgencia y reglas automáticas quedan fuera de la fase inicial.
- Título, descripción, estado y timestamps del ciclo de vida.
- Datos de resolución, causa raíz al cerrar y marcas de primera respuesta, resolución, cierre y reapertura.

Usar enums para los valores estables del dominio: estados, prioridades, visibilidad de entradas y origen. La prioridad debe basarse en impacto y urgencia, no en texto libre. Mantener como catálogos en BD los tipos, categorías y tags, porque pueden crecer o administrarse sin modificar código. Los tags serán exclusivamente informativos y no modificarán SLA, prioridad ni métricas.

### Estados y transiciones

Estados sugeridos: `New`, `Assigned`, `UnderAnalysis`, `WaitingForCustomer`, `WaitingForThirdParty`, `InDevelopment`, `InTesting`, `Resolved`, `Closed` y `Cancelled`.

Las transiciones deben centralizarse en una Action/Service o máquina de estados reutilizable:

- Toda creación inicia en `New` (“Nuevo”). Si se asignan responsables durante la creación, se registra adicionalmente la asignación; no se cambia automáticamente el estado inicial.
- Asignar/reasignar requiere autorización y conserva agente anterior y nuevo.
- `Assigned` puede pasar a `UnderAnalysis`; desde ahí a desarrollo, pruebas o espera según el trabajo.
- Los estados de espera exigen razón y aplican la pausa de SLA configurada.
- Resolver exige resumen y, si la política lo requiere, causa raíz; registra el tiempo de resolución y puede emitir respuesta pública.
- Cerrar solo procede desde `Resolved`, mediante acción autorizada, con razón si es administrativo; debe ser idempotente.
- Reabrir `Resolved` o `Closed` requiere razón, permiso y una ventana/política definida; el SLA se reinicia o recalcula según negocio.
- Cancelar exige motivo y no equivale a una resolución exitosa.

Una transición inválida debe producir un error de dominio claro y ningún cambio parcial. Persistir consistentemente y emitir eventos después de guardar correctamente.

### Entradas, adjuntos, tiempo y auditoría

Una `CaseEntry` es una actualización inmutable con autor, fecha, contenido, visibilidad (`Internal` o `Public`) y, si aplica, la transición asociada. Toda actualización será interna por defecto; un usuario autorizado podrá marcarla como pública para compartirla posteriormente con el cliente. Solo las actualizaciones `Public` estarán disponibles para el cliente cuando consulte el caso mediante la API futura. Cambiar la visibilidad deberá quedar auditado y nunca expondrá actualizaciones internas. Editar no debe sobrescribir historia: crear una entrada/evento de corrección con motivo.

Los adjuntos deben asociarse al caso o entrada, indicar audiencia/propietario y metadatos, validarse con allowlist de MIME/extensiones y tamaño, almacenarse mediante `Storage` privado y descargarse solo con autorización o URL temporal.

- Medir tiempo calendario desde creación hasta resolución/cierre, conservando ambos hitos si difieren.
- Medir tiempo efectivo con intervalos `WorkLog` de agentes (actor, inicio, fin y duración), no inferirlo de sesiones o estados.
- Mantener un `CaseEvent` append-only para creación, asignación, estado, prioridad, tiempos, resolución, cierre y reapertura; registrar actor, fecha, valores anterior/nuevo y metadatos.
- Nunca eliminar ni sobrescribir historial. Correcciones, anulaciones y reaperturas son nuevos eventos.
- Definir SLA de primera respuesta y resolución por prioridad, cliente o proyecto; documentar la precedencia entre reglas.
- Permitir pausar SLA en `WaitingForCustomer` y `WaitingForThirdParty` de forma configurable, indicando qué reloj se pausa, cuándo inicia, cuándo reanuda y cómo se contabiliza.

## Seguridad y API futura

El sistema es de uso interno para desarrolladores y creadores de producto. Durante la fase inicial los permisos del módulo permanecerán abiertos para los usuarios internos de la plataforma. La definición detallada de Policies, restricciones por rol y acciones exclusivas de superadministradores se trasladará a una fase posterior.

En cada API validar la pertenencia del cliente en la consulta autorizada: un cliente solo puede acceder a sus casos, proyectos, entradas públicas y adjuntos autorizados. No confiar en IDs ni filtros del frontend. Usar el identificador público externamente y no exponer el interno sin necesidad. Usar Sanctum, `Api/V1`, Form Requests, Resources, paginación, rate limiting, límites de carga y documentación de contratos/errores con Scramble si sigue siendo el mecanismo del proyecto. Evitar filtrar la existencia de recursos ajenos y no registrar secretos.

## Operación, notificaciones y métricas

Notificar asignación/reasignación, respuesta pública, cercanía y vencimiento de SLA, con preferencias, enlaces autorizados y deduplicación.

La búsqueda debe filtrar por cliente, proyecto, agente, estado, prioridad, etiqueta, fechas y condición de SLA; paginar y evitar N+1. Métricas mínimas: casos sin asignar, vencidos, próximos a vencer, tiempo medio de primera respuesta, tiempo medio de resolución, reabiertos y carga por agente, cliente y proyecto. Definir zona horaria, calendario laboral, pausas y tratamiento de cancelados antes de comparar indicadores.

## Plan de trabajo por fases

### 1. Núcleo interno

**Alcance:** creación interna; cliente/proyecto según tipo; identificador UUID y código público; uno o varios responsables; tipos y categorías desde seeders; prioridades enum; estados; historial; bandeja interna. La autorización detallada queda para una fase posterior.

**Aceptación:** usuario autorizado crea un caso válido; cliente es obligatorio; proyecto de otro cliente se rechaza; identificador público es único y no es la clave primaria; asignación/transiciones respetan reglas y generan historial; usuarios no autorizados no operan.

**Riesgos:** vocabulario y matriz de prioridad no aprobados; modelo de pertenencia de usuarios a proyectos; identificador y equipos futuros.

**Pruebas mínimas:** creación válida/inválida, pertenencia cliente-proyecto, autorización, formato/unicidad, transiciones permitidas/denegadas y auditoría sin sobrescritura.

### 2. Entradas, seguimiento, etiquetas e imágenes

**Alcance:** notas internas/públicas, solicitante/contacto, múltiples etiquetas visuales con color y duración estimada, imágenes y otros adjuntos, transiciones, historial completo y notificaciones básicas de asignación/respuesta pública. Esta fase queda fuera de la fase 1.

**Aceptación:** entradas internas nunca son visibles al cliente; públicas solo son visibles a destinatarios autorizados; adjuntos inválidos se rechazan; se conserva autor/fecha; notificaciones no filtran contenido ni se duplican.

**Riesgos:** errores de visibilidad, exposición de archivos, consistencia entrada-transición-evento y exceso de etiquetas que reduzca la claridad visual.

**Pruebas mínimas:** visibilidad por rol/cliente, descarga autorizada/denegada, validación de archivos, append-only y notificaciones encoladas sin fugas.

### 3. Tiempo, SLA y panel operativo

**Alcance:** `WorkLog`, tiempo calendario/efectivo, SLA de primera respuesta/resolución, pausas configurables, filtros, métricas y alertas.

**Aceptación:** relojes reproducibles; intervalos sin solapamientos según regla; pausas solo en estados configurados; métricas respetan zona horaria/calendario; panel muestra sin asignar, vencidos y próximos.

**Riesgos:** jornada laboral ambigua, cambios de prioridad, zonas horarias y rendimiento de agregaciones.

**Pruebas mínimas:** cálculos, pausa/reanudación, cambio de prioridad, vencimiento, métricas con resueltos/reabiertos/cancelados y permisos sobre `WorkLog`.

### 4. API/portal para clientes y servicios externos

**Alcance:** Sanctum; crear/consultar casos propios; entradas públicas y adjuntos autorizados; respuestas del cliente; rate limiting; Resources, errores y documentación versionada. Esta fase queda fuera de la fase 1 y se implementará posteriormente junto con las APIs e integraciones para terceros.

**Aceptación:** aislamiento estricto entre clientes; nunca se exponen entradas internas; IDs manipulados no filtran datos; endpoints paginados, limitados, versionados, documentados y auditados.

**Riesgos:** identidad de contactos, abuso de cargas/consultas, compatibilidad de contratos y sincronización con operación interna.

**Pruebas mínimas:** autenticación/expiración, aislamiento entre dos clientes, autorización de recursos anidados, rate limiting, payloads/adjuntos y contratos de error.

### 5. Automatizaciones, conocimiento, reportes e integraciones

**Alcance:** equipos, correo bidireccional, base de conocimiento, reportes avanzados, integraciones y automatizaciones; IA solo si se aprueba.

**Aceptación:** automatizaciones idempotentes, observables y auditables; reintentos no duplican casos/entradas; reportes explican filtros/zona horaria; IA no ejecuta acciones sensibles sin autorización.

**Riesgos:** bucles/duplicados de correo, proveedores, costes, privacidad y automatizaciones difíciles de revertir.

**Pruebas mínimas:** idempotencia/reintentos, fallos de proveedores, deduplicación, permisos de equipos, exactitud de reportes, auditoría y seguridad de integraciones.

## Decisiones confirmadas

- El nombre funcional del módulo es “Mesa de trabajo”.
- La fase 1 será exclusivamente interna; no incluirá APIs para terceros, portal, correo ni integraciones externas.
- La fase 1 no incluirá etiquetas visuales, imágenes ni otros adjuntos. Las actualizaciones podrán prepararse internamente, pero la consulta por clientes de las actualizaciones `Public` quedará para la fase de API/portal.
- El módulo cubrirá soporte, integraciones, planificación y nuevos trabajos de desarrollo/producto.
- La interfaz usará Bootstrap, Alpine.js y Livewire.
- Los tipos y categorías serán registros de BD, inicialmente cargados mediante seeders; su mantenimiento posterior será exclusivo de superadministradores.
- El catálogo inicial de tipos será `Support`, `Integration`, `Planning`, `Development`, `Improvement` e `Investigation`; las categorías se definirán dentro de cada tipo.
- Los responsables solo podrán ser usuarios administrativos de la plataforma o usuarios incluidos en la tabla pivote de miembros del proyecto; no habrá responsable principal.
- Las etiquetas visuales tipo Trello, sus colores y la referencia de duración estimada se implementarán después de la fase 1. Las imágenes y adjuntos también quedan fuera del núcleo inicial y se abordarán en la fase 2.
- Las prioridades iniciales serán `Low`, `Medium`, `High` y `Critical`.
- Un caso podrá tener uno o varios usuarios responsables; no habrá responsable principal.
- Un caso podrá tener múltiples etiquetas; no habrá un límite funcional de una sola etiqueta por caso y la asociación deberá permitir agregar o quitar tags fácilmente.
- El código público seguirá el formato `MKY-{aa}{secuencia de 6 dígitos}` —por ejemplo, `MKY-26000001`—; la secuencia se reiniciará cada año desde `000001` y el UUID será el identificador interno.
- Un caso creado desde una API futura iniciará como pendiente. En creación interna podrá quedar pendiente/abierto o asignarse inmediatamente.
- Toda asignación y transición generará historial append-only.
- Cada actualización tendrá visibilidad interna o pública; las actualizaciones públicas serán las únicas que podrá consultar el cliente mediante la API futura.
- Los casos de tipo `Support` exigirán cliente y proyecto; los demás tipos podrán ser internos sin cliente o proyecto.
- La relación de usuarios del proyecto se basará en una tabla pivote derivada del bloque actual de “contacto primario”, permitiendo múltiples miembros.
- Toda creación iniciará en `New` (“Nuevo”), incluso si se asignan responsables al crearla.
- Los tags serán exclusivamente informativos y no modificarán SLA, prioridad ni métricas.


## Validaciones necesarias antes de avanzar

- Estados, transiciones, reapertura, cierre y obligatoriedad de causa raíz.
- Modelo de solicitante interno y si se requiere conservar también un contacto externo para fases posteriores.
- Calendario, festivos, zona horaria, pausas y SLA por prioridad/cliente/proyecto.
- Etiquetas: estructura, colores y permisos de administración. No habrá límite funcional de etiquetas por caso. A diferencia de estados, prioridades, visibilidad y origen, los tags serán registros de BD y únicamente informativos; no afectan SLA, prioridad ni métricas.
- Imágenes y adjuntos: retención, datos sensibles y permisos de descarga.
- Canales/destinatarios de notificación y definición de primera respuesta.

- Diseño de etiquetas: catálogo administrable, paleta de colores y experiencia para asociar múltiples tags. No se definirá un límite funcional de etiquetas por caso; los tags serán informativos.

No implementar ninguna fase ni crear migrations, modelos, pantallas, APIs o cambios de negocio hasta recibir una instrucción explícita.


























