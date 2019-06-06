<?php

class amazonitin{

	# estableciendo en los determinantes que es un aplicacion base
	public static $type_complements = "Application";
	
	# definiendo rutas de nuestra aplicacion base
	public static $routes = [
		"path_image" => "icons"
	];

	# para las aplicaciones base es necesario especificar sus controladores
	# mejorar el rendimiento de las aplicaciones y bajo consumo de recurso
	# durante la ejecución
	public static $controllers = ["error", "tags", "server_render"];

	# funcion que te permite agregar un estilo a tu aplicacion desde un ser
	# componente alterno o desde elkmismo tuyo
	static public function add_style_server($complement, $style){

		# direccion del servidor donde visitara el archivo
		$uri = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['SERVER_NAME']}/server_render/exec/name/{$complement}/type/styles";

		# direcion donde se deben ver los elementos
		$dir = View::convertDirToUri($style, $uri);

		print("\n\t<link rel='stylesheet' type='text/css' href='{$dir}' />\n");
	}

	# funcion que te permite agregar un estilo a tu aplicacion desde un ser
	# componente alterno o desde elkmismo tuyo
	static public function add_script_server($complement, $script, $language = "text/javascript" ){

		# direccion del servidor donde visitara el archivo
		$uri = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['SERVER_NAME']}/server_render/exec/name/{$complement}/type/scripts";

		# direcion donde se deben ver los elementos
		$dir = View::convertDirToUri($script, $uri);

		print("\n\t<script type='{$language}' src='{$dir}'></script>\n");
	}

	static public function add_image_server($complement, $img, $params = null){

		# direccion del servidor donde visitara el archivo
		$uri = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['SERVER_NAME']}/server_render/exec/name/{$complement}/type/images";

		# enviando parametros hacia una imagen
		if(isset($params['params_url'])){
			foreach ($params['params_url'] as $key => $value) 
				$uri = "{$uri}/{$key}/{$value}";
		}

		# direcion donde se deben ver los elementos
		$dir = View::convertDirToUri($img, $uri);

		# aplicando atributos dentro de la etiqueta
		$attributes = isset($params["attributes"]) ? View::setAtrributesTags($params["attributes"]) : "";

		# aplicando atributos dentro de el estilo
		$styles = isset($params["styles"]) ? "style='".View::setStylesTags($params["styles"])."'" : "" ;

		print("\n\t<img {$attributes} {$styles} src='{$dir}' />\n");

	}

}