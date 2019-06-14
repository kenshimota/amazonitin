<?php

/* Esta clase se encargara de la creacion de registro dentro de los modelos
de a inserccion de datos dentro de los modelos que seran archivos referentes
a una table de mysql */
class model_migrations{

	public function __construct(){
		/* direccion de los modelos  y su conexion hacia la base de datos de forma directa */
		self::$models["dir"]= Config::get("path_application_default")."/models/";
		self::$models["connect"] = DB::getConnect() ? DB::getConnect() : exit(1);
	}

	/* funcion que se encargara de la creacion de un modelo de la aplicacion*/
	public static function create_model($name){
		$file = self::$models["dir"]."{$name}.php";
		$fp = fopen($file,"w");
		fwrite($fp, "<?php\n\nclass {$name} extends Model{\n\t\n}");
		fclose($fp);
	}

	/* funcion que registra los eventos de una tabla a otra */
	public static function event_record($table, $type, $id, $message = ""){

		# entrando a la tabla eventos para las migraciones
		$events = new schema_event_records();
		
		$events->insert([
			"name_table" => $table,
			"event_type" => $type,
			"id_record" => $id,
			"message" => $message
		]);

		return $events;
	}

	private static $models;
}