<?php
namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\helpers\Url;
use yii\web\View;
use yii\web\Session;
use yii\base\Exception;
use freesoftwarefactory\crm\Api;

class CrmController extends Controller
{

	public function init(){
		// $this->getView()->registerJsFile('js/fileinput.js');
		// $this->getView()->registerJsFile('js/fileinput.js');
		Yii::setAlias('@crmviews',rtrim(dirname(__FILE__),"/")."/../views");
		//$this->enableCsrfValidation = false;
	}

	private function getCurrentUsername(){
		return "TODO";
	}

	private function getApi(){
		return \Yii::$app->crm;
	}

	public function actionTest(){
		$this->layout = '@crmviews/base';
		//$this->getView()->registerJs('alert(888);',View::POS_READY);
		return $this->render('@crmviews/test');
	}
	
	public function actionAjaxselect(){
		if(!Yii::$app->request->isAjax) die('invalid ajax request');
		$selected_id = filter_input(INPUT_POST,"id",FILTER_SANITIZE_STRING);
		$s = new Session;
		$s->open();
		$s['selected_id'] = $selected_id;
		\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return array('result'=>true,'selected_id'=>$selected_id);
	}
		
	private function getSelectedContactIdOrDie(){
		$s = new Session;
		$s->open();
		return isset($s['selected_id']) ? $s['selected_id'] : '';
	}
	
	public function actionCreate(){
		$username = $this->getCurrentUsername();
		$this->layout = $this->getApi()->getLayout('create');
		return $this->render('@crmviews/create',['username'=>$username]);
	}

	public function actionAjaxcreate(){
		if(!Yii::$app->request->isAjax) die('invalid ajax request');
		$username = $this->getCurrentUsername();
		$api = $this->getApi();
		$meta = $api->getMeta();
		$attributes = array();
		$has_error = false;
		$errors = array();
		foreach($meta as $field_name=>$metadata){
			$value = filter_input(INPUT_POST,$field_name,FILTER_SANITIZE_STRING);
			if($api->validate($metadata, $value)){
				$attributes[$field_name] = $value;
			}else{
				$has_error = true;
				$errors[] = array("field"=>$field_name,"error"=>$api->last_error);
			}
		}
		$result = array();
		if($has_error){
			$result['contact_id']=null;
			$result['result'] = false;
			$result['errors'] = $errors;
		}else{
			if($contact_id = $api->createContact($username, $attributes)){
				$result['contact_id']=$contact_id;
				$result['result'] = true;
				$result['errors'] = array();
			}else{
				$result['contact_id']=null;
				$result['result'] = false;
				$result['errors'] = array(array(
					'field'=>'_general','error'=>
						'No se pudo crear el contacto.'.$api->last_error));
			}
		}
		
		\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return $result;
	}

	public function actionView(){
		$contact_id = $this->getSelectedContactIdOrDie();
		$this->layout = $this->getApi()->getLayout('find');
		$username = $this->getCurrentUsername();
		return $this->render('@crmviews/view',
			['username'=>$username,'contact_id'=>$contact_id]);
	}

	public function actionEdit(){
		$contact_id = $this->getSelectedContactIdOrDie();
		$this->layout = $this->getApi()->getLayout('edit');
		$username = $this->getCurrentUsername();
		return $this->render('@crmviews/edit',
			['username'=>$username,'contact_id'=>$contact_id]);
	}

	public function actionAjaxsave(){
		\Yii::$app->response->format = 'json';
		$contact_id = $this->getSelectedContactIdOrDie();
		$username = $this->getCurrentUsername();
		$api = $this->getApi();
		$meta = $api->getMeta();
		$attributes = array();
		$has_error = false;
		$errors = array();
		foreach($meta as $field_name=>$metadata){
			$value = filter_input(INPUT_POST,$field_name,FILTER_SANITIZE_STRING);
			if($api->validate($metadata, $value)){
				$attributes[$field_name] = $value;
			}else{
				$has_error = true;
				$errors[] = array("field"=>$field_name,"error"=>$api->last_error);
			}
		}
		$result = array();
		if($has_error){
			$result['contact_id']=$contact_id;
			$result['result'] = false;
			$result['errors'] = $errors;
		}else{
			if($api->updateContact($contact_id, $attributes)){
				$result['contact_id']=$contact_id;
				$result['result'] = true;
				$result['errors'] = array();
			}else{
				$result['contact_id']=$contact_id;
				$result['result'] = false;
				$result['errors'] = array(array(
					'field'=>'_general','error'=>
						'No se pudo guardar el contacto.'.$api->last_error));
			}
		}
		return $result;
	}

