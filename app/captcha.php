<?php
session_start();

// 生成随机验证码
$captcha = substr(md5(mt_rand()), 0, 5);

// 保存验证码到会话变量
$_SESSION['captcha'] = $captcha;

// 创建画布
$image = imagecreatetruecolor(65, 30);

// 设置背景颜色
$bg_color = imagecolorallocate($image, rand(200, 255), rand(200, 255), rand(200, 255));
imagefill($image, 0, 0, $bg_color);

// 设置验证码文本颜色
$text_color = imagecolorallocate($image, 255, 255, 255);

// 在画布上绘制验证码
imagestring($image, 5, 10, 5, $captcha, $text_color);

// 设置响应头，告诉浏览器输出的是图片
header("Content-type: image/png");

// 输出图像
imagepng($image);

// 释放内存
imagedestroy($image);
