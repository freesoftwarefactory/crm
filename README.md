FREESOFTWAREFACTORY/CRM
=======================

Setup Instructions:
------------------

1. Install mysql tables:

		mysql <databasename> < /ruta/al/repo/vendor/freesoftwarefactory/crm/mysql.schema.sql

2. Install the Console Commands:

		cd /bla/my-app/commands
		ln -s ../freesoftwarefactory/crm/console/CrmController.php .

3. Install the Controller:

		cd /bla/my-app/controllers
		ln -s ../freesoftwarefactory/crm/web/CrmController.php .

4. Setup files:
		
		// copy files
		cp /bla/my-app/vendor/freesoftwarefactory/crm/crm-config.php bla/my-app/config
		// reflect it in 'components' section  config/console.php, config/web.php
		'crm' => require(__DIR__.'/crm-config.php'),


Config Crm Fields
-----------------

Here you define all the crm fields.

Copy this file into your config/ path, as: 

		/your/app/config/crm-config.php

register it in your components section inside web.php and console.php:

		'crm' => require(__DIR__.'/crm-config.php'), 

```
<?php
return [
"class"=>"freesoftwarefactory\crm\Api",
"layouts"=>array(
	"default"=>"@crmviews/base",			// <-- use this layout
	"create"=>"",	// or, per view: create,edit,view,find
	"edit"=>"",		// ...
),
"fields"=>array(
		"first_name"=>array(
			"label"=>"Nombre",	// come on...
			"size"=>45,		//
			"min"=>0,		//
			"type"=>"text",	//
			"placeholder"=>"Ingrese el nombre",	//
			"default"=>"",		// default value
			"required"=>true,	// mark as required
			"list"=>1,			// used to be showed in find results
		),	
		"last_name"=>array(
			"label"=>"Apellido",
			"size"=>45,
			"min"=>0,
			"type"=>"text",
			"placeholder"=>"Ingrese el apellido",
			"list"=>1,
		),	
		"primary_email"=>array(
			"label"=>"Correo Personal",
			"size"=>80,
			"min"=>0,
			"type"=>"mail",
			"placeholder"=>"Ingrese un correo",
			"list"=>1,
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
];
```

