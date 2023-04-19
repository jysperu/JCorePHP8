<?php

if ( ! function_exists('date_str'))
{
	function date_str ($str, $timestamp = NULL, $Force = TRUE)
	{
		is_bool($timestamp) and $Force = $timestamp and $timestamp = NULL;

		if (is_array($str))
		{
			return array_map(function($v) use ($timestamp, $Force){
				return date_str($v, $timestamp, $Force);
			}, $str);
		}
		
		is_null($timestamp) and $timestamp = time();

		is_numeric($timestamp) or $timestamp = strtotime($timestamp);

		$return = $str;

		switch($str)
		{
			//Palabras de StrToTime
			case 'this week':
				$return = strtotime('this week');
				break;

			//Force date as now
			case 'now':
			case 'ahora':
			case 'today':
			case 'hoy':
				$return = time();
				break;
			
			case 'tomorrow':
			case 'mañana':
				$return = strtotime(date('Y-m-d H:i:s').' + 1 Day');
				break;
			
			case 'yesterday':
			case 'ayer':
				$return = strtotime(date('Y-m-d H:i:s').' - 1 Day');
				break;
			
			case 'now start':
			case 'now_start':
			case 'now-start':
				$return = strtotime(date('Y-m-d 00:00:00'));
				break;
			
			case 'now end':
			case 'now_end':
			case 'now-end':
				$return = strtotime(date('Y-m-d 23:59:59'));
				break;

			case 'this_week':
			case 'esta_semana':
				$d = date('w');
				$fis = ($d==0?'':($d==1?' - 1 Day':(' - '.$d.' Days')));
				$ffs = ($d==6?'':($d==5?' + 1 Day':(' + '.(6-$d).' Days')));
				
				$return = [
					strtotime(date('Y-m-d 00:00:00').$fis),
					strtotime(date('Y-m-d 23:59:59').$ffs)
				];
				break;
			
			case 'this_week_time':                             
				$d = date('w');
				$fis = ($d==0?'':($d==1?' - 1 Day':(' - '.$d.' Days')));
				$ffs = ($d==6?'':($d==5?' + 1 Day':(' + '.(6-$d).' Days')));
				
				$return = [
					strtotime(date('Y-m-d H:i:s').$fis),
					strtotime(date('Y-m-d H:i:s').$ffs)
				];
				break;
			
			case 'this_week_str':                              
				$ini = strtotime(date('Y-m-d 00:00:00', strtotime('this week')));
				
				$return = [
					$ini,
					strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $ini).' + 7 Days')).' - 1 Second')
				];
				break;
			
			case 'this_week_str_time':                         
				$ini = strtotime('this week');
				
				$return = [
					$ini,
					strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $ini).' + 7 Days')).' - 1 Second')
				];
				break;
			
			case 'this_month':
			case 'este_mes':                 
				$return = [
					strtotime(date('Y-m-01 00:00:00')),
					strtotime(date('Y-m-'.date('t').' 23:59:59'))
				];
				break;
			
			case 'last_month':
			case 'mes_pasado':
				$mon = strtotime(date('Y-m-d').' - 1 Month');
				
				$return = [
					strtotime(date('Y-m-01 00:00:00', $mon)),
					strtotime(date('Y-m-'.date('t', $mon).' 23:59:59', $mon))
				];
				break;
			
			case 'this_year':
			case 'este_año':                  
				$return = [
					strtotime(date('Y-01-01 00:00:00')),
					strtotime(date('Y-12-31 23:59:59'))
				];
				break;
			
			case 'last_year':
			case 'año_pasado':
				$yrs = strtotime(date('Y-m-d').' - 1 Year');
				
				$return = [
					strtotime(date('Y-01-01 00:00:00', $yrs)),
					strtotime(date('Y-12-31 23:59:59', $yrs))
				];
				break;

			//The dateFrom
			case 'timestamp':
			case 'hora':
				$return = $timestamp;
				break;
			
			case 'day_start':
				$return = strtotime(date('Y-m-d 00:00:00', $timestamp));
				break;

			case 'day_end':
				$return = strtotime(date('Y-m-d 23:59:59', $timestamp));
				break;
				
			case 'that_week':                                  
				$d = date('w', $timestamp);
				$fis = ($d==0?'':($d==1?' - 1 Day':(' - '.$d.' Days')));
				$ffs = ($d==6?'':($d==5?' + 1 Day':(' + '.(6-$d).' Days')));
				
				$return = [
					strtotime(date('Y-m-d 00:00:00', $timestamp).$fis),
					strtotime(date('Y-m-d 23:59:59', $timestamp).$ffs)
				];
				break;
				
			case 'that_week_time':                             
				$d = date('w', $timestamp);
				$fis = ($d==0?'':($d==1?' - 1 Day':(' - '.$d.' Days')));
				$ffs = ($d==6?'':($d==5?' + 1 Day':(' + '.(6-$d).' Days')));
				
				$return = [
					strtotime(date('Y-m-d H:i:s', $timestamp).$fis),
					strtotime(date('Y-m-d H:i:s', $timestamp).$ffs)
				];
				break;
				
			case 'that_week_str':                              
				$ini = strtotime(date('Y-m-d 00:00:00', strtotime('this week', $timestamp)));
				
				$return = [
					$ini,
					strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $ini).' + 7 Days')).' - 1 Second')
				];
				break;
				
			case 'that_week_str_time':                         
				$ini = strtotime('this week', $timestamp);
				
				$return = [
					$ini,
					strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $ini).' + 7 Days')).' - 1 Second')
				];
				break;
				
			case 'that_month':                                 
				$return = [
					strtotime(date('Y-m-01 00:00:00', $timestamp)),
					strtotime(date('Y-m-'.date('t', $timestamp).' 23:59:59', $timestamp))
				];
				break;
				
			case 'that_year':                                  
				$return = [
					strtotime(date('Y-01-01 00:00:00', $timestamp)),
					strtotime(date('Y-12-31 23:59:59', $timestamp))
				];
				break;

			default:
				$nms = 'Second|Minute|Hour|Day|Week|Month|Year';
				
				if(preg_match('/^(\-|\+)\=([\ ]*)([0-9]*)([\ ]*)(' . $nms . ')(s){0,1}/i', $str, $matchs))
				{
					if($matchs[3]*1===1)
					{
						$matchs[6] = '';
					}
					else
					{
						$matchs[6] = 's';
					}
					
					$return = strtotime(date('Y-m-d H:i:s', $timestamp) . ' ' . $matchs[1] . ' ' . strtocapitalize($matchs[3]) . ' ' . $matchs[5] . $matchs[6]);
				}
				else
				if(preg_match('/^last([\ \_]+)([0-9]*)([\ \_]+)(' . $nms . ')(s){0,1}([\ \_]*)(wt)*/i', $str, $matchs))
				{
					if(trim($matchs[7])==='')
					{
						$timestamp = strtotime(date('Y-m-d 23:59:59', $timestamp));
					}
					
					if($matchs[2]*1===1)
					{
						$matchs[5] = '';
					}
					else
					{
						$matchs[5] = 's';
					}
					
					$return = [
						strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', $timestamp).' - '.$matchs[2].' '.strtocapitalize($matchs[4]).$matchs[5])).' + 1 Second'),
						$timestamp
					];
				}
				else
				if(is_numeric($str))
				{
					$return = $str;
				}
				else
				{
					$_str = $str;
					
					if ( ! $Force and in_array($str, ['d.vmm']))
					{
						$str = 'PreReValid';
					}
					
					$return = strtotime($str, $timestamp);
					
					$return === FALSE and ! $Force and $return = $_str;
				}
				break;
		}

		$return === FALSE and $Force and $return = time();
		return $return;
	}
}

