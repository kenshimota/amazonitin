<?php

/* clas de schema_db que se encarga de eliminar y rehacer la base de datos */
class schema_dbController extends Controller{

	/* indice de base de datos */
	public function indexAction(){
	}

	/* Controllador que se encargara de la creacion de la base de datos */
	public function createAction(){

		$migrations_table = new Migrations();

		# creando tabla de migraciones
		$migrations_table->add_name_table("schema_migrations", true);
		
		# insertando los campos que seran agregando a la tabla
		$migrations_table->add_fields([
			"name_table" => "string",
			"file" => ["type"=> "string", "length" => 800] # campo que contiene el sql
		]);

		# creando la primera tabla
		$table1 = $migrations_table->create_table();

		# funcion que guarda un conjunto de eventos que se registran con las tablas
		$migrations_table->add_name_table("schema_event_records", true);

		# agregando los campos a la tabla
		$migrations_table->add_fields([
			"name_table" => "string",
			"event_type" => ["type" => "enum", "length" => ["C" ,"U", "D"], "default" => "C"],
			"id_record" => ["type" => "biginteger", "null" => 0],
			"message" => "text"
		]);

		#creando la segunda tabla
		$table2 = $migrations_table->create_table();

		if(!$table1 && !$table2)
			echo json_encode([
				"status" => "faulire",
				"errors" => [
					"message" => "La base de datos ya existe no puede ser creada",
					"code" => 422
				]
			]);
		else
			echo json_encode([
				"status" => true,
				"success"=> [
					"message" => "La base de datos fue creada con exito",
					"code" => 201
				]
			]);
	}

	/* Controllador que se encargara de la eliminar la base de datos
	y los registros de migraciones */
	public function destroyAction(){
		$db = new DB;
		if(!$db->getConnect());
			$db->destroy_db();
	}
}