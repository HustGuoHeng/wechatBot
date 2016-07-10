<?php
    require_once('./lib/wxBase.php');
	ini_set('max_execution_time', 3000);
	echo "<pre>";
    $wechatBot = new wechatBot();
    $wechatBot->run();

class wechatBot
{
    //不同的init地址对应不同的消息推送地址
    protected $service = array(
        'wx2.qq.com' 	=> 'webpush.wx2.qq.com', 
        'wx.qq.com'		=> 'webpush.wx.qq.com'
    );

    public $uuid = false;
    	
    //用于waitForLogin
    protected $waitForCheck = false; //用于标志用户是否扫描
    protected $tid = 1;

    //手机扫描成功后获取的参数
    protected $redirectUrl;
    protected $hostUrl;
    protected $loginSuccessCoreKey = [];
    protected $loginSuccessCookie; // 用户获取常用用户列表
    //用于初始化以及获取信息的参数
    protected $baseRequest = [];
	protected $baseInfo; //BaseResponse Count ContactList SyncKey User ChatSet SKey ClientVersion SystemTime GrayScale InviteStartCount MPSubscribeMsgCount MPSubscribeMsgList ClickReportInterval
	protected $deviceID = 'e159973572418266';
	protected $initCookie;
	//保存webWeixinGetContact获取到的用户信息
	protected $webWeixinGetContact;
    //用于消息接收与发送使用
    protected $syncKeyStr; 
    protected $msgPushUrl;

