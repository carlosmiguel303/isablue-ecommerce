# IsaBlue — tienda Laravel

Esta es la versión actual de IsaBlue, desarrollada con Laravel 12, PHP 8.4 y MySQL 8. Reemplaza el proyecto anterior Angular/Java; su historial sigue disponible en Git.

## Contenido

- `app/`, `resources/`, `routes/`, `public/`: tienda, administración y recursos visuales.
- `database/schema.sql`: estructura de las 22 tablas, con índices y relaciones, sin datos.
- `database/seed.sql`: catálogo público inicial y configuración seleccionada. No contiene clientes, usuarios, pedidos, pagos ni contraseñas. Conserva las etiquetas de los datos de demostración.
- `.env.example`: plantilla sin secretos. El `.env` real nunca se versiona.
- [Guía de DonWeb](docs/DONWEB.md): base de datos, configuración e instalación.

## Instalación

1. Usa PHP 8.4 o superior compatible con Composer y MySQL 8.
2. Ejecuta `composer install --no-dev --optimize-autoloader`.
3. Copia `.env.example` a `.env` y completa los datos privados del servidor.
4. Ejecuta `php artisan key:generate` una sola vez en la instalación nueva.
5. En una base vacía, importa `database/schema.sql` y después `database/seed.sql`.
6. Ejecuta `php artisan isablue:admin` para crear el administrador de forma interactiva.
7. Apunta la raíz pública del dominio a `public/` y permite escritura en `storage/` y `bootstrap/cache/`.
8. Ejecuta `php artisan config:cache` y `php artisan view:cache`.

No ejecutes `migrate:fresh`, `db:wipe` ni importes el esquema encima de una base existente. El seed incluye el historial de las migraciones ya aplicadas: para futuras versiones usa `php artisan migrate --force`, después de una copia de seguridad.

Las dependencias de `vendor/` se instalan con Composer y se fijan mediante `composer.lock`. Logs, sesiones, cachés, comprobantes de pago y archivos privados se excluyen del repositorio. Los datos reales y las cuentas existentes de la instalación local no se migran mediante este seed público.

## Pruebas

Instala las dependencias de desarrollo con `composer install`. Usa una instancia MySQL de pruebas y una base desechable llamada `isablue_2026_test`; las pruebas recrean sus tablas. Completa sus credenciales en `.env.testing`, genera una clave de prueba y ejecuta `php artisan test`. Nunca apuntes las pruebas a la base de la tienda.
