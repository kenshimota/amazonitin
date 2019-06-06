<?php

require "ModelIterator.php";
require "JoinModel.php";

class Model extends JoinModel implements IteratorAggregate{
	
	# constructor de un modelo de una tabla de base de datos
	public function __construct($record = []){

		# solo si hay una conexion segura
		if(empty(self::$connect))
			self::$connect = DB::getConnect() ? DB::getConnect() : exit(1); # permite obtener una conexion hacia la db

		$this->table = get_class($this); # de acuerdo a la clase que este extendida obtendra la identidad de la tabla

		if(is_array($record))
			$this->last_record = $record;
	}

	# mostrar el registro actual
	public function __get($key){
		
		if(!isset($this->last_record[0])){

			if(isset($this->last_record[$key]))
				return $this->last_record[$key];
		
			elseif( isset($this->last_record["id"]) ){

				return $this->last_record[$key] = $this->join($key, $this->table, $this->last_record['id']);
			}
		}

		return false;
	}

	# method que se ejecuta antes de hacer una serie de transacciones
	static public function transaction_start(){
		self::$connect->autocommit(false);
	}

	# function que permite el resguardo de una serie de datos ya obtenidos
	static public function transaction_end(){
		self::$connect->commit();
	}

	# funcion que se encarga de devolver la ultima consulta que realizaste
	public function getQuery(){
		return $this->last_query;
	}

	/* te permite imprimir un registro o una serie de ellos guardados en un objeto */
	public function __toString(){

		if(!$this->multiple){

			$id = !empty($this->last_record['id']) ? $this->last_record['id'] : null;

			# primero mandaremos a consultar todas la tablas que estan relacionadas
			# que sea hijas
			if( !empty($this->last_record) )
				foreach ($this->getChildren($id) as $key => $value)
					$this->last_record[ $key ] = json_decode( $value->__toString() );


			return empty($this->last_record) ? json_encode([]) : json_encode($this->last_record);
		}
		elseif($this->multiple){
			$index = 0; # indice
			$records = array(); # numero de registro contados
			while( $index < count($this->last_record) ){
				$records[] = json_decode("".$this->last_record[$index]);
				$index++;
			}
			return json_encode( $records );
		}

		return "[]";
	}


	# devuelve todos los registros de una table
	public  function all(){
		$query = "select {$this->select} from {$this->table} limit {$this->limit} offset {$this->offset}";
		$result = self::$connect->query($query);
		$this->getRecord($result);
		return $this;
	}

	# esta funcion te permite buscar datos de un elemento dentro de la 
	# tabla que se este consultando puede hacer 
	public  function find($key = array()){

		# depurando el resultado de la db
		$result = null;

		# buscando un registro por identidad
		if(is_numeric($key) || is_string($key)){
			$query  = "SELECT {$this->select} FROM {$this->table} WHERE id='{$key}' LIMIT 1";
			$this->last_query = "SELECT {$this->select} FROM {$this->table} WHERE id='{$key}'";
			$result = self::$connect->query($query);
		}

		# buscando una serie de registros
		if(is_array($key)){

			$params = "";
			$i = 0;

			foreach ($key as $id => $value) {

				if($i > 0)
					$params = "{$params} and";

				$params = "{$params} {$id} = '{$value}'";
				$i++;
			}

			
			$query = "select {$this->select} from {$this->table} where ({$params}) limit {$this->limit} offset {$this->offset}";

			$result = self::$connect->query($query);
			$this->last_query = "select {$this->select} from {$this->table} where ({$params})";
		}

		if($result){
			$this->last_record = $this->getRecord($result);
			return $this;
		}
		else
			return false;
	}

	# funcion que permite buscar un registro y si no existe lo crea
	public  function find_or_create($key = array()){
		
		$this->limit(1);
		$this->find($key);
		if( empty($this->last_record) )
			$this->insert($key);

		$this->limit(20);

		return $this;
	}

	# funcion propuesta para resolver la situacion
	public function length(){
		$result = self::$connect->query($this->last_query) or $this->consoleErrorSystem(self::$connect->error);
		return $result->num_rows;
	}

	# funcion que se ocupa de agregar los selects
	public function selects($select = array()){

		if(is_string($select))
			$this->select = $select;

		if(is_array($select))
			for ($i=0; $i < count($select); $i++){
				if($i > 0)
					$this->select = "{$this->select},{$select[$i]}";
				else
					$this->select = $select[$i];
			}
	}

