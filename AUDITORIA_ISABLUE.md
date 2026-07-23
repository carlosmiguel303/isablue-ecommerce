# AUDITORÍA TÉCNICA — ISABLUE E-COMMERCE

**Preparado para:** FORCETEMP S.A.C.
**Fecha:** 2026-07-23
**Alcance:** Repositorio `Isablue-deploy-ready` (frontend Angular + backend Spring Boot + PostgreSQL/Railway)
**Naturaleza del documento:** Diagnóstico. No se modificó código de negocio para producir esta auditoría; las conclusiones se verificaron leyendo el código fuente.

> **Nota de ubicación:** El proyecto Isablue real (con PayPal, Yape, Brevo y despliegue Railway) vive en
> `Playground/Isablue-deploy-ready/Isablue-deploy-ready/`, que es un repositorio Git independiente
> (rama `main`, remoto `origin/main`). El directorio `SELDO-v3-package` es **otro** producto
> (plataforma multiempresa SELDO) y **no** es el objeto de esta auditoría.

---

## 1. ARQUITECTURA ACTUAL

### 1.1 Componentes

| Capa | Tecnología | Detalle |
|------|-----------|---------|
| Frontend | Angular 16.1 (módulos NgModule, no standalone) | Bootstrap 5.3, ngx-toastr, SweetAlert2, jQuery. Node 18. |
| Backend | Spring Boot 3.3.5 / Java 17 | Arquitectura hexagonal (domain / application / infrastructure). Maven. |
| Seguridad | Spring Security + JWT (jjwt 0.11.5) | BCrypt, filtro `OncePerRequestFilter`, sesiones STATELESS. |
| ORM | Spring Data JPA / Hibernate | `ddl-auto=update`. |
| BD | PostgreSQL (perfil `pdn`/Railway); MySQL (perfil `dev`); H2 archivo (perfil `local`) | Tres perfiles con dialectos distintos. |
| Pagos | PayPal REST SDK 1.14.0, Culqi (HTTP), Yape (manual) | Ver sección 7. |
| Correo | Brevo API HTTP + fallback SMTP | Asíncrono, no bloquea la venta. |
| Facturación | Nubefact (boleta SUNAT), fallback boleta interna | Opcional por variables. |
| Despliegue | Docker multi-stage (backend) + Netlify (frontend) | `application-pdn.properties` apunta a Railway/Postgres. |

### 1.2 Estructura del backend (paquetes)

```
com.icodeap.ecommerce.backend
├── application/         → Servicios de caso de uso (Order, Product, User, Registration, Category, UploadFile)
├── domain/
│   ├── model/           → Modelos de dominio (Order, Product, User, OrderState, DataPayment…)
│   └── port/            → Interfaces de repositorio (ICategory/IOrder/IProduct/IUserRepository)
└── infrastructure/
    ├── adapter/         → Implementaciones JPA de los puertos
    ├── config/          → SecurityConfig, PaypalConfig, DataInitializer, FileStorageConfig, BeanConfiguration
    ├── dto/             → JWTClient, UserDTO
    ├── entity/          → Entidades JPA (User, Product, Order, OrderProduct, Payment, Promo, InfoPage, Category)
    ├── exception/       → GlobalExceptionHandler + excepciones de negocio
    ├── jwt/             → JWTGenerator, JWTAuthorizationFilter, Constants
    ├── mapper/          → MapStruct mappers
    ├── rest/            → Controladores REST
    └── service/         → PaypalService, CulqiService, NubefactService, MailService, CustomUserDetailService
```

### 1.3 Endpoints REST principales

| Método | Ruta | Seguridad | Función |
|--------|------|-----------|---------|
| POST | `/api/v1/security/register` | pública | Registro de cliente |
| POST | `/api/v1/security/login` | pública | Login → JWT |
| POST/GET | `/api/v1/security/google`, `/google/config` | pública | Login con Google |
| GET | `/api/v1/home`, `/home/{id}`, `/home/categories`, `/home/promos` | pública | Catálogo público |
| GET/POST/DELETE | `/api/v1/admin/products/**` | ROLE_ADMIN | ABM de productos |
| GET/POST/PUT/DELETE | `/api/v1/admin/promos`, `/admin/info`, `/admin/payments` | ROLE_ADMIN | Gestión admin |
| POST/GET | `/api/v1/orders`, `/orders/{id}`, `/orders/mine` | autenticado | Pedidos |
| POST | `/api/v1/orders/update/state/order` | ROLE_ADMIN | Cambiar estado de pedido |
| POST | `/api/v1/payments` (PayPal), `/payments/success`, `/payments/cancel` | autenticado / público | PayPal |
| GET/POST | `/api/v1/payments/culqi/config`, `/culqi/charge` | autenticado | Tarjeta (Culqi) |
| GET/POST | `/api/v1/payments/yape/info`, `/yape/register` | autenticado | Yape manual |
| GET | `/api/v1/users/me` | autenticado | Perfil actual |

