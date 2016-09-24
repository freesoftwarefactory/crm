<?php
namespace app\commands;

use yii\console\Controller;
use freesoftwarefactory\crm\Api;

class CrmController extends Controller {
protected $description = '
Micro CRM, 
permite consultar contactos y manejar metadata.

manejo de contactos:

	./yii crm	(this help)
	./yii crm/meta
	./yii crm/list fieldname,fieldname
	./yii crm/find keywords fieldname,fieldname
	./yii crm/create username first_name="christian a.",last_name="salazar h."
	./yii crm/update id first_name="christian a.",last_name="salazar h."
	./yii crm/view id
	./yii crm/delete id
	
manejo de relaciones:
	
	# relaciona al contacto_id con otro objetivo (target), indicando tipo de relacion
	# relname: `event_manager` es un ejemplo, podria ser: `tecnico_de_luces`
	# target: cualquier identificador de un objeto del sistema.
	./yii crm/rel <contact_id> <target> <relname>

	# lista todas las relaciones existentes
	./yii crm/listrel
	
	# examina una relacion
	./yii crm/viewrel <relid>
	
	# edita una relacion
	./yii crm/updaterel <rel_id> <target> <relname>
	
	# edita la metadata de una relacion
	./yii crm/updaterelmeta <rel_id> <metavalue>
	
	# borra una relacion
	./yii crm/deleterel <rel_id>
	
	# busqueda de relaciones, desde el contacto:
	# "dime que las cosas relacionadas con juanperez para `tecnico_de_luces`
	./yii crm/source <contactid> <relname>

	# busqueda de relaciones, desde el objetivo:
	# "quienes son los event_manager del recinto xyz (targetid)"
	./yii crm/target <targetid> <relname>

';

	private function info($text){
		printf("%s\n",$text);
	}

	private function getApi(){
		return \Yii::$app->crm;	
	}

	/**
	 	this help
	 */
    public function actionIndex() {
		$this->info($this->description);
    }

	public function actionTest(){
		$api = $this->getApi();
		$api->test();
	}

	/**
		show the available configuration 
	 */
	public function actionMeta(){
		$api = $this->getApi();
		$this->info("META=".print_r($api->getMeta(),true));
	}

	/**
	 	list all the contacts. [fieldname,fieldname, ... ]
	 */
	public function actionList($P1=''){
		$api = $this->getApi();
		$this->info("Lista de contactos:\n"
			."=====================================================");
		
		$fieldlist = $P1;
		if(('*' == $fieldlist) || ('na'==$fieldlist) || ('' == $fieldlist)){
			$meta = $api->getMeta();
			$fieldlist='';
			foreach($meta as $field_name=>$metadata)
				$fieldlist .= $field_name.',';
			$fieldlist = rtrim($fieldlist,',');
		}
		$this->info($fieldlist);
		$fields = explode(",",$fieldlist);

		if($list = $api->getContactList())
			foreach($list as $row){
				$text = sprintf("%-20s\t%-20s\t",
					$row->id,
					$row->user_name
				);
				if($fields)
					foreach($fields as $fieldname)
						$text .= sprintf("[%s]",
							$api->getMetaValue($row->id, $fieldname));

				$this->info($text);
			}
	}

	/**
	 	find contacts by keywords <keywords> [fieldname,fieldname,...]
	 */
	public function actionFind($P1,$P2=''){
		$api = $this->getApi();
		$keywords = $P1;
		$fieldlist = $P2;
		if(('*' == $fieldlist) || ('na'==$fieldlist) || ('' == $fieldlist)){
			$meta = $api->getMeta();
			$fieldlist='';
			foreach($meta as $field_name=>$metadata)
				$fieldlist .= $field_name.',';
			$fieldlist = rtrim($fieldlist,',');
		}
		$this->info($fieldlist);
		$fields = explode(",",$fieldlist);
		$list = $api->getFullContactList($keywords,1);
		if($list)
			foreach($list as $row){
				$text = sprintf("%-20s\t",$row->id);
				if($fields)
					foreach($fields as $fieldname)
						$text .= sprintf("[%s]",
							$api->getMetaValue($row->id, $fieldname));

				$this->info($text);
			}
	}

	/**
		create a new contact <username> [field=value,field=value...]
	*/
	public function actionCreate($P1='',$P2=''){
		$api = $this->getApi();
		$username = $P1;
		$fields = $P2;
		if(!$username) return;
		if((!$fields) || ('na'==$fields)) return;
		$pairs = explode(',',$fields);
		$meta = $api->getMeta();
		$attributes = array();
		foreach($pairs as $field){
			if(!$k = explode('=',$field))
				die("invalid field: $field\n");
			$fieldname = $k[0];
			$fieldvalue = ltrim(rtrim(trim($k[1]),','),',');
			if(!$metaitem = $api->getMetaItem($fieldname)){
				die("Invalid fieldname: {$fieldname} \n");
			}
			if(!$api->validate($metaitem, $fieldvalue))
				die($this->info(sprintf("Invalid Value for: $fieldname=$fieldvalue, %s\n",
					$api->last_error)));
			$this->info(sprintf("%s=%s",$fieldname,$fieldvalue));
			$attributes[$fieldname]=$fieldvalue;
		}
		$id = $api->createContact($username, $attributes);
		$this->info(sprintf("estatus creacion: %s",$id ? 
			"OK:".$id : "ERROR,".$api->last_error));
	}

