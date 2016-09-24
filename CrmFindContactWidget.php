<?php
namespace app\components;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
/*
 
	usage:

	1. define layout:

	<div id='entire_finder_group'>
	 	<input type='text' id='my_contact' value='...' />
		<input type='text' id='my_contact_label' value='...' />
		<button id='my_contact_buscar' 
			class='btn btn-success' title='Buscar Contacto'>
			<span class='glyphicon glyphicon-search'></span></button>
	</div>

	2. setup the widget:

	<?=\app\components\CrmFindContactWidget::widget([
		'selector'=>'#my_contact',
		'selector_label'=>'#my_contact_label',
		'selector_activator'=>'#my_contact_buscar',
		'selector_finder'=>'#entire_finder_group',
	]);?>
 
	how it works:

	1. Clicking the 'selector_activator' will show the finder and will
		hide the entire finder group.
	2. User perform a search using provided controls
	3. User click a contact, the selectors receive result.
	4. after selection, try:
		console.log($('#my_contact').data('contact'));

 */
class CrmFindContactWidget extends Widget
{                                    
	public $mode='finder'; 		// finder or browser
	public $crm_field = null; 	// which attribute in meta will be show
	public $placeholder='Busque tipeando algo y de click en lupa';
	public $selector; // jQuery selector of the element receiving the contact
	public $selector_label; // jQuery selector of the element receiving the label
	public $selector_activator; // jQuery selector of launcher
	public $selector_finder; // jQuery selector of the entire finder
	public $form; // a user provided html layout containing fields, or null
	public $readonly = true; // set to false to enable add and update oprs.
	public $default_form_layout = "
		<hr/>
		<div class='panel panel-default'>
			<div class='panel-heading'>Datos del Contacto</div>
			<div class='panel-body'>
				<form class='default-form'>
					%form%
					<div class='buttons'>
					<button type='button' 
						class='btn btn-primary save'>Guarda</button>
					<button type='button' 
						class='btn btn-default cancel'>Cierra</button>
					</div>
				</form>
			</div>
		</div>
	";
		
	public $find_action_url = ['/crm/ajaxfind'];
	public $get_action_url = ['/crm/ajaxget'];
	public $save_action_url = ['/crm/ajaxsave'];

	// THE SELECTED COLUMNS TO BE DISPLAYED ARE CONFIGURED IN CRM-CONFIG FILE
	// VIA SETTING THE 'list' ATTRIBUTE TO A VALUE GREATHER THAN ZERO.

	public function getapi(){
		return \Yii::$app->crm;	
	}

	public function init() {
		parent::init();
		$this->find_action_url = Url::toRoute($this->find_action_url);
		$this->get_action_url = Url::toRoute($this->get_action_url);
		$this->save_action_url = Url::toRoute($this->save_action_url);
		if($this->readonly)
			$this->save_action_url = null;
	}

