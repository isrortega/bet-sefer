# Prompts para Claude Code — Bet-Sefer

Guía operativa. No es parte del entregable; bórrala del repo antes de entregar
(o déjala, no molesta).

## Cómo usarla

1. Crea el repo vacío, copia `CLAUDE.md`, `docs/` y `README.template.md` dentro.
2. Abre Claude Code en la raíz.
3. Ejecuta los prompts **en orden**. Uno por sesión de trabajo.
4. Después de cada fase: `make check` y commit. No avances con la suite en rojo.

**Reglas de convivencia con el agente:**

- Al inicio de cada prompt, recuérdale qué documento leer. No asumas que
  recuerda `docs/` de la fase anterior.
- Si una fase se alarga, usa `/clear` y arranca la siguiente limpia. El contexto
  arrastrado degrada la calidad más que perder historia.
- Cuando proponga algo que contradiga los docs, la fuente de verdad son los
  docs. Dile que actualice el doc o que siga el doc, nunca que improvise.
- Pídele que corra los tests él mismo antes de decir que terminó.

---

## Fase 0 — Andamiaje

```
Lee CLAUDE.md y docs/08-infrastructure.md completos antes de escribir nada.

Crea el andamiaje del proyecto:
- Laravel 13 sobre PHP 8.4, con Inertia + Vue 3 + Tailwind 4.
- Paquetes: spatie/laravel-permission, spatie/laravel-activitylog,
  laravel/socialite, simplesoftwareio/simple-qrcode, intervention/image,
  pestphp/pest, larastan, laravel/pint.
- docker-compose.yml de desarrollo con los servicios del doc (app, web, postgres,
  redis, queue, scheduler, mailpit, minio, vite). El puerto SMTP 1025 NO se
  publica al host.
- docker/dev/Dockerfile y docker/prod/Dockerfile (multi-stage, non-root,
  healthcheck).
- Makefile con up, down, fresh, test, check, shell, logs.
- .env.example con todas las variables del doc, vacías.
- La estructura de carpetas de app/ que define CLAUDE.md.

No escribas todavía migraciones ni modelos de dominio.
Termina levantando el stack y comprobando que la página por defecto responde.
```

## Fase 1 — Base de datos

```
Lee docs/01-domain-model.md completo. Es la especificación exacta; síguela al pie
de la letra, incluidos tipos, nulabilidad, índices y nombres.

Crea las 8 tandas de migraciones en el orden del documento (extensiones primero).
Puntos que no se pueden expresar con el schema builder y van con DB::statement:
- la columna generada search_vector y su índice GIN
- el índice único parcial loans_one_active_per_copy
- los índices text_pattern_ops sobre categories.path y locations.path

Crea también los modelos Eloquent con:
- $fillable explícito (nunca $guarded = [])
- casts, incluyendo encrypted en document_number y phone
- $hidden para document_number, document_hash y phone
- todas las relaciones
- los enums PHP backed en app/Enums

No escribas seeders todavía. Termina con `make fresh` corriendo limpio y muéstrame
el resultado de `\d editions` y `\d loans` desde psql para verificar índices.
```

## Fase 2 — Roles, permisos y ciclo de vida de usuario

```
Lee docs/02-business-rules.md secciones 3 y 4, y docs/06-security.md.

Implementa:
- Seeder de roles y permisos con la matriz exacta del documento.
- Autenticación local (login, registro, verificación de correo, recuperación).
- Google SSO con Socialite. Toda cuenta SSO se crea con rol reader y estado
  pending_identity, sin excepción, ignorando cualquier claim del proveedor.
- El ciclo de vida pending_email → pending_identity → active → suspended.
- Generación de member_code con dígito de control.
- document_hash con HMAC-SHA256 sobre el número normalizado, y la búsqueda de
  lector por documento en el mostrador usando el hash.
- La acción de verificación de identidad por parte del bibliotecario.
- Rate limits de la tabla del doc de seguridad.
- Policies base.

Tests obligatorios en esta fase:
- una cuenta SSO nunca obtiene un rol distinto de reader
- un usuario pending_identity no puede pedir prestado
- throttling de login

Corre make check y muéstrame el resultado.
```

## Fase 3 — Catálogo e ISBN

