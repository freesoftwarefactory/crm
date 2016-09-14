<?php
namespace freesoftwarefactory\crm;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\Url;

class Api extends Component {
	// setup
	public $layouts;
	public $fields;
	private $dbh;
	//

	public $last_error = "";

	private function getDb(){
		if(null == $this->dbh)
			$this->dbh = new DbHelper(\Yii::$app->db);
		return $this->dbh;
	}

	public function test(){
		// DbHelper level 
		$this->getDb()->test('crm_contact',['id','user_name']);
		// This class
	}

	private function db_select($sql,$params=[]){
		return $this->getDb()->select($sql,$params);	
	}
	private function db_update($table,$values,$where,$params){
		return $this->getDb()->update($table,$values,$where,$params);	
	}
	private function db_insert($table,$values){
		return $this->getDb()->insert($table,$values);	
	}
	private function db_delete($table,$where,$params){
		return $this->getDb()->delete($table,$where,$params);	
	}

	public function getMeta(){
		return $this->fields;
	}

	public function getLayout($actionName){
		$layouts = $this->layouts;
		if(isset($layouts[$actionName])){
			if(""!=trim($layouts[$actionName]))
				return $layouts[$actionName];
		}
		return $layouts['default'];
	}

	public function getMetaItem($field_name){
		$m = $this->getMeta();
		if(!isset($m[$field_name]))
			return null;
		return $m[$field_name];
	}

	public function getListFields(){
		$fields = [];
		foreach($this->getMeta() as $field_name=>$data)
			if(isset($data['list']))
				$fields[] = $field_name;
		return $fields;
	}

	public function formEditConstructor($instance=null,$meta=null){
		$meta = null==$meta ? $this->getMeta() : $meta;
		$html = "";
		foreach($this->getMeta() as $field_name=>$data){
			$default_value = isset($data['default']) ? $data['default'] : '';
			$value = $default_value;
			if(null != $instance){
				if(is_array($instance)){
					$value = isset($instance[$field_name]) ? 
						$instance[$field_name] : $default_value;
				}elseif(is_object($instance)){
					$value = isset($instance->$field_name) ? 
						$instance->$field_name : $default_value;
				}
			}
			$value = htmlentities($value);
			$fieldset = "<div class='form-group'>";
			$type = $data['type'];
			$required = isset($data['required']) ? $data['required'] : false;
			$required = $required ? 'required' : '';
			$req = $required ? " <span class='req'>*</span>" : '';
			$_label ="<p><label class='label label-success'>
				{$data['label']}{$req}</label></p>";
			if('select' == $type){
			}elseif('textarea' == $type){
				$fieldset .= "
				{$_label}
				<textarea type='$type' name='$field_name' $required 
					placeholder='{$data['placeholder']}' 
						rows='{$data['rows']}' 
						class='crmfield form-control'
						maxlength='{$data['size']}' >$value</textarea>
				";
			}else{
				$fieldset .= "
				{$_label}
				<input type='$type' name='$field_name' 
					class='crmfield form-control' 
					placeholder='{$data['placeholder']}' $required
						maxlength='{$data['size']}' value='$value' />";
			}
			$fieldset .= "<div class='label label-danger error $field_name-error'></div>";
			$fieldset .= "</div>";
			$html .= $fieldset;
		}
		return $html;
	}

	public function formViewConstructor($instance=null,$meta=null){
		$meta = null==$meta ? $this->getMeta() : $meta;
		$html = "";
		foreach($this->getMeta() as $field_name=>$data){
			$default_value = isset($data['default']) ? $data['default'] : '';
			$value = $default_value;
			if(null != $instance){
				if(is_array($instance)){
					$value = isset($instance[$field_name]) ? 
						$instance[$field_name] : $default_value;
				}elseif(is_object($instance)){
					$value = isset($instance->$field_name) ? 
						$instance->$field_name : $default_value;
				}
			}
			$value = htmlentities($value);
			$fieldset = "
				<div class='form-group'>
					<label class='label label-default'>{$data['label']}</label>
					<p><div class='form-control crmvalue'>$value</div></p>
				</div>
			";
			$html .= $fieldset;
		}
		return $html;
	}

