<?php
	namespace freesoftwarefactory\crm;
	use yii\helpers\Url;
	use yii\web\View;
	use yii\helpers\Html;
	$api = \Yii::$app->crm;
	$ajax_select = Url::toRoute('/crm/ajaxselect');
	$url_view = Url::toRoute('/crm/view');
?>
<form id='crm_find' action='<?=Url::toRoute('/crm/ajaxfind');?>'>
	<?=Html::csrfMetaTags();?>
	<label>Lista de Contactos</label>
	<div class="row">
		<div class="col-lg-6">
			<div class="input-group">
				<input type="text" name='keywords'
					class="form-control" placeholder="busca por...">
				<span class="input-group-btn">
					<button class="btn btn-success" name='find' 
						type="button">Busca</button>
				</span>
			</div>
		</div>
	</div>
</form>
<div class='crm-contacts'></div>

<?php
$this->registerJs("
console.log('init crm_form');
var crm_form = $('#crm_find');

function crm_form_edit(){
	$('.view-contact').each(function(){
		var span = $(this);
		span.css('cursor','pointer');
		span.click(function(){
			var id = span.attr('data');
			$.ajax({ cache: false, type: 'post', async: true, data: { id : id},
				url: '{$ajax_select}',
				success: function(resp){ console.log('success',resp); 
					window.location.replace('{$url_view}');
				}, 
				error: function(e){ console.log(e.responseText); }
			});
		});
	});
}

crm_form.find('[name=find]').click(function(){
	var keywords = crm_form.find('[name=keywords]').val().trim();
	var data = { keywords: keywords };
	crm_form.find('[name=find]').attr('disabled','disabled');
	crm_form.find('[name=keywords]').attr('disabled','disabled');
	var _clear = function(){
		crm_form.find('[name=find]').attr('disabled',null);
		crm_form.find('[name=keywords]').attr('disabled',null);
	}
	$('.crm-contacts').html('<img src=\'/img/loading.gif\' />');
	$.ajax({ cache: false, type: 'post', async: true, data: data,
		url: crm_form.attr('action'),
		success: function(resp){ 
			console.log('success',resp); 
			_clear();
			$('.crm-contacts').html('');
			if(true == resp.status){
				$('.crm-contacts').html(resp.html);
				crm_form_edit();
			}else{
				$('.crm-contacts').html('sin resultados');
			}
		}, 
		error: function(e){ console.log(e.responseText); _clear(); 
			$('.crm-contacts').html('(sin resultados. ocurrio un error)');
		}
	});
});

",View::POS_READY);
?>

