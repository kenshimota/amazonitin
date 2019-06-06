<?php
include("table_migrations.php");

# clase encargada de las migraciones hacia la base de datos
class Migrations extends table_migrations{

	# funcion que ejecuta las migraciones de un proyecto de forma exclusiva
	public function exec_migrations(){

		# estableciendo conexiones
		$connect = DB::getConnect() ? DB::getConnect() : exit(1);
		$dir ="{$_SERVER["DOCUMENT_ROOT"]}/../config/db/migrations";

		# verifica la existencia de carpeta en donde encontrara los modelos
		if(is_dir($dir)){

			$migrations_json = $this->jsonMigration($dir); // obteniendo las consultas realizadas en un json

			# abre directorio de los modelos
			if($dh = opendir($dir)){
				$querys = scandir($dir);
				foreach($querys as $migration){

					print("{$dir}/{$migration}<br>");

					if($migration != "." && $migration != ".." && empty($migrations_json->$migration) ){

						// cuando inicio la ejecucion del script
						$date = new DateTime("now");
						
						// mostrando y ejecutando la migracion
						print("-------- execute migration {$migration} ------------\n");
						$query = $this->execQueryMigration("{$dir}/{$migration}");
						print("Mysql Query:\n\n{$query}\n\n");
						$connect->query($query) or die("Error in migration {$migration}, Mysql: {$connect->error}");
						$migrations_json->$migration = ["time_exec" => $date, "dir" => $dir];

						// calculando tiempo de ejecucion del script
						$result = $date->diff( new DateTime("now") );
						print("-------- Finish Migration {$result->f}s----------\n");

						$this->addJsonMigration($migrations_json, $dir);
					}
				}
			}

			# cierra el directorio de los modelos
			closedir($dh);
		}
		else 
			die("the folder {$dir} not found...!");
	}

	// esta funcion se ocupa de obtener el json de migraciones o crear el archivo
	private function jsonMigration($dir){
		$file = "{$dir}/../migrations.json";
		$json = "";

		// si existe el archivo va a obtener los datos nuevos del archivo
		if(is_file($file)){
			$fp = fopen($file, "r");
			while(!feof($fp))
				$json = $json.fread($fp, 1024);
			fclose($fp);
			return json_decode($json);
		}
		else
		{
			$fp = fopen($file, "w");
			fwrite($fp, json_encode([]) , 1024);
			fclose($fp);
			return new stdClass([]);
		}
	}

	// agregando los datos a un archivo json para saber que tablas an sido migradas
	private function addJsonMigration($json, $dir){
		$json = json_encode($json);
		$file = "{$dir}/../migrations.json";
		$fp = fopen($file, "w");
		fwrite($fp, $json, strlen($json));
		fclose($fp);
	}

	# esta funcion se encarga de hace run query de migracion
	private function execQueryMigration($file){
		
		// consulta a la db
		$query = "";
		if(is_file($file))
			$query = file_get_contents($file);
		else
			die("File Not Found {$file}");

		return $query;
	}
	
	private static $connect;
	private static $migrations;
}