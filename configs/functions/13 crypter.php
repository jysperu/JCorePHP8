<?php

use Contract\Crypter as CrypterInterface;

if ( ! function_exists('encrypt'))
{
	/**
	 * Encrypt a message
	 * 
	 * @param string $message - message to encrypt
	 * @param string $key - encryption key
	 * @return string
	 */
	function encrypt(string $message, string $key = 'JApi', CrypterInterface $Driver = null)
	{
		is_null ($Driver) and $Driver = new Driver\Crypter\Principal();

		return (string) $Driver -> encrypt($message, $key);
	}
}

if ( ! function_exists('decrypt'))
{
	/**
	 * Decrypt a message
	 * 
	 * @param string $encrypted - message encrypted with safeEncrypt()
	 * @param string $key - encryption key
	 * @return string
	 */
	function decrypt(string $encrypted, string $key = 'JApi', CrypterInterface $Driver = null)
	{
		is_null ($Driver) and $Driver = new Driver\Crypter\Principal();

		return (string) $Driver -> decrypt($encrypted, $key);
	}
}