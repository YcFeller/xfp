<?php
session_start();

// 验证用户是否已登录
$user_role = $_SESSION['user_role'] ?? null;
$required_role = 2;
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

$user_id = $_SESSION['user_id'];

// 连接数据库
$conn = new mysqli($servername, $db_user, $db_pass, $dbname);

// 检查连接
if ($conn->connect_error) {
  die("连接失败: " . $conn->connect_error);
}

// 根据请求操作执行相应的函数
$action = $_POST['action'] ?? '';

switch ($action) {
  case 'fetch':
    fetchOrders($conn, $user_id);
    break;
  case 'get':
    getOrder($conn);
    break;
  case 'update':
    updateOrder($conn);
    break;
  case 'batch_update':
    batchUpdateOrders($conn);
    break;
  case 'get_plans':
    getPlans($conn);
    break;
  default:
    echo '无效操作';
    break;
}

$conn->close();

// 获取订单列表
function fetchOrders($conn, $user_id)
{
  $query = $_POST['query'] ?? '';
  $planId = $_POST['plan_id'] ?? '';
  $sql = "SELECT * FROM xfp_order WHERE system_user_id = ? AND out_trade_no LIKE ?";
  $params = [$user_id, "%$query%"];
  $types = 'is';

  if ($planId !== '') {
    $sql .= " AND plan_id = ?";
    $params[] = $planId;
    $types .= 's';
  }

  $sql .= " ORDER BY out_trade_no DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();

  $output = '';
  while ($row = $result->fetch_assoc()) {
    // 解码 SKU 详情 JSON 数据
    $skuDetails = json_decode($row['sku_detail'], true);
    $skuOutput = '<ul>';
    foreach ($skuDetails as $sku) {
      $skuOutput .= '<li>';
      $skuOutput .= 'SKU ID: ' . htmlspecialchars($sku['sku_id']) . '<br>';
      $skuOutput .= '价格: ' . htmlspecialchars($sku['price']) . '<br>';
      $skuOutput .= '数量: ' . htmlspecialchars($sku['count']) . '<br>';
      $skuOutput .= '名称: ' . htmlspecialchars($sku['name']) . '<br>';
      $skuOutput .= '图片: <img src="' . htmlspecialchars($sku['pic']) . '" alt="SKU 图片" class="sku-image"><br>';
      $skuOutput .= '</li>';
    }
    $skuOutput .= '</ul>';

    $output .= '<tr>';
    $output .= '<td><input type="checkbox" data-order-id="' . htmlspecialchars($row['out_trade_no']) . '"></td>';
    $output .= '<td>' . htmlspecialchars($row['out_trade_no']) . '</td>';
    $output .= '<td>' . htmlspecialchars($row['sponsor_user_id']) . '</td>';
    $output .= '<td>' . htmlspecialchars($row['afdian_user_id']) . '</td>';
    $output .= '<td>' . htmlspecialchars($row['system_user_id']) . '</td>';
    $output .= '<td>' . $row['total_amount'] . '</td>';
    $output .= '<td>' . $row['downloads_limit'] . '</td>';
    $output .= '<td>' . $skuOutput . '</td>'; // 显示 SKU 详情
    $output .= '<td>' . htmlspecialchars($row['plan_id']) . '</td>';
    $output .= '<td><button class="editBtn" data-order-id="' . htmlspecialchars($row['out_trade_no']) . '">编辑</button></td>';
    $output .= '</tr>';
  }

  echo $output;
}

// 获取单个订单信息
function getOrder($conn)
{
  $orderId = $_POST['order_id'];
  $sql = "SELECT * FROM xfp_order WHERE out_trade_no = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('s', $orderId);
  $stmt->execute();
  $result = $stmt->get_result();
  $order = $result->fetch_assoc();

  echo json_encode($order);
}

// 更新订单信息（主要是下载限制）
function updateOrder($conn)
{
  $orderId = $_POST['order_id'];
  $downloadsLimit = $_POST['downloads_limit'];

  $sql = "UPDATE xfp_order SET downloads_limit = ? WHERE out_trade_no = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('is', $downloadsLimit, $orderId);

  if ($stmt->execute()) {
    echo '订单信息已更新';
  } else {
    echo '更新失败';
  }
}

// 批量更新订单信息
function batchUpdateOrders($conn)
{
  $orderIds = $_POST['order_ids'] ?? [];
  $downloadsLimit = $_POST['downloads_limit'];

  if (empty($orderIds)) {
    echo '没有选择任何订单';
    return;
  }

  $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
  $sql = "UPDATE xfp_order SET downloads_limit = ? WHERE out_trade_no IN ($placeholders)";
  $stmt = $conn->prepare($sql);

  $params = array_merge([$downloadsLimit], $orderIds);
  $types = str_repeat('s', count($orderIds)) . 'i'; // 参数类型字符串

  $stmt->bind_param($types, ...$params);

  if ($stmt->execute()) {
    echo '批量更新成功';
  } else {
    echo '批量更新失败';
  }
}

// 获取计划ID选项
function getPlans($conn)
{
  $user_id = $_SESSION['user_id'];
  $sql = "SELECT DISTINCT plan_id FROM xfp_order WHERE system_user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $plans = [];
  while ($row = $result->fetch_assoc()) {
    $plans[] = $row;
  }

  echo json_encode($plans);
}
