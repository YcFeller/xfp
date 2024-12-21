<?php
session_start();
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

// 如果权限验证通过，继续执行后续代码
echo json_encode(['message' => '权限验证通过。'], JSON_UNESCAPED_UNICODE);
echo "当前用户角色：" . $user_role;
echo "需要角色：" . $required_role . "<br>";
// 1秒后跳转到用户中心
echo "1秒后跳转到用户中心...";
header("refresh:1;url=./user/index.php");
?>
<style>
  body {
    background-color: #f0f0f0;
    font-family: Arial, sans-serif;
    font-size: 50px;
    text-align: center;
    padding: 50px;
  }
</style>