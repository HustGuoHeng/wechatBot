<?php

/**
 *	用户微信通信的相关函数
 */

//curl获取网页请求
function curlRequest($url, $isPost = false, $params = array(), $timeOut = 60, $header = 0, $cookie = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($isPost) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, $header);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
    if ($cookie) {
      	curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    print_r(curl_error ($ch));
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

//必要过程失败处理函数
function wrongResponse($data) {
	echo "<br>" . $data;
	ob_flush();
	flush();
	echo "<script> location.href='".getServiceUrl()."';</script>"; 
}

//将curl获取的cookie转换为字符串
function changeCookieToStr($cookie) {
    $cookie_str = '';
    foreach ($cookie as $key => $value) {
    	$cookie_str .= $key . "=" . $value . ';';
    }
    return $cookie_str;
}

//获取当前网址

function getServiceUrl() {
    $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    return $url;
}