<?php
/**
 * APPPATH/classes/APP/Proceso.php
 * @filesource
 */
namespace APP;

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * APP\Proceso
 */
use MetaException\JsonEncoder as JsonEncoderException;
use Helper;
use BenchMark;

trait Proceso
{
	public static function _send_response_on_shutdown ()
	{
		BenchMark :: endAllPoints ();
		action_apply('on_shutdown');

		$_type = static :: getResponseType ();
		if ( ! in_array($_type, [
			static :: ResponseTypeHtml,
			static :: ResponseTypeBody,
			static :: ResponseTypeCli,
			static :: ResponseTypeJson,
		]))
			return; ## Si no es html, body, json o cli; entonces, el buffer de salida es controlado por request

		$charset = static :: getCharset();
		$mime    = static :: getResponseMime();
		static :: addResponseHeader ('Content-Type: ' . $mime . '; charset=' . $charset);

		$headers = static :: getResponseHeaders();
		foreach ($headers as $header)
		{
			$header = (array) $header;
			$header[] = true;

			list($header, $replace) = $header;
			header($header, $replace);
		}

		action_apply('APP/send_response/before');

		$buffer = static :: getInstantBuffer(0); # y lo limpiará

		switch ($_type)
		{
			case static :: ResponseTypeCli: case static :: ResponseTypeJson:
				static :: _send_response_os_body($buffer);
				break;

			case static :: ResponseTypeBody:
				static :: _send_response_os_json($buffer);
				break;

			case static :: ResponseTypeHtml: default:
				static :: _send_response_os_html($buffer);
				break;
		}
	
		action_apply('APP/send_response/after');

		action_apply('do_when_end');
		action_apply('shutdown');

		flush();
		return;
	}

	private static function _html_or_callable($content)
	{
		if (is_array($content))
		{
			$buffer = '';
			foreach($content as $part)
			{
				$buffer .= static :: _html_or_callable ($part);
			}
			return $buffer;
		}

		if (is_string($content) and mb_strlen($content) <= 32 and $temp = explode('PHP_EOL', mb_strlen($content)) and count($temp) === 1 and is_callable($content))
		{
			$content = $content();
			return $content;
		}

		if ( ! is_string($content) and is_callable($content))
		{
			$content = $content();
			return $content;
		}

		return (string) $content;
	}

	private static function _rd ($str_or_array, array $replaces = [])
	{
		if (is_array($str_or_array))
		{
			$str_or_array = array_map(function($str) use ($replaces) {
				return static :: _rd ($str, $replaces);
			}, $str_or_array);
			return $str_or_array;
		}

		$str = (string) $str_or_array;

		do
		{
			$count_total = 0;
			foreach($replaces as $k => $v)
			{
				$str = str_replace('{{' . $k . '}}', $v, $str, $count);
				$count_total += $count;
			}
		}
		while($count_total);

		return $str;
	}

	private static function _reorder_assets (array $arr):array
	{
		usort($arr, function($a, $b){
			if ($a['position'] === 'head' and $b['position'] === 'body')
				return -1; ## $a in head

			if ($b['position'] === 'head' and $a['position'] === 'body')
				return +1; ## $b in head

			$a_orden = isset($a['orden']) ? $a['orden'] : 999;
			$b_orden = isset($b['orden']) ? $b['orden'] : 999;

			$a_orden = (int) $a_orden;
			$b_orden = (int) $b_orden;

			if ($a_orden === $b_orden)
				return 0;

			return $a_orden < $b_orden ? -1 : 1;
		});

		$ordenes = [
			'head' => [],
			'body' => [],
		];

		do
		{
			$ordenados = 0;

			foreach($arr as &$item)
			{
				if (isset($item['orden_nuevo']))
					continue;

				$codigo   = $item['codigo'];
				$position = $item['position'];
				$orden    = $item['orden'];
				$deps     = $item['deps'];

				if (count($deps) === 0)
				{
					$item['orden_nuevo'] = $ordenes[$position][$codigo] = $orden;
					$ordenados++;
					continue;
				}

				foreach($deps as $dep)
				{
					if (isset($ordenes[$position][$dep]))
					{ ## existe la orden en la misma posición
						if ($ordenes[$position][$dep] > $orden)
							$orden = $ordenes[$position][$dep];

						continue;
					}

					if ($position === 'head' and isset($ordenes['body'][$dep]))
					{ ## es recurso ha sido declarado en <head> pero la dependencia esta en <body>
						$item['position'] = 'body'; ## se cambia de posición
						continue 2; ## la órden se modificará después
					}

					if ($position === 'body' and isset($ordenes['head'][$dep]))
					{ ## es recurso ha sido declarado en <head> pero la dependencia esta en <body>
						continue;
					}
				}

				$item['orden_nuevo'] = $ordenes[$position][$codigo] = $orden;
				$ordenados++;
				continue;
			}
		}
		while($ordenados);

		usort($arr, function($a, $b){
			if ($a['orden_nuevo'] === $b['orden_nuevo']) return 0;
			return $a['orden_nuevo'] < $b['orden_nuevo'] ? -1 : 1;
		});

		return $arr;
	}

