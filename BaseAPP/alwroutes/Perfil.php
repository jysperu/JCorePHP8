<?php
namespace AlwRoute;

class Perfil
{
	public function __construct ()
	{
		if ( ! APP() -> Logged)
		{
			return APP() -> response_error('Usuario no se encuentra logueado');
		}
	}
}