---

## 2. FLUJO PRINCIPAL DE COMPRA

1. El visitante navega el catálogo público (`/tienda`, `HomeController`) sin autenticarse.
2. Agrega productos al carrito (`CartService`, persistido en `localStorage`, cantidad forzada a 1–10).
3. En `/cart/sumary` (protegido por `authGuard`) el frontend obtiene el perfil (`/users/me`) y crea la orden:
   `POST /api/v1/orders`. El backend **fija el `userId` desde el token**, fuerza estado `PENDING`,
   valida cantidades 1–10 y **recalcula el precio desde la BD** (no confía en el precio del cliente).
4. Selección de método de pago:
   - **Yape (activo por defecto):** `POST /payments/yape/register` guarda un pago `POR_CONFIRMAR`,
     limpia el carrito y abre WhatsApp para coordinar. **No cambia el estado de la orden a CONFIRMED**
     (queda `PENDING` hasta verificación manual). Correo de aviso vía Brevo.
   - **Tarjeta (Culqi):** `POST /payments/culqi/charge`. Con llaves reales tokeniza en el navegador
     (Culqi.js). **Sin llaves → cargo SIMULADO** que igualmente marca la orden `CONFIRMED` y emite boleta.
   - **PayPal (cableado pero oculto en la UI):** `POST /payments` devuelve `approval_url`; el callback
     `/payments/success` ejecuta el pago y marca `CONFIRMED`.
5. Pantalla `/payment/success` muestra el resultado guardado en `sessionStorage`.

---

## 3. FUNCIONALIDADES QUE YA FUNCIONAN (verificadas en código)

- ✅ Registro e inicio de sesión con JWT (HS512) + BCrypt. `JWTGenerator` exige secreto ≥ 64 caracteres.
- ✅ Login con Google (Google Identity Services) con creación automática de cuenta.
- ✅ Roles USER / ADMIN aplicados en `SecurityConfig` (`/api/v1/admin/**` → `hasRole("ADMIN")`).
- ✅ Catálogo público, detalle de producto, categorías y carrusel de promociones administrable.
- ✅ Carrito en `localStorage` con recálculo de totales y límites de cantidad.
- ✅ Creación de pedidos con **precio recalculado en servidor** y validación de pertenencia por usuario.
- ✅ Autorización de acceso a pedidos: `GET /orders/{id}` verifica dueño o ADMIN; `/orders/mine` filtra por usuario.
- ✅ Pago Yape (registro manual + WhatsApp) y pago con tarjeta en modo simulado o real (Culqi).
- ✅ Panel admin: productos, categorías, promociones, páginas informativas y lista de pagos.
- ✅ Boleta interna (serie B001 + correlativo) y opción Nubefact/SUNAT por variables.
- ✅ Correos a admin y comprador vía Brevo (HTTP), con fallback SMTP, en segundo plano.
- ✅ Subida de imágenes con validación de tipo, nombre aleatorio (UUID) y protección contra *path traversal*.
- ✅ Manejo global de errores (`GlobalExceptionHandler`) con mensajes claros en español.
- ✅ Interceptor de sesión vencida en el frontend (401/403 → cierra sesión y redirige a login).
- ✅ El backend **compila** (`mvn -DskipTests compile` → BUILD SUCCESS).

---

## 4. FUNCIONALIDADES INCOMPLETAS O DEFECTUOSAS