	# funcion que actualiza el registro actual que se este mostrando
	# para actualizar sus datos
	public function update($params = array()){

		# estableciendo el dato de actualización
		$params["date_update"] = date("Y-m-d H:i:s"); 
		
		$values_fields = array(); 
		$fields = $this->getFields();

		foreach ($fields as $key => $value) {

			# solo si existe un cierto campo dentro del registro
			# este podra ser agregado a la lista de parametros a enviar

			if(isset($params[$value]))
				$values_fields[ $value ] = self::$connect->real_escape_string( strip_tags($params[ $value]) );
		}

		if(!empty($values_fields))
		{
			$params_new = "";
			$i = 0;

			foreach ($values_fields as $key => $value) {

				if($i > 0)
					$params_new = "{$params_new},";

				if($key != "id"){
					$this->last_record[$key] = $value;
					$params_new = "{$params_new} $key='$value'";
				}
				$i++;
			}

			if($this->last_record)
			{
				$query = "update {$this->table} set {$params_new} where id='{$this->last_record['id']}'";

				$result = self::$connect->query($query) or $this->consoleErrorSystem("Ocurrion un problema al tratar de actualizar un registro (\"update {$this->table} set {$params_new} where id='{$this->last_record['id']}'\")");

				if( strcmp($this->table, "schema_event_records") ){
					$migrations = new Migrations();
					$migrations->event_record($this->table, "U", $this->last_record["id"], "Actualizando Registro");
				}

				return $this;
			}
		}
		else
			return null;
	}

	# funcion encargada de insertar un nuevo registro dentro de la tabla
	# de nuestra base de datos que usemos
	public  function insert($record = array()){

		$record["date_created"] = date("Y-m-d H:i:s");
		$record["date_update"] = date("Y-m-d H:i:s");

		$values_fields = array(); 
		$fields = $this->getFields();

		foreach ($fields as $key => $value) {
			# solo si existe un cierto campo dentro del registro
			# este podra ser agregado a la lista de parametros a enviar
			if(isset($record[$value]))
				$values_fields[ $value ] = self::$connect->real_escape_string( strip_tags($record[$value]) );
		}

		# solo si hay parametros a enviar este puede crear un 
		# registro hacia su tabla
		if(!empty($values_fields)){

			$text_fields = "";
			$values_text_fields = "";
			$i = 0;

			foreach ($values_fields as $key => $value) {

				if($i > 0 && $key != "id" ){
					$text_fields = "{$text_fields},";
					$values_text_fields = "{$values_text_fields},";
				}

				if($key != "id"){
					$text_fields = "{$text_fields} {$key}";
					$values_text_fields = "{$values_text_fields} '{$value}'";
					$i++;
				}
			}

			# consulta a realizar para la insercion de un registro
			$query = "insert into {$this->table} ({$text_fields}) values ({$values_text_fields})";

			$this->last_query = $query;

			$result = self::$connect->query($query) or die(self::$connect->error);

			// obteniendo la identidad antes que lo cambie por otra tabla
			$id = self::$connect->insert_id;

			if( strcmp($this->table, "schema_event_records") ){
				$migrations = new Migrations();
				$migrations->event_record($this->table, "C", self::$connect->insert_id, "Creacion de Registro");
			}

			if($result)
				return $this->find($id);
			
		}
		else
			return null;
	}

	/* eliminación perfecta de registros */
	public function delete(){

		/* Esto nos permitira ver el desarrollo de una serie de registros */
		if(isset($this->last_record[0])){
			
			for($i = 0; $i < count($this->last_record); $i++){
				$record = $this->last_record[$i];
				$record->delete();
			}

			$this->last_record = array();

		}
		elseif(isset($this->last_record["id"]))
			$this->destroy();
	}

	# funcion que evalua una consulta que vos ordenes
	public function where($sql = ""){
		$this->where = $sql;
		$this->last_query = "SELECT {$this->select} FROM {$this->table} WHERE {$sql}";
		$result = self::$connect->query("SELECT {$this->select} FROM {$this->table} WHERE {$sql} LIMIT {$this->limit} OFFSET {$this->offset}");
		return $this->getRecord( $result );
	}

	# funcion que se encarga de hacer el offset
	public function offset($start = 0){
		$this->offset =  $start;
	}

	# funcion que se agrega el limite
	public function limit($end = 20){
		$this->limit = $end;
	}

	# funcion encaragada para la interaccion de los elementos de un resultado
	# de datos de una gran cantidad de elemento
	public function getIterator(){
		return new ModelIterator($this->last_record);
	}

