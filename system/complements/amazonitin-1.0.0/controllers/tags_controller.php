<?php

<?php

/* Clase por defecto que se encargara de renderizar los archivos de acuerdo a tipo de
Archivo que son */
class TagsController extends Controller{


	// accion del controlador
	public function indexAction(){
	}

	// accion que controla la muestra de scripts que solicites en nuestra pagina
	public function scriptsAction(){

		# obtenido parametros para obtener el nombre del script obtener
		$script = $this->params['src'];

		# esto permite tener script perfectamente guardados en carpetas
		# separadas cuando la aplicacion se convierte demasiado grande
		if(isset($this->params['folders']))
			$path = Config::get('path_application_default')."/".Config::get('path_resources')."/".Config::get('path_script')."/" . base64_decode($this->params['folders']) . "/{$script}";
		else
			$path = Config::get('path_application_default')."/".Config::get('path_resources')."/".Config::get('path_script')."/{$script}";

		# verificando existencia del archivo que sera el script a mostrar
		if(file_exists($path)){
			header("Content-Type: application/javascript");
			$script = file_get_contents($path);
			print($script);
		}
		else{
			Report::setError("El script {$script} no puedo ser cargado, verifique el nombre o su ubicacion.../n");
			echo "El script {$script} no puedo ser cargado, verifique el nombre o su ubicacion.../n";
			header("HTTP/1.0 404 Not Found");
		}
	}

	// accion que controla la muestra de las hojas de estilos que solicites en nuestra pagina
	public function stylesAction(){

		# obtenido parametros para obtener el nombre de la hoja de estilo obtener
		$params = Config::get('params');
		$stylesheet = $params['src'];

		# nos permitira separar los diferentes estilos de cada pagina o controllador 
		# en carpetas cuando lo necesitemos
		if(isset($params['folders']))
			$path = Config::get('path_application_default')."/".Config::get('path_resources')."/".Config::get('path_style')."/".base64_decode($params['folders'])."/{$stylesheet}";
		else
			$path = Config::get('path_application_default')."/".Config::get('path_resources')."/".Config::get('path_style')."/{$stylesheet}";

		# verificando existencia del archivo que contendra los estilos a mostrar
		if(file_exists($path)){
			header("Content-Type: text/css");
			$style = file_get_contents($path);
			print($style);
		}
		else{
			Report::setError("La hoja de estilo {$stylesheet} no puedo ser cargada, verifique el nombre o su ubicacion.../n");
			header("HTTP/1.0 404 Not Found");
		}
	}

	# funcion que permite agregar paquete prediseñados no creados por el usuario
	# principal del framework
	public function packsAction(){

		# me permite obtener los parametros de la pagina
		$params = Config::get('params');

		if(isset($params['src']))
			
			switch ($params['complements']) {
				case 'scripts': $this->getScriptPack($params['src']); break;
				case 'styles': $this->getStylesPack($params['src']); break;
				case 'images': $this->getImagesPack($params['src']); break;
				default: break;
		}

	}

	private function getStylesPack($src){
		# obteniedo los parametros de la pagina
		$params = Config::get("params");

		# necesitamos el nombre del complemento y version para ir a su carpeta y 
		if(isset( $params['name'], $params['version'])){

			$path_app = Config::get("path_system");

			# verificamos si nuestro archivo esta en otra carpeta interna dentro
			# del mismo patron de sistema que trae
			if(isset($params['folders']))
				$path = $path_app."/system/complements{$params['name']}-{$params['version']}/src/stylesheets/".base64_decode($params['folders'])."/{$src}";
			else
				$path = $path_app."/system/complements/{$params['name']}-{$params['version']}/src/stylesheets/{$src}";

			# verificamos que el archivo del complemento exista antes
			if(is_file($path)){
				header("Content-Type: text/css");
				$styles = file_get_contents($path); # obtenido el contenido del script
				print($styles); # mostrando su contenido
			}
			else
				header("HTTP/1.0 404 Not Found");
		}

	}

	# obtiene el script de uno de los paquete de complementos que
	# son utilizables para el desarollo de la aplicacion
	private function getScriptPack($src){

		# obteniedo los parametros de la pagina
		$params = Config::get("params");

		# necesitamos el nombre del complemento y version para ir a su carpeta y 
		if(isset( $params['name'], $params['version'])){

			$path_app = Config::get("path_system");

			# verificamos si nuestro archivo esta en otra carpeta interna dentro
			# del mismo patron de sistema que trae
			if(isset($params['folders']))
				$path = "{$path_app}/system/complements/{$params['name']}-{$params['version']}/src/javascripts/".base64_decode($params['folders'])."/{$src}";
			else
				$path = "{$path_app}/system/complements/{$params['name']}-{$params['version']}/src/javascripts/{$src}";

			# verificamos que el archivo del complemento exista antes
			if(is_file($path)){
				header("Content-Type: application/javascript");
				$script = file_get_contents($path); # obtenido el contenido del script
				print($script); # mostrando su contenido
			}
			else
				header("HTTP/1.0 404 Not Found");

		}

	}

