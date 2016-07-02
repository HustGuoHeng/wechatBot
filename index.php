<?php
	echo "<pre>";
    $wechatBot = new wechatBot();
    $wechatBot->run();

class wechatBot
{
    public $uuid = false;
    
    //用于waitForLogin
    protected $waitForCheck = false; //用于标志用户是否扫描
    protected $tid = 1;

    //手机扫描成功后获取的参数
    protected $redirectUrl;
    protected $hostUrl;
    protected $loginSuccessCoreKey = [];
    //用于初始化以及获取信息的参数
    protected $baseRequest = [];
    /**
     * 	主体代码
     */
    public function run() {
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

    }
    /**
     * 获取uuid
     */
    public function getUuid() {
        $getUuidUrl = "https://login.weixin.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Fwx.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=en_US&_=1452859503801";

        while (!$this->uuid) {
	        $result = self::curlRequest($getUuidUrl);
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
        $result = $this->curlRequest($url, false, array(), 20);
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
            echo "<br>";
            echo "登录成功，正在进行下一步,请稍后：";
        }else if ($code == '408') {
           break;
        }
        return $code;
    }
    /**
     *	连获取连接成功后关键的信息
     */
    public function getCoreKey() {
    	$result = self::curlRequest($this->redirectUrl);
    	$xml = simplexml_load_string($result);
    	$arr = [];
    	foreach ($xml as $key => $value) {
    		$arr[$key] = (string)$value;
    	}
    	$this->loginSuccessCoreKey = $arr;
    	//print_r($this->loginSuccessCoreKey);
    }
    //初始化信息
    public function webWeixinInit() {

    	$url = "https://$this->hostUrl/cgi-bin/mmwebwx-bin/webwxinit?pass_ticket=" . $this->loginSuccessCoreKey['pass_ticket'] . "&skey=" . $this->loginSuccessCoreKey['skey'] . "&r=" . time();

		$this->baseRequest['Uin'] = $this->loginSuccessCoreKey['wxuin'];
		$this->baseRequest['Sid'] = $this->loginSuccessCoreKey['wxsid'];
    	$this->baseRequest['Skey'] = $this->loginSuccessCoreKey['skey'];
    	$this->baseRequest['DeviceID'] = 'e159973572418266';


    	$params = array('BaseRequest' => $this->baseRequest);
    	$params = json_encode($params);
    	$result = self::curlRequest($url, true, $params);
    	print_r($result);
    }
    /*
    * curl获取网页请求
    */
    public function curlRequest($url, $isPost = false, $params = array(), $timeOut = 60) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($isPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        print_r(curl_error ($ch));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
