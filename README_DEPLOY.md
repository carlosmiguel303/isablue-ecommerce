# Despliegue de ISABLUE

## Arquitectura de producción

- GitHub: repositorio privado y validación automática.
- Frontend Angular: Netlify, Cloudflare Pages o el mismo VPS.
- Backend Spring Boot: VPS o servicio administrado compatible con Java 17.
- Base de datos: MySQL 8 con backups diarios.
- Dominio y SSL: Cloudflare delante del frontend y de `api.tudominio`.

## Variables del backend

```text
SPRING_PROFILES_ACTIVE=pdn
MYSQL_HOST=mysql.example.com
MYSQL_PORT=3306
MYSQL_DATABASE=isablue
MYSQL_USER=isablue_app
MYSQL_PASSWORD=CAMBIAR
JWT_SECRET=CLAVE_ALEATORIA_DE_MAS_DE_64_CARACTERES
CORS_ALLOWED_ORIGINS=https://tudominio.com
FRONTEND_URL=https://tudominio.com
BACKEND_BASE_URL=https://api.tudominio.com
DEMO_DATA_ENABLED=false
UPLOAD_DIR=/app/uploads
```

También puede utilizarse una sola variable `DATABASE_URL` con una conexión JDBC MySQL completa.

## Variable del frontend

```text
API_URL=https://api.tudominio.com/api/v1
```

## Publicación desde GitHub

El workflow `.github/workflows/ci.yml` valida ambos proyectos en cada cambio. El proveedor de hosting puede conectarse a la rama `main` para desplegar después de una validación exitosa.

## Antes de cobrar ventas reales

1. Reemplazar PayPal Sandbox por Culqi o Mercado Pago.
2. Guardar el identificador de la transacción y validar webhooks firmados.
3. Desactivar usuarios y datos de demostración.
4. Configurar backups de MySQL y restaurar uno de prueba.
5. Configurar almacenamiento persistente para imágenes.
6. Añadir políticas, privacidad, devoluciones y Libro de Reclamaciones.
7. Probar compra aprobada, rechazada, duplicada y abandonada.

Nunca se deben guardar números de tarjeta ni CVV en MySQL.
