<?php

// sistema de amazonitin para consola
class Az{

	public function __construct($config, $dir){
		$this->_config = $config;
		$this->_dirStorage = $dir;
	}

	/* Esto entro en el menu */
	public function main($argv = [], $argc = 0){
		$this->_dirStorage = exec("cd ");
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
			$console->$method($this->_config["argument"]);
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

	// funcion que se encarga de crear el projecto
	private function _newProject($name = ""){

		# el projecto necesita saber que nombre del projecto es unico
		if(empty($name)){
			print("Error: Name of project is null or empty, please write the name project do create\n");
			print("\nPlease selected a option and add a argument\n\t$ az new --object_to_create=['controller', 'model', 'migration', 'project'] --name\nError option selected\n\n");
			exit(1);
		}

		$this->_dirApplication = "{$this->_dirStorage}/{$name}/";
		
		if(!is_dir($this->_dirApplication)){
			print("Creating Application {$name} in directory {$this->_dirApplication}...\n");
			mkdir($this->_dirApplication, 0777, true);
			print("Application Created Success..!\n");
		}
		else
			print("\n directori {$name} exist, Please other name for application\n\t$ az new --object_to_create=['controller', 'model', 'migration', 'project'] --name\n");
	}
}

# ejecucion de comando consola de la aplicacion
$console = new Az([], "");
$console->main($_SERVER["argv"], $_SERVER["argc"]);