<?php
namespace APP;

use Structure\Base as BaseStructure;
use MetaException;

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



	public static function ResponseAs (string $type, string $charset = null, string $mime = null)
	{
		static :: setResponseType ($type);

		isset($charset) and 
		static :: setCharset($charset);

		isset($mime) and
		static :: setResponseMime ($mime);
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

		action_apply('APP/Response/Type', $mime);
	}

	public static function getResponseType ()
	{
		return static::$_response_type;
	}

	public static function exitIfTypeIn ($types, int $exit_status = null)
	{
		$types = (array) $types;
		$types = array_map('mb_strtolower', $types);

		if (in_array(static::$_response_type, $types))
			exit($exit_status);
	}

	public static function exitIfTypeIsJson (int $exit_status = null)
	{
		static::exitIfTypeIn(['json', 'cli'], $exit_status);
	}

	public static function exitIfTypeIsHtml (int $exit_status = null, bool $include_type_body = true)
	{
		static::exitIfTypeIn('html', $exit_status);

		$include_type_body and 
		static::exitIfTypeIn('body', $exit_status);
	}

	public static function redirectIfTypeIn ($types, string $link)
	{
		$types = (array) $types;
		$types = array_map('mb_strtolower', $types);

		if (in_array(static::$_response_type, $types))
			redirect($link);
	}

	public static function redirectIfTypeIsJson (string $link)
	{
		static::redirectIfTypeIn('json', $link);
	}

	public static function redirectIfTypeIsHtml (string $link = null)
	{
		static::redirectIfTypeIn('html', $link);
	}



	protected static $_response_mime = 'text/html';

	public static function setResponseMime (string $mime)
	{
		static::$_response_mime = $mime;

		action_apply('APP/Response/Mime', $mime);
	}

	public static function getResponseMime ()
	{
		return static::$_response_mime;
	}



	
	public static $doctypes = [
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
		if (preg_match('#^Response#', $history_uri))
		{ ## Se está estableciendo la URI directo a la función desde donde se llamó
			$history_uri = str_replace('Response' . BS, '', $history_uri);
			$history_uri = explode('::', $history_uri); ## Separación de la clase::función
			array_unshift($history_uri, ''); ## que comience con un slash
			$history_uri = implode('/', $history_uri);
		}

		if (preg_match('#^http#', $history_uri))
		{
			$history_uri = str_replace(url(), '', $history_uri);
		}

		if (preg_match('#^http#', $history_uri))
		{
			throw MetaException :: quickInstance('El HistoryURI debe pertenecer a "' . url() . '"', [
				'history_uri' => $history_uri,
			]);
		}

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

	public static function setResponseHeaders (string $headers):void
	{
		static::$_response_headers = $headers;
	}

	public static function getResponseHeaders ():array
	{
		return static::$_response_headers;
	}

	public static function addResponseHeader ($header):bool
	{
		static::$_response_headers[] = $header;
		return true;
	}

	public static function delResponseHeader ($header):bool
	{
		$headers = static::$_response_headers;

		$index = array_search($header, $headers);

		if ($index === false)
			return true;

		unset($headers[$index]);
		$headers = array_values($headers);

		static::$_response_headers = $headers;
		return true;
	}

	public static function applyResponseHeaderNoCache ():void
	{
		header('Cache-Control: no-cache, must-revalidate'); //HTTP 1.1
		header('Pragma: no-cache'); //HTTP 1.0
		header('Expires: Sat, 26 Mar 1994 18:45:33 GMT'); // Date in the past
	}

	public static function applyResponseHeaderCache (int $days, string $for = 'private', string $rev = 'no-revalidate'):void
	{
		$time = 60 * 60 * 24 * $days;
		$cache_expire_date = gmdate("D, d M Y H:i:s", time() + $time);

		header('User-Cache-Control: max-age=' . $time . ', ' . $for . ', ' . $rev); //HTTP 1.1
		header('Cache-Control: max-age=' . $time . ', ' . $for . ', ' . $rev); //HTTP 1.1
		header('Pragma: cache'); //HTTP 1.0
		header('Expires: ' . $cache_expire_date . ' GMT'); // Date in the future
	}



	protected static $_response_assets_css = [];

	public static function getResponseAssetsCSS ()
	{
		return static::$_response_assets_css;
	}

	public static function registerCSS ($codigo, $uri = NULL, $options = [])
	{
		$lista =& static::$_response_assets_css;

		if (is_array($uri) and count($options) === 0)
			list ($options, $uri) = [$uri, null];

		if (is_null($uri) and count($options) === 0 and ! isset($lista[$codigo]))
			list ($uri, $codigo) = [$codigo, null];

		if (is_null($codigo))
		{
			$codigo = parse_url($uri, PHP_URL_PATH);
			$codigo = preg_replace('/\.min$/i', '', basename($codigo, '.css'));
		}

		isset($lista[$codigo]) or 
		$lista[$codigo] = [
			'codigo'   => $codigo,
			'uri'      => $uri,
			'loaded'   => false,
			'orden'    => 50,
			'version'  => null,
			'position' => 'body',
			'inline'   => false,
			'attr'     => [],
			'deps'     => [],
		];

		if ( ! isset($options['version']) or empty($options['version']))
			$options['version'] = $lista[$codigo]['version'];

		is_null($uri) and 
		$uri = $lista[$codigo]['uri'];

		if (empty($lista[$codigo]['version']) or empty($options['version']) or version_compare($lista[$codigo]['version'], $options['version'], '<'))
			$lista[$codigo] = array_merge($lista[$codigo], ['uri' => $uri], $options);

		return $codigo;
	}

	public static function addCSS ($codigo, $uri = NULL, $options = [])
	{
		$codigo = static::registerCSS($codigo, $uri, $options);
		static::$_response_assets_css[$codigo]['loaded'] = true;
		return $codigo;
	}

	public static function addInlineCSS (string $content, string $position = 'body', int $orden = 80)
	{
		static $_keys = [];

		$position = mb_strtolower($position);
		in_array($position, ['head', 'body']) or 
		$position = 'body';

		isset($_keys[$position . '_' . $orden]) or 
		$_keys[$position . '_' . $orden] = uniqid($position . '_' . $orden . '_');
		$codigo = $_keys[$position . '_' . $orden];

		isset(static::$_response_assets_css[$codigo]) and 
		$content = static::$_response_assets_css[$codigo]['uri'] . PHP_EOL . $content;

		static :: addCSS ($codigo, $content, [
			'orden'    => $orden,
			'position' => $position,
		]);

		static::$_response_assets_css[$codigo]['inline'] = true;
		return;
	}



	protected static $_response_assets_js = [];

	public static function getResponseAssetsJS ()
	{
		return static::$_response_assets_js;
	}

	public static function registerJS ($codigo, $uri = NULL, $options = [])
	{
		$lista =& static::$_response_assets_js;

		if (is_array($uri) and count($options) === 0)
			list ($options, $uri) = [$uri, null];

		if (is_null($uri) and count($options) === 0 and ! isset($lista[$codigo]))
			list ($uri, $codigo) = [$codigo, null];

		if (is_null($codigo))
		{
			$codigo = parse_url($uri, PHP_URL_PATH);
			$codigo = preg_replace('/\.min$/i', '', basename($codigo, '.js'));
		}

		isset($lista[$codigo]) or 
		$lista[$codigo] = [
			'codigo'   => $codigo,
			'uri'      => $uri,
			'loaded'   => false,
			'orden'    => 50,
			'version'  => null,
			'position' => 'body',
			'inline'   => false,
			'attr'     => [],
			'deps'     => [],
			'_before'  => [],
			'_after'   => [],
		];

		if ( ! isset($options['version']) or empty($options['version']))
			$options['version'] = $lista[$codigo]['version'];

		is_null($uri) and 
		$uri = $lista[$codigo]['uri'];

		if (empty($lista[$codigo]['version']) or empty($options['version']) or version_compare($lista[$codigo]['version'], $options['version'], '<'))
			$lista[$codigo] = array_merge($lista[$codigo], ['uri' => $uri], $options);

		return $codigo;
	}

	public static function addJS ($codigo, $uri = NULL, $options = [])
	{
		$codigo = static::registerJS($codigo, $uri, $options);
		static::$_response_assets_js[$codigo]['loaded'] = true;
		return $codigo;
	}

	public static function addInlineJS (string $content, int $orden = 80, string $position = 'body')
	{
		static $_keys = [];

		$position = mb_strtolower($position);
		in_array($position, ['head', 'body']) or 
		$position = 'body';

		isset($_keys[$position . '_' . $orden]) or 
		$_keys[$position . '_' . $orden] = uniqid($position . '_' . $orden . '_');
		$codigo = $_keys[$position . '_' . $orden];

		isset(static::$_response_assets_js[$codigo]) and 
		$content = static::$_response_assets_js[$codigo]['uri'] . PHP_EOL . $content;

		static :: addJS ($codigo, $content, [
			'orden'    => $orden,
			'position' => $position,
		]);

		static::$_response_assets_js[$codigo]['inline'] = true;
		return;
	}

	public static function localizeJS (string $codigo, string $content, string $before = false)
	{
		if ( ! isset(static::$_response_assets_js[$codigo]))
			static :: registerJS($codigo, '');

		static::$_response_assets_js[$codigo]['_' . ($before ? 'before' : 'after')][] = $content;
	}



	protected static $_response_result = null;

	public static function setResponseResult (string $status, string $message, $code = null)
	{
		$status = mb_strtolower($status);

		static::$_response_result = [
			'status'  => $status,
			'message' => $message,
			'code'    => $code,
		];
	}

	public static function getResponseResult ()
	{
		return static::$_response_result;
	}

	public static function cleanResponseResult ()
	{
		static::$_response_result = null;
	}

	public static function response_success (string $message = null, $code = null)
	{
		static::setResponseResult('success', $message ?? 'Todo procesado correctamente', $code ?? 200);
	}

	public static function response_error (string $error = null, $code = null)
	{
		static::setResponseResult('error', $error ?? 'Hubo un error desconocido', $code ?? 500);
	}

	public static function response_notice (string $notice, $code = null)
	{
		static::setResponseResult('notice', $notice, $code ?? 100);
	}

	public static function response_confirm (string $message, $code = null)
	{
		static::setResponseResult('confirm', $message, $code ?? 300);
	}

	public static function getResponseResultAsHtml (bool $clean = true)
	{
		if (is_null(static::$_response_result))
			return null;

		$rr = static::$_response_result;

		$class = ['alert', 'alert-' . $rr['status']];
		$rr['status'] === 'error' and $class[] = 'alert-danger';

		$html = '';
		$html.= '<div' . html_attrs(['class' => $class]) . '>';
			$html.= '<span class="alert-message">' . $rr['message'] . '</span>';
		if ( ! is_empty($rr['code']))
			$html.= '<small class="alert-code float-end">' . mb_strtoupper($rr['status']) . '#' . $rr['code'] . '</small>';
		$html.= '</div>';

		$html = filter_apply('APP/Response/ResultHtml', $html, $rr);

		$clean and static :: cleanResponseResult ();

		return $html;
	}


	protected static $_response_content_json = [];

	public static function setResponseContentJSON (array $json)
	{
		static::$_response_content_json = $json;
	}

	public static function addResponseContentJSON ($key, $val = null)
	{
		if (is_array($key))
		{
			$arr = $key;
			foreach ($arr as $key => $val)
			{
				static::setResponseContentJSON($key, $val);
			}
			return;
		}

		static::$_response_content_json[$key] = $val;
	}

	public static function getResponseContentJSON ()
	{
		return static::$_response_content_json;
	}



	protected static $_response_content_nojson = [];

	public static function setResponseContentNOJSON ($content)
	{
		$content = (array) $content;
		static::$_response_content_nojson = $content;
	}

	public static function addResponseContentNOJSON (string ...$contents)
	{
		foreach($contents as $content)
		{
			if (is_array($content))
			{
				foreach($content as $subcontent)
				{
					static::setResponseContentNOJSON($subcontent);
				}
				return;
			}

			static::$_response_content_nojson[] = $content;
		}
	}

	public static function getResponseContentNOJSON ()
	{
		return static::$_response_content_nojson;
	}
}