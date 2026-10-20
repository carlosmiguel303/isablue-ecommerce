# IsaBlue en DonWeb

## Antes de instalar

La aplicación requiere PHP 8.4 y MySQL 8, además de Composer y las extensiones habituales de Laravel, incluyendo PDO MySQL, mbstring, XML, cURL, fileinfo y OpenSSL. Confirma la versión disponible en tu plan. El esquema se exportó desde MySQL 8.0.43; no se ha validado en MariaDB.

GitHub almacena el código y los archivos SQL. Conectar Git no crea ni importa la base de datos automáticamente.

## 1. Crear la base y el usuario

En el panel Ferozo entra a **Base de datos → MySQL → Crear base de datos**. Puedes solicitar `isablue_2026`; guarda el nombre completo que asigne el panel, con su prefijo si lo añade.

En **Usuarios de base de datos → Crear usuario**, asigna un usuario exclusivo a esa base y define una contraseña fuerte. Conserva las credenciales en tu gestor de contraseñas.

También puedes crearla desde **Mi Cuenta → Mis servicios → tu hosting → Gestionar → Base de datos → Crear**. Consulta **Datos de la base / Cadena de conexión** para obtener el host, puerto, nombre y usuario exactos. No supongas que el usuario es `root`.

Referencia: [guía oficial de bases de datos de DonWeb](https://soporte.donweb.com/es/articles/15971873-web-hosting-y-wordpress-hosting-gestionar-bases-de-datos).

## 2. Importar SQL

1. Abre phpMyAdmin desde el panel y selecciona la base nueva y vacía.
2. En **Importar**, selecciona `database/schema.sql`, formato SQL y codificación UTF-8. Ejecuta la importación.
3. En la misma base, importa `database/seed.sql`.
4. Comprueba las 22 tablas y el catálogo: 12 categorías, 5 productos activos, 5 imágenes de productos y 3 banners. Hay 2 métodos de entrega de demostración.
5. Si falla una importación, revisa el error antes de continuar. Estos archivos están destinados a una instalación nueva; no los repitas encima de datos existentes.

El seed contiene las 7 migraciones ya aplicadas y 15 ajustes seleccionados. No incluye usuarios, hashes de contraseñas, clientes, pedidos, pagos, sesiones, movimientos de stock ni registros históricos. Tampoco publica datos de contacto, número/titular/QR de Yape: configura esos valores desde el administrador privado.

Los productos y envíos que estaban marcados como demostración conservan esa marca. Revisa stock, edades, precios y tarifas antes de admitir pedidos reales.

## 3. Configurar el servidor

Copia `.env.example` a `.env` **en el servidor**, en la raíz privada del proyecto. Completa:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TU_DOMINIO
APP_KEY=
DB_CONNECTION=mysql
DB_HOST=HOST_INDICADO_POR_DONWEB
DB_PORT=3306
DB_DATABASE=NOMBRE_COMPLETO_ASIGNADO
DB_USERNAME=USUARIO_ASIGNADO
DB_PASSWORD="CONTRASENA_PRIVADA"
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

`DB_HOST` es el servidor de base de datos indicado por DonWeb, no necesariamente el dominio de tu web. Usa `3306` solo si el panel no indica otro puerto. Conserva las comillas de la contraseña si contiene espacios o caracteres especiales; respeta la sintaxis dotenv.

Instala las dependencias desde la raíz del proyecto:

```sh
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan isablue:admin
php artisan config:cache
php artisan view:cache
```

`key:generate` es para esta instalación nueva: conserva esa clave en posteriores actualizaciones. `isablue:admin` pide nombre, correo y contraseña oculta; no hay una cuenta ni contraseña predeterminada. Rechaza correos existentes y no cambia cuentas locales.

Configura correo SMTP mediante `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` y `MAIL_FROM_ADDRESS`. La plantilla usa `MAIL_MAILER=log` hasta que lo configures; en ese modo no envía correos.

Si tu plan no ofrece terminal/SSH o Composer, solicita a DonWeb el mecanismo admitido antes de desplegar Laravel. El código sin `vendor/` instalado no podrá arrancar.

## 4. Integrar el repositorio cuando vayas a desplegar

- Repositorio: `https://github.com/carlosmiguel303/isablue-ecommerce.git`
- Rama: `main`.
- Directorio de clonación: una carpeta nueva y vacía, preferiblemente fuera de la raíz pública.
- Raíz pública del dominio: la subcarpeta `public/` del proyecto.

Nunca expongas toda la raíz del repositorio como web: allí están `.env`, dependencias y los SQL. Si DonWeb no permite cambiar la raíz pública a `public/`, solicita la configuración compatible antes de publicar.

Otorga escritura al usuario de PHP en `storage/` y `bootstrap/cache/`, sin permisos globales 777. No publiques esos directorios como recursos estáticos.

En **Mi sitio web → GIT**, configura el repositorio y la rama. Si el repositorio es privado, usa el procedimiento SSH de DonWeb. Al actualizar el código, el botón **Desplegar** trae los cambios; la importación MySQL se hace por separado.

Referencia: [integración Git oficial de DonWeb](https://soporte.donweb.com/es/articles/15971370-web-hosting-y-wordpress-hosting-integrar-un-repositorio-git-con-tu-hosting).

## Comprobaciones antes de abrir ventas

Verifica HTTPS, portada, catálogo, acceso del nuevo administrador, correo, stock real, métodos de entrega y configuración de Yape. Prueba el flujo de pedido con datos ficticios. Configura copias de seguridad privadas de la base y de los archivos subidos; nunca las subas a GitHub.