| # | Área | Estado |
|---|------|--------|
| F1 | **Inventario / stock** | No existe. `ProductEntity` no tiene campo `stock`. Se puede comprar cantidad ilimitada de productos agotados. |
| F2 | **Confirmación de pago sin cobro real (modo SIM)** | Si Culqi no tiene llaves, `CulqiService.charge` devuelve `SIM-...` y la orden se marca `CONFIRMED` + boleta emitida **sin cobrar**. Peligroso si se despliega a producción sin configurar llaves. |
| F3 | **Sin webhooks de PayPal/Culqi** | La confirmación depende del redirect del navegador. No hay verificación de monto ni idempotencia; un fallo de red tras el pago puede dejar la orden sin confirmar aunque se haya cobrado. |
| F4 | **PayPal oculto en la UI** | El backend y el servicio (`PaymentService.getUrlPaypalPayment`) están cableados, pero el checkout (`sumary-order`) solo muestra Yape y Tarjeta. PayPal quedó latente. |
| F5 | **Recuperación de contraseña / verificación de correo** | No existe. |
| F6 | **`environment.prod.ts` con placeholder** | Valor por defecto `https://TU-BACKEND-RAILWAY.up.railway.app/api/v1`. Si no se define `API_URL` en el build, el frontend en producción apunta a un host inexistente. |
| F7 | **Imágenes en disco efímero** | `UPLOAD_DIR=/app/uploads` es el filesystem del contenedor. En Railway cada redeploy **borra las imágenes subidas**. Falta almacenamiento persistente/objeto (S3/Cloudinary). |
| F8 | **Sin paginación** | `/home`, `/admin/products`, `/orders` (`findAll`) devuelven **todo** sin límite. Escala mal (ver sección 6). |
| F9 | **OrderState del frontend incompleto** | El enum Angular solo tiene `CANCELLED`/`CONFIRMED` (falta `PENDING`); funciona porque el backend fija el estado, pero es inconsistente. |
| F10 | **Logs con datos personales** | `LoginController` registra a nivel INFO el nombre y rol del usuario en cada login. |
| F11 | **Sin pruebas reales** | Solo `contextLoads()` en backend y el spec por defecto de Angular. No hay cobertura del flujo de compra ni de seguridad. |
| F12 | **`spring-boot-devtools` en runtime** | Incluido como dependencia; en producción aumenta memoria y puede provocar reinicios/clases duplicadas. Debería ser `optional`/excluido del jar de producción. |

---

## 5. RIESGOS DE SEGURIDAD (clasificados)

### 🔴 CRÍTICOS

- **C1 — Secreto JWT de producción quemado en el repositorio.**
  `application-pdn.properties:2` trae `jwt.secret` con un valor por defecto real
  (`isablueJwtProd2026Km9X...`). Cualquiera con acceso al repo puede **falsificar tokens** (incluido rol ADMIN)
  contra el entorno Railway si no se sobreescribió `JWT_SECRET`. **Debe rotarse el secreto y eliminar el valor por defecto.**
  → Archivo: `backend/src/main/resources/application-pdn.properties`

- **C2 — Confirmación de pedidos sin cobro (modo simulado en producción).**
  Si el backend arranca en producción **sin** `CULQI_SECRET_KEY`, el pago con tarjeta se aprueba solo
  y la orden se marca pagada. Ver F2. Debe bloquearse el modo SIM cuando el perfil es de producción.
  → `service/CulqiService.java`, `rest/CheckoutController.java`

### 🟠 ALTOS

- **A1 — Validación de Google opcional.** En `GoogleAuthController`, si `google.client-id` está vacío
  (valor por defecto), **no se valida el `aud`** del token: cualquier ID token de Google válido de *otra* app
  sería aceptado. Debe exigirse `google.client-id` configurado y rechazar si falta.
  → `rest/GoogleAuthController.java:72`

- **A2 — Subida de SVG (XSS almacenado).** `UploadFile.ALLOWED_TYPES` permite `image/svg+xml`. Un SVG puede
  contener JavaScript; servido desde `/uploads/**` (público) puede ejecutar scripts en el dominio del backend.
  Recomendado: quitar SVG o sanitizar/servir con `Content-Disposition: attachment` y CSP.
  → `application/UploadFile.java:14`

- **A3 — Sin límite de intentos (fuerza bruta).** `/security/login`, `/register` y `/google` no tienen
  rate limiting ni bloqueo. Password mínimo de solo 6 caracteres (`User.java`). Riesgo de credential stuffing.

- **A4 — `/payments/cancel` público con solo `orderId`.** Está en `permitAll`. Aunque PayPal es quien redirige,
  el endpoint acepta cualquier `orderId` y lo marca `CANCELLED` sin validar pertenencia. Un atacante puede
  cancelar pedidos ajenos por fuerza bruta de IDs.
  → `SecurityConfig.java:62`, `PaypalController.java:108`

