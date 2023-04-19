<?php
namespace Helper;

class CasterVal
{
	//=== Tipos de variables
	const MIXED         = 'MIXED';

	const Boolean       = 'Boolean';
	const Bool          = 'Boolean';

	const Numero        = 'Numero';

	const NumeroEntero  = 'Integer';
	const Entero        = 'Integer';
	const Integer       = 'Integer';

	const NumeroConDecimales = 'NumeroConDecimales';

	const Arreglo       = 'Array';
	const ArregloObjeto = 'ArrayDeObjetos';

	const Texto         = 'Texto';

	const FechaHora     = 'FechaHora';
	const Fecha         = 'Fecha';
	const Hora          = 'Hora';
	

	//=== Tamaños de variables
	const Ilimitado = -1;
	const SinLimite = -1;
	const Vacio     =  0;

	public static function convert (mixed $val, $tipo, ...$config )
	{
		//=== Sin conversión
		if ($tipo === static :: MIXED)
			return $val; ## No sufre conversión

		//=== Tipos de conversiones definidas

		if (in_array($tipo, [static :: Boolean, static :: Bool]))
		{
			$val = strtobool($val);
			return (bool) $val;
		}

		if (in_array($tipo, [static :: Numero]))
		{
			$val = floatval ($val);
			return $val;
		}

		if (in_array($tipo, [static :: NumeroEntero, static :: Entero, static :: Integer]))
		{
			$val = intval ($val);
			return (int) $val;
		}

		if (in_array($tipo, [static :: NumeroConDecimales]))
		{
			$decimales = isset($config[0]) ? $config[0] : 2;

			$val = floatval ($val);
			$val = number_format ($val, $decimales, '.', '');
			return $val;
		}

		if (in_array($tipo, [static :: Arreglo, static :: ArregloObjeto]))
		{
			if (is_string($val))
			{
				$json = json_decode($val, true);
				if ( ! is_null($json))
					$val = $json;
			}

			return (array) $val;
		}

		//=== Conversiones variable a STRING

		if (is_array($val))
			$val = json_encode($val);

		$val = (string) $val;

		if (in_array($tipo, [static :: Texto]))
		{
			return (string) $val;
		}

		//=== Conversiones generales

		$val = filter_apply('CasterVal/Convert/' . $tipo, $val);
		$val = filter_apply('ConvertVal/'        . $tipo, $val);

		return $val;
	}

	public static function defaultTypeVal ($tipo = static :: Texto)
	{
		if (in_array($tipo, [static :: Arreglo, static :: ArregloObjeto]))
			return [];

		return '';
	}

	public static function setMaxLen (mixed $val, $tipo, int $largo = 0, ? callable $on_reduced = null)
	{
		$val = static :: convert ($val, $tipo);

		if (is_object($val) or $largo <= 0)
			return $val;

		if (is_array($val))
		{
			$len = count($val);

			if ($len <= $largo)
				return $val;

			is_callable($on_reduced) and
			$on_reduced('Total de items del array supera el solicitado. Se procede a eliminar los últimos.');

			while(count($val) > $largo)
				array_pop($val);

			return $val;
		}

		$len = mb_strlen($val);

		if ($len > $largo)
		{
			is_callable($on_reduced) and
			$on_reduced('Total de caracteres del string supera el solicitado. Se procede a truncarlo.');

			$val = mb_substr($val, 0, $largo);
		}

		return $val;
	}
}