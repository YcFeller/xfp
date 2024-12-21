<?php
session_start();

$user_role = $_SESSION['user_role'] ?? null;
$required_role = 1;
if ($user_role === null) {
  echo json_encode(['error' => '未登录，请先登录。'], JSON_UNESCAPED_UNICODE);
  header("Location: ../pages/auth/login.php");
  exit;
} elseif ($user_role < $required_role) {
  echo json_encode(['error' => '权限不足，无法访问该页面。'], JSON_UNESCAPED_UNICODE);
  header("Location: ../index.php");
  exit;
}

// 引入数据库配置文件
require_once './config.php';

// 每秒请求5次限制
$limit = 5;
$interval = 1; // 秒

// 获取客户端IP地址
$ip = $_SERVER['REMOTE_ADDR'];

// 获取当前时间戳
$now = microtime(true);

// 检查请求次数是否超过限制
if (isset($_SESSION['requests'][$ip]) && $now - $_SESSION['requests'][$ip]['time'] < $interval) {
  $_SESSION['requests'][$ip]['count']++;
  if ($_SESSION['requests'][$ip]['count'] > $limit) {
    echo json_encode(['error' => '请求次数超过限制。'], JSON_UNESCAPED_UNICODE);
    exit;
  }
} else {
  $_SESSION['requests'][$ip] = ['time' => $now, 'count' => 1];
}

// 判断请求方法是否为POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $order_no = $_POST['order_no'] ?? ''; // 获取订单号
  $psn = $_POST['psn'] ?? ''; // 获取设备码
  $psw = $_POST['psw'] ?? ''; // 获取验证码

  // 验证输入的必填项
  if (empty($order_no) || empty($psn)) {
    echo json_encode(['error' => '请输入订单号和设备码。'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // 验证验证码
  if (empty($psw)) {
    echo json_encode(['error' => '请输入验证码。'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  if ($psw !== $_SESSION['captcha']) {
    echo json_encode(['error' => '验证码错误，请重新输入。'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // 连接到数据库
  $conn = new mysqli($servername, $db_user, $db_pass, $dbname);

  // 检查连接
  if ($conn->connect_error) {
    echo json_encode(['error' => '数据库连接失败。'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // 查询订单信息
  $sql = "SELECT downloads_limit, plan_id FROM xfp_order WHERE out_trade_no = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $order_no);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    echo json_encode(['error' => '订单号不存在。'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $order = $result->fetch_assoc();

  // 检查是否有剩余次数
  if ($order['downloads_limit'] <= 0) {
    echo json_encode(['error' => '剩余次数为零，无法生成解锁密码。'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // 减少剩余次数
  $sql = "UPDATE xfp_order SET downloads_limit = downloads_limit - 1 WHERE out_trade_no = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $order_no);
  if (!$stmt->execute()) {
    echo json_encode(['error' => '更新下载次数失败: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // 查询所有表盘ID（根据plan_id查询xfp_wflist表）
  $wf_sql = "SELECT watchface_id FROM xfp_wflist WHERE plan_id = ?";
  $wf_stmt = $conn->prepare($wf_sql);
  $wf_stmt->bind_param("s", $order['plan_id']);
  $wf_stmt->execute();
  $wf_result = $wf_stmt->get_result();

  if ($wf_result->num_rows === 0) {
    echo json_encode(['error' => '未找到匹配的表盘ID。'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $unlock_pwds = [];
  while ($wf = $wf_result->fetch_assoc()) {
    $wf_id = $wf['watchface_id'];
    $unlock_pwd = get_unlock_pwd($psn, $wf_id);
    $unlock_pwds[] = [
      'watchface_id' => $wf_id,
      'unlock_pwd' => $unlock_pwd
    ];
  }

  $response = [
    'unlock_pwds' => $unlock_pwds,
    'remaining' => $order['downloads_limit'] - 1
  ];

  // 如果用户已登录，则保存激活记录
  if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // 检查是否已经存在相同的激活记录
    $check_sql = "
        SELECT COUNT(*) as count
        FROM xfp_activation_records
        WHERE order_number = ? AND watchface_id = ? AND user_id = ? AND device_code = ? AND unlock_pwd = ?
    ";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("sssss", $order_no, $pwd['watchface_id'], $user_id, $psn, $pwd['unlock_pwd']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    $check_stmt->close();

    if ($check_row['count'] == 0) {
      // 插入激活记录
      $insert_sql = "
            INSERT INTO xfp_activation_records (order_number, watchface_id, user_id, device_code, unlock_pwd, activation_time)
            VALUES (?, ?, ?, ?, ?, NOW())
        ";
      $insert_stmt = $conn->prepare($insert_sql);
      foreach ($unlock_pwds as $pwd) {
        $insert_stmt->bind_param("sssss", $order_no, $pwd['watchface_id'], $user_id, $psn, $pwd['unlock_pwd']);
        if (!$insert_stmt->execute()) {
          echo json_encode(['error' => '保存激活记录失败: ' . $insert_stmt->error], JSON_UNESCAPED_UNICODE);
          exit;
        }
      }
      $insert_stmt->close();

      $response['activation_record_saved'] = true;
    } else {
      $response['activation_record_saved'] = false;
    }
  }


  // 关闭数据库连接
  $conn->close();

  echo json_encode($response, JSON_UNESCAPED_UNICODE);
} else {
  echo json_encode(['error' => '无效的请求方法。'], JSON_UNESCAPED_UNICODE);
}

// 生成解锁密码的函数
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

function get_unlock_pwd($psn, $wf_id)
{
  $text = $psn . "/data/app/watchface/market/" . $wf_id . "//_lua/app/";
  $sha256_hash = hash('sha256', $text);
  $last_six = substr($sha256_hash, -6);
  return convert_to_odd_numbers($last_six);
}