    /**
     * 	主体代码(仅仅供测试使用，具体的业务流程再行规划)
     */
    public function run() {
    	echo "微信网页自定义登陆程序<br>";
    	self::getUuid(); // 获取$uuid
    	//获取二维码
	    $src_url = self::getQRcodeUrl();
	    echo "<img style=\"width:200px\"src=\"$src_url\"></img>";//生成二维码图片
	    ob_flush();
	    flush();
	    //检测扫描状态
	    while (self::waitForLogin() != 200) {
    		true;
    	}
    	//获取扫描成功后关键信息
    	self::getCoreKey();
    	//网页微信初始化
    	self::webWeixinInit();
    	//获取用户常用联系人信息
    	// self::webWeixinGetContact();
        //获取群组详细信息
        // self::webWeixinBatchGetContent();
        /**
         * 简单的接受消息测试
         * todo 改成携程任务
		 */

		while (true) {
            $res = self::synccheck();
            if ($res['retcode'] == '0' && $res['selector'] == '0') {
                continue;
            } else {
                $result = self::webWeixinSync();
                echo "<br>";
                print_r($result);
            } 
            ob_flush();
            flush();
		}

        
 		
    }
    /**
     * 获取uuid
     */
    public function getUuid() {
        $getUuidUrl = "https://login.weixin.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Fwx.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=zh_CN&_=1452859503801";

        while (!$this->uuid) {
	        $result = curlRequest($getUuidUrl);
	        $result = preg_match('/uuid = "([a-zA-Z0-9_]*={2})"/', $result, $matches);
	        @$uuid =  $matches['1'];
	        $this->uuid = $uuid;
        }
    }
    /**
     * 获取二维码
     */
    public function getQRcodeUrl() {   
    	$url = "https://login.weixin.qq.com/qrcode/" . $this->uuid;
        return $url;                                                                                                                 
    }
    /**
     * 获取连接状态
     */
    public function waitForLogin() {
        $now_time = time();
        $url = "https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&uuid=" . $this->uuid . "&tip=" . $this->tid . "&r=" . $now_time . "&_=1452859503803";
        $result = curlRequest($url, false, array(), 20);
        preg_match('/window.code=([0-9]*)/',$result,$matches);
        
        @$code = $matches[1];
        if ($code == '201' && !$this->waitForCheck){
            echo "<br>";
            $this->waitForCheck =  true;
            $this->tid = 0;
            echo "扫描成功，请在手机确认登录！";
            ob_flush();
            flush();
     	} else if ($code == '200') {
     		preg_match('/window.redirect_uri=\"(\S*)\"/',$result,$matches);
            $this->redirectUrl = $matches['1'];
            $this->hostUrl = parse_url($this->redirectUrl, PHP_URL_HOST); 
            foreach ($this->service as $key => $value) {
                if ($key == $this->hostUrl) {
                    $this->msgPushUrl = $value;
                    continue;
                }
            }      
            if (empty($this->msgPushUrl)) {
                echo "<br>很抱歉，未能获取消息推送地址，请联系郭恒<guoheng@qiyi.com>";
                exit();
            }
            echo "<br>登录成功，正在获取关键信息……";
            ob_flush();
    		flush();
        }else if ($code == '408') {
           break;
        }
        return $code;
    }
    /**
     *	连获取连接成功后关键的信息
     */
    public function getCoreKey() {
    	$result = curlRequest($this->redirectUrl, false, [], 60, 1);
    	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
		$cookies = array();
		foreach($matches[1] as $item) {
    		parse_str($item, $cookie);
    		$cookies = array_merge($cookies, $cookie);
		}
		$this->loginSuccessCookie = $cookies;
		preg_match('/\<error\>\S*\<\/error\>/', $result, $match);
    	$xml = simplexml_load_string($match[0]);
    	$arr = [];
    	foreach ($xml as $key => $value) {
    		$arr[$key] = (string)$value;
    	}
    	$this->loginSuccessCoreKey = $arr;
    	echo "<br>关键信息已获取，正在进行初始化……";
    	ob_flush();
    	flush();
    }
    //初始化信息
    public function webWeixinInit() {

    	$url = "https://$this->hostUrl/cgi-bin/mmwebwx-bin/webwxinit?pass_ticket=" . $this->loginSuccessCoreKey['pass_ticket'] . "&skey=" . $this->loginSuccessCoreKey['skey'] . "&r=" . time();

        $this->baseRequest = array(
            'Uin'       =>  $this->loginSuccessCoreKey['wxuin'],
            'Sid'       =>  $this->loginSuccessCoreKey['wxsid'],
            'Skey'      =>  $this->loginSuccessCoreKey['skey'],
            'DeviceID'  =>  $this->deviceID
        );

    	$params = array('BaseRequest' => $this->baseRequest);
    	$params = json_encode($params);
    	//todo 这里会出现获取不到信息的情况，尚不明白具体原因，但影响不大
    	$result = curlRequest($url, true, $params, 60);
    	$result = json_decode($result);

    	if (!$result->BaseResponse->Ret) {
    		$this->baseInfo = $result;
            //获取synckey并转化为字符串以供后面获取信息    
            $arr = [];
            foreach ($this->baseInfo->SyncKey->List as $value) {
                $arr[] = $value->Key . "_" . $value->Val;
            }
            $this->syncKeyStr = implode('|', $arr);
	    	echo "<br>初始化成功！获取信息中……";
	    	ob_flush();
	    	flush();
	    } else {
	    	wrongResponse("初始化失败，五秒后页面即将刷新，请重新扫码登录！");
	    }
    }
    /**
     *	获取常用联系人信息
     *  todo 同样会出现获取信息失败的情况，尚不清楚具体原因
     */
    public function webWeixinGetContact() {
    	
    	$url = "https://$this->hostUrl/cgi-bin/mmwebwx-bin/webwxgetcontact?pass_ticket=".$this->loginSuccessCoreKey['pass_ticket'] . "&r=1467445194420&seq=0&skey=" . (string)$this->baseInfo->SKey;

    	$cookie_str = changeCookieToStr($this->loginSuccessCookie);
    	$result = curlRequest($url, false, [], 5, 0, $cookie_str);
    	$result = json_decode($result); //BaseResponse MemberCount MemberList Seq
    	if (!$result->BaseResponse->Ret) {
    		$this->webWeixinGetContact = $result;
    		echo "<br>常用联系人信息获取成功";
    	} else {
	    	wrongResponse("常用联系人信息获取失败，页面将在五秒后刷新，请重新扫码登录！");
    	}

    }
    /**
     * 获取用户信息(功能实现，根据具体应用在修改)
     */
   	public function webWeixinBatchGetContent() {
   		$url = "https://$this->hostUrl/cgi-bin/mmwebwx-bin/webwxbatchgetcontact?type=ex&r=1453373586582&pass_ticket=" . $this->loginSuccessCoreKey['pass_ticket'];

        $list = [];
        foreach ($this->baseInfo->ContactList as $value) {
            $list[] = array(
                'EncryChatRoomId' => '',
                'UserName'   => $value->UserName
            );
        }
   		$params = array(
   			'BaseRequest' => $this->baseRequest,
   			'Count' => count($list),
   			'List'  => $list
   			);
   		$params = json_encode($params);
   		// $cookie_str = self::changeCookieToStr();

   		$result = curlRequest($url, true, $params);
   		$result = json_decode($result);

   		if ($result) {
   			echo "<br>群详细信息获取成功";
   		}
   	}

