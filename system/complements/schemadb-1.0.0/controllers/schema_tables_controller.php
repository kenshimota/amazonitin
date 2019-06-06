<?php

/* controlador de todas las tablas que necesito dentro del sistema */
class schema_tablesController extends Controller{
	
	/* funcion que hace de indice a las tablas actuales */
	function indexAction(){

		$migrations_table = new Migrations();
		
		/*$migrations_table->add_name_table("users", true);
		
		$migrations_table->add_fields([
			
			"email" => [
				"type" => "string",
				"null" => false
			],
			
			"password" => [
				"type" => "string",
				"null" => false
			],

			"email_secondary" => "string",
			"session_count" => "biginteger",
			"signin_last" => "datetime",
			"logout_last" => "datetime",
			"question_firts" => "string",
			"response_firts" => "string",
			"question_secondary" => "string",
			"response_secondary" => "string",
			"status" => [
				"type" => "references",
				"table" => "users_status"
			]
		]);

		// creando tabla usuarios
		$migrations_table->create_table();

		// Naciones
		$migrations_table->add_name_table("nations", true);
		$migrations_table->add_fields([
			"name" => "string",
			"code" => "integer"
		]);
		$migrations_table->create_table();


		// estados
		$migrations_table->add_name_table("national_statuses", true);
		$migrations_table->add_fields([
			"nations" => "references",
			"name" => "string"
		]);
		$migrations_table->create_table();

		// cidudades
		$migrations_table->add_name_table("cities", true);
		$migrations_table->add_fields([
			"name" => "string",
			"national_statuses" => "references"
		]);
		$migrations_table->create_table();

		// imagenes
		$migrations_table->add_name_table("images", true);
		$migrations_table->add_fields([
			"path" => "string",
			"file" => "string",
			"height" => "integer",
			"width" => "integer",
			"host" => "string"
		]);
		$migrations_table->create_table();

		// detalles de usuarios
		$migrations_table->add_name_table("details_users", true);
		$migrations_table->add_fields([
			"users" => ["type" => "references", "table" => "users"],
			"name" => "string",
			"lastname" => "string",
			"photo_profile" => ["type" => "references", "table" => "images"],
			"city" => ["type" => "references", "table" => "cities"],
			"national_status" =>["type" => "references", "table" => "national_statuses"],
			"nation" => ["type" => "references", "table" => "nations"],
			"account_type" => "string",
			"symbol" => ["type" => "string", "length" => 1],
			"number_identification" => ["type" => "string", "length" => 20],
			"direction" => "string",
			"page_web" => "string"
		]);
		$migrations_table->create_table();

		// creando los numeros de telefonos para contactar un usuario
		$migrations_table->add_name_table("phones",true);
		$migrations_table->add_fields([
			"users" => "references",
			"number" => ["type" => "string", "length" => 11],
			"code" => ["type" => "references", "table" => "nations"]
		]);
		$migrations_table->create_table();

		$migrations_table->add_name_table("items", true);
		$migrations_table->add_fields([
			"users" => "references",
			"category_origin" => "biginteger",
			"category_primary" => "biginteger",
			"category_secondary" => "biginteger",
			"clicks" => "biginteger",
			"name" => "string",
			"description" => "text",
			"new" => [
				"type" => "bool",
				"default" => true
			],
			"price" => "biginteger",
			"expirate" => "date",
			"status_public" => [
				"type" => "bool",
				"default" => false
			],
			"state" => "biginteger",
			"city" => "biginteger"
		]);
		$migrations_table->create_table();

		$migrations_table->add_name_table("item_images", true);
		$migrations_table->add_fields([
			"images" => "references"
		]);
		$migrations_table->create_table();

		$migrations_table->add_name_table("item_images", true);
		$migrations_table->alter_table([
			[
				"action" => "add",
				"type" => "references",
				"name" => "items"
			]
		]);

		$migrations_table->add_name_table("likes", true);
		$migrations_table->add_fields([
			"users" => "references"
		]);
		$migrations_table->create_table();

		$migrations_table->add_name_table("item_likes", true);
		$migrations_table->add_fields([
			"users" => "references"
		]);
		$migrations_table->create_table();
		$migrations_table->alter_table([
			[
				"action" => "add",
				"type" => "references",
				"name" => "items"
			]
		]);
		$migrations_table->alter_table([
			[
				"action" => "add",
				"type" => "references",
				"name" => "likes"
			]
		]);*/
	}

	// funcion que migra las tablas
	function migrations_categoriesAction(){

		$migrations_table = new Migrations;
		
		// creando los categorias origines
		$migrations_table->add_name_table("categories_origin", true);
		$migrations_table->add_fields([
			"name" => "string"
		]);
		$migrations_table->create_table();

		//creando las categorias primarias
		$migrations_table->add_name_table("categories_primary", true);
		$migrations_table->add_fields([
			"name" => "string",
			"categories_origin_id" => "biginteger"
		]);
		$migrations_table->create_table();

		//creando las categorias secundarias
		$migrations_table->add_name_table("categories_secondary", true);
		$migrations_table->add_fields([
			"name" => "string",
			"categories_origin" => "biginteger",
			"categories_primary"=> "biginteger"
		]);
		$migrations_table->create_table();
	}

	/* funcion que se ocupa del cambio en la tabla usuarios */
	function testAction(){
		$a = new Migrations;
		$a->exec_migrations();
	}

	/* funcion que permite la creacion de las tablas */
	function createAction(){
	}

	var $post = array(
		"createAction"
	);


}