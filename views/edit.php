<?php
	namespace freesoftwarefactory\crm;
	use yii\helpers\Url;
	use yii\web\View;
	use yii\helpers\Html;
	$api = \Yii::$app->crm;
	$form_id = 'crm_form_'.rand(10000,99999);
	$_instance = $api->findContact($contact_id);
	$view_url = Url::toRoute('/crm/view/');
?>
<form id="<?=$form_id;?>" action='<?=Url::toRoute('/crm/ajaxsave');?>' method='POST'>
	<?=Html::csrfMetaTags();?>
	<div class='crm-form-body'>
		<?=$api->formEditConstructor($_instance);?></div>
	<div class='crm-form-buttons'>
		<input type='submit' name='submit' value='Guardar Contacto' 
			class='btn btn-success' />
		<span class='loading' style='display:none;'>
			<img src='/img/loading.gif' style='width: 24px;' /></span>
	</div>
	<span class='error _general-error'></span>
</form>
<span id='crm_after_save' style='display:none;'>
El Contacto ha sido guardado. <a href='<?=$view_url;?>'>Ver Contacto</a></span>
<?php
$this->registerJs("
	var _form = $('#$form_id');
	_form.find('[name=submit]').click(function(e){
		e.preventDefault();
		_form.find('.error').html('').hide();
		_form.find('[name=submit]').attr('disabled','disabled');
		_form.find('.crmfield').attr('disabled','disabled');
		_form.find('.loading').show();
		var action = _form.attr('action');
		var fields = {};
		_form.find('.crmfield').each(function(i,k){ 
			var n = $(this).attr('name');
			var v = $(this).val().trim();
			fields[n]=v;
		});
		console.log('submit..',action,fields);
		$.ajax({ cache: false, type: 'post', async: true, data: fields,
			url: action,
			success: function(resp){ 
				_form.find('[name=submit]').attr('disabled',null);
				_form.find('.crmfield').attr('disabled',null);
				_form.find('.loading').hide();
				console.log('success',resp); 
				//...
				if(true == resp.result){
					_form.hide();
					$('#crm_after_save').show();		
				}else{
					$.each(resp.errors,function(i,error){
						var _error = $('.'+error.field+'-error');
						_error.html(error.error);
						_error.show();
					});
				}
				//...
			}, 
			error: function(e){ console.log(e.responseText); 
				_form.find('[name=submit]').attr('disabled',null);
				_form.find('.crmfield').attr('disabled',null);
				_form.find('.loading').hide();
			}
		});
		return false;
	});
	console.log('crm_form #$form_id initialized..');
",View::POS_READY);
