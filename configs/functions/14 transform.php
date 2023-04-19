<?php

if ( ! function_exists('transform_time'))
{
	//Convertir Tiempo
	//$return = array|completo|reducido
	function  transform_time($seg, $return = 'reducido', $inverted = true)
	{
		static $txtplu  = ['segundos', 'minutos', 'horas', 'dias', 'semanas', 'meses', 'años'];
		static $txtsing = ['segundo',  'minuto',  'hora',  'dia',  'semana',  'mes',   'año' ];

		$r = ['sg' => $seg < 1 ? (round($seg * 1000) / 1000) : round($seg)];

		$r['mi'] = floor($r['sg']/60); $r['sg'] -= $r['mi']*60;
		$r['ho'] = floor($r['mi']/60); $r['mi'] -= $r['ho']*60;
		$r['di'] = floor($r['ho']/24); $r['ho'] -= $r['di']*24;
		$r['se'] = floor($r['di']/7 ); $r['di'] -= $r['se']*7 ;
		$r['me'] = floor($r['se']/4 ); $r['se'] -= $r['me']*4 ;
		$r['añ'] = floor($r['me']/12); $r['me'] -= $r['añ']*12;

		$obl = false;

		if ($r['añ']<>0 or $obl) $obl = true;
		$r['añ'] = [$r['añ'], $r['añ']==1?$txtsing[6]:$txtplu[6], $obl];

		if ($r['me']<>0 or $obl) $obl = true;
		$r['me'] = [$r['me'], $r['me']==1?$txtsing[5]:$txtplu[5], $obl];

		if ($r['se']<>0 or $obl) $obl = true;
		$r['se'] = [$r['se'], $r['se']==1?$txtsing[4]:$txtplu[4], $obl];

		if ($r['di']<>0 or $obl) $obl = true;
		$r['di'] = [$r['di'], $r['di']==1?$txtsing[3]:$txtplu[3], $obl];

		if ($r['ho']<>0 or $obl) $obl = true;
		$r['ho'] = [$r['ho'], $r['ho']==1?$txtsing[2]:$txtplu[2], $obl];

		if ($r['mi']<>0 or $obl) $obl = true;
		$r['mi'] = [$r['mi'], $r['mi']==1?$txtsing[1]:$txtplu[1], $obl];

		if ($r['sg']<>0 or $obl) $obl = true;    
		$r['sg'] = [$r['sg'], $r['sg']==1?$txtsing[0]:$txtplu[0], $obl];

		if($inverted){
			$r = array_merge(array('añ'=>[], 'me'=>[], 'se'=>[], 'di'=>[], 'ho'=>[], 'mi'=>[], 'sg'=>[] ), $r);
		}

		if($return=='array'){
			return $r;
		}

		$s = '';
		foreach($r as $x=>$y){
			if(!$y[2] and $return=='reducido') continue;
			$s .= ($s==''?'':' ').$y[0].' '.$y[1];
		}

		return $s;
	}
}

if ( ! function_exists('transform_size'))
{
	//Transformar tamaño
	function transform_size($size)
	{
		$size = (double) $size;

		if ($size < 1) 
			return round($size, 8) . ' b';

		static $units = ['B','Kb','Mb','Gb','Tb','Pb'];

		$size = round($size / pow(1024, ($i = floor(log($size, 1024)))), 4);
		$size.= ' ' . $units[$i];
		return $size;
	}
}