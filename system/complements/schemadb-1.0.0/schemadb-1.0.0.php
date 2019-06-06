<?php

class schemadb{
	
	public static $type_complements = "Application";

	# para las aplicaciones base es necesario especificar sus controladores
	# mejorar el rendimiento de las aplicaciones y bajo consumo de recurso
	# durante la ejecución
	public static $controllers = ["schema_db", "schema_seed", "schema_tables","schema_home"];
}