<?php

use Modelo\Structure;

abstract class BaseStructure extends JArray implements Structure
{
	protected $_options;

	public function __construct (array $options = [])
	{
		$options = array_merge([
			'title'              => '',
			'description'        => '',
			'canonical'          => '',
			'content_title'      => '',
			'content_subtitle'   => '',
			'content_breadcrumb' => '',
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
	 * getTitle ()
	 */
	public function getTitle ():string
	{
		return $this -> title;
	}

	/**
	 * getDescription ()
	 */
	public function getDescription ():string
	{
		return $this -> description;
	}

	/**
	 * getCanonical ()
	 */
	public function getCanonical ():string
	{
		return $this -> canonical;
	}

	/**
	 * getFaviconPart ()
	 * Contenido HTML la cual aloja la parte del FAVICON
	 * @return array|string|callable
	 */
	public function getFaviconPart ()
	{
		return [];
	}

	/**
	 * getJsonLDPart ()
	 * Contenido JSON la cual aloja la parte del JSONLD
	 * @return array|string|callable
	 */
	public function getJsonLDPart ()
	{
		return [];
	}

	/**
	 * getPreloadPart ()
	 * Contenido HTML la cual aloja los preloads
	 * @return array|string|callable
	 */
	public function getPreloadPart ()
	{
		return [];
	}

	/**
	 * getHeadScriptPart ()
	 * Contenido JS la cual se encontrará dentro de un <SCRIPT> en el tag <HEAD>
	 */
	public function getHeadScriptPart ()
	{
		return '';
	}

	/**
	 * getBodyScriptPart ()
	 * Contenido JS la cual se encontrará dentro de un <SCRIPT> en el tag <HEAD>
	 */
	public function getBodyScriptPart ()
	{
		return '';
	}


	/**
	 * getBodyBeforePart_before ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 */
	public function getBodyBeforePart_before ()
	{
		return '';
	}

	/**
	 * getBodyBeforePart ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 * Aquí se pueden alojar los NAV, HEADER, ...
	 */
	public function getBodyBeforePart ()
	{
		return '';
	}

	/**
	 * getBodyBeforePart_after ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 */
	public function getBodyBeforePart_after ()
	{
		return '';
	}


	/**
	 * getBodyContentPart_before ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyContentPart_before ()
	{
		return '';
	}


	/**
	 * getBodyContentPart_after ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyContentPart_after ()
	{
		return '';
	}


	/**
	 * getBodyAfterPart_before ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyAfterPart_before ()
	{
		return '';
	}

	/**
	 * getBodyAfterPart ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 * Aquí se pueden alojar los FOOTER, COPYRIGHT, ...
	 */
	public function getBodyAfterPart ()
	{
		return '';
	}

	/**
	 * getBodyAfterPart_after ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 */
	public function getBodyAfterPart_after ()
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
		return $attrs;
	}

	/**
	 * filterHeadTagAttrs ()
	 * Función que permite filtrar los atributos del tag <HEAD>
	 * PASO 04
	 */
	public function filterHeadTagAttrs (array $attrs):array
	{
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
		return $meta;
	}
}