### 🟡 MEDIOS

- **M1 — Credenciales demo débiles y por defecto.** `admin@isablue.pe / admin123` en `DataInitializer`.
  En `pdn`, `DEMO_DATA_ENABLED` por defecto es `false` (bien), pero `application.properties` base lo pone en `true`.
  Si algún entorno hereda el base, se crea un admin con clave trivial.
- **M2 — Datos Yape quemados** (número `985436488` / nombre `Haydee Hospinal`) como valores por defecto en
  código y en el componente Angular (`sumary-order.component.ts:25`). Debe venir siempre de configuración.
- **M3 — `ddl-auto=update` en producción.** Riesgo de cambios de esquema no controlados. Se recomienda Flyway/Liquibase.
- **M4 — Verbosidad de errores.** `handleGeneral` devuelve el `getSimpleName()` de la excepción al cliente (fuga menor de internals).
- **M5 — CORS con `allowCredentials=true`.** Correcto porque los orígenes son explícitos, pero cualquier error
  al listar orígenes (o un `*` accidental) rompería la política. Mantener orígenes cerrados por variable.

### 🟢 BAJOS

- **B1 — Logs INFO con PII** (F10).
- **B2 — `console.log` en `Order.getTotal()`** del frontend (código muerto/ruido).
- **B3 — Scripts de terceros por CDN** (`bootstrap`, `culqi`, `google`) en `index.html` sin SRI.
- **B4 — jQuery + popper.js** incluidos y probablemente sin uso real (peso innecesario).

---

## 6. PROBLEMAS QUE PODRÍAN CAUSAR "OUT OF MEMORY" EN RAILWAY

Railway asigna contenedores con memoria limitada (típicamente 512 MB en planes bajos). Riesgos detectados:

1. **JVM sin límite de heap explícito (causa más probable).** El `Dockerfile` arranca con
   `java -jar app.jar` **sin `-XX:MaxRAMPercentage` ni `-Xmx`**. En contenedores pequeños Spring Boot 3 +
   Hibernate + 3 drivers JDBC puede superar la RAM al iniciar → OOM Kill.
   **Acción:** `ENTRYPOINT ["java","-XX:MaxRAMPercentage=70","-XX:+UseSerialGC","-jar","app.jar"]` y fijar el plan.
   → `backend/Dockerfile`
2. **`spring-boot-devtools` empaquetado** (F12): incrementa consumo y activa reinicios en caliente.
3. **Endpoints sin paginación** (F8): `findAll()` de productos y **pedidos** carga todas las filas y sus
   colecciones (`OrderProduct`) en memoria. Con `open-in-view=true` y muchos pedidos, la huella crece linealmente.
4. **Tres drivers JDBC cargados** (MySQL + PostgreSQL + H2) — memoria y superficie innecesarias en producción.
5. **`CompletableFuture.runAsync` sin pool dedicado** en `MailService`: usa el `ForkJoinPool` común; ante
   picos de ventas podría acumular tareas. Bajo volumen no es crítico, pero conviene un executor acotado.
6. **HikariCP** ya está afinado para dormir (`minimum-idle=0`), lo cual ayuda; mantenerlo.

> Conclusión OOM: el factor #1 (heap sin límite) es casi siempre la causa del "Out of memory" al desplegar
> Spring Boot en Railway. Corregir el Dockerfile y excluir devtools resuelve la mayoría de los casos.

---

## 7. ESTADO DE PAYPAL, YAPE Y BREVO

### PayPal — ⚙️ Cableado, en sandbox, **oculto en la UI**
- `PaypalConfig` crea `APIContext(clientId, clientSecret, mode)` desde variables (`PAYPAL_*`, `mode=sandbox` por defecto).
- `PaypalController` crea el pago y maneja `success`/`cancel` con `RedirectView`.
- **Funciona técnicamente** pero: (a) el checkout Angular no ofrece el botón PayPal; (b) sin webhooks (F3);
  (c) sin validación de monto en el callback. Para cobros reales requiere credenciales `live` y verificación firmada.

### Yape — ✅ Operativo (manual)
- `YapeController` registra el pago como `POR_CONFIRMAR` y notifica por correo; la coordinación real es por WhatsApp.
- Número/nombre configurables por variable, pero con valores personales quemados por defecto (M2).
- **No hay verificación automática del pago** (es intencional: confirmación manual del comercio).