```
Lee docs/01-domain-model.md (bloques 2 y 3) y docs/03-isbn-and-ai.md.

Implementa:
- CRUD de editions y copies con Form Requests, Actions y Policies.
- Árboles de categories y locations con path materializado.
- El pipeline de ISBN: validación de checksum, normalización a ISBN-13,
  OpenLibraryProvider y GoogleBooksProvider llamados en paralelo con Http::pool,
  merge campo a campo según la tabla de precedencia, caché en Redis y en
  metadata_lookups, circuit breaker.
- Descarga y subida de portada a R2 con reencode por Intervention Image.
- Generación de code de ejemplar (BS- + base32 Crockford + dígito de control,
  aleatorio, nunca secuencial).
- Reglas de borrado: hard delete sin historial, soft delete con historial,
  bloqueo si hay ejemplar prestado.
- Búsqueda del documento de reglas de negocio sección 5.

Los tests de proveedores usan Http::fake() con fixtures grabados en
tests/Fixtures/metadata/. Ningún test toca la red.

Incluye el test que prueba que la segunda consulta del mismo ISBN hace cero
peticiones HTTP.
```

## Fase 4 — Circulación

```
Lee docs/02-business-rules.md secciones 1 y 2. Es el corazón del producto;
tómate el tiempo necesario aquí.

Implementa:
- BusinessCalendar (horario de atención + festivos, incluidos los recurrentes).
- LoanPolicyResolver con el factor de material especial y el snapshot de política.
- CopyStateMachine con la tabla de transiciones exacta y el registro en
  copy_status_transitions.
- Acciones de check-out, check-in y renovación, con lockForUpdate y manejo de la
  violación del índice único traducida a un mensaje amable.
- Todas las reglas de bloqueo al prestar, cada una con su mensaje propio.
- La cola del acomodador.

Tests: toda la sección "Unit" y el bloque "Circulation" de docs/07-testing.md,
incluido el test de concurrencia con dos check-outs simultáneos sobre el mismo
ejemplar.

No sigas a la fase 5 hasta que la cobertura de app/Services/Circulation esté al
100%.
```

## Fase 5 — Interfaz de staff

```
Lee docs/05-design-system.md completo antes de escribir una línea de Vue.
Implementa el sistema de tokens en el bloque @theme de Tailwind 4 primero, y
construye todo a partir de esos tokens; ningún color hardcodeado en componentes.

Pantallas, en este orden de prioridad:
1. Mostrador (front desk): campo de escaneo que siempre recupera el foco,
   operable solo con teclado, Enter confirma. Es la pantalla más importante del
   producto y donde se gasta el único acento brass.
2. Catálogo: tabla densa con búsqueda y filtros.
3. Ficha de edición: serif para lo bibliográfico, tabla de ejemplares al lado.
4. Alta por ISBN con el estado "Looking up ISBN…" y salida manual visible.
5. Cola del acomodador, móvil primero.
6. Administración: usuarios, roles, categorías, ubicaciones, políticas, festivos.

Respeta: color solo para estado, chips con icono + etiqueta, un único momento de
animación (la transición del chip de estado), foco visible en todo, sentence case,
cifras tabulares en columnas numéricas.

Todas las cadenas van a lang/en.json y lang/es.json. Ninguna cadena hardcodeada.
```

## Fase 6 — Punto público y QR

```
Lee docs/04-public-info-point.md completo.

Implementa las tres rutas públicas, móvil primero.

Lo crítico: el payload público se construye con una allow-list explícita en
PublicEditionResource y PublicCopyResource. No reutilices los resources de staff
ocultando campos — si mañana alguien agrega una columna al modelo, no debe poder
filtrarse.

Incluye:
- agregados de circulación, nunca fechas individuales
- fecha estimada de disponibilidad calculada desde el due_at más próximo
- el caso de ISBN que no tenemos, con sugerencia de adquisición y registro en
  demand_events
- enriquecimiento según el rol del visitante autenticado
- generación de QR y la hoja de etiquetas A4 imprimible
- el QR de carné del lector en su propio perfil

Tests: los del bloque "Public info point" de docs/07-testing.md. El test que
importa es el que serializa la respuesta anónima y afirma que no contiene ningún
nombre, correo, documento ni member_code.
```

## Fase 7 — Clasificación con IA

```
Lee la sección "AI classification" de docs/03-isbn-and-ai.md.

Implementa ClassifySuggestedTaxonomy. Restricciones:
- elige solo de las hojas del árbol de categorías existente; si nada encaja,
  devuelve null
- máximo 6 tags, prefiriendo las existentes
- salida JSON estricta, validada; si el parseo falla, se omite en silencio
- se ejecuta después del merge de metadatos, para poder usar el resumen
- si no hay resumen ni subjects, no se llama al modelo
- las sugerencias se muestran preseleccionadas y marcadas como "Suggested", y
  nada se guarda sin confirmación del bibliotecario
- nunca inventa datos bibliográficos, solo clasifica

Detrás de la bandera AI_CLASSIFICATION_ENABLED. La app debe funcionar completa
con la bandera apagada.
```

