<?php
include("table_migrations.php");

# clase encargada de las migraciones hacia la base de datos
class Migrations extends table_migrations{
	private static $migrations;
}