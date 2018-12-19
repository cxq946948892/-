<?php

header('Access-Control-Allow-Origin:*');
// header('Content-type:application/json');

require_once('lib/config.php');
require_once('lib/mysql.php');
require_once('ServerAPI.php');
require_once('lib/redis.php');
require_once('lib/mobileVerifyCode.php');
require_once('utility.php');
require_once('lib/phpqrcode/phpqrcode.php'); //引入phpqrcode类文件
session_start();

class groupAction {
	private $p;
	private $mysql;
	private $act;
	private $cfg;
	private $limit = 20; //小群限制上限人数

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

		$actArr = array(
			'create',	// 建群
			'addMember',// 添加群成员
			'delMember',// 删除群成员
			'breakGroup',	// 解散群
			'updateGroup',	// 编辑群信息
			'info',		// 群信息与成员列表查询
			'changeOwner',	// 移交群主
			'addManager',	// 任命管理员
			'delManager',	// 移除管理员
			'joinedTeams',	// 获取某个用户所加入高级群的群信息
			'updateTeamNick',// 修改群昵称
			'muteTeam',		// 修改消息提醒开关
			'muteTlist',	// 禁言群成员
			'leave',		// 主动退群
			'muteTlistAll',	// 将群组整体禁言
			'listTeamMute',	// 获取群组禁言列表
			'reportGroup',//举报群
			'playerLevel',//当前用户vip等级
			'teamLevel',//根据群ID获取vip等级
			'getqrcode', //获取群二维码
			'getgrouplist', //获取群列表
			'teamplayerCount', //获取群人数
			'getteaminfo', //获取群信息
			'getusergroulist' //获取某用户所加入的群信息
		);

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

	private function initMembers($members){
		if(empty($members)){
			$members = false;
		}else{
			if(is_array($members)){
				$members = $members;// 数组
			}else{
				if($d = json_decode($members,true)){
					$members = $d;
				}else{
					$members = false;
				}
			}
		}

		return $members;
	}


	private function initMembersNew($members){

		$members = explode(',', $members);

		if(empty($members)){
			$members = false;
		}else{
			if(is_array($members)){
				$members = $members;// 数组
			}else{
				if($d = json_decode($members,true)){
					$members = $d;
				}else{
					$members = false;
				}
			}
		}

		return $members;
	}


	// 创建群，群个数和成员数都有限制
	private function create($data){
		$model = new utility();
		$members = $this->initMembersNew($data['members']);
		if(!$members){
			return array('code'=>501,'errmsg'=>'群成员格式有误');
		}
		//判断拉群类型，type ,0-vip,1-普通群
		$type = $data['type'] ? $data['type'] : 0;
		if($type == 1){
			//普通群
			if(count($members) > 20){
				return array('code'=>501,'errmsg'=>'小群不能超过20个人');
			}
		}

		//判断用户身份，当要建VIP群的时候，只有VIP才能建群
		if(!$model->checkVipLevel($data['owner']) && $type == 0){
			return array('code'=>500,'errmsg'=>'vip会员才能创建群聊');
		}

		$announcement = '欢迎加入群聊';
		$intro        = '群聊';
		$msg          = '邀请您加入群聊';
		$magree       = 0;
		$joinmode     = 1;
		$beinvitemode = 1;
		$invitemode	  = 1;
		//$tname = '群聊-'.$data['owner']; //群聊名称
		$arr = $members;
		array_push($arr,$data['owner']);
		$tname = $model->gettname($arr); //群聊名称

		$re = $this->p->createGroup($tname,$data['owner'],$members,$announcement,$intro,$msg,$magree,$joinmode,$beinvitemode,$invitemode);

		if($re['code'] == 200){
			$data['tid'] = $re['tid'];
			$data['members'] = $members;
			if(isset($re['faccid']['accid']) && !empty($re['faccid']['accid'])){
				$data['members'] = array_values(array_diff($members,$re['faccid']['accid']));
			}

			$re['members_num'] = count($data['members']);
			$returnArr = $re;

			$data['tname']		  = $tname;
			$data['announcement'] = $announcement;
			$data['intro']        = $intro;
			$data['msg']          = $msg;
			$data['magree']       = $magree;
			$data['joinmode']     = $joinmode;
			$data['beinvitemode'] = $beinvitemode;
			$data['invitemode']   = $invitemode;
			$this->storeToDatabase($data);

			//生成群二维码
			$this->buildqrcode($re['tid']);
		}else{
			$returnArr = array('code'=>$re['code'],'errmsg'=>$re['desc']);
		}

		return $returnArr;
	}

