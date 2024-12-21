<?php
session_start();

$user_role = $_SESSION['user_role'] ?? null;
$required_role = 2;

if ($user_role === null) {
  echo json_encode(['error' => '未登录，请先登录。'], JSON_UNESCAPED_UNICODE);
  exit;
} elseif ($user_role < $required_role) {
  echo json_encode(['error' => '权限不足，无法访问该页面。'], JSON_UNESCAPED_UNICODE);
  exit;
}

require_once './config.php';

// 获取提交的数据
$user_id = $_SESSION['user_id'];
$email = $_POST['email'];
$afdian_user_id = $_POST['afdian_user_id'];
$afdian_token = $_POST['afdian_token'];

// 检查邮箱格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['error' => '无效的邮箱地址'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 连接数据库
$conn = new mysqli($servername, $db_user, $db_pass, $dbname);
if ($conn->connect_error) {
  echo json_encode(['error' => '数据库连接失败: ' . $conn->connect_error], JSON_UNESCAPED_UNICODE);
  exit;
}

// 检查 afdian_user_id 是否已存在
$sql = "SELECT COUNT(*) FROM users WHERE afdian_user_id = ? AND id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $afdian_user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_array();

if ($row[0] > 0) {
  echo json_encode(['error' => '爱发电用户ID 已存在，请使用其他 ID。'], JSON_UNESCAPED_UNICODE);
  $conn->close();
  exit;
}

// 更新用户信息
$sql = "UPDATE users SET email = ?, afdian_user_id = ?, afdian_token = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssi', $email, $afdian_user_id, $afdian_token, $user_id);

if ($stmt->execute()) {
  echo json_encode(['success' => '信息已成功更新'], JSON_UNESCAPED_UNICODE);
} else {
  echo json_encode(['error' => '更新失败: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
}

$conn->close();
