<?php

# Autor: Erik M. Galindo
# Fecha: 24 de Septiembre del 2018 
#
# clase que se encarga entra la relacion de fields y los modelos sin necesidad de establecer
# enlazes directos dentro la base de datos sino que este codigo de forma inteligenta estudiara
# la relacione de datos de la tabla
class JoinModel{

	# la tablas hijas tendra un field que dirige hacia que cual registro de su padre estan relacionadas 
	const child = 0x01ac;

	# las tablas padres no tiene un field en el sino que el hijo tiene un field que lo
	# enlaza con la id del registro en la tabla padre
	const father = 0x1010;

	const parentField = "parent";

	# Constructor
	public function __construct(){
		$data = $this->joinTables;
	}

	# carga a la tabla actual las tablas hijas
	public function getChildren($id){
		$nameTable = get_class($this); # nombre de la tabla
		$attributeTable = get_class_vars($nameTable); # los atributos que tiene la tabla para obtener sin hijos

		$tables_join = array_key_exists('join', $attributeTable) ? $attributeTable['join'] : array();

		$children = array();

		for($i = count($tables_join) - 1; $i >= 0; $i--){

			if($tables_join[$i]['parent'] == self::child){

				if( class_exists($tables_join[$i]['table']) ){
					$table_child = new $tables_join[$i]['table'];
					$children[ $tables_join[$i]['table'] ] = $this->join($tables_join[$i]['table'], $nameTable, $id);
				}

			}
			elseif( $tables_join[$i]["parent"] == self::father){

				if( class_exists($tables_join[$i]["table"]) ){
					$table_child = $this->table;
					$children[ $tables_join[$i]['table'] ] = $this->join($tables_join[$i]['table'], $nameTable, $id);
				}

			}

		}

		return $children;
	}


	// funcion de relaciones de tablas
	protected function join($key = null, $nameTableParent = "", $id = 1){
		
		# igualando la identidad de relacion del registro actual para saber a que padre u hijo
		# buscamos
		$this->id_relation = $id;

		# esta es la tabla actual que esta haciendo la consulta
		$this->table =  $nameTableParent;

		# el key no puede estar nulo y debe ser un string
		if($key != null && is_string($key) && $nameTableParent != ""){

			# las tablas ya enlazadas
			$tables_join = get_class_vars($nameTableParent);
			$tables = isset($tables_join['join']) ? $tables_join["join"] : [];

			# contando numero de tablas enlazadas
			$num_tables = count($tables);

			
			while( ($num_tables - 1) >= 0){

				if( isset($tables[$num_tables - 1][self::parentField]) && isset( $tables[$num_tables - 1]['table'] ) && isset($tables[$num_tables - 1]['field']) )
				{

					if(strcasecmp($tables[$num_tables - 1]['table'], $key) == 0)
						$this->study_relations_tables($tables[ $num_tables - 1]);
				}

				$num_tables--;
			}

		}

		return $this->record;

	}

	# funcion que estudia la relacion de la tablas 
	public function study_relations_tables($relation){

		switch ($relation[self::parentField]) {
			case self::father:
				$this->joinFather( $relation['table'], $relation['field'] );
			break;
			
			default:
				$this->joinChild( $relation['table'], $relation['field'] );
			break;
		}

	}

	# funcion que te permite enlazar con la tabla hija
	private function joinChild($nameTable = "", $fieldJoin = ""){

		if( class_exists($nameTable) ){

			$table = new $nameTable(1);
			
			$table->find([
				$fieldJoin => $this->id_relation
			]);

			$this->record = $table;
		}

	}

	# funcion que enlaza la tabla padre
	private function joinFather($nameTable = "", $fieldJoin = ""){

		# verificamos que el padre exista
		if( class_exists($nameTable) ){

			$table_child = new $this->table;
			if($data = $table_child->find($this->id_relation)){
				$table_father = new $nameTable;
				$table_father->find( $data->$fieldJoin );
				$this->record = $table_father;
			}
			else
				$this->record = new $nameTable;
		}
	}

	private $id_relation;
	private $record;
	private $table;
}