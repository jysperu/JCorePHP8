<?php

if ( ! function_exists('print_array'))
{
	/**
	 * print_array()
	 * Muestra los contenidos enviados en el parametro para mostrarlos en HTML
	 *
	 * @param	...array
	 * @return	void
	 */
	function print_array(...$array)
	{
		$r = '';

		$trace = debug_backtrace(false);
		while(count($trace) > 0 and isset($trace[0]['file']) and $trace[0]['file'] === __FILE__)
			array_shift($trace);

		$file_line = '';
		isset($trace[0]) and $file_line = '<small style="color: #ccc;display: block;margin: 0;">' . $trace[0]['file'] . ' #' . $trace[0]['line'] . '</small><br>';

		if (count($array) === 0)
		{
			$r.= '<small style="color: #888">[SIN PARAMETROS]</small>';
		}
		else
		{
			foreach ($array as $ind => $_arr)
			{
				if (is_null($_arr))
				{
					$_arr = '<small style="color: #888">[NULO]</small>';
				}
				elseif (is_string($_arr) and empty($_arr))
				{
					$_arr = '<small style="color: #888">[VACÍO]</small>';
				}
				elseif (is_bool($_arr))
				{
					$_arr = '<small style="color: #888">[' . ($_arr ? 'TRUE' : 'FALSE') . ']</small>';
				}
				elseif (is_array($_arr) and function_exists('array_html'))
				{
					$_arr = array_html($_arr);
				}
				elseif (is_a($_arr, 'ArrayObject') and function_exists('array_html') and $_class = get_class($_arr) and $_arr = (array)$_arr)
				{
					$_arr = '<small style="color: #000">['.$_class.']</small><br><br>' .
							array_html($_arr);
				}
				else
				{
					$_arr = htmlentities(print_r($_arr, true));
				}

				$r.= ($ind > 0 ? '<hr style="border: none;border-top: dashed #ebebeb .5px;margin: 12px 0;">' : '') . 
					$_arr;
			}
		}

		echo '<pre class="dipa">' . 
				'<style>.dipa{' . 
					'display:block;text-align:left;color:#444;background:#fff;position:relative;z-index:99999999999;' . 
					'margin:5px 5px 15px;padding:0 10px 10px;border:solid 1px #ebebeb;box-shadow:4px 4px 4px rgba(235,235,235,.5)' . 
				'}</style>' . 
				$file_line . 
				$r . 
			 '</pre>' . 
			 PHP_EOL;
	}
}

if ( ! function_exists('print_r2'))
{
	/**
	 * print_r2()
	 * @see print_array
	 */
	function print_r2(...$array)
	{
		return call_user_func_array('print_array', $array);
	}
}

if ( ! function_exists('die_array'))
{
	/**
	 * die_array()
	 * Muestra los contenidos enviados en el parametro para mostrarlos en HTML y finaliza los segmentos
	 *
	 * @param	...array
	 * @return	void
	 */
	function die_array(...$array)
	{
		call_user_func_array('print_array', $array);
		die();
	}
}

