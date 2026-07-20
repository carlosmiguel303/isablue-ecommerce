# ISABLUE Ecommerce

Primera versión de la tienda de juguetes ISABLUE.

## Tecnología

- Angular 16 para la tienda y el panel administrativo.
- Java 17, Spring Boot 3 y Maven para la API.
- MySQL 8 para productos, clientes y pedidos.
- JWT para autenticación.
- Docker Compose para levantar MySQL en desarrollo.

## Ejecución local

1. Levantar MySQL con `docker compose up -d`.
2. Iniciar la API desde `backend` con `mvn spring-boot:run`.
3. Iniciar Angular desde `front` con `npm start`.
4. Abrir `http://localhost:4200`.

La tienda puede mostrar productos de demostración aunque la API todavía no esté iniciada.

## Pagos

La base heredada conserva PayPal Sandbox únicamente como demostración. Antes de cobrar ventas reales se reemplazará por Culqi o Mercado Pago, con credenciales de ISABLUE y webhooks verificados. La aplicación no debe almacenar números de tarjeta.

## Imágenes

Las imágenes actuales son referenciales. Las fotografías oficiales de la fanpage deben incorporarse como archivos optimizados y autorizados, no enlazarse directamente desde Facebook.