	/**
		view a contact details and field values, <contact_id>
	 */
	public function actionView($P1){
		$api = $this->getApi();
		$contact_id = $P1;
		if(!$attributes = $api->findContact($contact_id)){
			$this->info("Contacto no existe.");
			return;
		}
		foreach($attributes as $attr=>$val){
			if('creation_date'==$attr) $val = date("Y-m-d H:i:s",$val);
			if('mod_date'==$attr) $val = date("Y-m-d H:i:s",$val);
			$this->info(sprintf("%20s => %s",$attr,$val));
		}

	}

	/**
	 	delete a contact, <contact_id>
	 */
	public function actionDelete($P1){
		$api = $this->getApi();
		$contact_id = $P1;
		if(!$attributes = $api->deleteContact($contact_id)){
			$this->info("Contacto no existe.");
			return;
		}else
		$this->info("Contacto Eliminado");
	}

	/**
	 	update a contact. <contact_id> <field=value,field=value, ... >
	 */
	public function actionUpdate($P1,$P2){
		$api = $this->getApi();
		$contact_id = $P1;
		$fields = $P2;
		if(!$contact_id) return;
		if((!$fields) || ('na'==$fields)) return;
		// fields es una tira de campos separados por coma:
		// first_name="christian a.",last_name=salazar
		// por tanto, separalos primero por ',' 
		//	y luego cada token separalo por '='
		$pairs = explode(',',$fields);
		$meta = $api->getMeta();
		$attributes = array();
		foreach($pairs as $field){
			if(!$k = explode('=',$field))
				die("invalid field: $field\n");
			$fieldname = $k[0];
			$fieldvalue = ltrim(rtrim(trim($k[1]),','),',');
			if(!$metaitem = $api->getMetaItem($fieldname)){
				die("Invalid fieldname: {$fieldname} \n");
			}
			if(!$api->validate($metaitem, $fieldvalue))
				die($this->info(sprintf("Invalid Value for: $fieldname=$fieldvalue, %s\n",
					$api->last_error)));
			$attributes[$fieldname]=$fieldvalue;
		}
		$api->updateContact($contact_id, $attributes);
		$this->info("OK");
	}

	/**
		create a relationship. <contact_id> <target> <relname> 
	 */
	public function actionRel($P1,$P2,$P3) { 
		$api = $this->getApi();
		$contact_id = $P1;
		$target = $P2;
		$relname = $P3;
		if($relid=$api->relExists($contact_id, $target, $relname)){
			$this->info("La relacion ya existe: {$relid}");
		}else{
			$rel_id=$api->createRel($contact_id, $target, $relname);
			$this->info("Creada: {$rel_id}");
		}
	}
	
	/**
		list relationships	 
	 */
	public function actionListrel() { 
		$api = $this->getApi();
		if($api->listRel())
		foreach($api->listRel() as $r){
			$this->info(sprintf(
				"#%04s S:%-20s T:%-20s %-30s (%s)",
				$r->id,$r->contact_id,$r->target_id,$r->relname,$r->meta));
		}
	}
	
	/**
	 	view a relationship <rel_id> see also listrel
	 */
	public function actionViewrel($P1) { 
		$api = $this->getApi();
		$rel_id = $P1;
		$r = $api->getRel($rel_id);
		if($r)
		foreach(array("id","creation_date","mod_date",
			"contact_id","target_id","relname","meta") as $attr){
			$val = $r->$attr;
			if(('creation_date'==$attr) || ('mod_date'==$attr))
				$val = date("Y-m-d H:i:s",$r->$attr);
			$this->info(sprintf("%20s => %s",$attr,$val));
		}
	}

	/**
	 	update a relationship <rel_id> <target> <relname>
	 */
	public function actionUpdaterel($P1,$P2,$P3) { 
		$api = $this->getApi();
		// <rel_id> <target> <relname>
		$rel_id = $P1;
		$target = $P2;
		$relname = $P3;
		$r=$api->updateRel($rel_id, $target, $relname);
		$this->info("updated r=[$r]");
	}

	/**
	 	update the relationship metadata <rel_id> <value>
	 */
	public function actionUpdaterelmeta($P1,$P2) { 
		$api = $this->getApi();
		// <rel_id> <value>
		$rel_id = $P1;
		$value = $P2;
		$r=$api->updateRelMeta($rel_id, $value);
		$this->info("updated r=[$r]");
	}

	/**
		delete a relationship <relid> 
	 */
	public function actionDeleterel($P1) { 
		$api = $this->getApi();
		$rel_id = $P1;
		$r=$api->deleteRel($rel_id);
		$this->info("updated flag: [$r]");
	}

	/**
	 	find relationships given A in [A---relname--->B], <contact_id> <relname>
	 */
	public function actionSource($P1,$P2) { 
		$api = $this->getApi();
		$contact_id = $P1;
		$relname    = $P2;
		$list = $api->listRelUsingSource($contact_id, $relname);
		if($list) foreach($list as $r){
			$this->info(sprintf(
				"#%04s %-20s %-20s %-50s",
				$r->id,$r->contact_id,$r->target_id,$r->relname));
		}
	}

	/**
	 	find relationships given B in [A---relname--->B], <target> <relname>
	 */
	public function actionTarget($P1,$P2) { 
		$api = $this->getApi();
		$target_id = $P1;
		$relname    = $P2;
		$list = $api->listRelUsingTarget($target_id, $relname);
		if($list) foreach($list as $r){
			$this->info(sprintf(
				"#%04s %-20s %-20s %-50s",
				$r->id,$r->contact_id,$r->target_id,$r->relname));
		}
	}
}
