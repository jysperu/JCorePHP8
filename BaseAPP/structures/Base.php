<?php
namespace Structure;

use JArray;
use APP;

class Base extends JArray
{
	protected $_options;

	public function __construct (array $options = [])
	{
		$options = array_merge([
			'title'         => '',
			'content_title' => '',
		], $options);

		$this -> _options = new JArray($options);
		parent :: __construct($this -> _options);

		APP :: setResponseHtmlStructure($this);
	}

	/**
	 * init ()
	 * Permite realizar alguna acción previo a formatear el buffer
	 * PASO 01
	 */
	public function init():void
	{}

	/**
	 * loadAssets ()
	 * Permite cargar los recursos CSS y JS para el buffer
	 * PASO 02
	 */
	public function loadAssets():void
	{}



	/**
	 * getFaviconPart ()
	 * Contenido HTML la cual aloja la parte del FAVICON
	 */
	public function getFaviconPart ():string
	{
		return '';
	}

	/**
	 * getJsonLDPart ()
	 * Contenido JSON la cual aloja la parte del JSONLD
	 */
	public function getJsonLDPart ():array
	{
		return [];
	}

	/**
	 * getHeadScriptPart ()
	 * Contenido JS la cual se encontrará dentro de un <SCRIPT> en el tag <HEAD>
	 */
	public function getHeadScriptPart ():string
	{
		return '';
	}


	/**
	 * getBodyBeforePart_Prev ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 */
	public function getBodyBeforePart_Prev ():string
	{
		return '';
	}

	/**
	 * getBodyBeforePart ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 * Aquí se pueden alojar los NAV, HEADER, ...
	 */
	public function getBodyBeforePart ():string
	{
		return '';
	}

	/**
	 * getBodyBeforePart_Post ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 */
	public function getBodyBeforePart_Post ():string
	{
		return '';
	}


	/**
	 * getBodyContentPart_Prev ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyContentPart_Prev ():string
	{
		return '';
	}


	/**
	 * getBodyContentPart_Post ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyContentPart_Post ():string
	{
		return '';
	}


	/**
	 * getBodyAfterPart_Prev ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyAfterPart_Prev ():string
	{
		return '';
	}

	/**
	 * getBodyAfterPart ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 * Aquí se pueden alojar los FOOTER, COPYRIGHT, ...
	 */
	public function getBodyAfterPart ():string
	{
		return '';
	}

	/**
	 * getBodyAfterPart_Post ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyAfterPart_Post ():string
	{
		return '';
	}



	/**
	 * filterHtmlTagAttrs ()
	 * Función que permite filtrar los atributos del tag <HTML>
	 * PASO 03
	 */
	public function filterHtmlTagAttrs (array $attrs):array
	{
		$attrs['prefix'] = 'og: https://ogp.me/ns#';

		return $attrs;
	}

	/**
	 * filterHeadTagAttrs ()
	 * Función que permite filtrar los atributos del tag <HEAD>
	 * PASO 04
	 */
	public function filterHeadTagAttrs (array $attrs):array
	{
		$attrs['itemscope'] = null;
		$attrs['itemtype']  = 'http://schema.org/WebSite';

		return $attrs;
	}

	/**
	 * filterBodyTagAttrs ()
	 * Función que permite filtrar los atributos del tag <BODY>
	 * PASO 07
	 */
	public function filterBodyTagAttrs (array $attrs):array
	{
		return $attrs;
	}



	/**
	 * filterHeadMetaName ()
	 * Función que permite filtrar los <meta name="">
	 * PASO 05
	 */
	public function filterHeadMetaName (array $meta):array
	{
		$meta['viewport']                     = 'width=device-width, initial-scale=1, shrink-to-fit=no';
		$meta['HandheldFriendly']             = 'True';
		$meta['MobileOptimized']              = '320';
		$meta['mobile-web-app-capable']       = 'yes';
		$meta['apple-mobile-web-app-capable'] = 'yes';
		$meta['robots']                       = 'noindex, nofollow';
		$meta['apple-mobile-web-app-title']   = '{{Response::title}}';
		$meta['application-name']             = APPNAME;
		$meta['msapplication-TileColor']      = '#fff';
		$meta['theme-color']                  = '#f00';
		$meta['generator'] = 'JCorePHP8@2022';

		return $meta;
	}

	/**
	 * filterHeadMetaProperty ()
	 * Función que permite filtrar los <meta property="">
	 * PASO 06
	 */
	public function filterHeadMetaProperty (array $meta):array
	{
		return $meta;
	}

	/**
	 * filterHeadMetaOthers ()
	 * Función que permite filtrar los <meta x="">
	 * PASO 06
	 */
	public function filterHeadMetaOthers (array $meta):array
	{
		isset($meta['http-equiv']) or $meta['http-equiv'] = [];
		$meta['http-equiv']['X-UA-Compatible'] = 'IE=edge,chrome=1';

		return $meta;
	}
}