### Brevo — ✅ Implementado, desactivado si falta la llave
- `MailService` usa la API HTTP de Brevo (`https://api.brevo.com/v3/smtp/email`) porque Railway bloquea SMTP saliente.
- Si `BREVO_API_KEY` está vacío intenta SMTP; si nada está configurado, **no envía** (no rompe la venta).
- Envío asíncrono y tolerante a fallos. Correcto en diseño; sólo requiere configurar la llave y `MAIL_ADMIN_TO`.

---

## 8. PROPUESTA PARA CONVERTIR ISABLUE EN PLANTILLA REUTILIZABLE

Objetivo: vender la misma base a distintos clientes cambiando **marca, colores, productos, pagos y textos**
sin reconstruir. Estrategia recomendada: **configuración por variables de entorno + tema + "seed" por cliente**,
manteniendo un solo código.

### 8.1 Externalizar la identidad de marca (branding)
- Mover a variables/tabla de configuración: nombre de tienda, logo, colores primarios/secundarios, WhatsApp,
  textos del footer, moneda, país. Hoy están dispersos (HTML, componentes, `DataInitializer`, propiedades).
- Frontend: un archivo `theme.json` / variables CSS (`--brand-primary`…) leído al arrancar, en vez de colores fijos.
- Reemplazar todas las cadenas "Isablue"/"Juguetes" quemadas por tokens de configuración.

### 8.2 Parametrizar catálogo y datos demo
- Convertir `DataInitializer` en un *seeder* que lea un JSON por cliente (categorías/productos iniciales)
  en lugar de productos de juguetes fijos.

### 8.3 Abstraer pagos por proveedor (patrón Strategy)
- Ya existe un contrato implícito (Culqi/PayPal/Yape). Formalizar una interfaz `PaymentProvider`
  y activarla por variable (`PAYMENT_PROVIDERS=yape,culqi`). Añadir webhooks firmados como requisito común.

### 8.4 Multi-marca real (opcional, fase avanzada)
- Para vender a varios clientes en una sola instancia, evaluar el enfoque multi-tenant que ya explora
  el proyecto hermano **SELDO** (tienda por `slug`). Para "una instancia por cliente" basta con branding + seed.

### 8.5 Higiene de plantilla
- Quitar valores personales (Yape, correos, secretos) del código; todo por `.env`.
- `.env.example` completo y `README` de "cómo clonar para un cliente nuevo".
- Migraciones versionadas (Flyway) para poder evolucionar el esquema entre clientes.

---

## 9. PLAN DE IMPLEMENTACIÓN POR FASES

### FASE 0 — Estabilización y seguridad crítica (antes de tocar features)
- Rotar y externalizar `JWT_SECRET` (C1). Eliminar el valor por defecto de producción.
- Bloquear el modo de pago simulado en perfiles de producción (C2/F2).
- Corregir `Dockerfile` (heap) y excluir `devtools` (sección 6).
- Exigir `google.client-id` y validar `aud` (A1). Restringir `/payments/cancel` (A4). Quitar/mitigar SVG (A2).
- Añadir pruebas para cada corrección.

