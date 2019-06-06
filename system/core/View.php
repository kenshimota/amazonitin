<?php

class View{

	/* llamada a una funcion que le permite hacer llamadas a las function
	de los complementos */
	public function __call($function ,$params = null){
		
		$complements = get_class_vars("packComplementsFile")["complements"];

		/* en busqueda de un complemento que contenga la funcion mientras
		este instalado */
		foreach ($complements as $complement => $version) {
			if( method_exists($complement, $function) ){
				$class = new $complement();
				call_user_func_array([$class, $function], $params);
				return true;
			}
		}

		// imprimi un error cuando no halla una funcion
		print("<div class='alert alert-danger' style='width: fit-content; margin:0px auto;'>La funcion {$function} no ha sido encontrada</div>");
		return false;
	}

	# renderizacion de la plantilla que se mostrara
	public function render(){

		$this->setParams( Config::get("params") );

		# solo si existe la vista renderizara
		if(class_exists( Config::get("controller")."Controller" ) )
		{
			# datos de donde se renderizaran la plantilla
			$path_app   = Config::get("path_application");
			$path_views = Config::get("path_views");
			$path_space = str_ireplace("\\", "/", Config::get("space_name"));
			$controller = Config::get("controller");
			$action     = Config::get("action");

			# archivo que se buscara para mostrar
			if(empty($path_space))
				$file_to_show = "{$path_app}/{$path_views}/{$controller}/{$action}.phtml";
			else
				$file_to_show = "{$path_app}/{$path_views}/{$path_space}/{$controller}/{$action}.phtml";


			# cada vez que consiga un tipo de dato que sera renderizado
			if(is_file($file_to_show)){
				$this->getContentTemplate($file_to_show);
				echo $this->template;
			}
		}

	}

	# introduce los parametros que mostraremos
	public function setParams($param = array()){
		
		if(!empty($param))
		{
			foreach ($param as $key => $value) {
				$this->params[$key] = $value;
			}
		}
	}

	# funcion que te permite agregar un script a vuestra pagina
	public function add_script($path_file = "application.js", $folders = null){

		# directorios de script
		$dir = Config::get("REQUEST_SCHEMA")."://{$_SERVER['HTTP_HOST']}/tags/scripts";

		# si existen parametros de carpetas estas sera obtenidas de ciertas carpetas alli
		if($folders != null)
			$dir = "{$dir}/folders/". base64_encode($folders)."/src/{$path_file}";
		else
			# busca si en el string recibido hay direccion de carpetas
			$dir = $this->convertDirToUri($path_file, $dir);

		print("<script type='text/javascript' src='{$dir}'></script>\n");
	}

	# funcion que permite agregar estilos css a la web actual
	public function add_style($path_file = "application.css", $folders = null){

		# direccion de estilos en la applicacion
		$dir = Config::get("REQUEST_SCHEMA")."://{$_SERVER['HTTP_HOST']}/tags/styles";

		if($folders != null)
			$dir = "{$dir}/folders/".base64_encode($folders)."/src/{$path_file}";
		else
			$dir = $this->convertDirToUri($path_file, $dir);


		print("<link rel='stylesheet' type='text/css' href='{$dir}'>\n");
	}

	public function get_uri_image($src = "",$params){

		# lo primero es establecer en la busqueda del controllador que se encarga de
		# buscar la imagen que proyectara con sus parametros
		$dir = Config::get("REQUEST_SCHEMA")."://".$_SERVER['SERVER_NAME']."/tags/images";

		# direccionamiento de la imagenes
		if(isset($params['params_url'])){
			foreach ($params['params_url'] as $key => $value) 
				$dir = "{$dir}/{$key}/{$value}";
		}

		# esto permite pasar parametros sobre methodos sobre carpetas
		return $dir = $this->convertDirToUri($src, $dir);
	}

	# esta funcion te permite obtener una variable
	# dentro de la localizacion de imagenes, para usarla de fondo o como quieras
	public function get_location_image($src = "", $params = null){
		return $this->get_uri_image($src, $params);
	}

