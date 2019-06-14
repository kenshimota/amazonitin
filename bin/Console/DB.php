<?php

/**
* Clase que se encargara de realizar todas las transacciones
* Que deban ocurrir en la base de datos
*/
class DB{

	# constructor de base de datos
	public function __construct(){
		
		$data_connect = get_class_vars('DataBases');

		self::$connect = new Mysqli( $data_connect['host'], $data_connect['user'], $data_connect['password']) or die("Error de conexion (". mysqli_connect_errno() .") : ". mysqli_connect_error());

		if(self::$connect->connect_error)
			die("Error de conexion (". mysqli_connect_errno() ."): ". mysqli_connect_error());
		else
			print("La conexion fue realizada con exito: ".self::$connect->host_info."\n");

		/* Busca la existencia de la base de datos */
		$exist_db = !mysqli_connect_error() ? $this->find_db($data_connect['dbname']) : true;

		# si la base de datos no existe este permitira crearla
		if($exist_db){
			$success = $this->create_db($data_connect['dbname']);
			if(!$success)
				die("La base de datos no pudo ser creada...".self::$connect->error);
		}
		else
			self::$db_application = $data_connect['dbname'];
	}

	/* Funcion que busca la existencia de una base de datos si esta existe
	retorna 1 de lo contrario devuelve 0 */
	public function find_db($db){
		$query = self::$connect->query("USE `{$db}`;");
		return !$query;
	}

	/* Funcion que se encarga de crear la base de datos y devuelve una serie de noticias
	que puede recibir el servidor */
	public function create_db($db_save){
		$query = mysqli_query(self::$connect, "create database `{$db_save}`");
		if(!$query)
			die("Ocurrio un error al crear la base de datos {$db_save}");
		else{
			self::$db_application = $db_save;
			print("La base de datos ".self::$db_application." fue creada con exito \n");
			self::$connect = self::getConnect(); 
			return true;
		}
	}

	# funcion que se encarga de eliminar la base de datos
	public function destroy_db(){
		$query = $this->execute("DROP DATABASE  `".self::$db_application."`");
		if(!$query)
			die("Ocurrio un error al eliminar la base de datos\n Mysqli({$query->errno}):{$query->error}");
		else{
			print("La base de datos ".self::$db_application." fue eliminada con exito \n");
			self::$db_application = null;
			return true;
		}
	}

	# te permite devolver la conexion que obtuviste
	# nota: solo puede devolverte una conexion si ya ha podido conectarse anteriormente
	public static function getConnect($db = true){
		$data_connect = get_class_vars('DataBases');
		if($db)
			$connect_tmp = new Mysqli( $data_connect['host'], $data_connect['user'], $data_connect['password'], $data_connect['dbname']);
		else
			$connect_tmp = new Mysqli( $data_connect["host"], $data_connect["user"], $data_connect["password"] );
		if($connect_tmp)
			$connect_tmp->set_charset("utf8");
		return $connect_tmp ? $connect_tmp : null;
	}

	public function execute($query, $file = ""){

		$result = self::$connect->query($query);
		if(!$result){
			if(empty($file))
				die("Ocurrio un error en la consulta\nMysqli(".self::$connect->errno."): ".self::$connect->error."\n\n");
			else
				die("Ocurrio un error en la consulta del archivo {$file}\nMysqli(".self::$connect->errno."): ".self::$connect->error."\n\n");
		}

		print("Mysqli Query:\n\t{$query}\nResulta de la consulta: ".json_encode($result)."\nconsulta realizada con exito...!\n");
		return $result;
	}

	# cierra la conexion hacia la base de datos de la pagina
	public function __destruct(){
		if(!mysqli_connect_error())
			self::$connect->close() or die( self::$connect->mysql_error() );
	}

	private static $db_application;
	private static $connect;
}