### FASE 1 — Robustez del flujo de compra
- Inventario/stock (F1) con validación en checkout.
- Webhooks firmados de Culqi/PayPal + idempotencia y verificación de monto (F3).
- Paginación en catálogo/pedidos (F8/OOM #3).
- Almacenamiento persistente de imágenes (F7).

### FASE 2 — Cuenta y operación
- Recuperación de contraseña y verificación de correo (F5).
- Rate limiting en autenticación (A3). Política de contraseñas.
- Migraciones Flyway (M3). Observabilidad básica (health, métricas, logs sin PII).

### FASE 3 — Plantilla reutilizable
- Externalizar branding (8.1), seeder por cliente (8.2), abstracción de pagos (8.3).
- Documentación de "clonado por cliente" y `.env.example` completo.
- Suite de pruebas E2E del flujo de compra.

### FASE 4 — Escala (opcional)
- Multi-tenant estilo SELDO si se vende como SaaS en una sola instancia.

---

## 10. LISTA EXACTA DE ARCHIVOS QUE NECESITARÍAN CAMBIOS

> Rutas relativas a `Isablue-deploy-ready/Isablue-deploy-ready/`.

### Seguridad crítica (Fase 0)
- `backend/src/main/resources/application-pdn.properties` — quitar default de `jwt.secret` (C1).
- `backend/src/main/resources/application.properties` — revisar `DEMO_DATA_ENABLED` y defaults sensibles.
- `backend/src/main/java/.../service/CulqiService.java` — bloquear SIM en producción (C2/F2).
- `backend/src/main/java/.../rest/CheckoutController.java` — no confirmar orden si el cargo fue simulado en prod.
- `backend/Dockerfile` — flags de heap (`-XX:MaxRAMPercentage`), GC (OOM).
- `backend/pom.xml` — marcar `spring-boot-devtools` como no empaquetado / quitar drivers no usados en prod.
- `backend/src/main/java/.../rest/GoogleAuthController.java` — exigir y validar `client-id`/`aud` (A1).
- `backend/src/main/java/.../config/SecurityConfig.java` — endurecer `/payments/cancel` (A4).
- `backend/src/main/java/.../application/UploadFile.java` — quitar/sanitizar SVG (A2).

### Robustez (Fase 1)
- `backend/src/main/java/.../infrastructure/entity/ProductEntity.java` — campo `stock` (F1).
- `backend/src/main/java/.../rest/OrderController.java` — validar stock en checkout (F1).
- `backend/src/main/java/.../rest/PaypalController.java` — webhook + verificación de monto (F3).
- Nuevos: `PaymentWebhookController`, servicio de almacenamiento de objetos (F7).
- `backend/src/main/java/.../rest/HomeController.java` y `ProductController.java` — paginación (F8).

### Frontend
- `front/src/environments/environment.prod.ts` + `front/scripts/set-env.cjs` — evitar placeholder en build (F6).
- `front/src/app/components/orders/sumary-order/sumary-order.component.ts` — quitar Yape quemado (M2); habilitar PayPal si se decide (F4).
- `front/src/app/common/order-state.ts` — añadir `PENDING` (F9).
- `front/src/app/common/order.ts` — quitar `console.log` (B2).

### Pruebas (todas las fases)
- `backend/src/test/java/.../` — pruebas de `OrderController` (precio/stock), `SecurityConfig` (roles),
  `JWTGenerator`, `CulqiService` (modo SIM bloqueado), `UploadFile` (tipos).
- `front/src/app/**/*.spec.ts` — checkout y guards.

### Plantilla (Fase 3)
- `backend/.../config/DataInitializer.java` — seeder por JSON de cliente.
- Nuevo `theme`/config de branding en frontend y backend.
- `README` de clonado por cliente + `.env.example` ampliado.

---

## 11. RESULTADO DE COMPILACIÓN Y PRUEBAS (línea base)

- **Backend `mvn -DskipTests compile`:** ✅ BUILD SUCCESS.
- **Backend `mvn test`:** ver sección de implementación (el único test es `contextLoads()` con `@SpringBootTest`,
  que intenta levantar el contexto completo con el perfil por defecto `dev` → MySQL en `localhost`).
- **Frontend:** Angular 16 con Node 18; el build usa `set-env.cjs` + `ng build`.

---

## 11-BIS. PARTE A — AJUSTES SEGUROS APLICADOS LOCALMENTE (2026-07-23)

> **Estado:** cambios **locales, sin commit, sin push, sin desplegar**. Base `main@fb22bf5`.
> Producción (Railway/Netlify/PostgreSQL/Brevo) **no fue modificada**. Pendiente de aprobación QAS.
> Compilaciones/pruebas: **backend 13 tests, 0 fallos**; **Angular build de producción OK**.

### Confirmaciones del flujo actual (verificadas, sin cambios de comportamiento)
- Crear pedido deja estado **PENDING** (`OrderController` fuerza el estado y el `userId` del token).
- Abrir WhatsApp **no** confirma ni marca pagado el pedido (solo abre `wa.me`).
- La confirmación sigue siendo **manual** por el admin vía `/orders/update/state/order`.

### Cambios aplicados (todos config-driven y compatibles hacia atrás)

| # | Cambio | Archivos | Riesgo | Impacto | ¿Requiere despliegue? |
|---|--------|----------|--------|---------|----------------------|
| 1 | Feature flag `payments.online.enabled` (default **false**). PayPal/Culqi responden 409 controlado y no procesan; `/payments/cancel` y `/success` no tocan órdenes cuando está apagado | `CheckoutController`, `PaypalController`, `application.properties` | Bajo | Endpoints latentes quedan inertes; el frontend ya no muestra tarjeta (`*ngIf="false"`) | Sí, para que aplique en backend (comportamiento igual al actual para el usuario) |
| 2 | Cargo Culqi **SIMULADO** solo si `payments.simulated-card.enabled=true` (perfiles locales). En prod lanza error y **nunca confirma sin cobro** | `CulqiService`, properties | Bajo | Elimina el riesgo de confirmar pedidos sin pago | Sí |
| 3 | Rechazo de **SVG** en subida de imágenes (anti-XSS). Solo afecta subidas nuevas | `UploadFile.java` | Bajo | Imágenes existentes intactas | Sí |
| 4 | `JWT_SECRET` sin valor por defecto en `application-pdn.properties` | `application-pdn.properties` | **Medio-Alto** | Si Railway no tiene `JWT_SECRET`, el backend no arranca | **Sí + verificación previa** (ver abajo) |
| 5 | Externalización de datos personales (WhatsApp, Yape, correos, nombre tienda) a configuración/env. Sin valores quemados en código | 9 archivos front + `YapeController`, `MailService`, `InfoPageEntity`, `application*.properties` | Bajo-Medio | En prod hay que definir `STORE_WHATSAPP`/`YAPE_*` o esos datos aparecen vacíos | Sí + definir env vars |
| 6 | `DEMO_DATA_ENABLED` por defecto **false** y sin credenciales demo por defecto; guardas para no crear usuarios con credenciales vacías | `application.properties`, `DataInitializer` | Bajo | dev/local activan demo explícitamente | Sí (pdn ya tenía demo off) |
| 7 | CORS de `pdn` sin URL productiva quemada (viene de `CORS_ALLOWED_ORIGINS`) | `application-pdn.properties` | Medio | Si falta el env, el frontend queda bloqueado por CORS | Sí + definir env |
| 8 | Dockerfile: `-XX:MaxRAMPercentage=75` vía `JAVA_OPTS` | `backend/Dockerfile` | Bajo | Reduce (no garantiza) el riesgo de OOM en Railway | Sí (reconstruir imagen) |
| 9 | `.env.example` actualizado con todas las variables y valores ficticios | `backend/.env.example` | Nulo | Documentación | No |
| 10 | Pruebas nuevas: `CulqiServiceTest`, SVG en `UploadFileTest` (+ perfil de test H2 de la fase previa) | `backend/src/test/**` | Nulo | Cobertura | No |

### Verificación OBLIGATORIA antes de desplegar (cambio #4 — JWT)
El objetivo #11 pide **no rotar** el secreto ni cerrar sesiones. Antes de desplegar:
1. Confirmar en Railway que la variable `JWT_SECRET` está definida **con el valor actualmente vigente**
   (el mismo que hoy usa producción). Si hoy producción usa el default del código, **primero** definir
   `JWT_SECRET` en Railway con ese valor exacto, **luego** desplegar este cambio. Así no se invalidan sesiones.

### Cambios que NO recomiendo desplegar todavía (requieren decisión/coordinación QAS)
- Todos los de la tabla marcados "Sí": deben ir juntos, con las env vars nuevas ya configuradas en Railway
  (`JWT_SECRET`, `CORS_ALLOWED_ORIGINS`, `STORE_WHATSAPP`, `YAPE_NUMBER`, `YAPE_NAME`, y opcionalmente
  `PAYMENTS_ONLINE_ENABLED`, `MAIL_FROM_NAME`). Desplegar sin ellas degrada contacto/CORS/arranque.
- El límite de heap del Dockerfile es prudente pero **no está demostrado** que el Dockerfile sea la única
  causa del OOM; debe validarse con métricas reales tras el primer despliegue de staging.

### Instrucciones de reversión (todo es local)
- Cambios en archivos versionados: `git checkout -- <archivo>` (o `git restore .` para todos).
- Archivos nuevos (tests, docs): borrarlos manualmente.
- No hay commits ni cambios remotos que revertir.

## 12. SIGUIENTE ACCIÓN RECOMENDADA

Ejecutar **Fase 0** empezando por lo que impide un despliegue seguro/estable en Railway:
1. Corregir el `Dockerfile` (heap) y excluir `devtools` → resuelve el "Out of memory".
2. Rotar el `JWT_SECRET` y eliminar el default de `application-pdn.properties`.
3. Bloquear el pago simulado en producción.

Cada corrección irá acompañada de su prueba, en cambios pequeños y sin tocar credenciales,
servicios externos ni infraestructura sin autorización.