	# cargara todo lo que pueda conseguir en la carpeta de modelos
	public function load_models(){

		$path_app = Config::get("path_application");
		$path_models = Config::get("path_models");

		$dir = "{$path_app}/{$path_models}";

		# verifica la existencia de carpeta en donde encontrara los modelos
		if(is_dir($dir)){
			# abre directorio de los modelos
			if($dh = opendir($dir)){

				while($models = readdir($dh)){
					if($models != "." && $models != ".." && $model = strstr($models,".php",true))
						require_once "{$dir}/{$model}.php";
				}

			}

			# cierra el directorio de los modelos
			closedir($dh);
		}
	}

	# funcion que permite cambiar el tipo de excepsiones
	public function changedException($bool = true){
		switch ($bool) {
			case false:
				$this->autoException = false;
			break;
			default:
				$this->autoException = true;
			break;
		}
	}

	# funcion que permite revertir el evento antes de que ocurra
	public static function rollback(){
		return self::$connect->rollback();
	}

	# funcion directa para la destruccion de un registro
	private function destroy(){

		if ( array_key_exists("id", $this->last_record) ) {
			$id = $this->last_record["id"];
			
			$query = "delete from {$this->table} where id='{$this->last_record['id']}'";
			
			$result = self::$connect->query($query) or $this->consoleErrorSystem("Ocurrio un problema al eliminar el registro query -> (\"delete from {$this->table} where id='{$this->last_record['id']}'\") ");
			
			Report::setInfo("Eliminación de un registro");
			
			$this->last_record = array();
			
			if( strcmp($this->table, "schema_event_records") ){
				$migrations = new Migrations();
				$migrations->event_record($this->table, "D", $id, "Eliminando Registro");
			}

			return [];
		}
		else
		{

			foreach (Report::getErrors() as $key => $error) {
				print("<br>{$key}: {$error}<br>");
			}

			#header("HTTP/1.0 422 unproccessable");
			exit(1);
		}
	}

	# obtener una serie de registro de acuerdo a una consulta a la tabla
	private function getRecord($result){

		# esto permite saber si hubo una buena consulta 
		if($result){

			switch ($result->num_rows) {
				case 1:
					foreach ($result->fetch_assoc() as $key => $value)
						$this->last_record[$key] = $value;
					return $this->last_record;
				break;
				case 0:
					return null;
				break;
				default:

					if($result->num_rows > 1){


						$this->multiple = true;

						# registro vacio primero
						$record = array();


						for($i=0; $reg = $result->fetch_assoc(); $i++){
							$record[$i] = new $this->table($reg);
							/*$record[$i]->selects("{$this->select}");
							$record[$i]->find($reg["id"]);*/
						}

						return $this->last_record = $record;
					}

				break;
			}
		}
	}

	# esta funcion privada consigue obtener todos los campos que tiene 
	# una tabla dentro de nuestra base de datos
	private function getFields(){
		$query  = "select {$this->select} from {$this->table}";
		$this->last_query = $query;
		$result = self::$connect->query($query);

		# visita cada uno de los campos de nuestra tabla
		while($field = $result->fetch_field())
			$fields[] = $field->name; # devolviendo el nombre de cada uno de los campos
		return $fields;
	}

	# funcion que se encarga de devolver le valor como un string formal
	private function valueString($value){

		$chars = urldecode($value);
		$chars_html = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES | ENT_HTML5);
		foreach ($chars_html as $key => $value)
			if( strrpos($chars, $key) )
				return htmlentities($chars);

		return $chars;
	}

	/* funcion privada que da un mensaje de error y muestra que ocurrio un error en la base de datos haciendo un headers y cambiando el estado de la cabezera */
	private function consoleErrorSystem($message){
		
		if($this->autoException == true){
			Report::setError($message);
			header("HTTP/1.0 422 Unprocessable");
			self::$connect->rollback();
			foreach (Report::getErrors() as $key => $error)
					print("
						<br>
							<span style='color: red; font-weight: bold;'>
								Error (".($key + 1)."):
								<label style = 'color: #666;'>{$error}</label>
							</span>
						<br>
					");

			exit(1); # terminando con el proceso de ejecución de el programa
		}
	}

	#contedra la tabla que se va ha examinar
	private $table;
	private $offset = 0;
	private $multiple = false;
	private $limit = 20;
	private $select = "*";
	private $last_query = "";
	private $where = "";
	private $last_record = array();
	protected static $connect;
}