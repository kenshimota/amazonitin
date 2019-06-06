<?php

/* lista de class que se necesitan para manejar la pagina */
require_once 'Controller.php';
require_once 'View.php';
require_once 'Model.php';

/* Clase que de encargara de la redireccionamiento de la pagina 
los render */
class Router{

	/* Constructor de nuestra clase */
	public function __construct(){

		# array de las configuraciones
		$config  = Array(
			"controller" => null,
			"action" => null,
			"params" => []
		);

		# verificamos si la funcion esta en un espacio de de nombres
		$verify_space = $this->search_to_controller_space();

		if(Config::get('controller') == null && Config::get('action') == null){
			$config["controller"] = !empty(Config::get("section_uri")[0])  ? Config::get("section_uri")[0] : "home";
			$config["action"] = !empty(Config::get("section_uri")[1])  ? Config::get("section_uri")[1] : "index";
		}


		# empezamos a buscar los parametros que necesitare de ahora en adelante
		for ($i = 2; $i < count( $section_uri = Config::get("section_uri") ); $i = $i + 2){
			
			# deben existir los 2 tipos el parametros y su valor para ver que funcione
			if(isset($section_uri[$i], $section_uri[$i + 1])){

				$key   = $this->valueString($section_uri[$i]);
				$value = $this->valueString($section_uri[$i + 1]);
				$config["params"][$key] =  $value;
			}
		}

		# de acuerdo al method que este establecido buscando sus parametros
		switch ( Config::get("method") ) {
			case "PUT":
				# parametros que seran establecidos
				$paramsUpdate = fopen("php://input", "r");

				while ($data = fread($paramsUpdate, 1024)){
					$array_data = explode("&", $data);
					for($i = 0; $i < count($array_data); $i++){
						$string = explode("=", $array_data[$i]);
						$config["params"][ $string[0] ] = isset($string[1]) ? $string[1] : null;
					}
				}
			break;
			default:
				# agregando parametros obtenidos de los methodos post y get
				foreach ($_REQUEST as $key => $value)
					$config["params"][$key] = $value;
			break;
		}

		# agregando controlador y vista
		Config::set($config);

		$this->load();
	}

	# funcion que se encarga de devolver le valor como un string formal
	public function valueString($value){

		$chars = urldecode($value);
		$chars_html = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES | ENT_HTML5);
		foreach ($chars_html as $key => $value)
			if( strrpos($chars, $key) )
				return htmlentities($chars);

