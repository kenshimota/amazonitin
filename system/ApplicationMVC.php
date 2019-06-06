<?php

/* Lista de clase que se encargara en cada parte del sistema */
require_once 'config.php'; # libreria que se encarga de obtener la configuracion del sistema
require_once'db.php'; # libreria que se encarga de la configuracion y la conexion a la db
require_once 'report.php'; # libreria que se encargara de los reportes de errores
require_once 'core/Router.php'; # Controla las rutas de direcciones de la pagina
require_once 'core/Packs.php'; # Realiza acciones sobre los paquete recepsion de estos
require_once "core/MigrationsCore/Migrations.php"; # ealiza las migraciones del sistema

/* Clase principal de nuestra aplicacion */
class ApplicationMVC{

	/* Constructor de nuestra aplicacion */
	public function __construct(){
		
		/* Se encargara de agregar lo datos de la
		configuracion */
		self::$config = new Config();
		$this->addConfig();
		self::$db = new Databases();
		new Router();
	}

	/* Es funcion te permite agregar librearias y clase de la configuracion 
	de forma automatica */
	public function addClassConfig($class = null, $folder_class = null){

		if(!empty($class)){
			$folder = !empty($folder_class) ? $folder_class : self::$config->get('folder_config');
			if($this->includeFile("{$folder}/{$class}.php")){
				Report::setInfo("El archivo {$folder}/{$class}.php para una de las clase de configuracion fue incluido correctamente dentro de la aplicacion");
				return 1;
			}
			else{
				Report::setError("No fue posible conseguir la clase de configuracion dentro de {$folder}/{$class}.php, por favor verifique la ubicacion de la clase de configuracion");
				return 0;
			}

		}
		else
			return (-1);
	}

	/* Esta funcion se encargara de agregar las configuracion cargadas en la carpeta
	de config */
	private function addConfig(){

		$class_config = [ 
			'routes', // contiene las rutas de ubicaciones de la aplicacion
			'databases', // esta se encargara de los atributos hacia la base de datos
			'namespaces_uri' // los espacios de nombres
		];

		/* descompondra todos los archivos de configuracion que
		se incluyan en nuevas versiones del sistema */
		foreach ($class_config as $key) {
			if(!class_exists($key))
				$this->addClassConfig($key) or die("problemas para obtener el archivo {$key}.php en la configuración");
		}

		# segundo cargaremos las rutas actuales de la aplicacion para que no ocurra errores
		$routes = get_class_vars("Routes");

		# estableciendo ubicacion de la aplicacion
		$routes["path_application"] = str_ireplace(
			"/config",
			"",
			Config::get("folder_config")
		)."/{$routes["path_application"]}";

		Config::set($routes);
	}

	/* Clase que se encargara de la inclusion de la direccion de un archivo */
	private function includeFile($direction){

		if( is_file( $direction ) ){
			require_once $direction;
			return true; 
		}
		else
			return false;
	}

	protected static $config;
	protected static $db;
}
