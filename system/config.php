<?php 

/**
* @author Erik Mota Galindo
* @date_create: 17 de Julio del 2018
* @date_update: 17 de Julio del 2018
* @return: string
**/

/* Clase que contendra la configuracion de nuestra aplicacion */
class Config
{
	/* Funcion que se encargara de cargar los datos de configuracion de 
	nuestra aplicacion actualmente */
	public static function set( $config = array() ){
		foreach ($config as $key => $value)
			self::$config[$key] = $value;
	}

	/* Puedes obtener informacion de la configuracion actual de cada aplication */
	public static function get($key = null){
		if(isset(self::$config[$key]))
			return self::$config[$key];
		else
			return null;
	}

	/* funcion que te permite devolver todas las configuraciones en un metodo array
	comprendido el cual es guardado dentro de una serie de parametros comprendidos
	en la configuracion */
	public static function getAllConfig(){
		return self::$config;
	}

	/* -- Constructor de la clase --  */
	public function __construct(){


		self::$config['uri'] = substr($_SERVER["REQUEST_URI"], 1); // host actual
		self::$config["method"] = $_SERVER["REQUEST_METHOD"]; // obteniedo el method que se utilizara formalmente
		$app = str_ireplace("index.php", "", "/var/www/html/red_social_new/public/index.php");
		self::$config['folder_config'] = "{$app}/../config";

		# busca con formalidad si el sistema tiene una escena de conexion segura o no
		# https or http
		self::$config["REQUEST_SCHEMA"] = !empty( $_SERVER["REQUEST_SCHEME"] ) ? $_SERVER["REQUEST_SCHEME"] : "http"; 

		# verifica si existe un controlador determinado descomprimiendo la url
		if(!self::$config['uri'])
			self::$config["section_uri"] = [];
		else{
			$path = str_ireplace($_SERVER["DOCUMENT_ROOT"], "", $app);
			$host = str_ireplace(self::$config['uri'], "", $path);
			self::$config["location"] = explode("?", $host);
			self::$config["query"] = empty(self::$config["location"][1]) ? [] : $_GET;
			self::$config["section_uri"] = explode("/", self::$config["location"][0] );
		}
	}

	private static $config;
}