		return $chars;
	}

	/* funcion encargada de cargar la pagina principal */
	private function load(){

		# de esta forma que hemos obtenidos los controladores y las acciones
		# de la pagina pronto nos basaremos en los que viene
		$controller = Config::get("controller");
		$action = Config::get("action");

		# ubicacion de los controladores
		$path_controller = Config::get("path_controller");
		$path_app = Config::get("path_application");


		# archivo que debe tener el controlador de nuestra aplicacion
		$file_controller = get_include_path()."/{$controller}_controller.php";

		if(!file_exists($file_controller))
		{
			# instancia que permitira saber sobre los complementos instalados en nuestra
			# aplicacion
			$complements = new packComplementsFile();

			# funcion que hace la busqueda en los controladores de paquete
			if($complement = $this->search_to_controller_packs($controller)){
				
				# cambiando la configuracion de la renderizacion y el manejo de controladores
				Config::set([
					"path_application_default" => Config::get("path_application"),
					"path_application" => $complement["routes"]["path_application"],
					"controller" => $controller,
					"action" => $action
				]);

				# no se cual es la ruta pero veremos
				set_include_path( 
					realpath("{$complement['routes']['path_application']}/controllers")
				);
			}

			else
				Controller::consoleErrorSystem("No se puedo encontrar el controlador {$controller}..!");
		}

		$controller = Config::get("controller"); # controlador y acción
		$action = Config::get("action"); 

		# arrchivo que debe tener el controlador de nuestra aplicacion
		$file_controller = get_include_path()."/{$controller}_controller.php";

		# Extraigos todos los modelos conectados a la base de datos
		$models = new Model();
		$models->load_models();

		require_once $file_controller;

		# obteniendo todas las configuraciones locales
		$config = Config::getAllConfig();

		# obteniendo la clase que esta dentro de espacio de nombres
		$class = "{$config['controller']}Controller";
		$method = "{$config['action']}Action";

		# esta funcion verifica si hay un error en la busqueda de algun method o si la accion a ejecutar no concuerda con el method POST, PUT y DELETE si eso se ejecutara en el
		if(method_exists(new $class, $method) && $this->check_method($class, $method, $config["method"])){
			$app = new $class();
			$app->$method();
			$app->show();
		}
		else
			Controller::consoleErrorSystem("La acci&oacute;n {$config['action']} no fue encontrada dentro de la pagina dentro del method {$config["method"]}");

	}

	private function check_method($controller, $action, $method = "GET"){


		# obteniedo los method que se pueden ejecutar en el controlador
		$methods_controller = get_class_vars($controller);
		$methods_controller["post"] = isset($methods_controller["post"]) ? $methods_controller["post"]: [];
		$methods_controller["put"] = isset($methods_controller["put"]) ? $methods_controller["put"]: [];
		$methods_controller["delete"] = isset($methods_controller["delete"]) ? $methods_controller["delete"]: [];

		switch ($method) {
			case "POST":
				return $this->actions_check_method($action, $methods_controller["post"]);
			break;

			case "PUT":
				return $this->actions_check_method($action, $methods_controller["put"]);
			break;

			case "DELETE":
				return $this->actions_check_method($action, $methods_controller["delete"]);
			break;

			default:

				// tipo de cabezeras posible de ejecutar
				$is_post   = !$this->actions_check_method($action, $methods_controller["post"]);
				$is_put    = !$this->actions_check_method($action, $methods_controller["put"]);
				$is_delete = !$this->actions_check_method($action, $methods_controller["delete"]);
				
				if($is_post && $is_put && $is_delete)
					return true;
				else
					return false;
			break;
		}

		var_dump( get_defined_vars() );

	}

	/* verificamos si la action aplicada esta de acuerdo al encapsulamiento de enventos que se desea realizar */
	private function actions_check_method($is_action, $actions = array()){

		if(is_array($actions)){
			for($i = 0; $i < count($actions); $i++)
			if(!strcasecmp($actions[$i], $is_action))
				return true;
		}
		elseif( is_string($actions) )
			return !strcasecmp($is_action, $actions);

	}

	/* funcion que evalua si la uri esta en un espacio de nombre y devuelve el method */
	private function search_to_controller_space(){

		# ubicacion de controladores
		$path_application = Config::get("path_application");
		$path_controller = Config::get("path_controller");

		# buscando la clase que se encarga de los espacios de url que necesitemos
		$spaces = get_class_vars("namespaces_uri");

		# foreach que nos ubica cada uno de espacio de nombres
		foreach ($spaces["spaces"] as $space) {

			# verifico si existe esa direccion de url
			if(stripos(Config::get("uri"), "{$space['uri']}") !== false){

				$uri = Config::get("uri");
				$uri = str_ireplace("{$space['uri']}", "", $uri);

				Config::set([
					"section_uri" => explode("/",  substr($uri, 1) ),
					"space_name" => str_ireplace("/", "\\", $space["uri"])
				]);

				# cambia la direccion de inclusion de archivos que esta en el espacio de nombres
				set_include_path( $dir = "{$path_application}/{$path_controller}/{$space["uri"]}");

				return true;
			}
		}

		# incluye por defecto los archivos que se te diran a continuación
		set_include_path("{$path_application}/{$path_controller}");

		return false;
	}

	# funcion que permite la busqueda avanzada de complementos empaquetados como apps
	# internas lista para ser utilizadas cuando sea necesario
	private function search_to_controller_packs(){

		foreach (Config::get("packs_application") as $pack => $attributes) {

			for ($i=0; $i < count($attributes["controllers"]) ; $i++) {

				if( strcasecmp(Config::get("controller"), $attributes["controllers"][$i]) == 0 )
					return $attributes;
			}
		}

		return null;

	}
}