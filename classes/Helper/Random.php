<?php
/**
 * Helper/Random.php
 * @filesource
 */

namespace Helper;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

class Random
{
	/**
	 * clave ()
	 * Obtener una clave aleatorio
	 *
	 * @param int $digitos	Cantidad de dígitos
	 * @param bool $min 	Incluír mínimos
	 * @param bool $may 	Incluír mayúsculas
	 * @param bool $num 	Incluír Números
	 * @param bool $tilde 	Incluír Tildes
	 * @param bool $sym 	Incluír Símbolos
	 * @return string
	 */
	public static function clave (
		int  $digitos = null,
		bool $min     = true,
		bool $may     = true,
		bool $num     = true,
		bool $tilde   = true,
		bool $sym     = true
	):string
	{
		if (is_null($digitos))
		{
			$digitos = rand(10, 15);
		}
		elseif ($digitos > 16)
		{
			$digitos = 16;
		}
		elseif ($digitos < 5)
		{
			$digitos = 5;
		}

		$primer_caracter = static :: salt (1, $min, $may, false, false, false, false); # El primer caracter se recomienda que sea letra
		return $primer_caracter . static :: salt ($digitos - mb_strlen($primer_caracter), $min, $may, $num, $tilde, $sym, false);
	}

	/**
	 * salt ()
	 * Obtener una salt aleatorio
	 *
	 * @param int $digitos	Cantidad de dígitos
	 * @param bool $min 	Incluír mínimos
	 * @param bool $may 	Incluír mayúsculas
	 * @param bool $tilde 	Incluír Tildes
	 * @param bool $num 	Incluír Números
	 * @param bool $sym 	Incluír Símbolos
	 * @param bool $spc 	Incluír Espacios
	 * @param bool $sym_h 	Si se incluye símbolos, considerar los difíciles
	 * @return string
	 */
	public static function salt (
		int  $digitos,
		bool $min   = true , 
		bool $may   = true , 
		bool $num   = true , 
		bool $tilde = false, 
		bool $sym   = false, 
		bool $spc   = false, 
		bool $sym_h = false
	):string
	{
		if ($digitos <= 0)
			return '';

		if ( ! $min and ! $may and ! $tilde and ! $num and ! $sym and ! $spc)
			return '';

		$return = [];

		do
		{
			$rand = rand(1, 10);

			switch($rand)
			{
				case 1:case 4:case 7: //Letra
					if ( ! $min AND ! $may AND ! $tilde)
						continue 2;

					$return[] = static :: letra(1, $min, $may, $tilde);
					break;

				case 2:case 5:case 8://Número
					if ( ! $num)
						continue 2;

					$return[] = static :: numero(1);
					break;

				case 3:case 6:case 9://Símbolo
					if ( ! $sym)
						continue 2;

					$return[] = static :: simbolo(1, $sym_h);
					break;

				case 10://Space
					if ( ! $spc)
						continue 2;

					$return[] = ' ';
					break;
			}
		}
		while(count($return) < $digitos);

		return implode('', $return);
	}

	/**
	 * licencia()
	 * Obtener un licencia aleatoria
	 *
	 * @param bool $generic Modo genérico
	 * @return string
	 */
	public static function licencia (
		bool $generic = false
	):string
	{
		$part = function ($digitos = 5) {
			return static :: salt (
				$digitos,
				false, # min
				true,  # may
				true,  # num
				false, # tilde
				false, # sym
				false  # space
			);
		};

		return implode('-', [
			$part ( $generic ? 5 : 6),
			$part (),
			$part (),
			$part (),
			$part ( $generic ? 5 : 9)
		]);
	}

	/**
	 * letra()
	 * Obtener una letra aleatoria
	 *
	 * @param int $digitos 	Cantidad de dígitos
	 * @param bool $min 	Incluír mínimos
	 * @param bool $may 	Incluír mayúsculas
	 * @param bool $tilde 	Incluír Tildes
	 * @return string
	 */
	public static function letra (
		int $digitos = 1, 
		bool $min = TRUE,
		bool $may = TRUE,
		bool $tilde = FALSE
	):string
	{
		if ($digitos <= 0)
			return '';

		if ( ! $min and ! $may and ! $tilde)
			return '';

		$letras = [];
		$min and $letras = array_merge($letras, Valores :: letrasEnMinuscula());
		$may and $letras = array_merge($letras, Valores :: letrasEnMayuscula());

		if ($tilde)
		{
			if ( ! $min and ! $may)
			{
				$min = true;
				$may = true;
			}

			$min and $letras = array_merge($letras, Valores :: tildesEnMinuscula());
			$may and $letras = array_merge($letras, Valores :: tildesEnMayuscula());
		}

		$return = [];
		do
		{
			$return[] = $letras[rand(0, count($letras) - 1)];
		}
		while(count($return) < $digitos);

		return implode('', $return);
	}

	/**
	 * numero()
	 * Obtener una numero aleatoria
	 *
	 * @param int $digitos 	Cantidad de dígitos
	 * @return string
	 */
	public static function numero (
		int $digitos = 1
	):string
	{
		if ($digitos <= 0)
			return '';

		$extra = '';
		if ($digitos > 9)
		{
			$digitos_extras = $digitos - 9;
			$extra = static :: numero ($digitos_extras);
			$digitos -= $digitos_extras;
		}

		$rand_min = 1;
		$digitos > 1 and $rand_min = pow(10, $digitos - 1);
		$rand_max = pow(10, $digitos) - 1;

		return rand($rand_min, $rand_max) . $extra;
	}

	/**
	 * simbolo()
	 * Obtener una numero aleatoria
	 *
	 * @param int $digitos 	Cantidad de dígitos
	 * @param bool inc_hard Incluír simbolos dificiles
	 * @return string
	 */
	public static function simbolo (
		int $digitos = 1,
		bool $inc_hard = false
	):string
	{
		if ($digitos <= 0)
			return '';

		$simbolos = Valores :: simbolos($inc_hard);

		$return = [];
		do
		{
			$return[] = $simbolos[rand(0, count($simbolos) - 1)];
		}
		while(count($return) < $digitos);

		return implode('', $return);
	}
}