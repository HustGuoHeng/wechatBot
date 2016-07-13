<?php
require_once('./lib/wxAdapt.php');
$wxRobot = new wxAdapt();

$wxRobot->init();
echo "<br>微信登录成功,请在微信文件助手输入command命令来使用微信助手机器人！";
echo "<br>如果您碰到任何问题，请联系开发者-郭恒<guoheng@qiyi.com>";