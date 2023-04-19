<?php
/**
 * APPPATH/classes/APP/Response.php
 * @filesource
 */
namespace APP;

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * APP\Response
 */
use Modelo\Structure;
use MetaException;

trait Response
{
	protected static $_response_info = [
		'html_structure' => null,
		'type'           => 'html',
		'mime'           => 'text/html',
		'doctype'        => 'html5',
		'canonical'      => null,
		'history_uri'    => null,
		'headers'        => [],
		'assets_css'     => [],
		'assets_js'      => [],
		'result'         => null,
		'content_json'   => [],
		'content_nojson' => [],
	];

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



	public static function setResponseHtmlStructure (Structure $instance):void
	{
		static :: $_response_info['html_structure'] = $instance;
	}

	public static function getResponseHtmlStructure ()
	{
		return static :: $_response_info['html_structure'];
	}

	public static function ResponseAs (string $type, string $charset = null, string $mime = null)
	{
		static :: setResponseType ($type);

		isset($charset) and 
		static :: setCharset($charset);

		isset($mime) and
		static :: setResponseMime ($mime);
	}

	public static function setResponseType (string $type)
	{
		static::$_response_info['type'] = mb_strtolower($type);

		switch(static::$_response_info['type'])
		{
			case static :: ResponseTypeHtml: case static :: ResponseTypeBody:
				static::setResponseMime('text/html');
				break;
			case static :: ResponseTypeJson: case static :: ResponseTypeCli:
				static::setResponseMime('application/json');
				break;
			case static :: ResponseTypeEventStream:
				static::setResponseMime('text/event-stream');
				header('Cache-Control: no-store');
				break;
		}

		if(in_array(static::$_response_info['type'], [
			static :: ResponseTypeHtml,
			static :: ResponseTypeBody,
			static :: ResponseTypeCli,
			static :: ResponseTypeJson,
		]))
		{
			if ( ! ob_get_level())
				ob_start();
		}
		else
		{
			if (ob_get_level())
				static :: clearBuffer();
		}
		

		action_apply('APP/Response/Type', $mime);
	}

	public static function getResponseType ()
	{
		return static::$_response_info['type'];
	}

	public static function exitIfTypeIn ($types, int $exit_status = null)
	{
		$types = (array) $types;
		$types = array_map('mb_strtolower', $types);

		if (in_array(static::$_response_info['type'], $types))
			exit($exit_status);
	}

	public static function exitIfTypeIsJson (int $exit_status = null)
	{
		static::exitIfTypeIn([
			static :: ResponseTypeJson, 
			static :: ResponseTypeCli,
		], $exit_status);
	}

	public static function exitIfTypeIsHtml (int $exit_status = null, bool $include_type_body = true)
	{
		static::exitIfTypeIn(static :: ResponseTypeHtml, $exit_status);

		$include_type_body and 
		static::exitIfTypeIn(static :: ResponseTypeBody, $exit_status);
	}

	public static function redirectIfTypeIn ($types, string $link)
	{
		$types = (array) $types;
		$types = array_map('mb_strtolower', $types);

		if (in_array(static::$_response_info['type'], $types))
			redirect($link);
	}

	public static function redirectIfTypeIsJson (string $link)
	{
		static::redirectIfTypeIn(static :: ResponseTypeJson, $link);
	}

	public static function redirectIfTypeIsHtml (string $link = null)
	{
		static::redirectIfTypeIn(static :: ResponseTypeHtml, $link);
	}

	public static function setResponseMime (string $mime)
	{
		static::$_response_info['mime'] = $mime;

		action_apply('APP/Response/Mime', $mime);
	}

	public static function getResponseMime ()
	{
		return static::$_response_info['mime'];
	}

	public static function setResponseDoctype (string $doctype)
	{
		static::$_response_info['doctype'] = $doctype;
	}

	public static function getResponseDoctype ()
	{
		return static::$_response_info['doctype'];
	}

	public static function setResponseCanonical (string $canonical)
	{
		static::$_response_info['canonical'] = $canonical;
	}

	public static function getResponseCanonical ()
	{
		return static::$_response_info['canonical'];
	}

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

