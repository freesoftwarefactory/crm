<?php
	namespace freesoftwarefactory\crm;
	use yii\helpers\Url;
	use yii\web\View;
	use yii\helpers\Html;
	$api = new Api;	
	$api = \Yii::$app->crm;
	$edit_url = Url::toRoute('/crm/edit');
	$_instance = $api->findContact($contact_id);
?>
<div class='crm-form-body'>
	<?=$api->formViewConstructor($_instance);?>
	<a class='btn btn-primary edit-contact' 
		href='<?=$edit_url;?>'>Editar</a>
</div>


