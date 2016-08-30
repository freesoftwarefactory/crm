<?php
return array(
"layouts"=>array(
	"default"=>"crm::base",
	"crear"=>"prueba", // definido en: resources/prueba.blade.php
),
"fields"=>array(
		"first_name"=>array(
			"label"=>"Nombre",
			"size"=>45,
			"min"=>0,
			"type"=>"text",
			"placeholder"=>"Ingrese el nombre",
			"default"=>"123",
		),	
		"last_name"=>array(
			"label"=>"Apellido",
			"size"=>45,
			"min"=>0,
			"type"=>"text",
			"placeholder"=>"Ingrese el apellido",
		),	
		"primary_email"=>array(
			"label"=>"Correo Personal",
			"size"=>80,
			"min"=>0,
			"type"=>"mail",
			"placeholder"=>"Ingrese un correo",
		),	
		"primary_phone"=>array(
			"label"=>"Telefono Personal",
			"size"=>45,
			"min"=>0,
			"type"=>"text",
			"placeholder"=>"Ingrese un telefono",
		),	
		"notes"=>array(
			"label"=>"Notas Adicionales",
			"size"=>45,
			"min"=>0,
			"rows"=>5,
			"type"=>"textarea",
			"placeholder"=>"Notas adicionales",
		),	
	)
);
