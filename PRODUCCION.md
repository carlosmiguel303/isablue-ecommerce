# Isablue Ecommerce — Guía de ejecución y paso a producción

## 1. Cómo correr el proyecto en local (modo prueba / sandbox)

### Backend (Java 17 + Spring Boot, base de datos H2 en archivo)
```bash
cd backend
# Compilar
JAVA_HOME="C:/Program Files/Java/jdk-17" mvn -DskipTests clean package
# Ejecutar (perfil local = H2, sin instalar MySQL)
SPRING_PROFILES_ACTIVE=local "C:/Program Files/Java/jdk-17/bin/java.exe" -jar target/backend-0.0.1-SNAPSHOT.jar
```
Queda en `http://localhost:8085`. Siembra datos demo (usuarios, categorías y productos).

### Frontend (Angular 16)
```bash
cd front
npm install   # solo la primera vez
npm start
```
Queda en `http://localhost:4200` (usar `localhost`, no `127.0.0.1`).

### Cuentas demo
| Rol | Correo | Contraseña |
|-----|--------|-----------|
| Administrador | admin@isablue.pe | admin123 |
| Cliente | cliente@isablue.pe | cliente123 |

---

## 2. Estado actual (qué funciona hoy, en sandbox)

- ✅ Login real contra el backend (admin y cliente), con JWT.
- ✅ Catálogo, buscador, carrito y detalle de producto.
- ✅ Carrusel de promociones administrable por el admin.
- ✅ Checkout con formulario de tarjeta → cobro **simulado** (sin dinero real).
- ✅ La orden se guarda como pagada y se registra el pago.
- ✅ El administrador ve **quién pagó** (menú → *Pedidos y pagos*), con monto y N.° de boleta.
- ✅ Se genera una **boleta interna** (serie B001, correlativo) por cada pago.

> En modo prueba el backend **simula** el cargo cuando no hay llaves. Todo el flujo
> (comprar → pagar → boleta → panel del admin) es demostrable de inmediato.

---

## 3. Activar COBRO REAL con tarjeta (Culqi)

**Requisito del cliente (Isablue):** cuenta comercial en Culqi (con RUC) → llaves `pk_...` y `sk_...`.

1. Definir variables de entorno del backend:
   ```
   CULQI_SECRET_KEY=sk_test_xxx   (o sk_live_xxx en producción)
   CULQI_PUBLIC_KEY=pk_test_xxx
   ```
2. **Paso pendiente de frontend:** integrar **Culqi.js** en el checkout para tokenizar la
   tarjeta (hoy el checkout envía `token: null` = modo simulado). Con la llave pública se
   carga `https://checkout.culqi.com/js/v4`, se obtiene el `token` de la tarjeta y se envía
   a `POST /api/v1/payments/culqi/charge`. El backend ya está listo para cobrar con ese token.

Con la llave secreta configurada, el backend llama a `https://api.culqi.com/v2/charges` y
cobra de verdad. Se prueba primero con llaves `test` y la tarjeta de prueba de Culqi.

---

## 4. Activar BOLETA ELECTRÓNICA real ante SUNAT (Nubefact)

**Requisitos del cliente (Isablue):** RUC, afiliación a facturación electrónica en SUNAT,
certificado digital y cuenta en Nubefact (OSE autorizado) → URL de API y token.

1. Definir variables de entorno del backend:
   ```
   NUBEFACT_URL=https://api.nubefact.com/api/v1/xxxxxxxx
   NUBEFACT_TOKEN=tu_token_de_nubefact
   ```
2. Con eso configurado, al pagar el backend emite la boleta vía Nubefact y guarda el
   **enlace al PDF oficial** en el pago (visible en la pantalla de éxito y en el panel admin).

> Nota tributaria: los ítems se envían como *exonerados* por defecto (juguetes suelen tener
> IGV; ajustar `tipo_de_igv`/`porcentaje_de_igv` en `NubefactService` según el régimen real
> de Isablue y su contador).

---

## 5. Pasar la base de datos a MySQL (producción)

El proyecto ya trae el perfil `dev` con MySQL. Crear la BD y el usuario:
```sql
CREATE DATABASE isablue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'isablue'@'%' IDENTIFIED BY 'una_clave_segura';
GRANT ALL PRIVILEGES ON isablue.* TO 'isablue'@'%';
FLUSH PRIVILEGES;
```
Ejecutar con `SPRING_PROFILES_ACTIVE=dev` y variables `DATABASE_URL`, `DATABASE_USERNAME`,
`DATABASE_PASSWORD`. Hibernate crea las tablas automáticamente (`ddl-auto=update`).

---

## 6. Checklist para salir a producción
- [ ] Llaves `live` de Culqi + Culqi.js integrado en el checkout.
- [ ] Nubefact con RUC + certificado + token; probar una boleta real.
- [ ] MySQL en el servidor con credenciales seguras.
- [ ] `JWT_SECRET` propio y fuerte (variable de entorno).
- [ ] Dominio + HTTPS, y `CORS_ALLOWED_ORIGINS`/`FRONTEND_URL` con el dominio real.
- [ ] `environment.prod.ts` del frontend apuntando a la API pública.
- [ ] Fotos reales de los productos cargadas desde el panel de administración.
