<?php

include("model_migrations.php");

/* Migraciones hacia la tabla */
class table_migrations extends model_migrations {

	# agregando campos dentro de una tabla hacer la migracion
	public static function add_fields($fields = array()){
		
		foreach ($fields as $field => $data)
			$field_defaults[$field] = $data;

		self::$table['fields'] = $field_defaults;
	}

	# funcion que se encarga de alterar una tabla actual dentro de la db
	public static function alter_table($columns){

		# tiene que exitir la tabla y debe poder ser cambiado de tipo
		if( isset(self::$table["name"]) && is_array($columns) ){

			$commits = "\n# Alteraciones de la Tabla ".self::$table["name"]."\n# Date: ".date("d-m-Y H:i:s")."\n# Fields Alterados: ". count($columns);

			# estableciendo que se alterara una serie de campos
			$sql = "{$commits}\n\nALTER TABLE ".self::$table["name"];

			for($i = 0; $i < count($columns); $i++){

				if($i > 0)
					$sql = "{$sql},";

				switch ($columns[$i]["action"]) {
					case "add": $action = "ADD"; break;
					case "delete": $action = "DROP"; break;
					case "modify": $action = "MODIFY"; break;
					case "change": $action = "CHANGE"; break;
					default: $action = "CHANGE"; break;
				}

				if($action != "DROP"){

					switch ($columns[$i]["type"]) {
						case "string": $type = "VARCHAR"; $length = 255; break;
						case "enum": $type = "ENUM"; break;
						case "text": $type = "TEXT"; break;
						case "bool": $type = "BOOLEAN"; $default = 0;break;
						case "integer": $type = "INT"; $length = 11; break;
						case "biginteger": $type = "BIGINT"; break;
						case "float": $type = "FLOAT"; $lenght = 2; break;
						case "double": $type = "DOUBLE"; break; 
						case "decimal": $type = "DECIMAL"; $length = 4; break;
						case "date": $type = "DATE"; break;
						case "datetime": $type = "DATETIME"; break;
						case "time": $type = "TIME"; break;
						case "references":
							
							$type = "BIGINT";
							$columns[$i]["null"] = false;

							$foreign_key = !strcmp("_id", $columns[$i]["name"]) ? $columns[$i]["name"] : "{$columns[$i]['name']}_id";

							self::$table["foreign_key"][] = [
								"index" => $foreign_key,
								"table" => isset( $columns[$i]["table"] ) ? $columns[$i]["table"] :  $columns[$i]['name'],
								"column" => isset($columns[$i]["column"]) ? $columns[$i]["column"] : "id"
							];

						break;
						case "year": $type = "YEAR"; $length = 4; break;
					}
				}

				if( strcmp("references", $columns[$i]["type"]) )
					$sql = "{$sql} {$action} COLUMN {$columns[$i]['name']}";
				else
					$sql = "{$sql} {$action} COLUMN {$foreign_key}";

				if( !isset($columns[$i]["rename"]) && ($action == "CHANGE"  || $action == "MODIFY"))
					$sql = "{$sql} {$columns[$i]['name']} {$type}";

				if( isset($columns[$i]["rename"]) && ($action == "CHANGE" || $action == "MODIFY") )
					$sql = "{$sql} {$columns[$i]['rename']} {$type}";


				if( $action == "ADD")
					$sql = "{$sql} {$type}";


				# primero verifica que exista la variable
				if( isset($columns[$i]["length"])){
					
					if($columns[$i]["length"] > 0)
						$length = $columns[$i]["length"];

					if(is_array( $columns[$i]["length"]  )){
						$values = $columns[$i]["length"];
						$length = "";
						for ($j = 0; $j < count($values); $j++) { 
							if($j > 0)
								$length = "{$length},";

							$length = "{$length}'{$values[$j]}'";
						}
					}
				}

				if( isset($length) )
					$sql = "{$sql}($length)";

				# verificando si su valor es nulo o no
				if( isset( $columns[$i]["null"] ) ){
					if($columns[$i]["null"] == false)
						$sql = "{$sql} NOT NULL";
				}

				# verificando si hay valores por defecto
				if( isset($columns[$i]["default"]) )
					$sql = "{$sql} DEFAULT '{$columns[$i]['default']}'";

				unset($type);
				unset($length);
				unset($action);
			}

			$sql = "{$sql}". self::getForeignKeys("alter") .";";

			$mysql = DB::getConnect();
			$query = $mysql->query($sql);

			if($query){
				self::create_file_sql($sql, "alter-table");
				return true;
			}
			else
				print( nl2br($sql) );
		}

		return false;

	}

