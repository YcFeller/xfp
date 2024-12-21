<?php
session_start();
// 检查用户是否已登录
$user_role = $_SESSION['user_role'] ?? null;
$required_role = 2;
if ($user_role === null) {
  echo json_encode(['error' => '未登录，请先登录。'], JSON_UNESCAPED_UNICODE);
  header("Location: ../../pages/auth/login.php");
  exit;
} elseif ($user_role < $required_role) {
  echo json_encode(['error' => '权限不足，无法访问该页面。'], JSON_UNESCAPED_UNICODE);
  header("Location: ../../index.php");
  exit;
}



// 每秒请求5次限制
$limit = 5;
$interval = 1; // 秒

// 获取客户端IP地址
$ip = $_SERVER['REMOTE_ADDR'];

// 获取当前时间戳
$now = $_SERVER['REQUEST_TIME_FLOAT'];

// 检查请求次数是否超过限制
if (isset($requests[$ip]) && $now - $requests[$ip]['time'] < $interval) {
  $requests[$ip]['count']++;
  if ($requests[$ip]['count'] > $limit) {
    echo json_encode(['error' => '请求次数超过限制。'], JSON_UNESCAPED_UNICODE);
    exit;
  }
} else {
  $requests[$ip] = ['time' => $now, 'count' => 1];
}

// 判断请求方法是否为GET或POST
if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
  $PSN = $_POST['psn'] ?? $_GET['psn']; // 获取设备码
  $WFID = $_POST['wfId'] ?? $_GET['wfId']; // 获取表盘ID
  $PSW = $_POST['psw'] ?? $_GET['psw']; // 获取验证码

  // 判断设备码和表盘ID是否为空
  if (empty($PSN) || empty($WFID)) {
    echo json_encode(['error' => '请输入设备码和表盘ID。'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  // 判断验证码是否为空
  if (empty($PSW)) {
    echo json_encode(['error' => '请输入验证码。'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  // 判断验证码是否正确
  if ($PSW != "xr1688s") {
    echo json_encode(['error' => '验证失败！请联系我获取密码！'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // 将字符串中的小写字母转换为奇数
  function convert_to_odd_numbers($s)
  {
    $result = [];
    for ($i = 0; $i < strlen($s); $i++) {
      $c = $s[$i];
      if (ctype_lower($c)) {
        $number = (ord($c) - ord('a') + 1) % 10;
        $number = $number % 2 == 0 ? $number + 1 : $number;
        $result[] = strval($number);
      } else {
        $result[] = $c;
      }
    }
    return implode('', $result);
  }

  // 生成解锁密码
  function get_unlock_pwd($psn, $wfId)
  {
    $text = $psn . "/data/app/watchface/market/" . $wfId . "//_lua/app/";
    $sha256_hash = hash('sha256', $text);
    $last_six = substr($sha256_hash, -6);
    return convert_to_odd_numbers($last_six);
  }

  $UNLOCK_PWD = get_unlock_pwd($PSN, $WFID);
  echo json_encode(['unlock_pwd' => $UNLOCK_PWD], JSON_UNESCAPED_UNICODE);
} else {
  echo json_encode(['error' => '无效的请求方法。'], JSON_UNESCAPED_UNICODE);
}