	public function validate($meta_item, $field_value){
		$this->last_error = "";
		$field_value = trim($field_value);
		$size = 1 * $meta_item['size'];			
		$min = 1 * $meta_item['min'];
		$required = isset($meta_item['required']) ? $meta_item['required'] : false;
		$len = strlen($field_value);
		if(!$len && $required){
			$this->last_error = "Este campo es requerido";
			return false;
		}

		if(($len >= $min) && ($len <= $size)){
			// TODO: regexp
			return true;
		}else{
			$this->last_error = 
			"Longitud invalida. Se esperan entre {$min} y {$size} caracteres.";
			return false;
		}
	}

	public function getMetaValue($contact_id, $field_name){
		if($r = $this->db_select(
			 'select meta_value from crm_contact_meta  '
			.' where contact_id = :id and meta_name = :fn', 
			[':id' => $contact_id, ':fn' => $field_name]))
				return $r[0]->meta_value;
		return "";		
	}

	public function setMetaValue($contact_id, $field_name, $field_value){
		$r = $this->db_select(
		'select id,meta_value from crm_contact_meta 
			where contact_id = :id and meta_name = :meta',
				[':id'=>$contact_id, ':meta'=>$field_name]);
		if($r){
			if($r[0]->meta_value != $field_value){
				$this->db_update('crm_contact_meta',[
					'meta_value'=>$field_value,
					'mod_date'=>time(),
				],'(contact_id=:a) and (meta_name=:b)',
					[':a'=>$contact_id,':b'=>$field_name]);
			}
		}else{
			$mID = 'CTME-'.hash('crc32',serialize(
				array(microtime(true),$field_name.$field_value)));
			$_mattr = array();
			$_mattr['id'] = $mID;
			$_mattr['creation_date'] = time();
			$_mattr['mod_date'] = time();
			$_mattr['contact_id'] = $contact_id;
			$_mattr['meta_name'] = $field_name;
			$_mattr['meta_value'] = $field_value;
			$this->db_insert('crm_contact_meta',$_mattr);
		}
		$this->db_update('crm_contact',['mod_date'=>time()],
			'id = :id',[':id'=>$contact_id]);
	}

	public function getContactList(){
		return $this->db_select('select * from crm_contact;');
	}
	
	public function getFullContactList($keywords,$list=1){
		$w = "where "; $w_sep=""; $w_params=array();
		if($kw = explode(" ",strtolower($keywords)))
			$n=0;
			foreach($kw as $k){
				$p = ':p'.$n;$n++;
				$w .= $w_sep." (lower(meta_value) like $p)";
				$w_sep = " OR ";
				$w_params[$p] = "%$k%";
			}
		$sql="select contact_id from crm_contact_meta $w group by contact_id";

		$columns = array();
		foreach($this->getMeta() as $fn=>$md)
			if(isset($md['list']))
				if($list==$md['list'])
					$columns[] = $fn;
		$results = array();
		if($rows=$this->db_select($sql,$w_params)){
			foreach($rows as $row){
				$object = new \stdclass;
				$object->id = $row->contact_id;
				foreach($columns as $col)
					$object->$col = $this->getMetaValue($row->contact_id, $col);
				$results[] = $object;
			}
		}
		return $results;
	}

	public function createContact($username, $attributes){
		$this->last_error = '';
		$ID = 'CTCO-'.hash('crc32',serialize(array(microtime(true),$attributes)));
		$_attr = array();
		$_attr['id'] = $ID;
		$_attr['creation_date'] = time();
		$_attr['mod_date'] = time();
		$_attr['user_name'] = $username;
		if($this->db_insert('crm_contact',$_attr)){
			foreach($attributes as $fieldname=>$fieldvalue){
				$mID = 'CTME-'.hash('crc32',serialize(
					array(microtime(true),$fieldname.$fieldvalue)));
				$_mattr = array();
				$_mattr['id'] = $mID;
				$_mattr['creation_date'] = time();
				$_mattr['mod_date'] = time();
				$_mattr['contact_id'] = $ID;
				$_mattr['meta_name'] = $fieldname;
				$_mattr['meta_value'] = $fieldvalue;
				$this->db_insert('crm_contact_meta',$_mattr);
			}
			return $ID;
		}else{
			$this->last_error = 'No se pudo crear un contacto';
			return null;
		}
	}

