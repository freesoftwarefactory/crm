<?php
namespace freesoftwarefactory\crm;
class DbHelper {
	private $db;
	public function __construct($db){
		$this->db = $db;	
	}
	public function select($sql,$arguments){
		if($rows = $this->db->createCommand($sql)
			->bindValues($arguments)->queryAll()){
			$objects = array();
			foreach($rows as $r){
				$obj = new \stdClass;
				foreach($r as $attr=>$value)
					$obj->$attr = $value;
				$objects[] = $obj;
			}
			return $objects;	
		}else
		return null;
	}
	public function update($table,$values,$where,$params){
		return $this->db->createCommand()->update($table,$values,$where)
			->bindValues($params)->execute();
	}
	public function insert($table,$values){
		return $this->db->createCommand()->insert($table,$values)->execute();
	}
	public function delete($table,$where,$params){
		return $this->db->createCommand()->delete($table,$where)
			->bindValues($params)->execute();
	}
	public function test($tablename,$fields){
		printf("[running tests at DbHelper, $tablename]\n");

		printf("insert...");
		$this->db->createCommand()->delete($tablename,[$fields[0]=>'TEST'])->execute();
		$test = [];$n=1;foreach($fields as $col)$test[$col]='TEST';
		if(!$this->insert($tablename,$test)) die("[error]\n");
		printf("OK\n");		
		$this->db->createCommand()->delete($tablename,[$fields[0]=>'TEST'])->execute();

		printf("select...");
		$this->insert($tablename,[$fields[0]=>'1',$fields[1]=>'TEST']);
		$this->insert($tablename,[$fields[0]=>'2',$fields[1]=>'TEST']);
		$this->insert($tablename,[$fields[0]=>'3',$fields[1]=>'TESTX']);
		$this->insert($tablename,[$fields[0]=>'4',$fields[1]=>'TESTX']);
		$rows = $this->select("select * from $tablename where {$fields[1]} = :t1;",
			[':t1'=>'TEST']);
		$this->db->createCommand()->delete($tablename,[$fields[1]=>'TEST'])->execute();
		$this->db->createCommand()->delete($tablename,[$fields[1]=>'TESTX'])->execute();
		if(count($rows) != 2) die("[error]\n");
		printf("OK\n");

		printf("update...");
		$this->insert($tablename,[$fields[0]=>'1',$fields[1]=>'TEST']);
		$this->insert($tablename,[$fields[0]=>'2',$fields[1]=>'TESTX']);
		if(!$this->update(
				$tablename,[ $fields[1]=>'TESTY' ],
					"{$fields[1]} = :t1",
						[":t1"=>'TESTX']))
							die("[error,return]\n");
		$rows = $this->select("select * from $tablename 
				where ({$fields[1]} = :t1) OR ({$fields[1]} = :t2);",
			[':t1'=>'TEST',':t2'=>'TESTY']);
		$this->db->createCommand()->delete($tablename,[$fields[1]=>'TEST'])->execute();
		$this->db->createCommand()->delete($tablename,[$fields[1]=>'TESTX'])->execute();
		$this->db->createCommand()->delete($tablename,[$fields[1]=>'TESTY'])->execute();
		if(count($rows)!==2) die("[error]\n");
		printf("OK\n");
		
		printf("update...");
		$this->insert($tablename,[$fields[0]=>'1',$fields[1]=>'TEST']);
		if(!$this->delete($tablename,"{$fields[1]}=:t1",[":t1"=>'TEST']))
			die("[error,return]\n");
		$rows = $this->select("select * from $tablename 
				where ({$fields[1]} = :t1);",[':t1'=>'TEST']);
		if(1==count($rows)) die("[error]\n");
		$this->db->createCommand()->delete($tablename,[$fields[1]=>'TEST'])->execute();
		$this->db->createCommand()->delete($tablename,[$fields[1]=>'TESTX'])->execute();
		$this->db->createCommand()->delete($tablename,[$fields[1]=>'TESTY'])->execute();
		printf("OK\n");

		printf("[done]\n");
	}
}