	# funcion que permite hacer un renombramiento de la tabla
	public static function rename_table($name){

		if(isset(self::$table["name"]))
		{
			$sql = "ALTER TABLE ".self::$table["name"]." RENAME TO {$name}";
			$mysql = DB::getConnect();
			$query = $mysql->query($sql);
			if($query){
				self::create_file_sql($sql, "rename-table");
				return true;
			}
			else
				return false;
		}

		return false;
	} 

	/* dando un nombre a la tabla que va hacer creada en nuestra base de datos */
	public static function add_name_table($name, $create_file = false){
		self::$table["name"] = !empty($name) ? $name : null;
		self::$table["file"] = $create_file;
	}

	/* funcion que analizara a la hora de crear la table */
	public static function create_table(){

		# verificando que no existe la tabla crea una migracion de la misma
		if( !self::table_exists() ){
			self::create_table_today();
			return true;
		}
		else
			return false;
	}

	/* funcion que te permite verificar la existencia de una  */
	public static function table_exists(){
		$mysql = DB::getConnect();
		return $mysql->query("describe ".self::$table["name"]);
	}

	/* funcion que se encarga de la eliminación de una tabla */
	public static function delete_table(){
		# verifica que la tabla a eliminar existe primeramente
		if(self::table_exists())
			self::delete_table_today();

		return true;
	}

	/* funcion que se encarga de eliminar la tabla actual */
	private static function delete_table_today(){
		$mysql = DB::getConnect();
		return $mysql->query("drop table ".self::$table["name"]) or die("Ocurrio un problema al eliminar la tabla");
	}

	/* funcion que se encarga de obtener todas las claves foranea que se 
	desean introducir a las tablas */
	private static function getForeignKeys($type_action = "create"){

		$sql  = "";
		$keys = isset(self::$table["foreign_key"]) ? self::$table["foreign_key"] : array();
		
		for ($i=0; $i < count($keys); $i++) {

			$field  = $keys[$i]["index"];
			$column = isset($keys[$i]["column"]) ? $keys[$i]["column"] : "id";

			if($type_action == "create")
				$sql = ",\n\tINDEX ({$field}),\n\tFOREIGN KEY ({$field})\n\tREFERENCES {$keys[$i]['table']}({$column})\n\t\tON UPDATE CASCADE ON DELETE RESTRICT";
			else
				$sql = ", ADD INDEX ({$field}), ADD FOREIGN KEY ({$field}) REFERENCES {$keys[$i]['table']}({$column}) ON UPDATE CASCADE ON DELETE RESTRICT"; 
		}

		return $sql;

	}

	/* funcion que permite la creacion de las tablas */
	private static function create_table_today(){

		/* obtiene el string de los campos ha realizar */
		$string_fields = self::getStringField();

		$commits = "/* Tabla realizada con exito \nDate: ".date("d-m-Y")." ".date("H:i:s")." \nName: ".self::$table['name']."\nFields: ".(count(self::$table["fields"]) + 3)." */";

		$string_sql = "{$commits}\nCREATE TABLE IF NOT EXISTS ".self::$table["name"]." (\n\tid BIGINT NOT NULL AUTO_INCREMENT,{$string_fields}\n\tdate_created DATETIME,\n\tdate_update DATETIME, \n\tPRIMARY KEY (id)". self::getForeignKeys() ."\n);";

		# funcion que se encarga de crear un archivo
		if( self::$table["file"] )
			self::create_file_sql($string_sql);

		# funcion que creara el modelo que estara relacionada a nuestra tabla
		if( self::$table["file"] )
			self::create_model( self::$table["name"] );

		# esto nos permita hacer una conexion directa a la db
		$mysql = DB::getConnect();
		$mysql->query($string_sql) or die( nl2br($mysql->error) );
	}