## Fase 8 — Datos de demostración

```
Lee la sección "Seeders" de docs/01-domain-model.md.

Crea los seeders en el orden del documento. Puntos importantes:
- las 40 ediciones salen de ISBNs reales, pero los seeders NO deben golpear la
  red: graba los payloads como fixtures y siembra desde ahí
- los 4 usuarios demo quedan active y con identidad verificada
- ~120 préstamos históricos repartidos en 6 meses, con algunos activos, algunos
  vencidos y la mayoría devueltos, para que el catálogo y los estados se vean
  vivos
- ejemplares repartidos entre varios estados, incluidos at_reception e
  in_transit, para que la cola del acomodador no esté vacía al abrirla
- algunos demand_events

`make fresh` debe dejar la app lista para una demo de 5 minutos sin tocar nada.
```

## Fase 9 — Endurecimiento

```
Lee docs/06-security.md y docs/07-testing.md completos.

Repasa y completa:
- cabeceras de seguridad (CSP sin unsafe-inline, HSTS, nosniff, frame-deny)
- todos los rate limits de la tabla
- validación de subidas por contenido y reencode
- confirmar que ningún endpoint público expone PII (revisa resource por resource)
- confirmar $hidden y ausencia de logs con datos sensibles
- la matriz completa de tests negativos de autorización: por cada ruta protegida,
  un test por cada rol que no debe alcanzarla
- tests de arquitectura de Pest
- composer audit y npm audit

Sube Larastan al nivel más alto que pase sin baseline y reporta en cuál quedó.
Muéstrame el reporte de cobertura por directorio.
```

## Fase 10 — Despliegue

```
Lee docs/08-infrastructure.md.

Crea:
- .github/workflows/ci.yml, que corre en cualquier rama y en pull requests
- .github/workflows/deploy.yml, que se dispara ÚNICAMENTE con push a main.
  Nada de workflow_dispatch, nada de otras ramas, nada de tags. Incluye el
  concurrency group deploy-production con cancel-in-progress: false.
- docker-compose.prod.yml sin mailpit, sin minio, sin vite
- Caddyfile
- el script de backup nocturno con pg_dump hacia R2

La rama de trabajo es develop; main solo recibe merges. Configúrame también el
comando de gh para proteger main exigiendo que pase ci.

Después del primer despliegue, recorre la lista de verificación del final del
documento y repórtame cada punto. En particular confírmame desde dentro del
contenedor en producción que APP_DEBUG está en false.
```

## Fase 11 — README

```
Lee README.template.md y docs/09-roadmap.md.

Conviértelo en el README definitivo:
- rellena las credenciales reales de los usuarios sembrados
- rellena las cifras reales de cobertura tras el último `make check`
- añade una captura del mostrador arriba del todo
- verifica que todos los enlaces a docs/ funcionan
- comprueba que las instrucciones de instalación funcionan desde cero en un
  clon limpio: hazlo de verdad, no lo asumas

Borra PROMPTS.md del repo.
```

---

## Prompts sueltos que vas a necesitar

**Cuando se desvíe del diseño:**
```
Esto se apartó de docs/05-design-system.md. Revisa el archivo y corrige:
[lo que sea]. Los tokens del bloque @theme son la única fuente de color; no
introduzcas valores nuevos sin actualizar el documento primero.
```

**Cuando quieras una revisión crítica antes de cerrar una fase:**
```
Antes de cerrar esta fase, revísala tú mismo como si fueras el evaluador técnico
de Valsoft. Busca específicamente: lógica de negocio filtrada a controladores,
rutas sin Policy, consultas N+1, cadenas sin traducir, y cualquier endpoint que
pueda exponer datos de un lector. Lista los hallazgos antes de corregir nada.
```

**Cuando algo no compile o los tests se pongan raros:**
```
No parchees el síntoma. Explícame primero cuál es la causa raíz y qué opciones
hay, y espera mi confirmación antes de cambiar código.
```

**Al final del día, si vas justo de tiempo:**
```
Quedan N horas. Revisa docs/00-brief.md y dime qué está incompleto, ordenado por
impacto en los criterios de evaluación (completeness, usability, product quality,
creativity). Propón qué cortar y muévelo a docs/09-roadmap.md. No cortes nada de
la lista "nunca recortar" de docs/07-testing.md.
```