if ( ! function_exists('date2'))
{
	function date2 ($formato = 'Y-m-d H:i:s', ...$timestamps)
	{
		if (count($timestamps) === 0 or ! is_int(end($timestamps)))
		{
			$timestamps[] = time();
		}

		if (count($timestamps) > 1)
		{
			while(count($timestamps) > 1)
			{
				$timestamp = array_pop($timestamps);
				$x = count($timestamps) - 1;
				
				$timestamps[$x] = date2($timestamps[$x], $timestamp);
				
				is_int($timestamps[$x]) or $timestamps[$x] = strtotime($timestamps[$x]);
				$timestamps[$x] === false and $timestamps[$x] = time();
			}
		}
		
		$timestamp = array_pop($timestamps);
		
		if (is_int($formato))
		{
			$nt = $formato;
			
			switch($timestamp)
			{
				case 'this week':
				case 'now':
				case 'ahora':
				case 'now start':
				case 'now_start':
				case 'now-start':
				case 'now end':
				case 'now_end':
				case 'now-end':
				case 'this_week_time':
				case 'this_week_str_time':
				case 'day_start':
				case 'day_end':
				case 'that_week_time':
				case 'that_week_str_time':
					$formato = 'Y-m-d H:i:s';
					break;
				
				case 'today':
				case 'hoy':
				case 'tomorrow':
				case 'mañana':
				case 'yesterday':
				case 'ayer':
				case 'this_week':
				case 'esta_semana':
				case 'this_week_str':
				case 'this_month':
				case 'este_mes':
				case 'this_year':
				case 'este_año':
				case 'that_week':
				case 'that_week_str':
				case 'that_month':
				case 'that_year':
				case 'last_month':
				case 'mes_pasado':
				case 'last_year':
				case 'año_pasado':
					$formato = 'Y-m-d';
					break;
				
				case 'timestamp':
					$formato = 'timestamp';
					break;
				
				case 'hora':
					$formato = 'H:i:s';
					break;

				default:
					$formato = 'Y-m-d H:i:s';
					break;
			}
			
			$timestamp = $nt;
			unset($nt);
		}
		
		if (mb_strtolower($formato) === 'iso8601')
		{
			// El formato iso8601 no requiere que convierta el timestamp a numero
			return date_iso8601($timestamp);
		}

		$timestamp = date_str($timestamp, FALSE);

		is_int($timestamp) or $timestamp = date_str($timestamp);

		$nformato = date_str($formato, $timestamp, false);

		if ($nformato !== $formato)
		{
			return $nformato;
		}

		if (mb_strtolower($formato) === 'timestamp')
		{
			return $timestamp;
		}

		$return = '';
		$split = str_split($formato);
		
		$dgt = '';
		
		for($x = 0; $x < count($split); $x++)
		{
			$c = $split[$x];
			
			if($c === '\\')
			{
				$return .= $split[$x+1];
				$x++;
			}
			elseif ($c === '"' or $c === '\'')
			{
				$x_ =1;
				$t = '';
				
				while($split[$x+$x_]<>$c)
				{
					$t.=$split[$x+$x_];
					$x_++;
				}
				
				$return.=$t;
				$x+=$x_;
			}
			elseif(preg_match('/[a-zA-Z]/', $c))
			{
				$dgt.=$c;
				
				if( ! ((count($split)-1) === $x) and preg_match('/[a-zA-Z]/', $split[$x+1]))
				{
					continue;
				}
				
				switch($dgt)
				{
					case 'de' :
						$return.='de';
						break;
						
					case 'del':
						$return.='del';
						break;
						
					case 'vmn':
						$return.=mes(date('m', $timestamp), 'normal');
						break;
						
					case 'vmm':
						$return.=mes(date('m', $timestamp), 'min');
						break;
						
					case 'vdn':
						$return.=dia(date('w', $timestamp), 'normal');
						break;
						
					case 'vdm':
						$return.=dia(date('w', $timestamp), 'min');
						break;
						
					case 'vdmn':
						$return.=dia(date('w', $timestamp), 'vmin');
						break;
						
					case 'LL':
						$return.=date2('d "de" vmn "de" Y', $timestamp);
						break;
						
					default:
						$return.=date($dgt, $timestamp);
						break;
				}

				$dgt='';
			}
			else
			{
				$return.=$c;
			}
		}

		return $return;
	}
}

