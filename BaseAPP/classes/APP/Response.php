<?php
namespace APP;

use Structure\Base as BaseStructure;

trait Response
{
	protected static $_response_html_structure;

	public static function setResponseHtmlStructure (BaseStructure $instance):void
	{
		static :: $_response_html_structure = $instance;
	}

	public static function getResponseHtmlStructure ()
	{
		return static :: $_response_html_structure;
	}



	protected static $_response_type = 'html';

	public static function setResponseType (string $type)
	{
		static::$_response_type = mb_strtolower($type);

		switch(static::$_response_type)
		{
			case 'html': case 'body':
				static::setResponseMime('text/html');
				break;
			case 'json': case 'cli':
				static::setResponseMime('application/json');
				break;
		}
	}

	public static function getResponseType ()
	{
		return static::$_response_type;
	}



	protected static $_response_mime;

	public static function setResponseMime (string $mime)
	{
		static::$_response_mime = $mime;
	}

	public static function getResponseMime ()
	{
		return static::$_response_mime;
	}



	protected static $_response_charset;

	public static function setResponseCharset (string $charset)
	{
		static::$_response_charset = $charset;
	}

	public static function getResponseCharset ()
	{
		return static::$_response_charset;
	}



	protected static $_response_lang;

	public static function setResponseLang (string $lang)
	{
		static::$_response_lang = $lang;
	}

	public static function getResponseLang ()
	{
		return static::$_response_lang;
	}



	
	public const doctypes = [
		'xhtml11'           => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "https://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
		'xhtml1-strict'     => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'xhtml1-trans'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'xhtml1-frame'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
		'xhtml-basic11'     => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "https://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
		'html5'             => '<!DOCTYPE html>',
		'html4-strict'      => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "https://www.w3.org/TR/html4/strict.dtd">',
		'html4-trans'       => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "https://www.w3.org/TR/html4/loose.dtd">',
		'html4-frame'       => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "https://www.w3.org/TR/html4/frameset.dtd">',
		'mathml1'           => '<!DOCTYPE math SYSTEM "https://www.w3.org/Math/DTD/mathml1/mathml.dtd">',
		'mathml2'           => '<!DOCTYPE math PUBLIC "-//W3C//DTD MathML 2.0//EN" "https://www.w3.org/Math/DTD/mathml2/mathml2.dtd">',
		'svg10'             => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "https://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">',
		'svg11'             => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">',
		'svg11-basic'       => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Basic//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11-basic.dtd">',
		'svg11-tiny'        => '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1 Tiny//EN" "https://www.w3.org/Graphics/SVG/1.1/DTD/svg11-tiny.dtd">',
		'xhtml-math-svg-xh' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "https://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd">',
		'xhtml-math-svg-sh' => '<!DOCTYPE svg:svg PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "https://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd">',
		'xhtml-rdfa-1'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "https://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">',
		'xhtml-rdfa-2'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.1//EN" "https://www.w3.org/MarkUp/DTD/xhtml-rdfa-2.dtd">'
	];

	protected static $_response_doctype = 'html5';

	public static function setResponseDoctype (string $doctype)
	{
		static::$_response_doctype = $doctype;
	}

	public static function getResponseDoctype ()
	{
		return static::$_response_doctype;
	}



	protected static $_response_canonical;

	public static function setResponseCanonical (string $canonical)
	{
		static::$_response_canonical = $canonical;
	}

	public static function getResponseCanonical ()
	{
		return static::$_response_canonical;
	}



	protected static $_response_history_uri;

	public static function setResponseHistoryURI (string $history_uri)
	{
		static::$_response_history_uri = $history_uri;
	}

	public static function getResponseHistoryURI ()
	{
		return static::$_response_history_uri;
	}

	public static function force_uri (string $history_uri)
	{
		static::setResponseHistoryURI($history_uri);
	}



	protected static $_response_headers = [];

	public static function setResponseHeaders (string $headers)
	{
		static::$_response_headers = $headers;
	}

	public static function getResponseHeaders ()
	{
		return static::$_response_headers;
	}

	public static function addResponseHeader ($header)
	{
		return static::$_response_headers[] = $header;
	}



	protected static $_response_assets_css = [];

	public static function setResponseAssetsCSS (string $css)
	{
		static::$_response_assets_css = $css;
	}

	public static function getResponseAssetsCSS ()
	{
		return static::$_response_assets_css;
	}



	protected static $_response_assets_js = [];

	public static function setResponseAssetsJS (string $js)
	{
		static::$_response_assets_js = $js;
	}

	public static function getResponseAssetsJS ()
	{
		return static::$_response_assets_js;
	}



	protected static $_response_content_json = [];

	public static function setResponseContentJSON (string $json)
	{
		static::$_response_content_json = $json;
	}

	public static function getResponseContentJSON ()
	{
		return static::$_response_content_json;
	}



	protected static $_response_content_nojson = [];

	public static function setResponseContentNOJSON (string $content)
	{
		static::$_response_content_nojson = $content;
	}

	public static function getResponseContentNOJSON ()
	{
		return static::$_response_content_nojson;
	}
}