	public function actionFind() {
		$this->layout = $this->getApi()->getLayout('find');
		return $this->render('@crmviews/find');
	}

	public function actionAjaxfind(){
		if(!Yii::$app->request->isAjax) die('invalid ajax request');
		$keywords = filter_input(INPUT_POST,"keywords",FILTER_SANITIZE_STRING);
		$view = filter_input(INPUT_POST,"view",FILTER_SANITIZE_STRING);
		$button_class = filter_input(INPUT_POST,"button_class",FILTER_SANITIZE_STRING);
		if(!$view) $view = 'edit'; // "edit", "choose"
		if(!$button_class) $button_class = 'btn btn-primary';

		$api = $this->getApi();
		$meta = $api->getMeta();
		$list = $api->getFullContactList($keywords,1);
		//

		// LIST ALL COLUMNS DEFINED IN METADATA MARKED WITH 'list'=>1..N
		$columns = array();
		foreach($meta as $field_name=>$metadata)
			if(isset($metadata['list']) ? $metadata['list'] : false)
				$columns[$field_name]=$metadata;
		
		if('edit'==$view){
			$head = "<tr><th>/</th>";
			foreach($columns as $field_name=>$metadata){
				$label = ucwords(strtolower($metadata['label']));
				$head .= "<th>$label</th>";
			}
			$head .= "</tr>";
			$html = "<table class='table'><thead>$head</thead><tbody>";
			foreach($list as $c){
				$contact_id = $c->id;
				$tr = "<tr>";
				$tr .= "<td><button class='{$button_class} view-contact' 
					data='{$contact_id}'>
					<span class='glyphicon glyphicon-pencil'></span>
						</button></td>";
				foreach($columns as $field_name=>$metadata)
					$tr .= "<td>{$c->$field_name}</td>";
				$tr .= "</tr>";
				$html .= $tr;
			}
			$html .= "</tbody></table>";
		}

		if('choose' == $view){
			$head = "<tr><th>/</th>";
			foreach($columns as $field_name=>$metadata){
				$label = ucwords(strtolower($metadata['label']));
				$head .= "<th>$label</th>";
			}
			$head .= "</tr>";
			$html = "<table class='table'><thead>$head</thead><tbody>";
			foreach($list as $c){
				$contact_id = $c->id;

				$_c = [];
				$_c['id'] = $contact_id;
				foreach($columns as $field_name=>$metadata)
					$_c[$field_name] = $c->$field_name;
				$_c = base64_encode(json_encode($_c));

				$tr = "<tr>";
				$tr .= "<td>
					<input type='radio' name='crm-contact' 
						data='$_c' /></td>";
				foreach($columns as $field_name=>$metadata)
					$tr .= "<td>{$c->$field_name}</td>";
				$tr .= "</tr>";
				$html .= $tr;
			}
			$html .= "</tbody></table>";
		}

		//
		$result['status'] = true;
		$result['keywords'] = $keywords;
		$result['html'] = $html;
		\Yii::$app->response->format = 'json';
		//\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return $result;
	}

	public function actionAjaxget(){
		$contact = null;
		if(!Yii::$app->request->isAjax) die('invalid ajax request');
		$contact_id = filter_input(INPUT_POST,"contact_id",FILTER_SANITIZE_STRING);
		$select = filter_input(INPUT_POST,"select",FILTER_SANITIZE_STRING);
		if('true'==$select) {
			$s = new Session;
			$s->open();
			$s['selected_id'] = $contact_id; // now can call save..
		}
		$api = $this->getApi();
		$contact = $api->findContact($contact_id);
		\Yii::$app->response->format = 'json';
		return $contact;
	}
}
		
