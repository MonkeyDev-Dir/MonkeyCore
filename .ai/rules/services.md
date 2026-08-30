---
paths:
  - 'app/Services/**'
---

# Services

## Lógica y consultas en Services
Crear y organizar la lógica que interactúa con modelos o base de datos dentro de la capa Services. Las consultas deben permanecer exclusivamente en Services.

## API central como fuente de datos
Las APIs propias deben consultar datos normalizados y almacenados localmente; las integraciones externas sincronizan esos datos mediante comandos o tareas programadas y no se consultan directamente desde cada consumidor.

## API Tokens con acceso general
Los API Tokens de aplicaciones se emiten con acceso general a las APIs disponibles. No se asignan ni validan abilities por servicio hasta que el equipo defina una política de permisos más granular.