	public function run() {
		$readonly = $this->readonly ? 'true' : 'false';
		$c = 'crm-find-contact-widget';
		$add_form = $this->getAddForm();
		$html = "
			<div class='{$c}'>
				<div class='{$c}-finder' style='display: none;'>
					<div class='input-group'>
						<input type='text' placeholder='{$this->placeholder}' 
							class='form-control {$c}-input' maxlength=30 />
						<span class='input-group-btn'>
							<button title='buscar' class='btn btn-primary {$c}-button'>
								<span class='glyphicon glyphicon-search'></span>
							</button>
							<button title='crear' class='btn btn-success {$c}-add'>
								<span class='glyphicon glyphicon-plus'></span>
							</button>
							<button title='volver' class='btn btn-default {$c}-close'>
								<span class='glyphicon glyphicon-share-alt'></span>
							</button>
						</span>
					</div>
					<div class='{$c}-form'>$add_form</div>
					<div class='{$c}-list'></div>
				</div>
			</div>
		";

		$this->view->registerJs("
			console.log('initialize $c');
			var _crm_handle_choose_contact = function(d){
				d.list.find('[type=radio]').click(function(e){
					var current = $('{$this->selector}');
					var current_label = $('{$this->selector_label}');
					var _c = JSON.parse(window.atob($(this).attr('data')));	
					console.log(_c);
					var full='',spc='';
					$.each(_c,function(attr,value){
						if('id' != attr){
							full += spc+value;
							spc=' ';
						}
					});
					current_label.val(full);
					current.val(_c.id);
					current.data('contact',_c);  // <-- FOR PUBLIC USAGE
					current.trigger('change');
					$('{$this->selector_finder}').show();
					d.finder.hide();
				});
				d.list.find('tbody tr').each(function(i1,tr){
					var data = null;
					var col1 = null;
					$(tr).find('td').each(function(i2,td){
						if(i2==0) data = $(td).find('input[name=crm-contact]').attr('data');
						if(i2==1) {
							col1 = $(td).html().trim();
							if('' == col1) col1 = '(sin nombre)';
							col1 = '<a href=\"#\" title=\"click edit\" '
								+'data=\"'+data+'\" class=\"edit-contact\">'+col1+'</a>';
							$(td).html(col1);
						}
					});
				});
				d.list.find('.edit-contact').click(function(e){
					e.preventDefault();
					var a = $(this);
					var data = JSON.parse(window.atob(a.attr('data')));
					console.log('edit contact:',a.html(),data);
					_launch_form(data);
				});
			};
			$('.{$c}').each(function(){
				var widget = $(this);
				console.log('initialize $c, widget detected.');
				var find_action_url = '{$this->find_action_url}';
				var get_action_url = '{$this->get_action_url}';
				var save_action_url = '{$this->save_action_url}';
				var find = widget.find('.{$c}-button');
				var close = widget.find('.{$c}-close');
				var add = widget.find('.{$c}-add');
				// form begin:
				var form_save = widget.find('.default-form .save');
				var form_cancel = widget.find('.default-form .cancel');
				// form end.
				close.click(function(e){
					$('{$this->selector_finder}').show();
					$('.{$c}-finder').hide();		
				});
				find.click(function(e){
					var crmdata = widget.data('crmdata');	
					var input = widget.find('.{$c}-input');
					var finder = widget.find('.{$c}-finder');
					var list = widget.find('.{$c}-list');
					var keywords = input.val().trim();
					//if(!keywords.length) return;
					$('.{$c}-list').show();
					$('.{$c}-form').hide();
					var _clear = function(){
					}
					//--find click ajax begins
					var view_mode = 'choose';
					if('browser'=='{$this->mode}')
						view_mode = 'browse';

					var _d = { keywords: keywords , view: view_mode, 
						button_class: 'none' , crm_field : '{$this->crm_field}'};
					console.log('ajax',_d);
					$.ajax({ cache: false, type: 'post', async: true, data: _d,
						url: find_action_url, success: function(resp){ 
							console.log('success',resp); 
							_clear();
							list.html('');
							if(true == resp.status){
								list.html(resp.html);
								_crm_handle_choose_contact(
									{ widget: widget , finder: finder,
										list: list , data : crmdata });
								try{
									var evt = 'crm:find:list:updated';
									console.log('crm:firing:event',evt);
									$( document ).trigger(evt, [list,keywords,resp]);
								}catch(e){ console.log(
									'crm:list:excepcion when calling event',e); }
							}else{
								list.html('sin resultados');
							}
						}, error: function(e){ 
							console.log(e.responseText); _clear(); 
							list.html('(sin resultados. ocurrio un error)');
						}
					});
					//--find click ajax ends
				}); // find click
				add.click(function(e){
					console.log('crm add clicked');
					_launch_form(null);
				}); // add click
				form_save.click(function(e){
					var form = $('.{$c}-form');
					var _data = [];
					form.find('.crmfield').each(function(){
						var value = $(this).val().trim();
						var attr = $(this).attr('name');
						_data.push({ name: attr, value: value });
					});
					console.log('sending',_data);
					$.ajax({ cache: false, type: 'post', async: true, 
						data: _data,
						url: save_action_url, success: function(resp){ 
							console.log('success save',resp);
							form.hide();
						}, error: function(e){ 
							console.log(e.responseText); _clear(); 
						}
					});
				});
				form_cancel.click(function(e){
					$('.{$c}-form').hide();
				});
				_render_form = function(form, contact){
					console.log('initialize form with',contact);	
					$.each(contact,function(attr,value){
						console.log(attr,value);
						form.find('[name='+attr+']').val(value);
					});
				};
				_launch_form = function(current_contact){
					console.log('launch form:',current_contact);
					//$('.{$c}-list').hide();
					var form = $('.{$c}-form');
					form.find('.crmfield').val('');//cleared
					if(null != current_contact){
						$.ajax({ cache: false, type: 'post', async: true, 
							data: { select : true , 
								contact_id : current_contact.id },
							url: get_action_url, success: function(resp){ 
								_render_form(form, resp);
							}, error: function(e){ 
								console.log(e.responseText); _clear(); 
							}
						});
					}
					form.show();
				};
			});
			console.log('initialize activator: {$this->selector_activator}');
			$('{$this->selector_activator}').click(function(){
				console.log('activator clicked');
				$('{$this->selector_finder}').hide();
				$('.{$c}-form').hide();
				$('.{$c}-finder').show();
				$('.{$c}-input').focus();
				console.log('activator clicked ends.');
			});
			if(true == {$readonly})$('.{$c}-form .save').remove();
			if(true == {$readonly})$('.{$c}-add').remove();

			if('browser'=='{$this->mode}'){
				$('.{$c}-close').remove();
				$('{$this->selector_activator}').trigger('click');
			}

			console.log('initialize $c is done');
		",\yii\web\View::POS_READY,'crm-find-contact-widget-scripts');

		return $html;
	}

	private function getAddForm(){
		if(null != $this->form) return $this->form;	
		$form  = "<input type='hidden' name='id' />";
		$form .= $this->readonly ?
			$this->api->formViewConstructor() : 
				$this->api->formEditConstructor();
		$form = str_replace("%form%",$form,
			$this->default_form_layout);
		return $form;
	} // getAddForm
}

