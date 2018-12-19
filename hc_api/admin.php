<?php

//我的VIP
header('Access-Control-Allow-Origin:*');
header("Content-Type: text/html;charset=utf-8");

require_once('lib/config.php');
require_once('lib/mysql.php');
require_once('ServerAPI.php');
require_once('lib/redis.php');
require_once('utility.php');
session_start();

class admin {
	private $p;
	private $mysql;
	private $act;
	private $cfg;

	public function __construct() {
		$this->ResponseHandler();
	}
	private function ResponseHandler() {
		$this->mysql = new MysqlDriver(Config::$mysql_config);
		$this->p     = new ServerAPI(Config::$AppKey,Config::$AppSecret,'curl');	
		$this->cfg = Config::init_host_info();
	}

	public function main($data){
		$this->act = $data['act'];
		$postData = $this->prepareData($data);
		$actArr = ['fresh_group'];

		if(!in_array($this->act,$actArr)){
			return json_encode(array('code'=>500,'errmsg'=>'非法请求'));
		}else{
			$return = $this->action($postData);
			$this->mysql->close();
			return json_encode($return);
		}
	}

	private function action($postData){
		$act = $this->act;
		$returnArr = $this->$act($postData);
		if(!$returnArr){
			$returnArr = array('code'=>555,'errmsg'=>'服务器错误');
		}
		
		return $returnArr;
	}

	private function prepareData($data){
		if(empty($data)){
			return array();
		}
		$ret = array();
		foreach($data as $key => $val){
			$ret[$key] = $val;
		}
		return $ret;
	}

	//公有方法
	private function fresh_group($data){
		$group = $this->mysql->select('wc_group','tid',array('status'=>1),'');

		if($group){
			foreach ($group as $key => $value) {
				$re = $this->p->queryDetail($value['tid']);
				if($re['code'] == 200){
					$members = $re['tinfo']['members'];
					if($members){
						$condition = $array = [];
						foreach ($members as $k => $v) {
							$array[$k] = $v['accid'];
						}
						$condition['members'] = json_encode($array);
						$condition['member_num'] = count($members);
						$condition['status'] = 1;
						$res = $this->mysql->update('wc_group',$condition,array('tid'=>$value['tid']));
					}else{
						$res = $this->mysql->update('wc_group',array('status'=>1),array('tid'=>$value['tid']));
					}
				}else{
					$res = $this->mysql->update('wc_group',array('status'=>2),array('tid'=>$value['tid']));
				}
			}
		}
		exit;
	}
}

$admin = new admin();
$response = $admin->main($_REQUEST);
echo $response;