if ( ! function_exists('date_iso8601'))
{
	/**
	 * date_iso8601 ()
	 * -Obtener el formato ISO8601 de una fecha
	 *
	 * @param int|string|null $time Fecha a formatear, si es NULL entonces se asume este momento
	 * @return string
	 */
	function date_iso8601 ($time = NULL)
	{
		static $_regex = '/(([0-9]{2,4})\-([0-9]{1,2})\-([0-9]{1,2}))*(\ )*(([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2}))*/';
		
		/** Convertimos NULL a momento actual */
		is_null($time) and $time = time();
		
		if ( ! preg_match($_regex, $time))
		{
			/** Convertimos STRING a TIME */
			is_string($time) and $time = date2($time);

			/** TIME to DATE */
			$time = date2('Y-m-d H:i:s', $time);
		}

		/** Obteniendo las partes del DATE */
		preg_match($_regex, $time, $matches);

		$R = [];
		
		$n = 2 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['P']['Y'] = $v;
		$n = 3 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['P']['M'] = $v;
		$n = 4 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['P']['D'] = $v;
		
		$n = 7 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['T']['H'] = $v;
		$n = 8 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['T']['M'] = $v;
		$n = 9 and isset($matches[$n]) and $v = (string)$matches[$n] and ! is_empty($v) and $R['T']['S'] = $v;

		isset($R['P']['Y']) and mb_strlen($R['P']['Y']) === 2 and $R['P']['Y'] = '19' . $R['P']['Y'];

		return implode('', array_map(function($k, $v){
			return $k . implode('', array_map(function($x, $y){
				return $y . $x;
			}, array_keys($v), array_values($v)));
		}, array_keys($R), array_values($R)));
	}
}

