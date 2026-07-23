# INVENTARIO DE ENDPOINTS Y FUNCIONES USADOS POR PRODUCCIÓN

**Base:** rama `main`, commit `fb22bf5991422f8deb010867213ee3ffbb27a030`
**Método:** cruce de cada servicio Angular (llamadas HTTP reales) contra los controladores Spring.
**Fecha:** 2026-07-23

## A. Endpoints ACTIVOS (el frontend desplegado los llama — NO romper contrato)

| Endpoint | Método | Llamado desde | Notas |
|---|---|---|---|
| `/api/v1/security/register` | POST | `authentication.service` | Registro |
| `/api/v1/security/login` | POST | `authentication.service` | Login JWT |
| `/api/v1/security/google/config` | GET | `authentication.service` | Se llama en cada carga del login |
| `/api/v1/security/google` | POST | `authentication.service` | Login Google |
| `/api/v1/home` | GET | `home.service` | Catálogo público |
| `/api/v1/home/{id}` | GET | `home.service` | Detalle producto |
| `/api/v1/home/categories` | GET | `home.service` | Menú categorías |
| `/api/v1/home/promos` | GET | `promo.service` | Carrusel |
| `/api/v1/home/info/{key}` | GET | `info.service` | Páginas informativas |
| `/api/v1/users/me` | GET | `user.service` | Perfil (checkout) |
| `/api/v1/orders` | POST | `order.service` | **Crea pedido PENDING** |
| `/api/v1/orders/mine` | GET | `order.service` | Pedidos del cliente |
| `/api/v1/orders/{id}` | GET | `order.service` | Detalle pedido |
| `/api/v1/orders/update/state/order` | POST | `order.service` | Admin cambia estado (confirmación manual) |
| `/api/v1/payments/yape/info` | GET | `payment.service` | **Checkout: número/nombre Yape** |
| `/api/v1/payments/yape/register` | POST | `payment.service` | **Registra pago POR_CONFIRMAR (no confirma pedido)** |
| `/api/v1/payments/culqi/config` | GET | `payment.service` | Se llama al cargar el checkout (solo lectura) |
| `/api/v1/admin/products` (+`/{id}`, DELETE) | GET/POST/DELETE | `product.service` | Panel admin |
| `/api/v1/admin/categories` (+`/{id}`, DELETE) | GET/POST/DELETE | `category.service` | Panel admin |
| `/api/v1/admin/promos` (+`/{id}`, `/reset`) | GET/POST/PUT/DELETE | `promo.service` | Panel admin |
| `/api/v1/admin/info` (+`/{key}`) | GET/PUT | `info.service` | Panel admin |
| `/api/v1/admin/payments` | GET | `payment.service` | Panel "Pedidos y pagos" |
| `/uploads/**`, `/images/**` | GET | `<img>` | Imágenes |

## B. Endpoints LATENTES (existen en backend; el frontend desplegado NO los invoca)

| Endpoint | Método | Estado |
|---|---|---|
| `/api/v1/payments` (PayPal create) | POST | `getUrlPaypalPayment()` existe en `payment.service` pero **ningún componente lo llama** |
| `/api/v1/payments/success` | GET | Callback PayPal — público, sin uso actual |
| `/api/v1/payments/cancel` | GET | Callback PayPal — público, sin uso actual (**riesgo**: cancela por orderId sin dueño) |
| `/api/v1/payments/culqi/charge` | POST | Solo lo llamaría la UI de tarjeta, que está **inalcanzable** (`*ngIf="false"` en el selector de método, `sumary-order.component.html:32`) |

## C. Confirmaciones del flujo productivo (verificado en código)

1. `OrderController.save` **fuerza `OrderState.PENDING`** y el `userId` del token (`OrderController.java:34-35`).
2. `YapeController.register` guarda `PaymentEntity` con estado `POR_CONFIRMAR` y **no llama a `updateStateById`** → el pedido sigue `PENDING`.
3. `openWhatsApp()` en el frontend solo abre `wa.me` — no ejecuta ninguna llamada HTTP de confirmación.
4. La confirmación es manual: el admin usa `/orders/update/state/order` (protegido `hasRole("ADMIN")`).
5. El selector Tarjeta/Yape del checkout está deshabilitado (`*ngIf="false"`); solo se muestra el panel Yape.

## D. Datos personales / valores quemados detectados (objeto de la Parte A)

| Dato | Ubicaciones |
|---|---|
| WhatsApp `51920097746` / `920097746` | `whatsapp.component.ts:10`, `sumary-order.component.ts:27`, `info-page.component.html:10`, `landing.component.html:120`, `payment-success.component.html:18`, `admin-payments.component.ts:74`, `MailService.java:87`, `InfoPageEntity.java:44,52` |
| Yape `985436488` / `Haydee Hospinal` | `application.properties:49-50`, `YapeController.java:26-29`, `sumary-order.component.ts:25` |
| Correos demo `admin@isablue.pe` / `cliente@isablue.pe` (+claves `admin123`/`cliente123`) | `application.properties:10-13`, `DataInitializer.java:30-33` |
| JWT secret de producción (default quemado) | `application-pdn.properties:2` |
| URL Railway productiva | `application-pdn.properties:22` (default de CORS) |
