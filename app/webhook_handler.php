<?php
// 引入数据库配置文件
require_once './config.php';

// 获取原始POST数据
$data = file_get_contents('php://input');

// 检查数据是否有效
if (!$data) {
  $response = [
    'ec' => 400,
    'em' => '无效的数据'
  ];
  echo json_encode($response, JSON_UNESCAPED_UNICODE);
  exit;
}

// 解析接收到的 JSON 数据
$data = json_decode($data, true);

// 验证解析后的数据结构
if (
  !isset($data['data']['type']) ||
  $data['data']['type'] !== 'order' ||
  !isset($data['data']['order'])
) {
  $response = [
    'ec' => 400,
    'em' => '数据格式错误'
  ];
  echo json_encode($response, JSON_UNESCAPED_UNICODE);
  exit;
}

// 获取订单数据
$order = $data['data']['order'];
$out_trade_no = $order['out_trade_no'];
$user_id = $order['user_id'];
$plan_id = $order['plan_id'];
$afdian_user_id = $order['user_private_id']; // 这里使用用户唯一标识符
$total_amount = $order['total_amount'];
$remark = $order['remark'];
$discount = $order['discount'];
$sku_detail = json_encode($order['sku_detail']);
$product_name = $order['sku_detail'][0]['name'] ?? '';
$downloads_limit = 1; // 默认设置为1

// 立即返回成功响应
$response = [
  'ec' => 200,
  'em' => ''
];
echo json_encode($response, JSON_UNESCAPED_UNICODE);

// 记录详细日志
$logFile = '../logs/order_processing_log.txt';
$logMessage = date('Y-m-d H:i:s') . " - 接收到订单数据: " . json_encode($order, JSON_UNESCAPED_UNICODE) . PHP_EOL;
file_put_contents($logFile, $logMessage, FILE_APPEND);

// 处理订单存储
try {
  // 连接到数据库
  $conn = new mysqli($servername, $db_user, $db_pass, $dbname);

  // 检查连接
  if ($conn->connect_error) {
    throw new Exception('数据库连接失败: ' . $conn->connect_error);
  }

  // 查询 plan_id 对应的用户ID
  $userSql = "SELECT user_id FROM xfp_wflist WHERE plan_id = ?";
  $userStmt = $conn->prepare($userSql);
  $userStmt->bind_param("s", $plan_id);
  $userStmt->execute();
  $userResult = $userStmt->get_result();

  if ($userResult->num_rows === 0) {
    // 未找到匹配的用户ID
    throw new Exception('未找到匹配的用户ID');
  }

  $userRow = $userResult->fetch_assoc();
  $system_user_id = $userRow['user_id'];

  // 检查订单是否已存在
  $checkSql = "SELECT id FROM xfp_order WHERE out_trade_no = ?";
  $stmt = $conn->prepare($checkSql);
  $stmt->bind_param("s", $out_trade_no);
  $stmt->execute();
  $checkResult = $stmt->get_result();

  if ($checkResult->num_rows > 0) {
    // 订单已存在，记录日志并退出
    $logMessage = date('Y-m-d H:i:s') . " - 订单号 $out_trade_no 已存在，未进行存储。" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    $conn->close();
    exit;
  }

  // 插入新订单
  $insertSql = "INSERT INTO xfp_order (
        out_trade_no, user_id, afdian_user_id, system_user_id, total_amount, remark, discount, sku_detail, product_name, plan_id, downloads_limit
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($insertSql);
  $stmt->bind_param(
    "ssssdsdsssi",
    $out_trade_no,
    $user_id,
    $afdian_user_id,
    $system_user_id,
    $total_amount,
    $remark,
    $discount,
    $sku_detail,
    $product_name,
    $plan_id,
    $downloads_limit
  );

  if (!$stmt->execute()) {
    throw new Exception('订单存储失败: ' . $stmt->error);
  }

  // 记录成功日志
  $logMessage = date('Y-m-d H:i:s') . " - 订单号 $out_trade_no 存储成功。" . PHP_EOL;
  file_put_contents($logFile, $logMessage, FILE_APPEND);

  // 关闭数据库连接
  $conn->close();
} catch (Exception $e) {
  // 记录错误日志
  $logMessage = date('Y-m-d H:i:s') . " - 处理订单时发生错误: " . $e->getMessage() . PHP_EOL;
  file_put_contents($logFile, $logMessage, FILE_APPEND);
}
