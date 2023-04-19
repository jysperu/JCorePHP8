# JCorePHP8

Núcleo/Framework ligero para PHP8+

> El núcleo trabaja utilizando [URLs amigables](https://es.wikipedia.org/wiki/URL_sem%C3%A1ntica)

## Modos de Instalación

#### — Via Composer

*Paso 01.-* Requerir la librería

```bin
composer require jysperu/JCorePHP8
```

*Paso 02.-* Añadir el archivo `index.php`

```php
<?php
define ('APPPATH', __DIR__);
require_once 'vendor/autoload.php';
return APP :: process ();
```

## Configuración del Servidor para las URLs amigables

#### — Apache

Añadir o modificar el archivo `.htaccess` de la carpeta que contiene la aplicación:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    Rewriterule . index.php [L]
</IfModule>
```

#### — NGinx

En el archivo de configuración correspondiente, dentro del bloque `server`, incluir lo siguiente:

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```
