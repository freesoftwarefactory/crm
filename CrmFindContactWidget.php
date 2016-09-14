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
	public $placeholder='Busque tipeando algo y de click en lupa';
	public $selector; // jQuery selector of the element receiving the contact
	public $selector_label; // jQuery selector of the element receiving the label
	public $selector_activator; // jQuery selector of launcher
	public $selector_finder; // jQuery selector of the entire finder
		
	public $find_action_url = ['/crm/ajaxfind'];

	// THE SELECTED COLUMNS TO BE DISPLAYED ARE CONFIGURED IN CRM-CONFIG FILE
	// VIA SETTING THE 'list' ATTRIBUTE TO A VALUE GREATHER THAN ZERO.

	public function init() {
		parent::init();
		$this->find_action_url = Url::toRoute($this->find_action_url);
	}

	public function run() {
		$c = 'crm-find-contact-widget';
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
							<button title='crear' class='btn btn-success {$c}-close'>
								<span class='glyphicon glyphicon-plus'></span>
							</button>
							<button title='volver' class='btn btn-default {$c}-close'>
								<span class='glyphicon glyphicon-share-alt'></span>
							</button>
						</span>
					</div>
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
					$('{$this->selector_finder}').show();
					d.finder.hide();
				});
			};
			$('.{$c}').each(function(){
				var widget = $(this);
				console.log('initialize $c, widget detected.');
				var find_action_url = '{$this->find_action_url}';
				var find = widget.find('.{$c}-button');
				var close = widget.find('.{$c}-close');
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
					if(!keywords.length) return;
					var _clear = function(){
					}
					//--find click ajax begins
					var _d = { keywords: keywords , view: 'choose', 
						button_class: 'none' };
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
			});
			console.log('initialize activator: {$this->selector_activator}');
			$('{$this->selector_activator}').click(function(){
				console.log('activator clicked');
				$('{$this->selector_finder}').hide();
				$('.{$c}-finder').show();
				$('.{$c}-input').focus();
			});
			console.log('initialize $c is done');
		",\yii\web\View::POS_READY,'crm-find-contact-widget-scripts');

		return $html;
	}
}