if ( ! function_exists('array_html'))
{
	/**
	 * array_html()
	 * Convierte un Array en un formato nestable para HTML
	 *
	 * @param array $arr Array a mostrar
	 * @return string
	 */
	function array_html (array $arr, $lvl = 0)
	{
		static $_instances = 0;

		$lvl = (int)$lvl;

		$lvl_child = $lvl + 1 ;
		$str = [];

		$lvl===0 and $str[] = '<div class="array_html" id="array_html_' . (++$_instances) . '">';

		$str[] = '<ol data-lvl="' . ($lvl) . '" class="array' . ($lvl > 0 ? ' child' : '') . '">';

		if (count($arr) === 0)
		{
			$_str = '';
			$_str.= '<li class="detail">';
			$_str.= '<pre class="child-inline">';
			$_str.= '<small style="color: #888">[Array vacío]</small>';
			$_str.= '</pre>';

			$str[] = $_str;
		}

		foreach ($arr as $key => $val)
		{
			$hash = md5(json_encode([$lvl, $key]));
			$ctype = gettype($val);
			$class = $ctype ==='object' ? get_class($val) : $ctype;

			$_str = '';

			$_str.= '<li class="detail" data-hash="' . htmlspecialchars($hash) . '">';
			$_str.= '<span class="key'.(is_numeric($key)?' num':'').(is_integer($key)?' int':'').'">';
			$_str.= $key;
			$_str.= '<small class="info">'.$class.'</small>';
			$_str.= '</span>';
			
			if ( $ctype === 'object')
			{
				$asarr = NULL;
				foreach(['getArrayCopy', 'toArray', '__toArray'] as $f)
				{
					if (method_exists($val, $f))
					{
						try
						{
							$t = call_user_func([$val, $f]);
							if( ! is_array($t))
							{
								throw new Exception('No es Array');
							}
							$asarr = $t;
						}
						catch(Exception $e)
						{}
					}
				}
				is_null($asarr) or $val = $asarr;
			}
			
			if (is_array($val))
			{
				$_str .= array_html($val, $lvl_child);
			}
			
			elseif ( $ctype === 'object')
			{
				$_str.= '<pre data-lvl="'.$lvl_child.'" class="'.$ctype.' child'.($ctype === 'object' ? (' ' . $class) : '').'">';
				$_str.= htmlentities(print_r($val, true));
				$_str.= '</pre>';
			}
			else
			{
				$_str.= '<pre data-lvl="'.$lvl_child.'" class="'.$ctype.' child-inline">';
				if (is_null($val))
				{
					$_str.= '<small style="color: #888">[NULO]</small>';
				}
				elseif (is_string($val) and empty($val))
				{
					$_str.= '<small style="color: #888">[VACÍO]</small>';
				}
				elseif (is_bool($val))
				{
					$_str.= '<small style="color: #888">['.($val?'TRUE':'FALSE').']</small>';
				}
				else
				{
					$_str.= htmlentities(print_r($val, true));
				}
				$_str.= '</pre>';
			}

			$str[] = $_str;
		}

		$str[] = '</ol>';

		if ($lvl === 0)
		{
			$str[] = 
				'<style>'.
					'.array_html {display: block;text-align: left;color: #444;background: white;position:relative}'.
					'.array_html * {margin:0;padding:0}'.
					'.array_html .array {list-style: none;margin: 0;padding: 0;}'.
					'.array_html .array .array {margin: 10px 0 10px 10px;}'.
					'.array_html .key {padding: 5px 10px;display:block;border-bottom: solid 1px #ebebeb}'.
					'.array_html .detail {display: block;border: solid 1px #ebebeb;margin: 0 0 0;}'.
					'.array_html .detail + .detail {margin-top: 10px}'.
					'.array_html .array .array .detail {border-right: none}'.
					'.array_html .child:not(.array), .array_html .child-inline {padding:10px}'.
					'.array_html .info {color: #ccc;float: right;margin: 4px 0 4px 4px;user-select:none}'.
					'.array_html.js .detail.has-child:not(.open)>.child {display:none}'.
					'.array_html.js .detail.has-child:not(.open)>.key {border-bottom:none}'.
					'.array_html.js .detail.has-child>.key {cursor:pointer}'.
					'.array_html.js .detail.has-child:before {content: "▼";float: left;padding: 5px;color: #ccc;}'.
					'.array_html.js .detail.has-child.open:before {content: "▲";}' . 
				'</style>'
			;

			$str[] = 
				'<script>'.
					';(function(){'.
						'var div = document.getElementById("array_html_'.$_instances.'");'.
						'var open = function(e){if(e.defaultPrevented){return;};var t = e.target;if(/info/.test(t.classList)){t = t.parentElement;};if(!(/key/.test(t.classList))){return;};t.parentElement.classList.toggle("open");e.preventDefault()};'.
						'div.classList.add("js");'.
						'div.querySelectorAll(".child").forEach(function(d){var p = d.parentElement, c = p.classList;c.add("has-child");c.add("open");p.onclick = open;});'.
					'}());' .
				'</script>'
			;
		}

		$lvl===0 and $str[] = '</div>';
		$str = implode('', $str);
		return $str;
	}
}

if ( ! function_exists('logger'))
{
	/**
	 * logger()
	 * Función que guarda los logs
	 *
	 * @param MetaException|Exception|TypeError|Error|string 	$message	El mensaje reportado
	 * @param int|null 		$code		(Optional) El código del error
	 * @param string|null	$severity	(Optional) La severidad del error
	 * @param array|null 	$meta		(Optional) Los metas del error
	 * @param string|null 	$filepath	(Optional) El archivo donde se produjo el error
	 * @param int|null 		$line		(Optional) La linea del archivo donde se produjo el error
	 * @param array|null 	$trace		(Optional) La ruta que tomó la ejecución hasta llegar al error
	 * @return void
	 */
	function logger ()
	{
		ErrorControl :: logger (func_get_args());
	}
}