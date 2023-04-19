<?php
namespace Modelo;

interface ErrorControlSaver
{
	public static function saveLog (string $hash, string $message, mixed $code, mixed $severity, array $metadata, string $filepath, string $fileline, array $trace, string $clase, string $baseclase):void;
}