if ( ! function_exists('moment'))
{
	/**
	 * moment ()
	 * Obtener un texto de relatividad de momentos
	 *
	 * @param int|string|null $from Fecha desde el cual ejecutar la relatividad del momento, si es NULL entonces se asume este momento
	 * @param int|string|null $to Fecha hacia el cual ejecutar la relatividad del momento, si es NULL entonces se asume este momento, este valor debe ser mayor o igual a $from
	 * @param bool $min Si la relatividad debe ser devuelta en texto corto o largo
	 *
	 * @return string
	 */
	function moment ($from = NULL, $to = NULL, $min = FALSE)
	{
		is_bool($to) and $min = $to and $to = NULL;
		
		/** Convertimos NULL a momento actual */
		is_null($to) and $to = time();
		is_null($from) and $from = time();
		
		/** Convertimos STRING a TIME */
		is_string($to) and $to = date2($to, $from, time()) and $to = date2('timestamp', $to);
		is_string($from) and $from = date2($from) and $from = date2('timestamp', $from);
		
		/** Nos aseguramos que $to sea mayor o igual a $from */
		$to < $from and $to = $from;
		
		/** Obtenemos la diferencia en Segundos */
		$_seg = $to - $from;
		
		if ($_seg < 30)
		{
			return _t($min ? 'Instante' : 'Hace un momento');
		}
		
		$_min = (int) floor ($_seg / 60);
		
		if ($_min === 0)
		{
			return _t ($min ? '%d Seg(s)' : 'Hace %d segundo(s)', $_seg);
		}
		elseif ($_min === 1 and ! $min)
		{
			return _t ('Hace un minuto');
		}
		elseif ($_min === 30 and ! $min)
		{
			return _t ('Hace media hora');
		}
		
		$_hor = (int) floor ($_min / 60);
		
		if ($_hor === 0)
		{
			return _t ($min ? '%d Min(s)' : 'Hace %d minuto(s)', $_min);
		}
		elseif ($_hor === 1 and ! $min)
		{
			return _t ('Hace una hora');
		}
		
		$_dia = (int) floor ($_hor / 24);
		
		if ($_dia === 0)
		{
			return _t ($min ? date2('H:i', $from) : ($_hor <= date('H') ? 'Hoy' : 'Ayer') . ' a las ' . date2('H:i:s', $from));
		}
		elseif ($_dia === 1 and ! $min)
		{
			return _t ('Hace un día');
		}
		
		$_sem = (int) floor ($_dia / 7);

		if ($_sem === 0)
		{
			return _t ($min ? date2('d.vmm', $from) : date2('LL', $from));
		}
		elseif ($_sem === 1 and $_dia === 7 and ! $min)
		{
			return _t ('Hace una semana');
		}
		elseif ($_sem === 2 and $_dia === 14 and ! $min)
		{
			return _t ('Hace dos semanas');
		}
		
		$_mes = (int) floor ($_sem / 4);

		if ($_mes === 0)
		{
			return _t ($min ? date2('d.vmm', $from) : date2('LL', $from));
		}
		elseif ($_mes === 1 and $_sem === 4 and ! $min)
		{
			return _t ('Hace un més');
		}
		
		$_ano = (int) floor ($_mes / 12);
		
		if ($_ano === 0)
		{
			return _t ($min ? date2('vmm.Y', $from) : date2('vmn "del" Y', $from));
		}
		elseif ($_ano === 1 and ! $min)
		{
			return _t ('Hace un año');
		}
		
		return _t ($min ? date2('d.vmm.Y', $from) : date2('LL', $from));
	}
}

