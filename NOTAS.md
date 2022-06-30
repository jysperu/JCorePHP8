
/*
 * NOTAS
 *
 * 04.	Recorrer todos los directorios de prioridad 1 a 999 incluyendo los que se agreguen en tiempo real de prioridad menor a mayor
 * |-	01.		Se lee el archivo `/init.php`
 * |-	02.		Se ejecuta el HOOK `JCore/Loaded/Init`
 *
 * 05.	Recorrer todos los directorios de prioridad 999 a 1
 * |-	01.		Se leen los archivos `/configs/functions/*.php`
 * |	|		Al ser la prioridad mas alta leído primero sobreescribe las funciones de las prioridades mas bajas
 * |
 * |-	02.		Se ejecuta el HOOK `JCore/Loaded/Functions`
 *
 * 06.	Recorrer todos los directorios de prioridad 1 a 999
 * |-	01.		Se lee el archivo `/vendor/autoload.php`
 * |	|		Al ser la prioridad mas baja leído primero permite que las librerías duplicadas se usen las de 
 * |
 * |-	02.		Se ejecuta el HOOK `JCore/Loaded/Vendors`
 * |
 * |-	03.		Se lee el archivo `/configs/config-dist.php` si existe; caso contrario,
 * |	|		Se lee el archivo `/configs/config.php`
 * |	|		Al ser la prioridad mas baja leído primero permite que las configuraciones sean sobreescritas por las prioridades mas altas
 * |
 * |-	04.		Se ejecuta el HOOK `JCore/Loaded/Config`
 *
 * la redirección SSL y/o WWW debe ser antes de procesar el request pero despues de leer el errorcontrol (AutoRedirections -> a veces requiere de si el usuario esta o no logueado)
 * la identificación del idioma es explícita al momento de requerir alguna función que necesite de la librería LANG
 *
 * por eficiencia
 * deberia cachear todos las rutas de archivos (/init.php, /configs/functions/*.php)
 * debería identificarse todos los directorios por defecto y leer la version de todos los directorios si alguno ha cambiado o no existe el archivo para la eficiencia entonces se restaura el archivo
 * en bixon en algunos casos puede que se requieran diferentes módulos y estos módulos se encuentran dentro del APPPATH en un archivo X y es este archivo que 
 *
 * los modulos CacheManager, SesionManager y HookManager no deberían leerse hasta que se requiera
 * |-	02.		Lee el módulo `HookManager`
 * | 	|		Facilita el uso de los hooks
 * |
 * |- 	03.		Lee el módulo `SesionManager`
 * | 	|		Permite administrar los datos de sesión mas fácil y/o seguro (@todo)
 * |
 * |-	04.		Lee el módulo `CacheManager`
 *
 *
 * en bixon es posible la personalización de las tablas así que debe considerarse un hook al momento de compilar todo en el performance para modificar las pantallas HTML y/o procesos en los requests
 *
 * obtejo tal  -> asociar objeto a base datos tal
 *
 * los $_POST, $_GET y php://input se limpian cuando son requeridos pero se pueden bloquear intentos de hackeo
 *
 *
 * Pueden haber casos en el que se requiera compilar mas directorios que los autoloads
 */