<?php

/* funcion que se encargara en los controladores de los de la aplicacion que 
nos permitira escribir el codigo de envio de parametros a la vista y hacia la
base de datos, nos ayudara a comunicar ha estos */
abstract class Controller{

	public function __call($function, $params = null){
		if( strcasecmp($function, "before_action") )
			return $this->get_functions_complements($function, $params);
	}

	// permite obtener la lista de funciones extraida de los complementos
	private function get_functions_complements($function, $params = null){
		$complements = get_class_vars("packComplementsFile")["complements"];

		/* en busqueda de un complemento que contenga la funcion mientras
		este instalado */
		foreach ($complements as $complement => $version) {
			if( method_exists(new $complement, $function)){
				$class = new $complement();
				$class->$function( $params );
				return true;
			}
		}

		// imprimi un error cuando no halla una funcion
		$this->consoleErrorSystem("La funcion {$function} no ha sido encontrada");
		return false;
	}

	# obteniendo una conexion con sus vista
	public function __construct(){
		
		# obteniendo parametros que fueron enviados a el servidor
		$this->params = Config::get("params");
		
		# estableciendo la vista a utilizar
		$this->view = new View();

		# patrones de sistema en el cual se ejecuta antes de ejecutar alguna acción
		if(method_exists($this, "before_action"))
			$this->before_action();
	}

	# funcion que me permite mostrar algo en json
	public function show_json($data = [], $status = 200){

		switch (gettype($data)) {
			case "object":
				if( method_exists($data, "__toString") )
					print($data);
				else{

					if($data = json_encode($data) )
						print($data);
					else
						$this->consoleErrorSystem("El objeto ingresado no es posible pasarlo a json");
				}
			break;
			case "array":
				print(json_encode($data));
			break;
			default:
				$this->consoleErrorSystem("Usted envio un objeto el cual no es permitido, para mostrar en json");
				exit;
			break;
		}

		@header("Content-Type: application/json");
		@header("HTTP/1.1 {$status}");

		exit;
	}

	# funcion que permite mostrar una pagina
	public function show(){
		$this->render();
	}

	protected function render(){
		# le envio los datos obtenidos a la vista
		$this->view->render();
	}

	# funcion por defecto de cada controllador
	abstract public function indexAction();

	// nos permite incluir librerias en el sistema cuando desee cargarlas
	public function setLib($file = ""){

		$library = Config::get("path_application")."/libs/{$file}";
		if(is_file($library))
			require_once $library;
		else
			$this->consoleErrorSystem("El archivo {$file} no ha sido encontrado en la libreria del sistema");

	}

	// lugar hacia donde va a renderizar el sistema
	public function renderation_uri($uri){
		self::renderation($uri);
	}

	/* esto permite renderizar mientras estas dentro del sistema */
	public static function renderation($uri = ""){
		$config = Config::getAllConfig();
		header("location: {$config['REQUEST_SCHEMA']}://{$_SERVER['SERVER_NAME']}/{$uri}");
	}


	/* funcion privada que da un mensaje de error y muestra que ocurrio un error nuestro controlador iniciado  y cambiando el estado de la cabezera */
	public static function consoleErrorSystem($message = ""){
		
		if( !empty($message) )
			Report::setError($message);
		
		header("HTTP/1.0 422 Unprocessable");
		foreach (Report::getErrors() as $key => $error)
			print("
				<br>
					<span style='color: red; font-weight: bold;'>
						Error (".($key + 1)."):
						<label style = 'color: #666;'>{$error}</label>
					</span>
				<br>
			");
		
		exit(1); # terminando con el proceso de ejecución de el programa
	}

	protected $params;
	protected $view;
}