    /**
     * 获取用户头像
     * @param $username 微信对的用户的标示id
     * @return  $headImaUrl 用户头像的连接
     */
    public function getIcon($username) {
        $url = "https://$this->hostUrl/cgi-bin/mmwebwx-bin/webwxgeticon?seq=".time()."&username=".$username."&skey=" . (string)$this->baseInfo->SKey;
        return $url;    
    }
    /**
     * 获取群头像
     * @param $username 微信群标示id
     * @return  $headImaUrl 用户头像的连接
     */
    public function getHeadImgUrl($username) {
        $url = "https://$this->hostUrl/cgi-bin/mmwebwx-bin/webwxgetheadimg?seq=".time()."&username=".$username."&skey=".(string)$this->baseInfo->SKey;
        return $url;
    }

    /**
     *  sync长链接用户，用户获得消息通知 
     *  todo not done
     */
    public function synccheck() {
    	$params = array(
    		'r' 		=> 	"146".time(), 
    		'sid' 		=> 	$this->baseRequest['Sid'],
    		'uin'		=> 	$this->baseRequest['Uin'],
            'skey'      =>  $this->baseRequest['Skey'], 
    		'deviceid'	=> 	$this->deviceID,
    		'synckey'	=> 	$this->syncKeyStr, 
    		'_'			=>	"1478070067112"
    	);
        $url = "https://$this->msgPushUrl/cgi-bin/mmwebwx-bin/synccheck?" . http_build_query($params);

        $cookie_str = changeCookieToStr($this->loginSuccessCookie);
        $result = curlRequest($url,false,[],300,0, $cookie_str);
        preg_match('/retcode:"([0-9]*)"/', $result, $matches);
        $retcode = $matches['1'];
        preg_match('/selector:"([0-9]*)"/', $result, $matches);
        $selector = $matches['1'];

        $result = array(
            'retcode' => $retcode,
            'selector' => $selector
        );

        return $result;
    }
    /**
     * 获取聊天信息并更新synckey
     */
    public function webWeixinSync() {
    	$url = "https://$this->hostUrl/cgi-bin/mmwebwx-bin/webwxsync?sid=".$this->baseRequest['Sid']."&skey=".$this->baseRequest['Skey']."&pass_ticket=" . $this->loginSuccessCoreKey['pass_ticket'];
    	//设置post传递的参数
    	$params = array(
    		"BaseRequest" 	=> $this->baseRequest,
    		"SyncKey" 	  	=> $this->baseInfo->SyncKey,
    		"rr"			=> time(),
    	);

    	$result = curlRequest($url, true, json_encode($params));
    	$result = json_decode($result);

        $arr = [];
        foreach ($result->SyncKey->List as $value) {
            $arr[] = $value->Key . "_" . $value->Val;
        }
        $this->syncKeyStr = implode('|', $arr);
    	return $result;
    }
}
