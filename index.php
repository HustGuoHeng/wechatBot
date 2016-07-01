<?php

    $wechatBot = new wechatBot();
    $wechatBot->getUuid();

    $src_url = $wechatBot->getQRcodeUrl();
    echo "<img src=\"$src_url\"></img>";//生成二维码图片
    ob_flush();
    flush();
    
    while ($wechatBot->waitForLogin() != 200) {
    	true;
    }

class wechatBot
{
    public $uuid = false;
    public $redirectUri;
    public $baseUri;

    protected $waitForCheck = false; //用于标志用户是否扫描
    protected $tid = 1;
    public function __construct()
    {
    }
    /**
     * 获取uuid
     */
    public function getUuid()
    {
        $getUuidUrl = "https://login.weixin.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https%3A%2F%2Fwx.qq.com%2Fcgi-bin%2Fmmwebwx-bin%2Fwebwxnewloginpage&fun=new&lang=en_US&_=1452859503801";

        while (!$this->uuid) {
	        $result = self::curlRequest($getUuidUrl);
	        $result = preg_match('/uuid = "([a-zA-Z0-9_]*={2})"/', $result, $matches);
	        $uuid =  $matches['1'];
	        $this->uuid = $uuid;
        }

    }
    /**
     * 获取二维码
     */
    public function getQRcodeUrl()
    {   
    	$url = "https://login.weixin.qq.com/qrcode/" . $this->uuid;
        return $url;                                                                                                                 
    }
    /**
     * 获取连接状态
     */
    public function waitForLogin()
    {
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
            echo "<br>";
            echo "登录成功，正在进行下一步,请稍后：";
            preg_match('/window.redirect_uri=\"(\S*)\"/',$result,$matches);
            print_r($result);
            $redirectUri = $matches['1'];
            $this->redirectUri = $redirectUri;
        }else if ($code == '408') {
           break;
        }
        return $code;
    }
    /*
    * curl获取网页请求
    */
    public function curlRequest($url, $isPost = false, $params = array(), $timeOut = 60)
    {
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
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
