# Actualización segura en Hostinger VPS

Esta actualización añade registro público, grupos aislados y SMTP por empresa.
No borra datos existentes, pero necesita ejecutar las migraciones antes de usar
la aplicación.

## Aplicar la actualización

1. Sube y descomprime el proyecto actualizado sustituyendo el código de la
   aplicación. Conserva el archivo `.env` actual del servidor; el paquete no lo
   incluye deliberadamente.
2. Desde la carpeta que contiene `docker-compose.yml`, ejecuta:

```bash
docker compose run --rm migrate
docker compose up -d --build --force-recreate web php cron
```

La primera orden debe mostrar que ejecuta `021_multitenant_grupos_y_auth.sql`
y `022_indices_grupos.sql` si todavía no se habían aplicado.

## Comprobar el aislamiento de grupos

Entra en MySQL dentro del VPS:

```bash
docker compose exec db mysql -uroot -p"$DB_PASSWORD" "$DB_NAME"
```

Ejecuta estas consultas:

```sql
SELECT id_usuario, nombre, rol, id_grupo FROM usuarios ORDER BY id_usuario;
SELECT id_grupo, nombre FROM grupos ORDER BY id_grupo;
SELECT archivo FROM _migraciones WHERE archivo IN (
  '021_multitenant_grupos_y_auth.sql',
  '022_indices_grupos.sql'
);
```

Cada administrador creado mediante el registro público debe tener un `id_grupo`
propio, distinto de `1`. Si las migraciones están presentes y el administrador
nuevo comparte `id_grupo = 1` con las cuentas antiguas, no uses esa cuenta para
probar: crea otra mediante el formulario de registro actualizado.

## Si siguen apareciendo todos los usuarios

Comprueba qué código está leyendo el contenedor PHP:

```bash
docker compose exec php grep -n "WHERE id_grupo = ?" /usr/share/nginx/html/public/api/usuarios.php
```

Debe devolver al menos una línea. Si no devuelve nada, el VPS conserva un
archivo `usuarios.php` antiguo: vuelve a subir el paquete, sobrescribe ese
archivo y repite los dos comandos de actualización.

## Correos

- `.env` del VPS: solo registro, verificación, recuperación y 2FA.
- Panel de cada empresa, Configuración SMTP: facturas, presupuestos y correos
  comerciales de ese grupo. No crees un `.env` por empresa.

