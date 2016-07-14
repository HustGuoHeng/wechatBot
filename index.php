<?php
require_once('./lib/wechatBot.php');
echo "<pre>";
$wechatBot = new wechatBot();
    
echo "微信网页自定义登陆程序<br>";
//获取并且输出二维码
$src_url = $wechatBot->getQRcodeUrl();
echo "<img style=\"width:200px\"src=\"$src_url\"></img>";//生成二维码图片
ob_flush();
flush();
	    
//检测扫描状态
while ($wechatBot->waitForLogin() != 200) {
    true;
}
//获取扫描成功后关键信息
$wechatBot->getCoreKey();
//网页微信初始化
$wechatBot->webWeixinInit();
//获取用户常用联系人信息
$contactInfo = $wechatBot->webWeixinGetContact();
//获取群组详细信息
$batchInfo = $wechatBot->webWeixinBatchGetContent();
		while (true) {
            $res = $wechatBot->synccheck();
            if ($res['retcode'] == '0' && $res['selector'] == '0') {
                continue;
            } else {
            	$a = $wechatBot->webWeixinSync();
            	
                print_r($a->AddMsgList);
            } 
            ob_flush();
            flush();
		}
// print_r($contactInfo);die();
// //获取目标群消息
// foreach ($batchInfo->ContactList as $value) {
//     if ($value->OwnerUin == 734322681 && $value->ContactFlag == 2) {
//         $aimChat['user_name'] = $value->UserName;
//         foreach ($value->MemberList as $val) {
//         	$aimChat['member_info'][$val->UserName] = $val->NickName;
//         }
//         break;
//     }
// }
$myName = $wechatBot->baseInfo->User->UserName;
// function dealWithRececivedInfo($receiveInfo)
// $is_send_ch_msg = false;
// $is_much_info = false;
// while (true) {
//     $res = $wechatBot->synccheck();
//     if ($res['retcode'] == '0' && $res['selector'] == '0') {
//         continue;
//     } else {
//         $result = $wechatBot->webWeixinSync();
// 		$receiveInfo = $result->AddMsgList;
// 		$lastInfo = end($receiveInfo);
//         if ($lastInfo->MsgType != '51' && $lastInfo->ToUserName == $aimChat['user_name']) {
//         	$content = $lastInfo->Content;
//         	if (preg_match('/(@[a-zA-Z0-9].*:)(.*)/',$content, $match)) {
//             	$username = $match['1'];
//             	$username = substr($username, 0, -1);
//             	$content = substr($content, 66);
//             	$wechatBot->sendMsg($aimChat['member_info'][$user_name] . ":" . $content, $aimChat['user_name']);
//         	} 
//         }
//     } 
// }
        // foreach ($wechatBot->baseInfo->ContactList as $key => $value) {
        //     if ($value->OwnerUin == 734322681 && $value->ContactFlag == 2) {
        //     // if ($value->OwnerUin == 0 && $value->ContactFlag == 0) {
        //         $toUserName = $value->UserName;
        //         break;
        //     }
        // }
        // for ($i=0; $i < 5; $i++) { 
        //     $wechatBot->sendMsg("感觉无爱！", $toUserName);
        //     ob_flush();
        //     flush();
        // }