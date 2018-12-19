<?php

//include_once(path_format('config.php'));
class MysqlDriver {

	private $conn = null;
	private $tag = "Mysql";
	private $is_log = true;//开启sql 日志
	
	function __construct($c) {
		$this->connect($c);
	}
	
	public function connect($c) {
        if(!isset($c['port'])){
            $c['port'] = '3306';
        }
        $server = $c['host'] . ':' . $c['port'];
        $this->conn = mysqli_connect($server, $c['username'], $c['password']);
     
		if ($this->conn) {
			$ret = mysqli_select_db($this->conn,$c['dbname']);
			if (!$ret) {
				return false;
			}
			if ($c['charset']) {
				return mysqli_set_charset($this->conn,$c['charset']);
			}
			return true;
		}
		return false;
    }
	
	public function close() {
		return mysqli_close($this->conn);
	}
	
    public function find($sql) {
        $data = array();
        $result = mysqli_query($this->conn,$sql);
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$data[] = $row;
			}
		}
        return $data;
    }
    
    public function select($table, $columns, $where, $other = '') {
        $cond = '';
        foreach ($where as $k => $v) {
			$value = mysqli_real_escape_string($this->conn,$v);
            $cond .= "`$k` = '$value' AND ";
        }
		$cond = substr($cond, 0, strlen($cond) - 5);
		
        $sql = "SELECT $columns FROM `{$table}` WHERE $cond $other";
        
    	$data = array();
    	$result = mysqli_query($this->conn,$sql);
    	if ($result) {
    		while ($row = mysqli_fetch_assoc($result)) {
    			$data[] = $row;
    		}
    	}
    	return $data;
    }
	
	public function insert($table, $row) {
        $stat = '';
        foreach ($row as $k => $v) {
			$value = mysqli_real_escape_string($this->conn,$v);
            $stat .= "`$k` = '$value',";
        }
		
        $stat = substr($stat, 0, strlen($stat) - 1);
        $sql = "INSERT INTO `{$table}` SET $stat";

        mysqli_query($this->conn,$sql);
        return mysqli_insert_id($this->conn);
	}
	public function update($table, $row, $where) {
        $stat = '';
        foreach ($row as $k => $v) {
			$value = mysqli_real_escape_string($this->conn,$v);
            $stat .= "`$k` = '$value',";
        }
        $stat = substr($stat, 0, strlen($stat) - 1);
		
        $cond = '';
        foreach ($where as $k => $v) {
			$value = mysqli_real_escape_string($this->conn,$v);
            $cond .= "`$k` = '$value' AND ";
        }
		$cond = substr($cond, 0, strlen($cond) - 5);
		
        $sql = "UPDATE `{$table}` SET $stat where $cond";
        return  mysqli_query($this->conn,$sql);
	}
	
	public function insert_or_update($table, $row, $field='') {
	
        $stat = '';
		$upd  = '';
		//
        foreach ($row as $k => $v) {
			$value = mysqli_real_escape_string($this->conn,$v);
            $stat .= "`$k` = '$value',";
			if($field){
			 $upd   = '`'.$field.'`'.'='.'`'.$field.'`'.'+'.$value;
			}
        }
        $stat = substr($stat, 0, strlen($stat) - 1);
		//
		if($field){
		  $sql = "INSERT INTO `{$table}` SET $stat ON DUPLICATE KEY UPDATE $upd";
		}else{
		  $sql = "INSERT INTO `{$table}` SET $stat ON DUPLICATE KEY UPDATE $stat"; 
		}
        return mysqli_query($this->conn,$sql);
	}
	
	public function query($sql) {
		return mysqli_query($this->conn,$sql);
	}
	
	private function logs($msg){
		file_put_contents(dirname(dirname(__FILE__))."/logs/sql.txt",$msg.PHP_EOL,FILE_APPEND);
	}

	public function escape_string($str){
		return mysqli_real_escape_string($this->conn,$str);
	}
	
}
 