	// 拉人入群，可同时拉多人
	private function addMember($data){
		if(!$data['tid']){
			return array('code'=>501,'errmsg'=>'参数错误tid和owner必传');
		}
		$members = $this->initMembersNew($data['accounts']);
		if(!$members){
			return array('code'=>501,'errmsg'=>'群成员格式有误');
		}

		// 本地群信息
		$tinfo = $this->mysql->select('wc_group','owner,magree,msg,members,member_num,type',array('tid'=>$data['tid'],'status'=>1));
		if(count($tinfo) == 0){
			return array('code'=>502,'errmsg'=>'群号tid有误或该群关闭了');
		}else{
			$tinfo = $tinfo[0];
		}
		//判断是否是小群，如果是小群，限制人数，如果是VIP群，也限制人数200人- type ,0-vip,1-普通群
		$tarminfo = $this->p->queryDetail($data['tid']);
		if($tarminfo){
			$tarminfo = $tarminfo['tinfo'];
			$count = count($tarminfo['admins']) + count($tarminfo['members']) + 1 + count($members);
		}else{
			return array('code'=>502,'errmsg'=>$re['desc']);
		}

		if($tinfo['type'] == 1){
			if($count > $this->limit){
				return array('code'=>502,'errmsg'=>'小群不能超过'.$this->limit.'个人');
			}
		}else{
			if($count >= 200){
				return array('code'=>502,'errmsg'=>'VIP不能超过200个人');
			}
		}
		// 判断是否已在该群
		$members = array_diff($members,json_decode($tinfo['members']));

		if(empty($members)){
			return array('code'=>502,'errmsg'=>'该成员已存在');
		}
		$re = $this->p->addIntoGroup($data['tid'],$tinfo['owner'],$members,$tinfo['magree'],$tinfo['msg']);
		if($re['code'] == 200){
			//更新群成员字段
			$update = $this->updateteaminfo($data['tid']);
			$returnArr = $re;
		}else{
			$returnArr = array('code'=>$re['code'],'errmsg'=>$re['desc']);
		}
		return $returnArr;
	}

	// 删除群成员
	private function delMember($data){
		if(!$data['tid']){
			return array('code'=>501,'errmsg'=>'参数错误tid和owner必传');
		}
		$members = $this->initMembersNew($data['accounts']);
		if(!$members){
			return array('code'=>501,'errmsg'=>'群成员格式有误');
		}

		// 本地群信息
		$tinfo = $this->mysql->select('wc_group','owner,members,member_num',array('tid'=>$data['tid'],'status'=>1));
		if(count($tinfo) == 0){
			return array('code'=>502,'errmsg'=>'群号tid有误或该群关闭了');
		}else{
			$tinfo = $tinfo[0];
		}
		$teamplayer = json_decode($tinfo['members'],true);

		foreach ($members as $key => $value) {
			$k = array_search($value, $teamplayer);
			unset($teamplayer[$k]);
		}

		$re = $this->p->kickFromGroup($data['tid'],$tinfo['owner'],$members);
		if($re['code'] == 200){
			//更新群成员字段
			$update = $this->updateteaminfo($data['tid']);
			$returnArr = $re;
		}else{
			if($re['code'] == 403){
				$returnArr = array('code'=>$re['code'],'errmsg'=>'玩家为管理员身份，管理员无法被踢出');
			}else{
				$returnArr = array('code'=>$re['code'],'errmsg'=>$re['desc']);
			}
		}

		return $returnArr;
	}

	private function storeToDatabase($data){
		$in = array(
			'tid'          => $data['tid'],
			'tname'        => urlencode($data['tname']),
			'owner'        => $data['owner'],
			'members'      => json_encode($data['members']),
			'announcement' => $data['announcement'],
			'intro'        => $data['intro'],
			'msg'          => $data['msg'],
			'magree'       => $data['magree'],
			'joinmode'     => $data['joinmode'],
			'member_num'   => count($data['members']),
			'type'   => $data['type'],
			'create_time'  => date('Y-m-d H:i:s',time())
		);

		$this->mysql->insert('wc_group',$in);

	}

