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

require_once './config.php';

$order_number = $_POST['order_number'] ?? '';
$captcha = $_POST['captcha'] ?? '';

if (empty($order_number)) {
  echo json_encode(['error' => '订单号不能为空']);
  exit;
}

// 验证验证码
if (!isset($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
  echo json_encode(['error' => '验证码错误']);
  exit;
}

// 连接到数据库
$conn = new mysqli($servername, $db_user, $db_pass, $dbname);
if ($conn->connect_error) {
  die("数据库连接失败: " . $conn->connect_error);
}

// 首先查询订单信息以获取 plan_id
$sql_order = "
    SELECT o.plan_id, o.sku_detail, o.downloads_limit
    FROM xfp_order o
    WHERE o.out_trade_no = ?
";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("s", $order_number);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if ($result_order->num_rows === 0) {
  echo json_encode(['error' => '未找到订单信息']);
  exit;
}

$row_order = $result_order->fetch_assoc();
$plan_id = $row_order['plan_id'];
$sku_detail = json_decode($row_order['sku_detail'], true);
$downloads_limit = $row_order['downloads_limit'];

// 使用获取的 plan_id 查询表盘信息
$sql_watchface = "
    SELECT w.name as watchface_name, w.watchface_id, w.status, w.image_link
    FROM xfp_wflist w
    WHERE w.plan_id = ?
";
$stmt_watchface = $conn->prepare($sql_watchface);
$stmt_watchface->bind_param("s", $plan_id);
$stmt_watchface->execute();
$result_watchface = $stmt_watchface->get_result();

$watchfaces = [];
while ($row_watchface = $result_watchface->fetch_assoc()) {
  $watchfaces[] = [
    'watchface_name' => $row_watchface['watchface_name'],
    'watchface_image' => !empty($row_watchface['image_link']) ? $row_watchface['image_link'] : ($sku_detail[0]['pic'] ?? ''), // 优先使用 image_link，否则使用 sku_detail
    'status' => $row_watchface['status']
  ];
}

$data = [
  'watchfaces' => $watchfaces,
  'downloads_limit' => $downloads_limit,
];

echo json_encode($data);

$stmt_order->close();
$stmt_watchface->close();
$conn->close();
