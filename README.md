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

Here you define all the crm fields. Copy this file into your config/ path, as: 

	/your/app/config/crm-config.php

register it in your components section inside web.php and console.php:

	'crm' => require(__DIR__.'/crm-config.php'), 


Example setup file:


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
			"browse"=>1,		// see also: crm_field
		),	
		"last_name"=>array(
			"label"=>"Apellido",
			"size"=>45,
			"min"=>0,
			"type"=>"text",
			"placeholder"=>"Ingrese el apellido",
			"list"=>1,
			"browse"=>1,		// see also: crm_field
		),	
		"primary_email"=>array(
			"label"=>"Correo Personal",
			"size"=>80,
			"min"=>0,
			"type"=>"mail",
			"placeholder"=>"Ingrese un correo",
			"list"=>1,
			"browse"=>1,		// see also: crm_field
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

## Contact Widget

This widget is designed to find/edit/create contacts, it has two modalities:
'finder' or 'browser'.  

* The 'finder' modality helps you pick one contact and select into a existing 
text input (a hidden input, as an example).  

* The 'browser' modality will present contacts using a 'checkbox' selector.


```
[html]

<?php
<style>
	.crm-form-group {
		display: inline-block;
	}
</style>
<div class='row'>
	<div class='col-md-12'>

		<input type='hidden' name='contact_id_receptor' />
		
		<div class='input-group' id='contacts_finder'>
			<input id='contact_selector' readonly 
				type='text' placeholder=''
					class='form-control' value='' />
			<span class='input-group-btn'>
				<button id='contacts_activator' 
					title='busque o cree un contacto haciendo click aqui'
					class='btn btn-primary'>
					<span class='glyphicon glyphicon-search'></span>
				</button>
				<button 
					title=''
					class='btn btn-success' id='submit_contact'>
					<span class='glyphicon glyphicon-plus'></span>OK</button>
			</span>
		</div>

		<?=\app\components\CrmFindContactWidget::widget([
			'mode'=>'browser',	// or 'finder'
			'readonly'=>false,
			'selector'=>'[name=contact_id_receptor]',
			'selector_label'=>'#contact_selector',
			'selector_activator'=>'#contacts_activator',
			'selector_finder'=>'#contacts_finder',
			'crm_field'=>'browse',  // show all columns with 'browse' attrib.
		]);?>
	</div>
</div>
```

## Customize Contact Widget using Events Handlers

The contact widget fires some events to help you take desitions in you
own implementation, just add this code snippets:

```
$( document ).on( 'crm:find:list:updated', { some: 'data'}, 
	function( event, list, keywords, resp ) {

});

$( document ).on( 'crm:form:launch', { some: 'data'}, 
	function( event, widget, current_contact) {

	widget.find('.result-list').hide();
});

$( document ).on( 'crm:form:render:contact', { some: 'data'}, 
	function( event, widget, current_contact, response) {
	
});
```

## API

Use the api methods via:

	\Yii::$app->crm

## Console Access

Handle all contacts and relationships using a Console:

	./yii crm



