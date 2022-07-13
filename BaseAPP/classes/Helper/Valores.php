<?php
/**
 * Helper/Valores.php
 * @filesource
 */

namespace Helper;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

class Valores
{
	public static function letrasEnMinuscula ():array
	{
		return ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
	}

	public static function letrasEnMayuscula ():array
	{
		return ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
	}

	public static function vocalesEnMinuscula ():array
	{
		return ['a', 'e', 'i', 'o', 'u'];
	}

	public static function vocalesEnMayuscula ():array
	{
		return ['A', 'E', 'I','O', 'U'];
	}

	public static function tildesEnMinuscula ($inc_hard = false):array
	{
		return array_merge(
			['á', 'é', 'í', 'ó', 'ú', 'ñ'],
			($inc_hard ? ['ä', 'ë', 'ï', 'ö', 'ü'] : [])
		);
	}

	public static function tildesEnMayuscula ($inc_hard = false):array
	{
		return array_merge(
			['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
			($inc_hard ? ['Ä', 'Ë', 'Ï', 'Ö', 'Ü'] : [])
		);
	}

	public static function numeros ():array
	{
		return [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
	}

	public static function simbolos ($inc_hard = false):array
	{
		return array_merge(
			['#', '@', '$', '&', '¿', '?', '¡', '!', '%', '=', '+', '-', '*', '_', '/', '.', ',', ';', ':', '(', ')', '{', '}', '[', ']', '"', '\''], 
			($inc_hard ? ['°', '~', '|', '\\', '<', '>'] : [])
		);
	}

	public static function meses ():array
	{
		return ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'];
	}

	public static function dias ():array
	{
		return ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sabado'];
	}

	public static function tab ():string
	{
		return "\t";
	}

	public static function enter ():string
	{
		return "\n";
	}
}