	# una imagen puede ser solicitada con imagenes diseño personalizado 
	# a la etiqueta y asi sucesivamente
	public function add_image($src = "", $params = null){

		# obteniendo la direccion de un atributo
		$dir = $this->get_uri_image($src, $params);

		$attributes = null;
		$styles = null;

		# si existe estilos, esos serar agregados a la atributo de estilo
		if(isset($params['styles'])){
			$styles = "style='{$this->setStylesTags($params['styles'])}'";
		}

		# si existe atributos se los agregara a las etiquetas
		if(isset($params['attributes'])){
			$attributes = $this->setAtrributesTags($params['attributes']);
		}

		print("<img src='{$dir}' {$styles} {$attributes} />\n");
	}

	# funcion que convierte /carpeta1/carpeta2/file.extension
	# en una url muy parecida ha esta /Folders/KdEJCdU=/src/file.extension
	# para mandar de forma segura la ubicacion exacta de un archivo en una carpeta
	# dentro del sistema
	static public function convertDirToUri($src, $dir = ""){

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

	# retorna los estilos que se insertara en una etiqueta html
	static public function setStylesTags($attr = array()){

		# el string de estilo ha devolver en una etiqueta
		$string_style = null;

		foreach ($attr as $key => $value){
			$string_style = "{$string_style}{$key}:{$value};";
		}

		return $string_style;
	}

	# retornara los atributos que se insertaran a la etiqueta de html que usaras
	static public function setAtrributesTags($attr = array()){

		# el string que contrendra los atributos de la etiqueta html
		$string_attr = null;

		foreach ($attr as $key => $value) {
			$string_attr = " {$key}='{$value}' ";
		}

		return $string_attr;
	}

	public function get_template($file){

		$name_tmp = $file;

		$file = !($position = strrpos($file, '/')) ? Config::get('path_application')."/".Config::get("path_views")."/".Config::get("controller")."/{$file}" : Config::get('path_application')."/".Config::get("path_views")."/{$file}";

		/* Solo si el archivo existe sera obtenido */
		if(file_exists($file) ){
			$this->getContentTemplate($file);
			print($this->template);
		}
		else
		{
			header("HTTP/1.0 422 Not Proccessable Entity");
			Report::setError("El archivo {$name_tmp} no ha sido encontrado..!Por favor verifique el nombre y la dirección del archivo");
			$this->getContentTemplate("../system/complements/amazonitin-1.0.0/views/error/index.phtml");
			print($this->template);
			exit(1);
		}
	}

	# obtendra el contenido del archivo
	private function getContentTemplate($file){

		# establecemos variables obtenidas pasadas a nuestros parametros
		if(!empty($this->params))
			extract(["params" => $this->params]);

		ob_start();
		require_once $file;
		$this->template = ob_get_contents();
		ob_end_clean();
	}

	# esta funcion permite agregar paquetes que esten instalados en el sistema
	public function add_pack($name = ""){

		/* intancia hacia los paquetes de complementos */
		$pack_files = new PackComplementsFile();

		# verifica que el parametro de paquete no este vacio
		if( !empty($name) ){

			# verifica que el pack existe, y si tiene todos su archivos complementarios
			if( $result = $pack_files->set_pack($name) )
			{

				# obtenemos sus archivos antes de mostrarlos
				foreach ($pack_files->get_uri_files() as $file => $data) {

					switch ($data['type']) {
						case 'script':
							print("\t<script type='text/javascript' src = '{$data['url']}'></script>\n");
						break;

						case 'style':
							print("\t<link rel='stylesheet' type='text/css' href='{$data['url']}' />\n");
						break;

						default:
							print("\t<script type='text/javascript' src = '{$data['url']}'></script>\n");
						break;
					}


				}
			}
			else
				Report::setError("El paque no puede ser intanciado, verifique su nombre o su version. Si esta todo correcto, es posible que un archivo este corrupto o no esta en el paquete");

		}
		else{
			Report::setError("El paquete de no ha sido hallado en la configuración de los complementos..\n");
		}
	}

	# plantilla phtml que se va ha mostrar
	protected $template;
	protected $params;
}