if ( ! function_exists('date_recognize'))
{
	function date_recognize($date, $returnFormat = NULL)
	{
		if(is_empty($date)){
			return NULL;
		}

		if(preg_match('/^\d{4}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/', $date)){
			$this_format = 'Y-m-d';
		}
		elseif(preg_match('/^\d{4}[-](0[1-9]|1[012])[-]([1-9]|[12][0-9]|3[01])$/', $date)){
			$this_format = 'Y-m-j';
		}
		elseif(preg_match('/^\d{4}[-]([1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/', $date)){
			$this_format = 'Y-n-d';
		}
		elseif(preg_match('/^\d{4}[-]([1-9]|1[012])[-]([1-9]|[12][0-9]|3[01])$/', $date)){
			$this_format = 'Y-n-j';
		}
		elseif(preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[012])\/\d{4}$/', $date)){
			$this_format = 'd/m/Y';
		}
		elseif(preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\/\d{4}$/', $date)){
			$this_format = 'd/F/Y';
		}
		elseif(preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(January|February|March|April|May|June|July|August|September|October|November|December)\/\d{4}$/', $date)){
			$this_format = 'd/M/Y';
		}
		else
		{
			return NULL;//Formato no reconocido
		}

		if(is_null($returnFormat))
		{
			return $this_format;
		}

		$date = date_create_from_format($this_format, $date);
		return date2($returnFormat, strtotime($date->format('Y-m-d H:i:s')));
	}
}

if ( ! function_exists('diffdates'))
{
	function diffdates($fecha_mayor='now_end', $fecha_menor='now', $possitive=true)
	{
		$fecha_mayor = date2('timestamp', $fecha_mayor);
		$fecha_menor = date2('timestamp', $fecha_menor);

		if ($possitive and $fecha_menor > $fecha_mayor)
			list ($fecha_menor, $fecha_mayor) = [$fecha_mayor, $fecha_menor];

		$diff = $fecha_mayor - $fecha_menor;
		return $diff;
	}
}
