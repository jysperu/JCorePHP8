<?php
namespace Modelo;

interface Crypter
{
	public function encrypt (string $to_encrypt, string $key = null):string;

	public function decrypt (string $to_decrypt, string $key = null):string;
}