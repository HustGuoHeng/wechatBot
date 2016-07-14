<?php
require_once('wechatBot.php');

class wxAdapt extends wechatBot {
	//command key
	//用于判断是否监听信息
	protected $is_listen = true;


	//接受到的信息
	protected $contactsInfo;
	protected $batchInfo;
	protected $receivedInfo;
	//发送消息队列,避免重复发送
	protected $sendMsgIdArray = [];
	protected $command = [];
	public function __construct() {
		parent::__construct();
	}

	public function init() {
		echo "微信网页自定义登陆程序<br>";
		parent::getUuid(); // 获取$uuid
		//获取二维码
	    $src_url = parent::getQRcodeUrl();
	    echo "<img style=\"width:200px\"src=\"$src_url\"></img>";//生成二维码图片
	    ob_flush();
	    flush();
	    //检测扫描状态
	    while (parent::waitForLogin() != 200) {
    		continue;
    	}
    	//获取扫描成功后关键信息
    	parent::getCoreKey();
    	//网页微信初始化
    	parent::webWeixinInit();
    	//获取用户常用联系人信息
    	$this->contactsInfo = parent::webWeixinGetContact();
    	//获取群组详细信息
    	$this->batchInfo =parent::webWeixinBatchGetContent();
    	echo "<pre>";
    	// print_r($this->batchInfo);
    	//开始监听信息
    	$this->listen();
    	return true;
	}
	//监听任务，默认开启一次即可
	protected function listen() {
		while (true) {
			if ($this->is_listen) {
				$res = parent::synccheck();
	            if ($res['retcode'] == '0' && $res['selector'] == '0') {
	                continue;
	            } else {
	            	$this->receivedInfo = parent::webWeixinSync();
	                $this->scheduler($this->receivedInfo);
	            } 
			} else {
				continue;
			}
		}
	}
	// 数据信息处理与任务监听
	//目前仅仅支持文字信息的接受与
	public function scheduler($receivedInfo) {
		$receivedInfo = end($receivedInfo->AddMsgList);
		if ($receivedInfo->FromUserName == $this->baseInfo->User->UserName) {
			//命令模式暂时不设置
		} else {
			switch ($receivedInfo->MsgType) {
				case '1':
					$this->dealNewTextInfo($receivedInfo);
					break;
				case '10002':
					break;
				default:
					# code...
					break;
			}
		}

	}
	//
	// $msgType = array(
	// 	'51'	=>	'click',
	// 	'47'	=>	'picture',
	// 	'1'		=>	'text',
	// 	'10002'	=>	'revokemsg'
	// );
	protected function dealNewTextInfo($newInfo) {
		$content = $newInfo->Content;
		$result = [];
		if (preg_match('/(@[0-9a-zA-Z]*):(\S*)/', $content, $match)) {
			$result['fromUserName'] 	= $match[1];
			$result['batchUserName'] 	= $newInfo->FromUserName;
			$result['content'] 			= $match[2];
		} else {
			$result['fromUserName'] 	= $newInfo->FromUserName;
			$result['batchUserName']	= 'null';
			$result['content']			= $content;
		}
		//根据解析的数据生成文字
		$msg = '';
		if ($result['batchUserName']) {
			$msg .= $this->getBatchName($result['batchUserName']) . '-';
		}
		$msg .= $this->getNickNameByUserName($result['fromUserName'], $result['batchUserName']);
		$msg .= ':' . $result['content'];
		//发送文字信息
		if (!in_array($newInfo->MsgId, $this->sendMsgIdArray)) {
			$this->sendMsg(strip_tags($msg));
			$this->sendMsgIdArray[] = $newInfo->MsgId;
		}
		
	}
	//获取好友的名称，如果附带群username则获得群好友的名称
	protected function getNickNameByUserName($userName, $batchUserName = null) {
		if ($batchUserName) {
			foreach ($this->batchInfo->ContactList as $value) {
				if ($value->UserName == $batchUserName) {
					foreach ($value->MemberList as $val) {
						if ($val->UserName == $userName) {
							return $val->DisplayName ? $val->DisplayName : $val->NickName;
						}
					}
				}
			}
			return false;
		} else {
			foreach ($this->contactsInfo->MemberList as $value) {
				if ($value->UserName == $userName) {
					return $value->DisplayName ? $value->DisplayName : $value->NickName;
				}
			}
		}
		return false;
	}
	//获取群的名称
	protected function getBatchName($userName) {
		foreach ($this->batchInfo->ContactList as $value) {
			if ($value->UserName == $userName) {
				return $value->NickName;
				break;
			}
		}
		return false;
	}

	public function set_listend_status($status) {
		$this->is_listen = $status;
	}


}