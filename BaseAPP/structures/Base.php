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
	{}

	/**
	 * getHeadScriptPart ()
	 * Contenido JS la cual se encontrará dentro de un <SCRIPT> en el tag <HEAD>
	 */
	public function getHeadScriptPart ():string
	{}

	/**
	 * getBodyBeforePart ()
	 * Contenido HTML la cual se añadirá antes del contenido buffer
	 * Aquí se pueden alojar los NAV, HEADER, ...
	 */
	public function getBodyBeforePart ():string
	{}

	/**
	 * getBodyAfterPart ()
	 * Contenido HTML la cual se añadirá después del contenido buffer
	 * Aquí se pueden alojar los FOOTER, COPYRIGHT, ...
	 */
	public function getBodyAfterPart ():string
	{}

	/**
	 * filterHtmlTagAttrs ()
	 * Función que permite filtrar los atributos del tag <HTML>
	 * PASO 03
	 */
	public function filterHtmlTagAttrs (array $attrs):array
	{}

	/**
	 * filterBodyTagAttrs ()
	 * Función que permite filtrar los atributos del tag <BODY>
	 * PASO 04
	 */
	public function filterBodyTagAttrs (array $attrs):array
	{}
}