	/**
	 * 群主解散群聊
	 * @param  [type] $data [description]
	 * tid,owner,account
	 */
	private function breakGroup($data){
		//群ID
		if(!$data['tid']){
			return array('code'=>501,'errmsg'=>'参数错误tid必传');
		}
		if(!$data['accid']){
			return array('code'=>501,'errmsg'=>'参数错误accid必传');
		}

		// 本地群信息
		$tinfo = $this->mysql->select('wc_group','*',array('tid'=>$data['tid'],'status'=>1));
		if(count($tinfo) == 0){
			return array('code'=>502,'errmsg'=>'群号tid有误或该群关闭了');
		}else{
			$tinfo = $tinfo[0];
		}

		//判断，如果不是群主，不可以解散群
		if($tinfo['owner'] != $data['accid']){
			return array('code'=>501,'errmsg'=>'非群主无权解散群');
		}
		
		$re = $this->p->removeGroup($data['tid'],$data['accid']);
		if($re['code'] == 200){
			$returnArr = $re;
			$update = array('status'=>2);
			$this->mysql->update('wc_group',$update,array('tid'=>$data['tid']));
		}else{
			$returnArr = array('code'=>$re['code'],'errmsg'=>$re['desc']);
		}

		return $returnArr;
	}
	/**
	 * 更新群聊信息
	 * 只传入需要修改的字段
	 */
	private function updateGroup($data){
		if(!$data['tid'] || !$data['owner'] || !$data['account'] || $data['owner'] != $data['account']){
			return array('code'=>501,'errmsg'=>'参数错误tid,owner,account必传');
		}

		$info = $this->mysql->select('wc_group','*',array('tid'=>$data['tid'],'owner'=>$data['owner'],'status'=>1));
		if(count($info) == 0){
			return array('code'=>502,'errmsg'=>'群号tid有误或该群关闭了');
		}
		// $info = $info[0];
		$arr = array(
			'tname'        =>'',
			'announcement' =>'',
			'intro'        =>'',
			'joinmode'     =>'',
			'custom'       =>'',
			'icon'         =>'',
			'beinvitemode' =>'',
			'uptinfomode'  =>'',
			'upcustommode' =>''
		);
		$c = 0;
		foreach($arr as $key => $val){
			if(isset($data[$key])){
				$arr[$key] = $data[$key];
			}else{
				$arr[$key] = false;
				$c++;
			}
		}
		if($c == count($arr)){
			return array('code'=>502,'errmsg'=>'什么都没修改');
		}
		$re = $this->p->updateGroup($data['tid'],$data['owner'],$arr['tname'],$arr['announcement'],$arr['intro'],$arr['joinmode'],$arr['custom'],$arr['icon'],$arr['beinvitemode'],$arr['invitemode'],$arr['uptinfomode'],$arr['upcustommode']);

		if($re['code'] == 200){
			// 更新数据库
			$returnArr = $re;
			$upArr = array_filter($arr);
			$upArr['tname'] = urlencode($upArr['tname']);
			$re = $this->mysql->update('wc_group',$upArr,array('tid'=>$data['tid']));
		}else{
			$returnArr = array('code'=>$re['code'],'errmsg'=>$re['desc']);
		}

		return $returnArr;
	}

