# Actualización del carrusel y del proceso de compra

Esta actualización no cambia tablas ni requiere importar SQL. Conserva la base actual, `.env`, las imágenes subidas y los pedidos.

## Publicación en DonWeb

La instalación existente usa dos carpetas hermanas:

- `isablue`: aplicación privada.
- `public_html`: archivos públicos y el punto de entrada adaptado.

Antes de reemplazar un archivo, guarda su versión del servidor fuera de `public_html`. Compara los archivos del servidor con la versión anterior: conserva cualquier personalización que ya exista.

Publica primero `app/Services/StoreReadiness.php`, luego los controladores y servicios modificados, los recursos de `public/assets`, las vistas y finalmente `routes/web.php`. Los archivos de `public/assets` se copian a `public_html/assets`.

No reemplaces `public_html/index.php`, `.env`, `storage`, `vendor` ni `public_html/uploads`. No importes `schema.sql` ni `seed.sql` en una base que ya está funcionando.

## Administración de promociones

En `/admin/banners` puedes cargar hasta cinco imágenes por operación, editar cada título/enlace, cambiar el orden, ocultar o eliminar diapositivas. Usa imágenes JPG, PNG o WebP, de hasta 5 MB. Para campañas completas se recomienda 1920 × 640 px y textos grandes, con la misma proporción en todas las imágenes. Si el hosting limita el tamaño total de una carga, sube una imagen por vez o ajusta sus límites de carga desde el panel PHP.

La presentación «Imagen completa» muestra el diseño sin recortarlo. Las otras presentaciones conservan los textos y colores configurables.

## Antes de recibir pedidos reales

En `/admin/configuracion` se muestran los datos pendientes: número y titular de Yape, entregas y productos verificados. Actualiza las existencias en Inventario y verifica precios y edades en cada producto. Desmarca los datos de prueba solo después de comprobarlos. Desactiva el modo de prueba cuando termines.

Los pedidos reales se bloquean si faltan los datos de Yape o si contienen productos/entregas de prueba. Los pedidos históricos de prueba mantienen su etiqueta para no mezclarse con las ventas.

El pago por Yape se valida manualmente en el panel: subir una captura no confirma el cobro. El stock se descuenta al confirmar el Yape y puede agotarse mientras un pedido está pendiente. Revisa el pago recibido y el inventario antes de confirmar cada pedido.

Con `MAIL_MAILER=log` no salen correos. El panel muestra esta condición; los pedidos siguen disponibles en Administración y Mi cuenta. Configura el correo SMTP privado en el `.env` del servidor para enviar avisos por email. No publiques sus credenciales.
