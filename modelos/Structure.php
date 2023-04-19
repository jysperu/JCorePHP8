<?php
namespace Modelo;

/**
 * Structure
 *
 * Formato HTML
 * {{APP :: getResponseDoctype}}
 * <html {{filterHtmlTagAttrs}}>
 * <head {{filterHeadTagAttrs}}>
 *     <title>{{getTitle}}</title>
 *     {{filterHeadMetaName     @meta_name}}
 *     {{filterHeadMetaProperty @meta_property}}
 *     {{filterHeadMetaOthers   @meta_x}}
 *     {{getFaviconPart}}
 *     {{getJsonLDPart}}
 *
 *     {{APP :: getResponseAssetsCSS :: head (uri & inline) @minimized_cache}}
 *     {{APP :: getResponseAssetsJS :: head  (uri & inline) @minimized_cache}}
 *     {{getHeadScriptPart}}
 * </head>
 * <body {{filterBodyTagAttrs}}>
 *     {{getBodyBeforePart_before}}
 *     {{getBodyBeforePart}}
 *     {{getBodyBeforePart_after}}
 *     {{APP :: getResponseResultAsHtml}}
 *     {{getBodyContentPart_before}}
 *     {{APP :: getResponseContentNOJSON @ bodyContentPart}}
 *     {{getBodyContentPart_after}}
 *     {{getBodyAfterPart_before}}
 *     {{getBodyAfterPart}}
 *     {{getBodyAfterPart_after}}
 *
 *     {{APP :: getResponseAssetsCSS :: body (uri & inline) @minimized_cache}}
 *     {{APP :: getResponseAssetsJS :: body  (uri & inline) @minimized_cache}}
 *     {{getBodyScriptPart}}
 *     {{APP :: getResponseContentNOJSON @detected_script}}
 * </body>
 * </html>
 *
 * Formato Body
 *     {{APP :: getResponseResultAsHtml}}
 *     {{getBodyContentPart_before}}
 *     {{APP :: getResponseContentNOJSON}}
 *     {{getBodyContentPart_after}}
 *     {{APP :: getResponseContentNOJSON @detected_script}}
 * 
 * Al momento de compila el buffer de salida para un ResponseType = 'html':
 *
 * $structure -> init();                   ## Se pueden cambiar ajustes justo antes de empezar a formatear el buffer de salida
 * $structure -> loadAssets();             ## Se puede añadir recién los assets si aún no se habían añadido
 * APP :: getResponseContentNOJSON();      ## El APP compilará todos los contenidos del body ...................................... (Si algún item es string, no tiene múltiples líneas y hay menos de 32 caracteres o es callable intentará ejecutar la función y un callback)
 * $structure -> getBodyBeforePart_before(); ## Devuelve las líneas html de body_before_part ........................................ (Si es string, no tiene múltiples líneas y hay menos de 32 caracteres o es callable intentará ejecutar la función y un callback)
 * $structure -> getFaviconPart();         ## Devuelve las líneas html del favicon ................................................ (Si es string, no tiene múltiples líneas y hay menos de 32 caracteres o es callable intentará ejecutar la función y un callback)
 * $structure -> getJsonLDPart();          ## Devuelve las líneas que estarán dentro del json-ld .................................. (Si es string, no tiene múltiples líneas y hay menos de 32 caracteres o es callable intentará ejecutar la función y un callback)
 * $structure -> getHeadScriptPart();      ## Devuelve las líneas que estarán dentro del <script> en el <head> .................... (Si es string, no tiene múltiples líneas y hay menos de 32 caracteres o es callable intentará ejecutar la función y un callback)
 */
interface Structure 
{
	/**
	 * init ()
	 * Permite realizar alguna acción previo a formatear el buffer
	 * PASO 01
	 */
	public function init():void;

	/**
	 * loadAssets ()
	 * Permite cargar los recursos CSS y JS para el buffer
	 * PASO 02
	 */
	public function loadAssets():void;



	/**
	 * getTitle ()
	 */
	public function getTitle ():string;

	/**
	 * getDescription ()
	 */
	public function getDescription ():string;

	/**
	 * getCanonical ()
	 */
	public function getCanonical ():string;

	/**
	 * getFaviconPart ()
	 * Contenido HTML la cual aloja la parte del FAVICON
	 * @return array|string|callable
	 */
	public function getFaviconPart ();

	/**
	 * getJsonLDPart ()
	 * Contenido JSON la cual aloja la parte del JSONLD
	 * @return array|string|callable
	 */
	public function getJsonLDPart ();

	/**
	 * getPreloadPart ()
	 * Contenido HTML la cual aloja los preloads
	 * @return array|string|callable
	 */
	public function getPreloadPart ();

	/**
	 * getHeadScriptPart ()
	 * Contenido JS la cual se encontrará dentro de un <SCRIPT> en el tag <HEAD>
	 */
	public function getHeadScriptPart ();

	/**
	 * getBodyScriptPart ()
	 * Contenido JS la cual se encontrará dentro de un <SCRIPT> en el tag <HEAD>
	 */
	public function getBodyScriptPart ();


	/**
	 * getBodyBeforePart_before ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 */
	public function getBodyBeforePart_before ();

	/**
	 * getBodyBeforePart ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 * Aquí se pueden alojar los NAV, HEADER, ...
	 */
	public function getBodyBeforePart ();

	/**
	 * getBodyBeforePart_after ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 */
	public function getBodyBeforePart_after ();


	/**
	 * getBodyContentPart_before ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyContentPart_before ();


	/**
	 * getBodyContentPart_after ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyContentPart_after ();


	/**
	 * getBodyAfterPart_before ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyAfterPart_before ();

	/**
	 * getBodyAfterPart ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 * Aquí se pueden alojar los FOOTER, COPYRIGHT, ...
	 */
	public function getBodyAfterPart ();

	/**
	 * getBodyAfterPart_after ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyAfterPart_after ();



	/**
	 * filterHtmlTagAttrs ()
	 * Función que permite filtrar los atributos del tag <HTML>
	 * PASO 03
	 */
	public function filterHtmlTagAttrs (array $attrs):array;

	/**
	 * filterHeadTagAttrs ()
	 * Función que permite filtrar los atributos del tag <HEAD>
	 * PASO 04
	 */
	public function filterHeadTagAttrs (array $attrs):array;

	/**
	 * filterBodyTagAttrs ()
	 * Función que permite filtrar los atributos del tag <BODY>
	 * PASO 07
	 */
	public function filterBodyTagAttrs (array $attrs):array;



	/**
	 * filterHeadMetaName ()
	 * Función que permite filtrar los <meta name="">
	 * PASO 05
	 */
	public function filterHeadMetaName (array $meta):array;

	/**
	 * filterHeadMetaProperty ()
	 * Función que permite filtrar los <meta property="">
	 * PASO 06
	 */
	public function filterHeadMetaProperty (array $meta):array;

	/**
	 * filterHeadMetaOthers ()
	 * Función que permite filtrar los <meta x="">
	 * PASO 06
	 */
	public function filterHeadMetaOthers (array $meta):array;
}