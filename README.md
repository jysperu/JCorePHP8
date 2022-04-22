# JCorePHP8
Núcleo/Framework ligero para PHP8+

> El núcleo trabaja utilizando [URLs amigables](https://es.wikipedia.org/wiki/URL_sem%C3%A1ntica)

## Modos de Instalación

#### — Via Composer

*Paso 01.-* Requerir la librería

```bin
composer require jysperu/JCorePHP8
```

*Paso 02.-* Añadir el archivo `index.php` dentro de la carpeta pública

```php
<?php
define ('HOMEPATH', __DIR__);
require_once 'vendor/autoload.php';
return JCore :: process();
```

#### — Descargando el pre-compilado

*Paso 01.-* [Descargar la librería](https://github.com/jysperu/JCorePHP8/releases/latest/) y descomprimirlo en la carpeta pública

*Paso 02.-* Añadir el archivo `index.php` dentro de la carpeta pública

```php
<?php
define ('HOMEPATH', __DIR__);
return require_once HOMEPATH . '/process.php';
```

*Paso 03.-* Requerir dependencias del núcleo utilizando composer (`composer install`)

## Configuración del Servidor

#### — Apache

Modificar el archivo `.htaccess` de la carpeta que contiene la aplicación.

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    Rewriterule . index.php [L]
</IfModule>
```

#### — NGinx

Modificar el archivo de configuración `/etc/nginx/conf.d/default.conf` o el bloque `server` correspondiente.

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```