	private static function _structure_content($structure, $method)
	{
		ob_start();
		$buffer = $structure -> $method();
		is_array($buffer) and $buffer = implode('', $buffer);
		is_string($buffer) or $buffer = (string) $buffer;
		$buffer .= ob_get_contents();
		ob_end_clean();

		$buffer = static :: _html_or_callable($buffer);
		return $buffer;
	}

	private static function _send_response_os_html( ? string $buffer = null)
	{
		empty($buffer) or static :: addResponseContentNOJSON($buffer); ## Si hay buffer que se una al resto del contenido

		$structure = static :: getResponseHtmlStructure();
		is_null($structure) and $structure = use_theme('DashBoard');

		$structure -> init();
		$structure -> loadAssets();

		$bodyContentPart        = static :: _html_or_callable(static :: getResponseContentNOJSON());
		$bodyContentPart_before = static :: _structure_content ($structure, 'getBodyContentPart_before');
		$bodyContentPart_after  = static :: _structure_content ($structure, 'getBodyContentPart_after');

		$responseResultAsHtml = static :: getResponseResultAsHtml();

		$bodyBeforePart        = static :: _structure_content ($structure, 'getBodyBeforePart');
		$bodyBeforePart_before = static :: _structure_content ($structure, 'getBodyBeforePart_before');
		$bodyBeforePart_after  = static :: _structure_content ($structure, 'getBodyBeforePart_after');
		$bodyAfterPart         = static :: _structure_content ($structure, 'getBodyAfterPart');
		$bodyAfterPart_before  = static :: _structure_content ($structure, 'getBodyAfterPart_before');
		$bodyAfterPart_after   = static :: _structure_content ($structure, 'getBodyAfterPart_after');

		$body = '';
		empty($bodyBeforePart_before)  or $body .= PHP_EOL . $bodyBeforePart_before;
		empty($bodyBeforePart)         or $body .= PHP_EOL . $bodyBeforePart;
		empty($bodyBeforePart_after)   or $body .= PHP_EOL . $bodyBeforePart_after;
		empty($responseResultAsHtml)   or $body .= PHP_EOL . $responseResultAsHtml;
		empty($bodyContentPart_before) or $body .= PHP_EOL . $bodyContentPart_before;
		empty($bodyContentPart)        or $body .= PHP_EOL . $bodyContentPart;
		empty($bodyContentPart_after)  or $body .= PHP_EOL . $bodyContentPart_after;
		empty($bodyAfterPart_before)   or $body .= PHP_EOL . $bodyAfterPart_before;
		empty($bodyAfterPart)          or $body .= PHP_EOL . $bodyAfterPart;
		empty($bodyAfterPart_after)    or $body .= PHP_EOL . $bodyAfterPart_after;

		$body = filter_apply('APP/send_response/body', $body);

		## buscando <script> en el body para enviarlo al final del body
		$body_tmp = explode('<script>', $body);
		$body = array_shift($body_tmp);

		while (count($body_tmp))
		{
			$script = array_shift($body_tmp);
			$script = explode('</script>', $script, 2);

			if (count($script) > 1)
			{
				$body .= $script[1];
			}

			$script = array_shift($script);
			static :: addInlineJS($script, 999, 'body');
		}

		## procesando el titulo de la página
		$title_str = static :: _html_or_callable($structure -> getTitle());

		## procesando la descripción de la página
		$description_str = static :: _html_or_callable($structure -> getDescription());

		if (empty($description_str))
		{
			$description_str = $body;
			$description_str = strip_tags($description_str);
			$enter = Helper\Valores :: enter ();
			$description_str = explode($enter, $description_str);
			$description_str = array_map('trim', $description_str);
			$description_str = implode(' ', $description_str);
			$description_str = extracto($description_str, 157, 0.5, '...');
		}

		## obteniendo el charset
		$charset = static :: getCharset();

		## obteniendo el lenguaje
		$lang = static :: getLang();

		## obteniendo la URL base
		$base = static :: url('base');

		## obteniendo el path para la cookie
		$path4cookie = static :: url('cookie_base');

		## obteniendo la URL que se plantará como history
		$history_uri = def_empty(static :: getResponseHistoryURI (), static :: url ('full_wq'));

		## obteniendo la URL canónica
		$canonical = def_empty($structure -> getCanonical(), static :: url('full'));

		## obteniendo la CSS (head, body)
		$AssetsCSS = static :: getResponseAssetsCSS();
		$AssetsCSS = static :: _reorder_assets($AssetsCSS);

		## obteniendo la JS (head, body)
		$AssetsJS = static :: getResponseAssetsJS();
		$AssetsJS = static :: _reorder_assets($AssetsJS);

		## datos a reemplazar como datos
		$replaces = [
			'Response::title'       => $title_str,
			'Response::description' => $description_str,
			'Response::charset'     => $charset,
			'Response::lang'        => $lang,
		];

		###############################################################
		## Enviar el buffer                                          ##
		## - También se va alojando en una variable para el          ##
		##   procesamiento del buffer como cache a futuro            ##
		###############################################################

		## Enviar el <doctype>
		$doctype  = static :: getResponseDoctype();
		$doctypes = static :: $doctypes;
		isset($doctypes[$doctype]) and $doctype = $doctypes[$doctype];

		$html = $doctype;
		echo $doctype;flush();

		## Enviar el <html>
		$tag_html_open = '<html' . html_attrs($structure -> filterHtmlTagAttrs([
			'lang'   => $lang,
			'class'  => [],
			'prefix' => 'og: https://ogp.me/ns#',
		])) . '>';
		$html.= PHP_EOL . $tag_html_open;
		echo PHP_EOL . $tag_html_open;flush();

		## Enviar el <head>
		$tag_head_open = '<head' . html_attrs($structure -> filterHeadTagAttrs([
			'itemscope' => null,
			'itemtype'  => 'http://schema.org/WebSite',
		])) . '>';
		$html.= PHP_EOL . $tag_head_open;
		echo PHP_EOL . $tag_head_open;flush();

		##   Enviar el <title>
		$title_tag = '	<title itemprop="name">' . $title_str . '</title>';
		$html.= PHP_EOL . $title_tag;
		echo PHP_EOL . $title_tag;flush();

		## Enviar el <meta charset>
		$meta = '	<meta charset="' . $charset  . '">';
		$html.= PHP_EOL . $meta;
		echo PHP_EOL . $meta;flush();

		## Enviar los <meta name>
		$_metas = $structure -> filterHeadMetaName([
			'description'                  => extracto($description_str, 157, 1, '...'), ## 140 < X > 160
			'viewport'                     => 'width=device-width, initial-scale=1, shrink-to-fit=no',
			'HandheldFriendly'             => 'True',
			'MobileOptimized'              => '320',
			'mobile-web-app-capable'       => 'yes',
			'apple-mobile-web-app-capable' => 'yes',
			'robots'                       => 'noindex, nofollow',
			'apple-mobile-web-app-title'   => '{{Response::title}}',
			'application-name'             => APPNAME,
			'msapplication-TileColor'      => '#fff',
			'theme-color'                  => '#f00',
			'generator'                    => 'JCorePHP8@2022' . (date('Y') > 2022 ? ('-' . date('Y')) : ''),
		]);
		foreach ($_metas as $name => $content)
		{
			$content = static :: _rd($content, $replaces);

			$meta = '	<meta name="' . $name . '" content="' . $content . '">';
			$html.= PHP_EOL . $meta;
			echo PHP_EOL . $meta;flush();
		}

		## Enviar los <meta property>
		$_metas = $structure -> filterHeadMetaProperty([]);
		foreach ($_metas as $name => $content)
		{
			$content = static :: _rd($content, $replaces);

			$meta = '	<meta property="' . $name . '" content="' . $content . '">';
			$html.= PHP_EOL . $meta;
			echo PHP_EOL . $meta;flush();
		}

		## Enviar los <meta *>
		$_metas = $structure -> filterHeadMetaOthers([
			'name'       => [],
			'property'   => [],
			'http-equiv' => [
				'Content-Type'    => 'text/html; charset=' . $charset,
				'X-UA-Compatible' => 'IE=edge,chrome=1',
			],
		]);
		foreach ($_metas as $type => $metas)
		{
			foreach ($metas as $name => $content)
			{
				$content = static :: _rd($content, $replaces);

				$meta = '	<meta ' . $type . '="' . $name . '" content="' . $content . '">';
				$html.= PHP_EOL . $meta;
				echo PHP_EOL . $meta;flush();
			}
		}

		## Enviar el <base>
		$base_tag = '	<base href="' . $base . '" itemprop="url" />';
		$html.= PHP_EOL . $base_tag;
		echo PHP_EOL . $base_tag;flush();

		## Enviar el <link rel="canonical">
		$canonical_tag = '	<link rel="canonical" href="' . $canonical . '"/>';
		$html.= PHP_EOL . $canonical_tag;
		echo PHP_EOL . $canonical_tag;flush();

		## Enviar el <link rel="shortcut icon">
		## ====================================
		##	<link rel=apple-touch-icon    sizes=180x180 href="..."> ## png
		##	<link rel=icon type=image/png sizes=32x32   href="..."> ## png
		##	<link rel=icon type=image/png sizes=194x194 href="..."> ## png
		##	<link rel=icon type=image/png sizes=192x192 href="..."> ## png
		##	<link rel=icon type=image/png sizes=16x16   href="..."> ## png
		##	<link rel=manifest                          href="..."> ## webmanifest
		##	<link rel=mask-icon            color="#..." href="..."> ## svg & color
		##	<link rel="shortcut icon"                   href="..."> ## ico
		##
		## Añadir en el <meta name>
		##	<meta name=apple-mobile-web-app-title content="..."> ## {{Response::title}}
		##	<meta name=application-name           content="..."> ## APPNAME
		##	<meta name=msapplication-TileColor    content="..."> ## color
		##	<meta name=msapplication-TileImage    content="..."> ## png
		##	<meta name=msapplication-config       content="..."> ## xml
		##	<meta name=theme-color                content="..."> ## color
		##
		$favicon = static :: _structure_content ($structure, 'getFaviconPart');
		is_array($favicon) and $favicon = implode(PHP_EOL . '	', $favicon);
		if ( ! empty($favicon))
		{
			$favicon = '	' . $favicon;
			$html.= PHP_EOL . $favicon;
			echo PHP_EOL . $favicon;flush();
		}

		## Enviar los <script type="application/ld+json">
		## ==============================================
		##	{
		##		"@context" : "https://schema.org",
		##		"@type"    : "...",
		##		...
		##	}
		##
		$json_ld = static :: _structure_content ($structure, 'getJsonLDPart');
		is_array($json_ld) and $json_ld = implode(PHP_EOL . '	', $json_ld);
		if ( ! empty($json_ld))
		{
			$json_ld = '	' . $json_ld;
			$html.= PHP_EOL . $json_ld;
			echo PHP_EOL . $json_ld;flush();
		}

		## Enviar los <link rel=preload>
		## =============================
		##	<link rel=preload href=... as=style>
		##	<link rel=preload href=... as=script>
		##	<link rel=preload href=... as=image>
		##	<link rel=preload href=... as=image media="(min-width: 1px) and (max-width: 240px)">
		##	<link rel=preload href=... as=image media="(min-width: 241px) and (max-width: 360px)">
		##	<link rel=preload href=... as=image media="(min-width: 361px) and (max-width: 480px)">
		##	<link rel=preload href=... as=image media="(min-width: 481px) and (max-width: 720px)">
		##	<link rel=preload href=... as=image media="(min-width: 721px) and (max-width: 1080px)">
		##	<link rel=preload href=... as=image media="(min-width: 1081px) and (max-width: 1440px)">
		##	<link rel=preload href=... as=image media="(min-width: 1441px) and (max-width: 2160px)">
		##	<link rel=preload href=... as=font type=font/woff2>
		##
		$preloads = static :: _structure_content ($structure, 'getPreloadPart');
		is_array($preloads) and $preloads = implode(PHP_EOL . '	', $preloads);
		if ( ! empty($preloads))
		{
			$preloads = '	' . $preloads;
			$html.= PHP_EOL . $preloads;
			echo PHP_EOL . $preloads;flush();
		}

		## Assets (CSS & head & ! inline)
		$assets = array_filter($AssetsCSS, function($asset){
			return $asset['position'] === 'head' and ! $asset['inline'];
		});

		foreach ($assets as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'version'   => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'rel'  => 'stylesheet',
				'type' => 'text/css',
			], (array) $dats['attr']);

