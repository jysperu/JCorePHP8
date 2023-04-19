<?php
namespace Modelo;

interface DBManager
{
	public function select (string $query):DBResult;

	public function insert (string $query);

	public function update (string $query);

	public function delete (string $query);
}