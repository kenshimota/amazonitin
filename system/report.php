<?php

/**
* Clase que se encarga de hacer reporte de lo que sucede dentro del sistema
***/
class Report{

	/* Agrega la informacion de errores ocurrido al escribir datos 
	o fallo en la conexiones, entre otros */
	public static function setError($messague = null){

		# cada vez que ocurre un error notifico sobre este
		if(class_exists("ApplicationConsole"))
			print("\nError: {$message}\n");

		self::$errors[] = $messague;
	}

	/* Agrega las notificaciones al sistema que se necesiten */
	public static function setInfo($messague = null){
		
		if(class_exists("ApplicationConsole"))
			print("\nnotice: {$messague}\n");

		self::$notices[] = $messague;
	}

	/* Funcion que se encarga de devolver los errores acumulados */
	public static function getErrors(){
		if(self::$errors != null)
			return self::$errors;
		else
			return array();
	}

	public static function getInfos(){
		if(self::$notices != null)
			return self::$notices;
		else
			return array();
	} 

	private static $notices;
	private static $errors;
}