		static::$_response_info['history_uri'] = $history_uri;
	}

	public static function getResponseHistoryURI ()
	{
		return static::$_response_info['history_uri'];
	}

	public static function force_uri (string $history_uri)
	{
		static::setResponseHistoryURI($history_uri);
	}

	public static function setResponseHeaders (array $headers):void
	{
		static::$_response_info['headers'] = $headers;
	}

	public static function getResponseHeaders ():array
	{
		return static::$_response_info['headers'];
	}

	public static function addResponseHeader ($header):bool
	{
		static::$_response_info['headers'][] = $header;
		return true;
	}

	public static function delResponseHeader ($header):bool
	{
		$headers = static::$_response_info['headers'];

		$index = array_search($header, $headers);

		if ($index === false)
			return true;

		unset($headers[$index]);
		$headers = array_values($headers);

		static::$_response_info['headers'] = $headers;
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

	public static function getResponseAssetsCSS ():array
	{
		return static::$_response_info['assets_css'];
	}

	public static function registerCSS ($codigo, $uri = NULL, $options = [])
	{
		$lista =& static::$_response_info['assets_css'];

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
			'position' => 'head',
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
		static::$_response_info['assets_css'][$codigo]['loaded'] = true;
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

		isset(static::$_response_info['assets_css'][$codigo]) and 
		$content = static::$_response_info['assets_css'][$codigo]['uri'] . PHP_EOL . $content;

		static :: addCSS ($codigo, $content, [
			'orden'    => $orden,
			'position' => $position,
		]);

		static::$_response_info['assets_css'][$codigo]['inline'] = true;
		return;
	}

	public static function getResponseAssetsJS ():array
	{
		return static::$_response_info['assets_js'];
	}

	public static function registerJS ($codigo, $uri = NULL, $options = [])
	{
		$lista =& static::$_response_info['assets_js'];

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
		static::$_response_info['assets_js'][$codigo]['loaded'] = true;
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

		isset(static::$_response_info['assets_js'][$codigo]) and 
		$content = static::$_response_info['assets_js'][$codigo]['uri'] . PHP_EOL . $content;

		static :: addJS ($codigo, $content, [
			'orden'    => $orden,
			'position' => $position,
		]);

		static::$_response_info['assets_js'][$codigo]['inline'] = true;
		return;
	}

	public static function localizeJS (string $codigo, string $content, bool $before = false)
	{
		if ( ! isset(static::$_response_info['assets_js'][$codigo]))
			static :: registerJS($codigo, '');

		static::$_response_info['assets_js'][$codigo]['_' . ($before ? 'before' : 'after')][] = $content;
	}

	public static function setResponseResult (string $status, string $message, $code = null)
	{
		$status = mb_strtolower($status);

		static::$_response_info['result'] = [
			'status'  => $status,
			'message' => $message,
			'code'    => $code,
		];
	}

	public static function getResponseResult ()
	{
		return static::$_response_info['result'];
	}

	public static function cleanResponseResult ()
	{
		static::$_response_info['result'] = null;
	}

	public static function getResponseResultAsHtml (bool $clean = true)
	{
		if (is_null(static::$_response_info['result']))
			return null;

		$rr = static::$_response_info['result'];

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

	public static function setResponseContentJSON (array $json)
	{
		static::$_response_info['content_json'] = $json;
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

		static::$_response_info['content_json'][$key] = $val;
	}

	public static function getResponseContentJSON ()
	{
		return static::$_response_info['content_json'];
	}

	public static function setResponseContentNOJSON ($content)
	{
		$content = (array) $content;
		static::$_response_info['content_nojson'] = $content;
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

			static::$_response_info['content_nojson'][] = $content;
		}
	}

	public static function getResponseContentNOJSON ()
	{
		return static::$_response_info['content_nojson'];
	}



	public static function response_success (string $message = null, $code = null, bool $exit_ifjson = true)
	{
		static::setResponseResult('success', $message ?? 'Todo procesado correctamente', $code ?? 200);

		$exit_ifjson and static :: exitIfTypeIsJson();
	}

	public static function response_error (string $error = null, $code = null, bool $exit_ifjson = true)
	{
		static::setResponseResult('error', $error ?? 'Hubo un error desconocido', $code ?? 500);

		$exit_ifjson and static :: exitIfTypeIsJson();
	}

	public static function response_notice (string $notice, $code = null, bool $exit_ifjson = true)
	{
		static::setResponseResult('notice', $notice, $code ?? 100);

		$exit_ifjson and static :: exitIfTypeIsJson();
	}

	public static function response_confirm (string $message, $code = null, bool $exit_ifjson = true)
	{
		static::setResponseResult('confirm', $message, $code ?? 300);

		$exit_ifjson and static :: exitIfTypeIsJson();
	}
}