	# accion realizada para obtener una imagen dentro de la solicitud de imagenes del servidor
	public function imagesAction(){

		$params = Config::get("params");
		$image_src = $params['src'];

		if(isset($params['folders']))
			$path = Config::get('path_application_default')."/".Config::get('path_resources')."/".Config::get('path_image')."/". base64_decode($params['folders']) ."/{$image_src}";
		else
			$path = Config::get('path_application_default')."/".Config::get('path_resources')."/".Config::get('path_image')."/{$image_src}";

		# primero verificaremos que la imagen que estamos solicitando se encuentre
		if(file_exists($path)){

			if( isset($params['x']) || isset($params['y']) )
				$this->createThumbnails($path, $params['x'], $params['y']);
			else
				$this->createThumbnails($path);

		}
		else{
			Report::setError("La imagen solicitada no se encuentra en el lugar, probablemente fue removido a otra ubicación/n");
			header("HTTP/1.0 404 Not Found");
		}
	}

	# function privada que creara la imagen miniatura o del tamaño que la necesitemos
	private function createThumbnails($src = "",$width = null, $height =  null){

		$top = 0;
		$left = 0;
		$right = 0;
		$bottom = 0;

		# obteniedo parametros
		$params = Config::get("params");

		if(!is_file($src) )
			header("HTTP 1.1/ 404");

		$data_img = getimagesize($src);

		if($width == null || $height == null){
			$width = $data_img[0]; # definiendo altura por anchura
			$height = $data_img[1];
		}

		# de esta forma hara los calculos para no distorcionar la imagen
		if(isset($params['maxWidth'])){
			if($params['maxWidth'] < $data_img[0]){
				$height = ($data_img[1] * $params['maxWidth']) / $data_img[0];
				$width = $params['maxWidth'];
			}
			else{
				$height = $data_img[1];
				$width = $data_img[0];
			}
		}

		if(isset($params['maxHeight'])){
			if($params["maxHeight"] < $data_img[1]){

				if(!isset($width)){
					$height = $params['maxHeight'];
					$width = ($data_img[0] * $params['maxHeight']) / $data_img[1];
				}
				else
				{
					if($height > $params["maxHeight"]){
						$height = $params['maxHeight'];
						$width = ($data_img[0] * $params['maxHeight']) / $data_img[1];
					}
				}
			}
			else{
				$height = $data_img[1];
				$width = $data_img[0];
			}
		}

		switch ($data_img['mime']) {
			case 'image/jpeg': $img_tmp = @imagecreatefromjpeg($src); break;
			case 'image/gif': $img_tmp = @imagecreatefromgif($src); break;
			case 'image/png': $img_tmp = @imagecreatefrompng($src); break;
			default: $img_tmp =  @imagecreatefromjpeg($src); break;
		}

		header("Content-Type: {$data_img['mime']}");

		if($data_img["mime"] == "image/gif")
			$image = imagecreate($width, $height);
		else
			$image = imagecreatetruecolor($width, $height);


		// copiando el tamaño redimensionado
		imagecopyresized($image, $img_tmp, 0, 0, 0, 0, $width, $height, $data_img[0], $data_img[1]);

		// esta funcion esta diseñada para poder centrar la imagen dedica
		if(!empty($this->params["center"])){

			$y = 0;
			$x = 0;

			if($width > $height){
				$height = $this->params["center"];
				$width = ($data_img[0] * $height) / $data_img[1];
			}
			else{
				$width = $this->params["center"];
				$height = ($data_img[1] * $width) / $data_img[0];
			}

			if($data_img["mime"] == "image/gif"){
				$image = imagecreate($width, $height);
				$img = imagecreate($this->params["center"], $this->params["center"]);
			}
			else{
				$image = imagecreatetruecolor($width, $height);
				$img = imagecreatetruecolor($this->params["center"], $this->params["center"]);
			}

			// copiando el tamaño redimensionado
			imagecopyresized($image, $img_tmp, 0, 0, 0, 0, $width, $height, $data_img[0], $data_img[1]);

			// calculando la proporcion
			if($width > $height)
				$x = ($width -$height) / 2;
			else
				$y = ($height - $width) / 2;

			imagecopy($img, $image, 0, 0, $x, $y, $this->params["center"], $this->params["center"]);
			$image = $img;
		}

		// esta funcion hace un corte a la foto tremendo
		if(!empty($this->params["cut"])){

			$cut = explode("x", $this->params["cut"]);

			$y = $cut[1];
			$x = $cut[0];
			$src_h = 0;
			$src_w = 0;

			if($x < $y){
				$height = $y;
				$width = ($data_img[0] * $height) / $data_img[1];
			}
			else{
				$width = $x;
				$height = ($data_img[1] * $width) / $data_img[0];
			}


			if($x < $width && empty($this->params["cut_width"]))
				$x = $width;
			
			if($y > $height && empty($this->params["cut_height"]))
				$y = $height;

			if($data_img["mime"] == "image/gif"){
				$image = imagecreate($width, $height);
				$img = imagecreate($cut[0], $cut[1]);
			}
			else{
				$image = imagecreatetruecolor($width, $height);
				$img = imagecreatetruecolor($x, $y);
			}

			// copiando el tamaño redimensionado
			imagecopyresized($image, $img_tmp, 0, 0, 0, 0, $width, $height, $data_img[0], $data_img[1]);

			if( !empty($this->params["cut_width"]) )
				$src_h = ($height - $y) / 2;

			if( !empty($this->params["cut_height"]) )
				$src_w = ($width - $x) / 2;

			imagecopy($img, $image, 0, 0, 0, $src_h, $width, $height);
			$image = $img;
		}

		# mostrando la imagen de acuerdo a que tipo de imagen es
		switch ($data_img['mime']) {
			case 'image/jpeg': imagejpeg($image); break;
			case 'image/gif': imagegif($image); break;
			case 'image/png': imagepng($image); break;
			default: imagejpeg($image); break;
		}
	}
}

