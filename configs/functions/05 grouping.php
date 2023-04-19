<?php

if ( ! function_exists('grouping'))
{
	function grouping($array, $opts=[])
	{
		$opts = array_merge([
			'prefix' => [NULL, NULL, NULL],//Singular, Plural, Zero
			'suffix' => [NULL, NULL, NULL],//Singular, Plural, Zero
			'union'  => [', ', ' y '],     //normal, last
		], $opts);

		is_string($array) and $array = [ $array ];
		$array = array_unique($array);

		$r = '';
		$c = count($array);
		$t = 2;//Zero

			if ($c == 0) { $t=2; }
		elseif ($c == 1) { $t=0; }
		elseif ($c >= 2) { $t=1; }

		if(is_string($opts['prefix'])) $opts['prefix'] = [$opts['prefix']];
		if( ! isset ($opts['prefix'][2]) or is_null($opts['prefix'][2])){$opts['prefix'][2] = $opts['prefix'][0];}
		if( ! isset ($opts['prefix'][1]) or is_null($opts['prefix'][1])){$opts['prefix'][1] = $opts['prefix'][0];}

		if(is_string($opts['suffix'])) $opts['suffix'] = [$opts['suffix']];
		if( ! isset ($opts['suffix'][2]) or is_null($opts['suffix'][2])){$opts['suffix'][2] = $opts['suffix'][0];}
		if( ! isset ($opts['suffix'][1]) or is_null($opts['suffix'][1])){$opts['suffix'][1] = $opts['suffix'][0];}

		if(is_string($opts['union'])) $opts['union'] = [$opts['union']];
		if(is_null  ($opts['union'][0])) $opts['union'][0] = ' ';
		if( ! isset ($opts['union'][1]) or is_null($opts['union'][1])){$opts['union'][1] = $opts['union'][0];}

		$r .= $opts['prefix'][$t];

			if ($c == 0) {}
		elseif ($c == 1) {$r.=$array[0];}
		elseif ($c >= 2) {
			$last = array_pop($array);
			$r.=implode($opts['union'][0], $array);
			$r.=$opts['union'][1].$last;
		}

		$r .= $opts['suffix'][$t];
		return $r;
	}
}