	public function findContact($contact_id){
		if($r = $this->db_select('select * from crm_contact  where id = :id', 
			[':id' => $contact_id])){
			$values = $r[0];
			$meta = $this->getMeta();
			foreach($meta as $field_name=>$metadata)
				$values->$field_name = $this->getMetaValue(
					$contact_id, $field_name);
			return $values;
		}else
		return null;
	}

	public function deleteContact($contact_id){
		if($r = $this->db_delete('crm_contact',"id = :id",
			[":id"=>$contact_id])){
				$this->db_delete(
					'crm_contact_meta','contact_id = :id',
						[':id' => $contact_id]);
			return true;
		}else
		return false;
	}

	public function updateContact($contact_id, $attributes){
		$this->last_error='';
		if(!$r = $this->db_select('select id from crm_contact  where id = :id', 
			[':id' => $contact_id])){
				$this->last_error='Contacto inexistente';
				return false;
		}
		foreach($attributes as $attr=>$value)
			$this->setMetaValue($contact_id, $attr, $value);
		return true;
	}


	public function relExists($a,$b,$relname){
		$row = $this->db_select('select id from crm_contact_rel 
			where contact_id=:a and target_id=:b and relname=:c',
				[':a'=>$a,':b'=>$b,':c'=>$relname]);
		if(!$row) return null;
		if(0==count($row)) return null;
		return $row[0]->id;
	}
	
	public function createRel($a,$b,$relname){
		$ok=$this->db_insert('crm_contact_rel',[
			"creation_date"=>time(),"mod_date"=>time(), "contact_id"=>$a,
				"target_id"=>$b,"relname"=>$relname]);
		return $this->relExists($a,$b,$relname);
	}

	public function updateRel($relid, $target,$relname){
		return $this->db_update('crm_contact_rel',[
				"target_id" => $target, 
				"relname" => $relname,
			],"id = :id",[":id"=>$relid]);
	}

	public function deleteRel($relid){
		return $this->db_delete('crm_contact_rel',"id = :id",[":id"=>$relid]);
	}

	public function deleteRelAllByTarget($target, $relname){
		return $this->db_delete('crm_contact_rel',
			"target_id = :target and relname = :relname",
				[":target"=>$target,':relname'=>$relname]);
	}

	public function deleteRelAllBySource($source, $relname){
		return $this->db_delete('crm_contact_rel',
			"contact_id = :contact and relname = :relname",
				[":contact"=>$source,':relname'=>$relname]);
	}

	public function listRel(){
		$list=$this->db_select('select * from crm_contact_rel order by id;');
		if($list) return $list;
		return [];
	}

	public function getRel($relid){
		if(!$r=$this->db_select(
			'select * from crm_contact_rel where id = :r',[':r'=>$relid]))
				return null;
		return $r[0];
	}

	public function listRelUsingSource($contact_id, $relname){
		return $this->db_select('select * from crm_contact_rel where
			contact_id = :a and relname = :b
		',[':a'=>$contact_id, ':b'=>$relname]);
	}
	
	public function listRelUsingTarget($target_id, $relname){
		return $this->db_select('select * from crm_contact_rel where
			target_id = :a and relname = :b
		',[':a'=>$target_id, ':b'=>$relname]);
	}

	public function listContactNamesInRelByTarget($target, $relname){
		// helper. return all the names (comma separated) of such contacts
		// involved in the relationship to a target_id
		$results = "";
		if($list = $this->listRelUsingTarget($target, $relname)){
			$meta = $this->getMeta();
			$fields = [];
			foreach($meta as $field_name=>$data)
				if(isset($data['list']))
					$fields[] = $field_name;
			$sep='';
			foreach($list as $row){
				$contact_id = $row->contact_id;
				$names = "";$sep2='';
				foreach($fields as $field_name){
					$names .= $sep2.$this->getMetaValue($contact_id, $field_name);
					$sep2=' ';
				}
				$names = ucwords(strtolower($names));
				$results .= $sep.$names;		
				$sep=',';
			}
		}
		return $results;
	}

}
