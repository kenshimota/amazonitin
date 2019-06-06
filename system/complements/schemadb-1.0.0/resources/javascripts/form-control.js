
// al carga el documento principal en html
$(document).ready(load_form);


/* funcion que se encarga de crear campos de formulario
en para los nuevos campos que seran utilizados */
function insert_fields_form(){
	$("#fields").append('<div class="row"><div class="col"><input type="text" placeholder="Field" name = "table[fields][name][]" class="form-control form-sm" id = "name-field" /></div><div class="col"><select type="text" class="form-control form-sm" name="table[fields][type][]" ><option>string</option><option>text</option><option>bool</option><option>integer</option><option>biginteger</option><option>float</option><option>double</option><option>decimal</option><option>date</option><option>datetime</option><option>time</option><option>year</option><option>references</option></select></div><div class="col"><input type="text" class="form-control" name = "table[fields][quantity][]" placeholder="cantidad" /></div></div>');
}

/* funcion que se encarga de la creacion de una tabla */
function create_table(){

	fetch("/schema_tables/create/",{
		method: "POST",
		headers: {
			"Content-Type" : "application/x-www-form-urlencoded"
		},
		body: "table=" + JSON.stringify({text: "Hola como estas"})+"&name=Erik mota"
	});
}

// evento ocurrido
function load_form(){
	var elements = $("#fields input.form-control#name-field");
	console.info(elements ? "Elementos cargados correctamente" : "Los elementos no fueron cargados correctamente");
}