	private function reportGroup($data){
		if(!$data['reasonId'] || !$data['tid'] || !$data['accid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}
		$re = $this->mysql->select('wc_users','*',array('accid'=>$data['accid'],'status'=>1));
		if(count($re) == 0){
			return array('code'=>500,'errmsg'=>'该用户不存在');
		}
		$re2 = $this->mysql->select('wc_group','*',array('tid'=>$data['tid'],'status'=>1));
		if(count($re2) == 0){
			return array('code'=>500,'errmsg'=>'该群不存在');
		}
		$in = array(
			'tid'         => $data['tid'],
			'accid'       => $data['accid'],
			'reason_id'   => $data['reasonId'],
			'create_time' => date('Y-m-d H:i:s',time())
		);
		$ret = $this->mysql->insert('wc_group_warn',$in);
		if($ret){
			return array('code'=>200,'errmsg'=>'');
		}else{
			return array('code'=>500,'errmsg'=>'服务器错误');
		}
		
	}

	/**
	 * 获取群二维码
	 * @param [tid] [群ID]
	 */
	public function getqrcode($data){
		//先查询数据库里的二维码信息,如果没有找到，就生成
		$local = $this->cfg['local_url'];
		if(!$data['tid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}
		$result = $this->mysql->select('wc_group','*',array('tid'=>$data['tid']));
		$qrcode = $result[0]['qrcode'];

		if(!$qrcode){
			$qrcode = $this->buildqrcode($data['tid']);
		}
		$res = $local.$qrcode;
		return array('code'=>200,'url'=>$res);
	}

	/**
	 * 生成群二维码
	 * @param [tid] [群ID]
	 * config.domain + '/login.html?action=addTeam&tid='+ this.splitSessionId
	 */
	public function buildqrcode($tid){
		$domain = $this->cfg['main_url'];
		$url = $domain.'/login.html?action=addTeam&tid='.$tid; //二维码内容
		$errorCorrectionLevel = 'L';//容错级别
		$matrixPointSize = 6;//生成图片大小
 		
 		$imgName = $tid.'.png';
 		$date = date('Y-m-d');
		$path = 'images/team/'.$date;
		$path_name = $path.'/'.$imgName;

		if(!is_dir($path)){
			mkdir($path,0777,true);
		}

		//生成二维码图片
 		QRcode::png($url, $path_name, $errorCorrectionLevel, $matrixPointSize, 2);
 		//生成后将修改数据库中的结果
 		$re = $this->mysql->update('wc_group',array('qrcode'=>$path_name),array('tid'=>$tid));
 		return $path_name;
	}

	/**
	 * 玩家vip等级查询
	 * @return [type] [description]
	 */
	public function playerLevel($data){
		if(!$data['accid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}

		$model = new utility();
		$res = $model->checkVipLevel($data['accid']);

		if(count($res) == 0){
			return array('code'=>200,'errmsg'=>'','vip_level'=>0);
		}else{
			return array('code'=>200,'errmsg'=>'','vip_level'=>1);
		}
	}

	/**
	 * 根据群主来获取玩家vip等级查询
	 * @return [type] [description]
	 */
	public function teamLevel($data){
		if(!$data['tid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}

		$tinfo = $this->mysql->select('wc_group','owner,type',array('tid'=>$data['tid']));
		$res = $this->playerLevel(['accid'=>$tinfo[0]['owner']]);
		$res['owner'] = $tinfo[0]['owner'];
		$res['type'] = $tinfo[0]['type'];
		return $res;
	}

	/**
	 * 查找群列表
	 * @return [type] [description]
	 */
	public function getgrouplist($data){
		if(!$data['accid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}
		//
		if(!$data['type'] && $data['type'] != 0){
			return array('code'=>500,'errmsg'=>'参数错误');
		}

		$re = $this->p->joinTeams($data['accid']);
		$result = array();
		if($re['code'] == 200){
			// 判断传的type
			$infos = $re['infos'];
			if($infos){
				$x = 0;
				foreach ($infos as $key => $value) {
					$tinfo = $this->mysql->select('wc_group','*',array('tid'=>$value['tid']));
					if($tinfo[0]['type'] == $data['type']){
						$result[$x]['tname'] = $value['tname'];
						$result[$x]['tid'] = $value['tid'];
						$result[$x]['owner'] = $value['owner'];
						$x++;
					}
				}
			}

			$returnArr = array('code'=>$re['code'],'result'=>$result);
		}else{
			$returnArr = array('code'=>$re['code'],'errmsg'=>$re['desc']);
		}
		return $returnArr;
	}

	/**
	 * 获取某用户所加入的群信息
	 * @return [type] [description]
	 */
	public function getusergroulist($data){
		if(!$data['accid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}

		$re = $this->p->joinTeams($data['accid']);
		$result = array();
		if($re['code'] == 200){
			$infos = $re['infos'];
			$returnArr = array('code'=>$re['code'],'result'=>$infos);
		}else{
			$returnArr = array('code'=>$re['code'],'errmsg'=>$re['desc']);
		}
		return $returnArr;
	}

	/**
	 * 主动退群
	 * @return [type] [description]
	 */
	public function leave($data){
		if(!$data['tid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}
		//
		if(!$data['accid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}

		// 本地群信息
		$tinfo = $this->mysql->select('wc_group','owner,magree,msg,members,member_num,type',array('tid'=>$data['tid'],'status'=>1));
		if(count($tinfo) == 0){
			return array('code'=>502,'errmsg'=>'群号tid有误或该群关闭了');
		}else{
			$tinfo = $tinfo[0];
		}

		$re = $this->p->leave($data['tid'],$data['accid']);
		if($re['code'] == 200){
			//更新群成员字段
			$update = $this->updateteaminfo($data['tid']);
			$returnArr = array('code'=>$re['code'],'result'=>1);
		}else{
			$returnArr = array('code'=>$re['code'],'errmsg'=>$re['desc']);
		}
		return $returnArr;
	}

	//更新群成员字段
	public function updateteaminfo($tid){
		$members = $this->queryDetail($tid);
		if($members['code'] == 200){
			$update['member_num'] = count($members['result']); //当前群人员人数
			$array = [];
			foreach ($members['result'] as $key => $value) {
				$array[$key] = $value['accid'];
			}
			$update['members'] = json_encode($array);
			$res = $this->mysql->update('wc_group',$update,array('tid'=>$tid));
		}
		return $res;
	}

	//获取当前群成员并更新写入数据库
	public function queryDetail($tid){
		$res = $this->p->queryDetail($tid);
		if($res['code'] == 200){
			$members = $res['tinfo']['members'];
			return array('code'=>200,'result'=>$members);
		}else{
			return array('code'=>502,'errmsg'=>'获取群信息失败');
		}
	}

	//判断群成员人数
	public function teamplayerCount($data){
		if(!$data['tid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}

		// 本地群信息
		$tinfo = $this->mysql->select('wc_group','owner,magree,msg,members,member_num,type',array('tid'=>$data['tid'],'status'=>1));
		if(count($tinfo) == 0){
			return array('code'=>502,'errmsg'=>'群号tid有误或该群关闭了');
		}else{
			$tinfo = $tinfo[0];
		}

		$tarminfo = $this->p->queryDetail($data['tid']);
		if($tarminfo){
			$tarminfo = $tarminfo['tinfo'];
			$count = count($tarminfo['admins']) + count($tarminfo['members']) + 1 + 1;
		}else{
			return array('code'=>502,'errmsg'=>$re['desc']);
		}

		//判断群的类型
		if($tinfo['type'] == 1){
			//普通群不能超过数量
			if($count > $this->limit){
				return array('code'=>555,'errmsg'=>'小群不能超过'.$this->limit.'个人');
			}
		}else{
			//VIP群
			if($count >= 200){
				return array('code'=>555,'errmsg'=>'VIP不能超过200个人');
			}
		}
		return array('code'=>200,'errmsg'=>'');
	}

	//获取群信息
	public function getteaminfo($data){
		if(!$data['tid']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}

		// 本地群信息
		$tinfo = $this->mysql->select('wc_group','owner,magree,msg,members,member_num,type',array('tid'=>$data['tid'],'status'=>1));
		if(count($tinfo) == 0){
			return array('code'=>502,'errmsg'=>'群号tid有误或该群关闭了');
		}else{
			$tinfo = $tinfo[0];
		}

		$re = $this->p->queryDetail($data['tid']);
		if($re['code'] == 200){
			$returnArr = array('code'=>$re['code'],'result'=>$re['tinfo']);
		}else{
			$returnArr = array('code'=>$re['code'],'errmsg'=>$re['desc']);
		}
		return $returnArr;
	}

}

$group = new groupAction();

// $_POST = array(
//  	'act'=>'updateGroup',
//  	'account'=>'cm2017',
// 	'owner'=>'cm2017',
// 	'tid'=>'219632023',
// 	'tname'=>'新的'
// );
// $_POST = array(
//  	'act'=>'breakGroup',
//  	'account'=>'cm2017',
// 	'owner'=>'cm2017',
// 	'tid'=>'219632023'
// );
// $_POST = array(
//  	'act'=>'create',
// 	'owner'=>'dc011',
// 	'members'=>'["cm2017"]',
// 	'verifyCode'=>'2209'
// );
// $_POST = array(
// 	'tid'=>'207245933',
// 	'reasonId'=>'1',
// 	'accid'=>'dcrao1',
// 	'act'=>'reportGroup'
// );
$response = $group->main($_REQUEST);
echo $response;
