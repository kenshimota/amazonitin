<?php


include_once "Console/DB.php";

if(!empty($_SERVER["PHP_DIR"]))
	defined("PATH_AMAZONITIN") 
		||  define("PATH_AMAZONITIN", "{$_SERVER["PHP_DIR"]}/../");
else
	defined("PATH_AMAZONITIN") 
	||  define("PATH_AMAZONITIN", "/amazonitin1.0.0/");


// sistema de amazonitin para consola
class Az{

	public function __construct($config, $dir){
		$this->_config = $config;
		$this->_dirStorage = $dir;
	}

	/* Esto entro en el menu */
	public function main($argv = [], $argc = 0){

		if(empty($_SERVER["PWD"]))
			$this->_dirStorage = exec("cd ./");
		else
			$this->_dirStorage = "{$_SERVER["PWD"]}";
		
		
		$this->_config["server"] = $_SERVER;


		foreach(["script", "method", "object", "argument"] as $key => $resource){
			if(!empty($argv[$key]))
				$this->_config[$resource] = $argv[$key];
			else
				$this->_config[$resource] = "";
		}

		if(!empty($this->_config["method"]))
			$this->run();
		else
			$this->_help();
	}

	# funcion que se encarga de mostrar la ayuda del
	private function _help(){
		print("
------------- AZ (Amazonition) ----------
------------- COMMANDO HELP -------------
Create a new object or project 
\t\$ az new --object_to_create=['controller', 'model', 'migration', 'project'] --name

Destroy a object created or project
\t\$ az destroy --object_to_create=['controller', 'model', 'migration', 'project'] --name

Actions that executa in databases
\t\$ az db --option=['drop', 'create', 'migrate', 'seed']
\n");
	}

	# funcion privada que nos permite ejecutar los comandos
	private function run(){
		
		$console = new self($this->_config, $this->_dirStorage);
		$method = "_{$this->_config["method"]}{$this->_config["object"]}";

		if(method_exists($console, $method))
			$this->$method($this->_config["argument"]);
		else{
			print("\nPlease selected a option and add a argument\n");
			$console->_help();
			print("Error option selected\n\n");
		}
	}

	// esta funcion da una explicacion de los nuevo method a ejecutar y las posible restricciones
	private function _new(){
		print("\nPlease selected a option and add a argument\n\t$ az new --object_to_create=['controller', 'model', 'migration', 'project'] --name\nError option selected\n\n");
	}

	// esta funcion se ocupa de hacer una migracion nueva incorporada al sistema de shell
	private function _newMigration($name = "new-migration"){
		try{

			if(!$this->getProject())
				throw new Exception("Project Not Found into {$this->_dirStorage}/.az-project.json..., Please Load an Project for can do migrates...\n");			
			$this->_config["routes"] = $this->get_routes_app(); # funcion que se encarga de obtener los directorios de la aplicacion
			$file = "{$this->_dirStorage}/{$this->_config["application"]->app->path_config}/db/migrations/".date("YmdHis")."-{$name}.sql";
			print("Creting File of Migration {$file}\n");
			file_put_contents($file,"");

		} catch(Exception $e){
			die($e->getMessage());
		}
	}

	// esta funcion se encarga de obtener los detalles del proyecto que se esta utilizando
	private function getProject(){


		$az = "{$this->_dirStorage}/.az-project.json";
		if(is_file($az))
			$this->_config["application"] = json_decode(file_get_contents($az));
		else
			$this->_config["application"] = [];

		return is_file($az) && json_decode(file_get_contents($az));
	}

	
	/* funcion que obteni las rutas de los directorios de aplicacion */
	private function get_routes_app(){
		
		# directorio de configuracion de datos
		$dir = "{$this->_dirStorage}/{$this->_config["application"]->app->path_config}";

		try {

			if(!is_file("{$dir}/routes.php"))
				throw new Exception("Routes Not Found..., Please Create File {$dir}/Routes.php of get params the directories of application...");

			require_once "{$dir}/routes.php";

			if(!class_exists("Routes"))
				throw new Exception("Class Routes Not Found..., Please Create do Class File {$dir}/Routes.php");

			return get_class_vars("Routes");

		} catch (Exception $e) {
			print($e->getMenssage);
			exit(1);
		}
	}

	// esta funcion se ocupa de hacer una llamada para insertar la semilla del programa
	private function _dbSeed(){
		try{
			if(!$this->getProject())
				throw new Exception("Project Not Found into {$this->_dirStorage}/.az-project.json..., Please Load an Project for can do migrates...\n");

			$this->_config["connect"] = $this->connection_database();
			$this->_config["routes"] = $this->get_routes_app(); # funcion que se encarga de obtener los directorios de la aplicacion
			include_once PATH_AMAZONITIN."/system/core/Model.php";# directorio para cargar los modelos
			$this->_config["model"] = new Model;
			$this->_config["model"]->load_models("{$this->_dirStorage}/{$this->_config["routes"]["path_application"]}/{$this->_config["routes"]["path_models"]}/");
			

			# directorio que se encarga de ejecutar las inserciones y acciones para la semilla del programa
			$dir =  "{$this->_dirStorage}/{$this->_config["application"]->app->path_config}/db/";
			if(is_file("{$dir}/seed.php")){
				Model::transaction_start();
					require_once "{$dir}/seed.php";
				Model::transaction_end();
			}
			else
				throw new Exception("File Not Found {$dir}/seed.php, Please Create File {$dir}/seed.php for load data to database");
			

		}catch(Exception $e){
			die($e->getMessage());
		}
	}

	// esta funcion se ocupa de la migraciones a realizar de la db
	private function _dbMigrate(){

		try{
			if(!$this->getProject() )
				throw new Exception("Project Not Found into {$this->_dirStorage}/.az-project.json..., Please Load an Project for can do migrates...\n");

			
			$this->_config["connect"] = $this->connection_database();
			$this->_config["routes"] = $this->get_routes_app(); # funcion que se encarga de obtener los directorios de la aplicacion
			$dir =  "{$this->_dirStorage}/{$this->_config["application"]->app->path_config}/db/migrations/";
			$querys = array();

			# verifica la existencia de carpeta en donde encontrara los modelos
			if(is_dir($dir)){
				$migrations_json = $this->jsonMigration($dir); // obteniendo las consultas realizadas en un json
				# abre directorio de los modelos
				if($dh = opendir($dir)){
					$querys = scandir($dir);
					foreach($querys as $migration){
						if($migration != "." && $migration != ".." && empty($migrations_json->$migration) ){

							// cuando inicio la ejecucion del script
							$date = new DateTime("now");
							// mostrando y ejecutando la migracion
							print("-------- execute migration {$migration} ------------\n");
							$query = file_get_contents("{$dir}/{$migration}");
							$this->_config["connect"]->execute($query); # ejecutando el Query
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
			throw new Exception("the folder {$dir} not found...!");
		
		} catch (Exception $e){
			die($e->getMessage());
		}
	}

	// esta funcion se ocupa de obtener el json de migraciones o crear el archivo
	private function jsonMigration($dir){
		$dir = realpath($dir);
		$file = "{$dir}/../migrations.json";
		$json = "";
	
		// si existe el archivo va a obtener los datos nuevos del archivo
		if(is_file($file)){
			$json = file_get_contents($file);
			return json_decode($json);
		}
		else{
			file_put_contents($file, json_encode( new stdClass() ));
			return new stdClass();
		}
	}
	
	// agregando los datos a un archivo json para saber que tablas an sido migradas
	private function addJsonMigration($json, $dir){
		$dir = realpath($dir);
		$json = json_encode($json);
		$file = "{$dir}/../migrations.json";
		file_put_contents($file, $json);
	}

	/* function for connect a db mysql */
	private function connection_database(){

		# directorio de configuracion de datos
		$dir = "{$this->_dirStorage}/{$this->_config["application"]->app->path_config}";
	
		try{
	
			if(!is_file("{$dir}/databases.php"))
				throw new Exception("Databases Not Found..., Please Create File {$dir}/databases.php of get params the connection mysql...");
	
			# include library with params connection of mysql
			require_once "{$dir}/databases.php";
	
			if(!class_exists("Databases"))
				throw new Exception("Class Databases Not Found..., Please Create do Class File {$dir}/databases.php");
	
			# haciendo nombramiento de la base de datos
			return new Databases;
	
		} catch (Exception $e) {
			print_r($e->getMessage());
			exit(1);	
		}
	}

	// funcion que se encarga de crear una base de datos
	private function _dbCreate(){
		try{
			if(!$this->getProject() )
				throw new Exception("Project Not Found into {$this->_dirStorage}/.az-project.json..., Please Load an Project for can do migrates...\n");
			$this->_config["connect"] = $this->connection_database();
		} catch(Exception $e){
			die($e->getMessage());
		}
	}

	# funcion que se encarga de eliminar la base de datos
	private function _dbDrop(){
		try{
			if(!$this->getProject() )
				throw new Exception("Project Not Found into {$this->_dirStorage}/.az-project.json..., Please Load an Project for can do migrates...\n");
			$this->_config["connect"] = $this->connection_database();
			$this->_config["connect"]->destroy_db();
		} catch(Exception $e){
			die($e->getMessage());
		}
	}

	// funcion que se encarga de crear el projecto
	private function _newProject($name = ""){

		# el projecto necesita saber que nombre del projecto es unico
		if(empty($name)){
			print("Error: Name of project is null or empty, please write the name project do create\n");
			print("\nPlease selected a option and add a argument\n\t$ az new --object_to_create=['controller', 'model', 'migration', 'project'] --name\nError option selected\n\n");
			exit(1);
		}

		$this->_dirApplication = "{$this->_dirStorage}/{$name}";
		
		if(!is_dir($this->_dirApplication)){
			print("Creating Application {$name} in directory {$this->_dirApplication}...\n");
			
			# caperta de la aplicacion
			mkdir($this->_dirApplication, 0777, true);
			mkdir("{$this->_dirApplication}/public", 0777, true);
			file_put_contents("{$this->_dirApplication}/public/index.php", "");

			# carpetas y archivos de configuraciones
			mkdir("{$this->_dirApplication}/config", 0777, true);
			file_put_contents("{$this->_dirApplication}/config/databases.php", "<?php\n/* Clase que enviara los datos que se necesitaran para crear los datos de la base de datos */\nClass Databases extends DB {\n\tprotected static \$host='127.0.0.1'; // host que conecta la base de datos\n\tprotected static \$dbname = '{$name}_development'; // base de datos de la aplicacion\n\tprotected static \$user='root'; // usuario de la conexion a la base de datos\n\tprotected static \$password='';// password del usuario de la base de datos\n}");

			file_put_contents("{$this->_dirApplication}/config/routes.php", file_get_contents(PATH_AMAZONITIN."/default/routes.php"));
			file_put_contents("{$this->_dirApplication}/config/namespaces_uri.php", file_get_contents(PATH_AMAZONITIN."/default/namespaces_uri.php"));

			mkdir("{$this->_dirApplication}/config/db/", 0777, true);
			file_put_contents("{$this->_dirApplication}/config/db/migrations.json", json_encode([]));
			mkdir("{$this->_dirApplication}/config/db/migrations", 0777, true);
			file_put_contents("{$this->_dirApplication}/config/db/seed.php", file_get_contents(PATH_AMAZONITIN."/default/seed-default.php"));

			# carpeta y archivos que comprenden la app
			mkdir("{$this->_dirApplication}/app", 0777, true);
			mkdir("{$this->_dirApplication}/app/controllers", 0777, true);
			file_put_contents("{$this->_dirApplication}/app/controllers/home_controller.php", file_get_contents(PATH_AMAZONITIN."/default/controller_default.php"));

			mkdir("{$this->_dirApplication}/app/models", 0777, true);
			mkdir("{$this->_dirApplication}/app/libs", 0777, true);
			
			mkdir("{$this->_dirApplication}/app/views", 0777, true);
			mkdir("{$this->_dirApplication}/app/views/home/", 0777, true);
			file_put_contents("{$this->_dirApplication}/app/views/home/index.phtml","<!DOCTYPE html>\n<html>\n<head>\n\t<meta charset=\"utf-8\" />\n\t<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n\t<title>{$name}</title>\n\t<meta name='viewport' content='width=device-width, initial-scale=1'>\n</head>\n<body>\n\t<h1>Bienvenidos a nuestro WebSite {$name} Hecha con Amazonitin 1.0.0</h1>\n</body>\n</html>");

			mkdir("{$this->_dirApplication}/app/resources", 0777, true);
			
			mkdir("{$this->_dirApplication}/app/resources/javascripts", 0777, true);
			file_put_contents("{$this->_dirApplication}/app/resources/javascripts/application.js", "// Coding Script");

			mkdir("{$this->_dirApplication}/app/resources/styles", 0777, true);
			file_put_contents("{$this->_dirApplication}/app/resources/styles/application.css", "/* Style of Application */");

			mkdir("{$this->_dirApplication}/app/resources/images", 0777, true);

			# creando archivos de configuracion del projecto
			file_put_contents("{$this->_dirApplication}/.az-project.json", json_encode([
				"app"=>[
					"name" => $name,
					"path_config" => "config",
					"created_at" => date("Y-m-d H:i:s")
				],
				"framework" => [
					"name" => "Amazonitin",
					"version" => "1.0.0",
					"date" => "2019-06-17"
				]
				])
			);
			print("Application Created Success..!\n");
		}
		else
			print("\n directory {$name} exist, Please other name for application\n\t$ az new --object_to_create=['controller', 'model', 'migration', 'project'] --name\n");
	}
}

# ejecucion de comando consola de la aplicacion
$console = new Az([], "");
$console->main($_SERVER["argv"], $_SERVER["argc"]);