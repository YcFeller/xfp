<?php
session_start();

// 引入数据库配置文件
require_once '../../app/config.php';

// 验证用户是否登录
$user_role = $_SESSION['user_role'] ?? null;
$required_role = 1;
if ($user_role === null) {
  echo json_encode(['error' => '未登录，请先登录。'], JSON_UNESCAPED_UNICODE);
  header("Location: ../../pages/auth/login.php");
  exit;
} elseif ($user_role < $required_role) {
  echo json_encode(['error' => '权限不足，无法访问该页面。'], JSON_UNESCAPED_UNICODE);
  header("Location: ../../index.php");
  exit;
}

// 连接到数据库
$conn = new mysqli($servername, $db_user, $db_pass, $dbname);

// 检查连接
if ($conn->connect_error) {
  echo json_encode(['error' => '数据库连接失败。'], JSON_UNESCAPED_UNICODE);
  exit;
}

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 获取搜索订单号（如果有）
$order_no_search = $_GET['order_no'] ?? '';

// 设置每页显示的记录数
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// 查询激活记录
$sql = "
    SELECT * 
    FROM xfp_activation_records 
    WHERE user_id = ?
    " . (!empty($order_no_search) ? " AND order_number LIKE ?" : "") . "
    ORDER BY activation_time DESC
    LIMIT ?, ?
";
$stmt = $conn->prepare($sql);

if (!empty($order_no_search)) {
  $search_term = '%' . $order_no_search . '%';
  $stmt->bind_param("ssii", $user_id, $search_term, $offset, $records_per_page);
} else {
  $stmt->bind_param("sii", $user_id, $offset, $records_per_page);
}

$stmt->execute();
$result = $stmt->get_result();

// 获取总记录数
$count_sql = "SELECT COUNT(*) AS total FROM xfp_activation_records WHERE user_id = ?" . (!empty($order_no_search) ? " AND order_number LIKE ?" : "");
$count_stmt = $conn->prepare($count_sql);
if (!empty($order_no_search)) {
  $count_stmt->bind_param("s", $search_term);
} else {
  $count_stmt->bind_param("s", $user_id);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];

// 计算总页数
$total_pages = ceil($total_records / $records_per_page);

// 关闭数据库连接
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>XFP-激活记录</title>
  <link rel="stylesheet" href="../../../files/css/activation_records.css">
</head>

<body>
  <div class="container">
    <h1 class="title">我的激活记录</h1>
    <form class="search-form" method="GET" action="">
      <label for="order_no" class="label">订单号:</label>
      <input type="text" id="order_no" name="order_no" value="<?php echo htmlspecialchars($order_no_search); ?>" class="input">
      <button type="submit" class="button">搜索</button><br>
      <a href="../../../index.php" class="button">返回首页</a>
    </form>

    <table class="table">
      <thead class="table-header">
        <tr>
          <th class="table-header-cell">ID</th>
          <th class="table-header-cell">订单号</th>
          <th class="table-header-cell">用户ID</th>
          <th class="table-header-cell">设备码</th>
          <th class="table-header-cell">解锁密码</th>
          <th class="table-header-cell">激活时间</th>
        </tr>
      </thead>
      <tbody class="table-body">
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="table-row">
              <td class="table-cell"><?php echo htmlspecialchars($row['id']); ?></td>
              <td class="table-cell"><?php echo htmlspecialchars($row['order_number']); ?></td>
              <td class="table-cell"><?php echo htmlspecialchars($row['user_id']); ?></td>
              <td class="table-cell"><?php echo htmlspecialchars($row['device_code']); ?></td>
              <td class="table-cell"><?php echo htmlspecialchars($row['unlock_pwd']); ?></td>
              <td class="table-cell"><?php echo htmlspecialchars($row['activation_time']); ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr class="table-row">
            <td colspan="7" class="table-cell">没有找到记录。</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&order_no=<?php echo htmlspecialchars($order_no_search); ?>" class="pagination-link">上一页</a>
      <?php endif; ?>

      <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>&order_no=<?php echo htmlspecialchars($order_no_search); ?>" class="pagination-link">下一页</a>
      <?php endif; ?>
    </div>
  </div>
</body>

</html>