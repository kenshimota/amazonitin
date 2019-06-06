<?php

class Packs{

	/* Por defecto obtendra el primer complemento */
	public function __construct(){

		# apps empaquetadas osea complementos dentro de la aplicacion
		$app_pack = Array();

		foreach ($this->complements as $pack => $version) {

			$data_pack = $this->getData($pack);

			if($data_pack['type'] == "Application"){

				# ubicacion del complemento
				$data_pack["routes"]["path_application"] = PATH_AMAZONITIN."/system/complements/". $data_pack["name"] ."-". $data_pack["version"];

				$app_pack[$pack]  = $data_pack; 
			}

			// funcion que ejecuta para la instalacion o instanciacion de
			// un paquete de complemento
			if( method_exists(new $pack, "exec") )
				$pack::exec();

		}

		Config::set(["packs_application" => $app_pack]);
	}

	/* me permite obtener informacion del paquete que se esta utilizando */
	public function __get($key){
		if(isset(self::$pack[$key]))
			$key;
		else
			Report::setError("El atributo o metodo {$key} no esta definido dentro del siguiente elemento");
	}

	/* funcion que permite la inserccion de una paquete para hacer la referencia y validacion */
	public function set_pack($name){

		# funcion que nos permite obtener los datos de un paquete
		if( $data_pack = $this->getData($name) ){
			self::$pack['name'] = $name; # introduce el nombre del paquete
			self::$pack['version'] = $data_pack['version']; # introduce la version actual
			self::$pack['type'] = $data_pack['type']; # introduce que tipo de complemento es
			self::$pack['list_file'] = $data_pack['files']; # devuelve la lista de archivos del pack actual
			return true;
		}
		else
			Report::setError("Por algun motivo no se a hallado el archivo de referencia del paquete {$name} - {$data_pack['version']}.../n");
	}

	# funcion que devuelve los datos obtenidos de un paquete
	public function getData($name){

		$version = $this->valid_exist($name);

		# archivo que contiene la informacion del complemento
		$file_complement = PATH_AMAZONITIN."/system/complements//$name-$version//$name-$version.php";

		# verificamos la existencia de la direccion de datos
		if( file_exists( $file_complement ) && $version != false ){

			# si la clase no existe es que el archivo no ha sido includo dentro
			# del codigo principal de la aplicacion asi que lo incluiremos
			if(!class_exists($name))
				require_once $file_complement;

			$attr = get_class_vars($name); # despues de obtener la informacion necesaria del a clase introducida

			return [
				'name' => $name,
				'version' => $version,
				'type' => $attr['type_complements'],
				'files' => isset($attr['files_path']) ? $attr['files_path'] : null,
				'controllers' => isset($attr['controllers']) ? $attr['controllers'] : null,
				'routes' => isset($attr['routes']) ? $attr['routes'] : null
			];
		}

	}

	/* funcion que nos permitira saber si un complemento esta existente o no */
	public function valid_exist($name){

		# si deseas validar un paquete que se acabada de introducir este los hace despues de
		# hacer la siguente verificacion sin necesidad de repetir su nombre
		if($name == "" && isset(self::$pack))
			$name = self::$pack['name'];

		foreach ($this->complements as $pack => $version) {

			if(strcasecmp( $pack, $name) == 0)
				return $version;
		}

		return false;
	}

	// funcion que retorna los archivos obtenidos con un link
	public function get_uri_files(){

		if(self::$pack['type'] == "StylesAndScripts" || self::$pack['type'] == "Scripts" || self::$pack['type'] == 'Styles' ){

			# array que contendra los archivos a obtener de un complemento
			$files = Array();
			
			foreach (self::$pack['list_file'] as $file => $type) {

				$name = self::$pack['name'];
				$version = self::$pack['version'];

				switch ($type) {
					case 'script':

						# obteniendo archivo del sistema
						$files[$file] = [
							'url' => $this->convertDirToUri($file, Config::get("REQUEST_SCHEMA")."://{$_SERVER['HTTP_HOST']}/tags/packs/complements/scripts/name/{$name}/version/{$version}"),
							'type' => $type
						];

					break;

					case 'style':

						# obteniendo archivo del sistema
						$files[$file] = [
							'url' => $this->convertDirToUri($file, Config::get("REQUEST_SCHEMA")."://{$_SERVER['HTTP_HOST']}/tags/packs/complements/styles/name/{$name}/version/{$version}"),
							'type' => $type
						];
					break;

					default:
						# obteniendo archivo del sistema
						$files[] = [
							'url' => $this->convertDirToUri($file, Config::get("REQUEST_SCHEMA")."://{$_SERVER['HTTP_HOST']}/tags/packs/complements/scripts/name/{$name}/version/{$version}"),
							'type' => $type
						];

					break;
				}

			}

			return $files;

		}
		else
			Report::setError("el paquete de complementos no es de scripts ni de styles, sino una aplicacion base de instalacion que perrmite hacer procesos opcionales na tu aplicacion ");
	}

	# funcion que convierte /carpeta1/carpeta2/file.extension
	# en una url muy parecida ha esta /Folders/KdEJCdU=/src/file.extension
	# para mandar de forma segura la ubicacion exacta de un archivo en una carpeta
	# dentro del sistema
	private function convertDirToUri($src, $dir = ""){

		if( !($position = strrpos($src, '/')) )
			$dir = "{$dir}/src/{$src}";
		else{

			$string_folders = "";
			$string_file = "";

			for($i = 0; $i < $position; $i++)
				$string_folders = $string_folders."{$src[$i]}";

			for ($i = ($position + 1); $i < strlen($src) ; $i++)
				$string_file = $string_file."{$src[$i]}";

			# otorgo la direccion de carpetas en donde conseguira la imagen
			$dir = "{$dir}/folders/".base64_encode($string_folders)."/src/{$string_file}";
		}

		return $dir;
	}

	private static $pack;
	private static $pack_apps; # contrendra la informacion de las app dentro de complementos que vallan a ser intaladas
}