			if ( ! empty($dats['uri']))
			{
				$attr['href'] = $dats['uri'];

				if ( ! is_null($dats['version']))
				{
					$_has_sign = preg_match('/\?/i', $attr['href']);
					$attr['href'] .= ($_has_sign ? '&' : '?') . $dats['version'];
				}
			}

			$tag  = '	<link' . html_attrs($attr) . ' />';
			$html.= PHP_EOL . $tag;
			echo PHP_EOL . $tag;flush();
		}

		## Assets (CSS & head & inline)
		$assets = array_filter($AssetsCSS, function($asset){
			return $asset['position'] === 'head' and $asset['inline'];
		});

		foreach ($assets as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'rel'  => 'stylesheet',
				'type' => 'text/css',
			], (array) $dats['attr']);

			$content = $dats['uri'];
			$content = Helper\Compressor :: CSS ($content);

			$tag  = '	<style' . html_attrs($attr) . '>' . $content . '</style>';
			$html.= PHP_EOL . $tag;
			echo PHP_EOL . $tag;flush();
		}

		## Modificación del script location
		$script = 'location.base="' . $base . '";' . 
				  'location.path4cookie="' . $path4cookie . '";' . 
				  'history.replaceState(null, "", "' . $history_uri . '")' .
				  static :: _structure_content ($structure, 'getHeadScriptPart');
		$tag  = '	<script>' . $script . '</script>';
		$html.= PHP_EOL . $tag;
		echo PHP_EOL . $tag;flush();

		## Assets (JS & head & ! inline)
		$assets = array_filter($AssetsJS, function($asset){
			return $asset['position'] === 'head' and ! $asset['inline'];
		});

		foreach ($assets as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'version'   => NULL,
				'attr'      => [],
				'_before'   => [],
				'_after'    => [],
			], $dats);

			// before
			foreach($dats['_before'] as $script)
			{
				$script = Helper\Compressor :: JS ($script);
				$tag  = '	<script>' . $script . '</script>';
				$html.= PHP_EOL . $tag;
				echo PHP_EOL . $tag;flush();
			}

			// <script>
			$attr = array_merge([
				'type' => 'application/javascript',
			], (array) $dats['attr']);

			if ( ! empty($dats['uri']))
			{
				$attr['src'] = $dats['uri'];

				if ( ! is_null($dats['version']))
				{
					$_has_sign = preg_match('/\?/i', $attr['src']);
					$attr['src'] .= ($_has_sign ? '&' : '?') . $dats['version'];
				}
			}

			$tag  = '	<script' . html_attrs($attr) . '></script>';
			$html.= PHP_EOL . $tag;
			echo PHP_EOL . $tag;flush();

			// after
			foreach($dats['_after'] as $script)
			{
				$script = Helper\Compressor :: JS ($script);
				$tag  = '	<script>' . $script . '</script>';
				$html.= PHP_EOL . $tag;
				echo PHP_EOL . $tag;flush();
			}
		}

		## Assets (JS & head & inline)
		$assets = array_filter($AssetsJS, function($asset){
			return $asset['position'] === 'head' and $asset['inline'];
		});

		foreach ($assets as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'type' => 'application/javascript',
			], (array) $dats['attr']);

			$content = $dats['uri'];
			$content = Helper\Compressor :: JS ($content);

			$tag  = '	<script' . html_attrs($attr) . '>' . $content . '</script>';
			$html.= PHP_EOL . $tag;
			echo PHP_EOL . $tag;flush();
		}

		## </head>
		$tag_head_close = '</head>';
		$html.= PHP_EOL . $tag_head_close;
		echo PHP_EOL . $tag_head_close;flush();

		## <body>
		$tag_body_open = '<body' . html_attrs($structure -> filterBodyTagAttrs([
			'class' => [],
		])) . '>';
		$html.= PHP_EOL . $tag_body_open;
		echo PHP_EOL . $tag_body_open;flush();

		## {{bodyContent}}
		$vEnter = vEnter();
		$vTab = vTab();
		
		$body = trim ($body);
		$html.= PHP_EOL . $body;
		echo PHP_EOL . $body;flush();

		## Assets (CSS & body & ! inline)
		$assets = array_filter($AssetsCSS, function($asset){
			return $asset['position'] === 'body' and ! $asset['inline'];
		});

		foreach ($assets as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'version'   => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'rel'  => 'stylesheet',
				'type' => 'text/css',
			], (array) $dats['attr']);

			if ( ! empty($dats['uri']))
			{
				$attr['href'] = $dats['uri'];

				if ( ! is_null($dats['version']))
				{
					$_has_sign = preg_match('/\?/i', $attr['href']);
					$attr['href'] .= ($_has_sign ? '&' : '?') . $dats['version'];
				}
			}

			$tag  = '	<link' . html_attrs($attr) . ' />';
			$html.= PHP_EOL . $tag;
			echo PHP_EOL . $tag;flush();
		}

		## Assets (CSS & body & inline)
		$assets = array_filter($AssetsCSS, function($asset){
			return $asset['position'] === 'body' and $asset['inline'];
		});

		foreach ($assets as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'rel'  => 'stylesheet',
				'type' => 'text/css',
			], (array) $dats['attr']);

			$content = $dats['uri'];
			$content = Helper\Compressor :: CSS ($content);

			$tag  = '	<style' . html_attrs($attr) . '>' . $content . '</style>';
			$html.= PHP_EOL . $tag;
			echo PHP_EOL . $tag;flush();
		}

		## Assets (JS & body & ! inline)
		$assets = array_filter($AssetsJS, function($asset){
			return $asset['position'] === 'body' and ! $asset['inline'];
		});

		foreach ($assets as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'version'   => NULL,
				'attr'      => [],
				'_before'   => [],
				'_after'    => [],
			], $dats);

			// before
			foreach($dats['_before'] as $script)
			{
				$script = Helper\Compressor :: JS ($script);
				$tag  = '	<script>' . $script . '</script>';
				$html.= PHP_EOL . $tag;
				echo PHP_EOL . $tag;flush();
			}

			// <script>
			$attr = array_merge([
				'type' => 'application/javascript',
			], (array) $dats['attr']);

			if ( ! empty($dats['uri']))
			{
				$attr['src'] = $dats['uri'];

				if ( ! is_null($dats['version']))
				{
					$_has_sign = preg_match('/\?/i', $attr['src']);
					$attr['src'] .= ($_has_sign ? '&' : '?') . $dats['version'];
				}
			}

			$tag  = '	<script' . html_attrs($attr) . '></script>';
			$html.= PHP_EOL . $tag;
			echo PHP_EOL . $tag;flush();

			// after
			foreach($dats['_after'] as $script)
			{
				$script = Helper\Compressor :: JS ($script);
				$tag  = '	<script>' . $script . '</script>';
				$html.= PHP_EOL . $tag;
				echo PHP_EOL . $tag;flush();
			}
		}

		## Assets (JS & body & inline)
		$assets = array_filter($AssetsJS, function($asset){
			return $asset['position'] === 'body' and $asset['inline'];
		});

		foreach ($assets as $dats)
		{
			$dats = array_merge([
				'codigo'    => NULL,
				'uri'       => NULL,
				'attr'      => [],
			], $dats);

			$attr = array_merge([
				'type' => 'application/javascript',
			], (array) $dats['attr']);

			$content = $dats['uri'];
			$content = Helper\Compressor :: JS ($content);

			$tag  = '	<script' . html_attrs($attr) . '>' . $content . '</script>';
			$html.= PHP_EOL . $tag;
			echo PHP_EOL . $tag;flush();
		}

		## Modificación del script location
		$script = trim(static :: _structure_content ($structure, 'getBodyScriptPart'));
		if ( ! empty($script))
		{
			$tag  = '	<script>' . $script . '</script>';
			$html.= PHP_EOL . $tag;
			echo PHP_EOL . $tag;flush();
		}

		## <body>
		$tag_body_close = '</body>';
		$html.= PHP_EOL . $tag_body_close;
		echo PHP_EOL . $tag_body_close;flush();

		## </html>
		$tag_html_close = '</html>';
		$html.= PHP_EOL . $tag_html_close;
		echo PHP_EOL . $tag_html_close;flush();

		action_apply('APP/send_response/html', $html); ## Se puede minificar y cachear el resultado para ciertas pantallas
	}

	private static function _send_response_os_body( ? string $buffer = null)
	{
		empty($buffer) or static :: addResponseContentNOJSON($buffer); ## Si hay buffer que se una al resto del contenido

		$structure = static :: getResponseHtmlStructure();
		is_null($structure) and $structure = use_theme('DashBoard');

		$structure -> init();

		$bodyContentPart        = static :: _html_or_callable(static :: getResponseContentNOJSON());
		$bodyContentPart_before = static :: _structure_content ($structure, 'getBodyContentPart_before');
		$bodyContentPart_after  = static :: _structure_content ($structure, 'getBodyContentPart_after');

		$responseResultAsHtml = static :: getResponseResultAsHtml();

		$body = '';
		empty($responseResultAsHtml)   or $body .= PHP_EOL . $responseResultAsHtml;
		empty($bodyContentPart_before) or $body .= PHP_EOL . $bodyContentPart_before;
		empty($bodyContentPart)        or $body .= PHP_EOL . $bodyContentPart;
		empty($bodyContentPart_after)  or $body .= PHP_EOL . $bodyContentPart_after;

		$body = filter_apply('APP/send_response/body', $body);

		echo $body;flush();

		action_apply('APP/send_response/body', $body);
	}

	private static function _send_response_os_json( ? string $buffer = null)
	{
		$data = (array) static :: getResponseContentJSON();

		$result = static :: getResponseResult(); ## Resultado de algún procedimiento
		if ( ! is_null($result))
		{
			$message_attr = $data_temp['status'] === 'error' ? 'error' : 'message';

			$data_temp = [
				'status'      => $result['status'],
				$message_attr => $result['message'],
			];

			is_null($result['code']) or
			$data_temp['code'] = $result['code'];

			$data = array_merge($data_temp, $data);
		}

		if ( ! empty($buffer))
		{
			$k = 'message';
			if (isset($data['error']) or (isset($data['message']) and ! is_empty($data['message'])))
				$k = 'buffer';

			$data[$k] = $buffer;
		}

		$data = filter_apply('APP/send_response/json', $data);

		$output = json_encode($data);
		if ($output === false)
		{
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					$error = 'No errors';
				break;
				case JSON_ERROR_DEPTH:
					$error = 'Maximum stack depth exceeded';
				break;
				case JSON_ERROR_STATE_MISMATCH:
					$error = 'Underflow or the modes mismatch';
				break;
				case JSON_ERROR_CTRL_CHAR:
					$error = 'Unexpected control character found';
				break;
				case JSON_ERROR_SYNTAX:
					$error = 'Syntax error, malformed JSON';
				break;
				case JSON_ERROR_UTF8:
					$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
				case JSON_ERROR_RECURSION:
					$error = 'One or more recursive references in the value to be encoded';
				break;
				case JSON_ERROR_INF_OR_NAN:
					$error = 'One or more NAN or INF values in the value to be encoded';
				break;
				case JSON_ERROR_UNSUPPORTED_TYPE:
					$error = 'A value of a type that cannot be encoded was given';
				default:
					$error = 'Unknown error';
				break;
			}

			logger (new JsonEncoderException ($error, $data));
			$output = '';
		}

		echo $output;
	}

	protected static $_proceso_info = [
		'uri_inicial' => null,
		'uri' => null,
		'ids' => [],
	];

	protected static function prepararProceso ()
	{
		static :: $_proceso_info['uri'] = static :: $_proceso_info['uri_inicial'] = static :: getUrlPath();

		static :: detectResponseType ();
		static :: detectUriIDs ();
		static :: setResponseHeaders([]);

		if (static :: $_proceso_info['uri'] === '/')
			static :: $_proceso_info['uri'] = '/inicio';

		static :: $_proceso_info['uri'] = filter_apply('APP/PreProceso/URI', static :: $_proceso_info['uri']);
	}

	protected static function detectUriIDs ()
	{
		$_uri = (string) filter_apply('APP/detectUriIDs', static :: $_proceso_info['uri'], static :: $_proceso_info['ids']);
		$_ids = (array) static :: $_proceso_info['ids'];

		// Quitar los números del URI
		$_uri = explode('/', $_uri);
		empty($_uri[0]) and array_shift($_uri);

		$_uri_new = [];
		foreach($_uri as $_uri_part)
		{
			if (preg_match('/^[0-9]+$/', $_uri_part))
			{
				$_ids[] = $_uri_part;
			}
			elseif (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\=(.+)$/', $_uri_part, $matches))
			{
				$_ids[$matches[1]] = $matches[2];
			}
			else
			{
				$_uri_new[] = $_uri_part;
			}
		}

		static :: $_proceso_info['uri'] = '/' . implode('/', $_uri_new);
		static :: $_proceso_info['ids'] = $_ids;
	}

	protected static function detectResponseType ()
	{
		if (defined('FORCE_RSP_TYPE'))
			return static :: setResponseType(FORCE_RSP_TYPE);

		if (isset($_GET['contentOnly']) or ( isset($_GET['_']) and $_GET['_'] === 'co' ))
			return static :: setResponseType('body');

		if (
			(
				isset($_SERVER['HTTP_X_REQUESTED_WITH']) and 
				(
					mb_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' or 
					mb_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'androidapp' or 
					mb_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'iosapp'
				)
			) or isset($_GET['json']) or preg_match('/\.json$/i',  static :: getUrlPath())
		)
			return static :: setResponseType('json');

		if (ISCOMMAND)
			return static :: setResponseType('cli');

		action_apply('APP/detectResponseType');

		## html ya está pre-definido
	}



	public static function procesar (string $namespace, string $process_uri = null)
	{
		static $_actioneds = [];
		$_nmsp_req_action = ! in_array($namespace, $_actioneds);

		$_nmsp_req_action and $_actioneds[] = $namespace;

		$uri = $process_uri;
		empty($uri) and $uri = static :: getURI();

		$_nmsp_req_action and action_apply('APP/Procesar/' . $namespace . '/Before');

		$found = search_class_for ($namespace, $uri, 'index');

		if (is_null($found))
		{
			if ($namespace === 'Response' and empty($process_uri))
			{
				$uri = '/error404';
				$uri = filter_apply('APP/Procesar/Response/Error404', $uri);

				static :: procesar ($namespace, $uri);
			}

			$_nmsp_req_action and action_apply('APP/Procesar/' . $namespace);
			return;
		}

		list($class, $function, $params) = $found;

		$instance = cached_class($class, static :: getIDS ());

		$request_method = static :: getRequestMethod ();
		$response_type   = mb_strtoupper(static :: getResponseType ());

		foreach([$request_method . '_', ''] as $x)
		{
			foreach([$response_type . '_', ''] as $y)
			{
				if ($method = $x . $y . $function and is_callable([$instance, $method]))
				{
					$function = $method;
					break 2;
				}

				if ($method = $y . $x . $function and is_callable([$instance, $method]))
				{
					$function = $method;
					break 2;
				}
			}
		}

		if (is_null($function) or ! is_callable([$instance, $function]))
		{
			if ($namespace === 'Response' and empty($process_uri))
			{
				$uri = '/error404';
				$uri = filter_apply('APP/Procesar/Response/Error404', $uri);

				static :: procesar ($namespace, $uri);
			}

			$_nmsp_req_action and action_apply('APP/Procesar/' . $namespace);
			return;
		}

		call_user_func_array([$instance, $function], $params);

		$_nmsp_req_action and action_apply('APP/Procesar/' . $namespace);
	}

	public static function setURI ($uri):void
	{
		static :: $_proceso_info['uri'] = $uri;
	}

	public static function getURI ():string
	{
		return static :: $_proceso_info['uri'];
	}

	public static function getUriInicial ():string
	{
		return static :: $_proceso_info['uri_inicial'];
	}

	public static function setIDS ($ids):void
	{
		static :: $_proceso_info['ids'] = $ids;
	}

	public static function getIDS ()
	{
		return static :: $_proceso_info['ids'];
	}

	public static function addID ($id):void
	{
		static :: $_proceso_info['ids'][] = $id;
	}
}