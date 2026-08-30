---
paths:
  - 'app/Http/**'
---

# Http

## Capas HTTP para APIs versionadas
Ubica los controladores, Form Requests y Resources de API bajo Api/V1 y sepáralos por audiencia: Public, Consumers, Internal y Webhooks. Mantén los controladores delgados y delega la lógica en Services.
