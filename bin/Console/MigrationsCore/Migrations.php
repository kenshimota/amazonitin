<?php

# esta funcion se ocupa las migraciones
require_once "table_migrations.php";

# clase encargada de las migraciones hacia la base de datos
class Migrations extends table_migrations{

	# funcion que ejecuta las migraciones de un proyecto de forma exclusiva
	public function exec_migrations(){

		# estableciendo conexiones
		$connect = DB::getConnect() ? DB::getConnect() : exit(1);
		$dir =  "{$_SERVER["DOCUMENT_ROOT"]}/../config/db/migrations/";
		$querys = array();
		
		# verifica la existencia de carpeta en donde encontrara los modelos
		if(is_dir($dir)){
			# abre directorio de los modelos
			if($dh = opendir($dir)){
				$querys = scandir($dir);
				foreach($querys as $migration){
					if($migration != "." && $migration != "..")
						$connect->query( $this->execQueryMigration("{$dir}{$migration}") ) or die("Ocurrio un archivo con la migracion {$migration}, Mysql: {$connect->error}");
				}

			}
			# cierra el directorio de los modelos
			closedir($dh);
		}
	}

	# esta funcion se encarga de hace run query de migracion
	private function execQueryMigration($file){
		
		// consulta a la db
		$query = "";
		if(is_file($file)){
			$fp = fopen($file,"r");
			while(!feof($fp))
				$query = fread($fp, 1024);
			fclose($fp);
		}

		return $query;
	}
	
	private static $connect;
	private static $migrations;
}