	# funcion que se encarga de hacer un archivo sql que sera exportado
	private static function create_file_sql($sql, $action = "create-table"){

		$dir = Config::get("folder_config")."/db/migrations/";
		$file = "$dir/".date("Ymd").date("His")."_".$action."_".self::$table["name"].".sql";
		$fp = fopen($file,"w");
		fwrite($fp, $sql);
		fclose($fp);
	}

	private static function getField($name, $field, $len = null, $default = null, $null = true,$table = null){

		# obteniendo el tipo y el valor por defecto	
		# de un field
		switch ($field) {
			
			case "string": $type = "VARCHAR"; $length = 255; break;
			case "enum": 

				$length = "";
				$type ="ENUM";

				$i = 0;
				# se descomponen los caracteres a mostrar en el enum
				foreach ($len as $char){
					if($i > 0)
						$length = "{$length},'{$char}'";
					else
						$length = "{$length}'{$char}'";
					$i++;
				}

				$null = false;

			break;
			case "text": $type = "TEXT"; break;
			case "bool": $type = "BOOLEAN"; $default = 0;break;
			case "integer": $type = "INT"; $length = 11; break;
			case "biginteger": $type = "BIGINT"; break;
			case "float": $type = "FLOAT"; $lenght = 2; break;
			case "double": $type = "DOUBLE"; break; 
			case "decimal": $type = "DECIMAL"; $length = 4; break;
			case "date": $type = "DATE"; break;
			case "datetime": $type = "DATETIME"; break;
			case "time": $type = "TIME"; break;
			case "year": $type = "YEAR"; $length = 4; break;
			case "references":
				$type = "BIGINT";
				$null = false;
			break;
		}

		# cambiando de longitud de acuerdo a lo que se desea realizar en la tabla
		# y verificaba si el valor introducido es un numero entero
		if($len > 0 && is_int($len) ){
			$length = $len;
		}

		# el tipo y el nombre de la tabla
		$string = "\n\t{$name} {$type}";

		# verificamos que  exista una longitud y luego si espositiva y se la agregamos
		if( isset($length) ){
			if($length > 0 || is_string($length) )
				$string = "{$string}({$length})";
		}

		if( $null == false )
			$string = "{$string} NOT NULL";

		# valores por defecto durante la creacion de una tabla
		if( isset($default) ){
			if(is_int($default))
				$string = "{$string} DEFAULT {$default}";
			elseif(is_string($default))
				$string = "{$string} DEFAULT '{$default}'";
		}

		$string = "{$string},";

		return $string; 

	}

	private static function getStringField(){

		$string = "";

		foreach (self::$table["fields"] as $field => $data) {

			if( is_string($data) ){

				# guardando las variables que haran referencia sobre un tabla
				if($data == "references"){

					$name = $field;
					$field = !strcmp("_id", $field) ? $field : "{$field}_id";

					self::$table["foreign_key"][] = [
						"index" => $field,
						"table" => isset( $data["table"] ) ? $data["table"] : $name
					];
				}

				$string = $string.self::getField($field, $data);
			}

			elseif( is_array($data) ){

				$type    = $data["type"]; 
				$length  = empty( $data["length"] )  ? null : $data["length"];
				$default = empty( $data["default"] ) ? null : $data["default"];
				$null    = true;

				# guardando las variables que haran referencia sobre un tabla
				if($type == "references"){

					$name = $field;
					$field = !strcmp("_id", $field) ? $field : "{$field}_id";

					self::$table["foreign_key"][] = [
						"index" => $field,
						"table" => isset( $data["table"] ) ? $data["table"] : $field,
						"column"=> isset( $data["column"] ) ? $data["column"] : "id"
					];
				}

				if(isset($data["null"]) && $data["null"] == 0)
					$null = false;

				$string = $string.self::getField($field, $type, $length, $default, $null);

			}

		}

		return "\t{$string}";

	}

	protected static $table;
}