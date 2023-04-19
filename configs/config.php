<?php

/**
 * default_charset
 * Charset por defecto
 *
 * @global
 */
$config['default_charset'] = 'UTF-8';

/**
 * default_timezone
 * TimeZone por defecto
 *
 * @global
 */
$config['default_timezone'] = 'America/Lima';

/**
 * default_lang
 * Idioma por defecto
 *
 * @global
 */
$config['default_lang'] = 'es-PE';

/**
 * available_languages
 * Idiomas disponibles
 *
 * @global
 */
$config['available_languages'] = [
	'es-PE',
];

/**
 * path_before
 * Parte de la ruta: /public_html({/path_before)/HOMEPATH/index.php
 * Sirve para identificar si la aplicación se ejecuta en una subcarpeta
 * o desde la raiz, con ello podemos añadir esos subdirectorios /{...} en el enlace
 * Incluír el slash (/) inicial si es el caso
 *
 * @global
 */
$config['path_before'] = null;

/**
 * session_name
 *
 * @global
 */
$config['session_name'] = 'jca_by_jcore';

/**
 * session_path
 *
 * @global
 */
$config['session_path'] = ROOTPATH . DS . '_data' . DS . 'sesiones';

/**
 * cache_lifetime
 *
 * @global
 */
$config['cache_lifetime'] = 60 * 60 * 24 * 7 * 4 * 12; ## 01 año

/**
 * cache_path
 *
 * @global
 */
$config['cache_path'] = ROOTPATH . DS . '_data' . DS . 'cache';