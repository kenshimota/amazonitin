<?php

/* funcion que renderiza servidores */
class server_renderController extends Controller{

	/* Todas las aplicaciones debe tener un indice de accion */
	public function indexAction(){
	}

	/* exec es el method que ejecuta las renderizaciones dentro del controlador
	analiza el problema del documento si es encontrado o no y luego lo renderiza
	para que sea visible ante otros usuarios*/
	public function execAction(){

		$params = Config::get("params");

		# contendra el nombre y la version
		$name = $this->getComplement($params["name"]);

		# ubicacion de Amazonitin y en busqueda del complemento, la lista de archivos en la
		# carpeta de recursos de aplicaciones
		$dir_app = Config::get("path_system")."\\system\\complements\\{$name}\\resources\\";
		$type    = $params["type"]; 

		switch($type){
			case 'images':
				isset($params["folders"]) ? $this->getImage($dir_app, $params["src"], $params["folder"]) : $this->getImage($dir_app, $params["src"]);
			break;

			case "styles":
				isset($params["folders"]) ? $this->getStyle($dir_app, $params["src"], $params["folders"]) : $this->getStyle($dir_app, $params["src"]);
			break;

			default:
				isset($params["folders"]) ? $this->getScript($dir_app, $params["src"], $params["folders"]) : $this->getScript($dir_app, $params["src"]);
			break;
		}
	}

	# funcion que permite ver obtener un estilo de un complementos Applicacion
	private function getStyle($dir, $style, $folders = null){

		if( $folders != null )
			$dir = realpath("{$dir}\\styles\\".str_replace("/","\\",base64_decode($folders)));
		else
			$dir = realpath("{$dir}\\styles\\");

		$dir = "{$dir}\\{$style}";

		if( is_file($dir) ){
			$file = file_get_contents($dir);
			header("Content-Type: Text/Css");
			print($file);
		}
		else
			header("HTTP 1.1/ 404");
	}

	# funcion que permite ver obtener un estilo de un complementos Applicacion
	private function getScript($dir, $script, $folders = null){

		if( $folders != null )
			$dir = realpath("{$dir}\\javascripts\\".str_replace("/","\\",base64_decode($folders)));
		else
			$dir = realpath("{$dir}\\javascripts\\");

		$dir = "{$dir}\\{$script}";

		if( is_file($dir) ){
			$file = file_get_contents($dir);
			header("Content-Type: application/javascript");
			print($file);
		}
		else
			header("HTTP 1.1/ 404");
	}

	/* obtiene que componente y su version a instalar */
	private function getComplement($name = ""){

		$complements = get_class_vars("packComplementsFile")["complements"];

		foreach ($complements as $complement => $version) {

			if(!strcasecmp($complement, $name) )
				return "{$complement}-{$version}";
		}

		return false;
	}

	/* funcion que obtiene la imagen si es imagen lo que se necesita */
	private function getImage($dir, $img, $folders = null){

		# obteniendo parametros
		$params = Config::get("params");
		$width = isset($params["x"]) ? $params["x"] : null;
		$height= isset($params["y"]) ? $params["y"] : null;
		
		if( $folders != null )
			$dir = realpath("{$dir}\\images\\".str_replace("/","\\",base64_decode($folders)));
		else
			$dir = realpath("{$dir}\\images\\");

		# prueba de su valor
		$dir = "{$dir}\\{$img}";

		if( is_file( $dir ) )
			$this->createThumbnails($dir, $width, $height);
		else
			header("HTTP 1.0/ 404");
	}

	# function privada que creara la imagen miniatura o del tamaño que la necesitemos
	private function createThumbnails($src = "",$width = null, $height =  null){

		# obteniedo parametros
		$params = Config::get("params");

		$data_img = getimagesize($src);

		if($width == null || $height == null){
			$width = $data_img[0]; # definiendo altura por anchura
			$height = $data_img[1];
		}

		# de esta forma hara los calculos para no distorcionar la imagen
		if(isset($params['maxWidth'])){
			$height = ($data_img[1] * $params['maxWidth']) / $data_img[0];
			$width = $params['maxWidth'];
		}

		if(isset($params['maxHeight'])){
			$width = ($data_img[0] * $params['maxHeight']) / $data_img[1];
			$height = $params['maxHeight'];
		}

		switch ($data_img['mime']) {
			case 'image/jpeg': $img_tmp = imagecreatefromjpeg($src); break;
			case 'image/gif': $img_tmp = imagecreatefromgif($src); break;
			case 'image/png': $img_tmp = imagecreatefrompng($src); break;
			default: $img_tmp = imagecreatefromjpeg($src); break;
		}

		header("Content-Type: {$data_img['mime']}");

		if($data_img['mime'] == "image/jpeg")
			$image = imagecreatetruecolor($width, $height);
		else{
			$image = imagecreate($width, $height);
			$transparent = imagecolorallocate($image, 0, 0, 0);
			imagecolortransparent($image, $transparent);
		}
		
		imagecopyresized($image, $img_tmp, 0, 0, 0, 0, $width, $height, $data_img[0], $data_img[1]);

		# mostrando la imagen de acuerdo a que tipo de imagen es
		switch ($data_img['mime']) {
			case 'image/jpeg': imagejpeg($image); break;
			case 'image/gif': imagegif($image); break;
			case 'image/png': imagepng($image); break;
			default: imagejpeg($image); break;
		}
	}
}