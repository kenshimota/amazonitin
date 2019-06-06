<?php

class React{

	/* funcion que se ejecuta al instalar */
	static public function exec(){

		$path_application = Config::get('path_application')."/".Config::get('path_resources')."/".Config::get('path_script'); 

		$path_complement = "{$path_application}/components/";

		if( !is_dir($path_complement) && is_dir($path_application))
			mkdir( $path_complement );
	}

	/* funcion que permite agregar el componente reactivo*/
	public function react_component($arguments = array()){

		$path_complement = Config::get('path_application')."/".Config::get('path_resources')."/".Config::get('path_script')."/components";

		$div = "<div id='{$arguments[0]}-react-component-az'>\n</div>\n";
		$component_react = file_get_contents("{$path_complement}/{$arguments[0]}.js");

		if(!isset( $arguments[2] ) ){
			$src = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['SERVER_NAME']}/tags/scripts";
			$uri = View::convertDirToUri("components/{$arguments[0]}.js", $src);
			print("{$div}\n\t<script type='text/babel' src='{$uri}'></script>\n");
		}
	}

	public static $type_complements = "Scripts";
	public static $files_path = [
		"react.min.js" => "script",
		"react-dom-server.min.js" => "script",
		"react-dom.min.js" => "script",
		"react-with-addons.min.js" => "script",
		"browser.min.js" => "script"
	];
}