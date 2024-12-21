<?php
session_start();
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

// 引入数据库信息
require_once '../../app/config.php';

// 获取系统用户ID（从session中获取）
$system_user_id = $_SESSION['user_id'] ?? null;

if (!$system_user_id) {
  die("用户未登录");
}

// 连接到数据库
$conn = new mysqli($servername, $db_user, $db_pass, $dbname);

// 检查连接
if ($conn->connect_error) {
  die("连接失败: " . $conn->connect_error);
}

// 从数据库中调取 afdian_user_id 和 afdian_token
$stmt = $conn->prepare("SELECT afdian_user_id, afdian_token FROM users WHERE id = ?");
$stmt->bind_param("i", $system_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  die("用户数据未找到");
}

$user_data = $result->fetch_assoc();
$afdian_user_id = $user_data['afdian_user_id'];
$afdian_token = $user_data['afdian_token'];

// 获取当前时间戳
$ts = time();
$page = 1;
$list_count = 1; //计数
$sponsors = [];

do {
  // 构建请求参数
  $params = [
    "page" => $page
  ];

  // 按照要求的格式拼接字符串
  $kv_string = "params" . json_encode($params) . "ts" . $ts . "user_id" . $afdian_user_id;

  // 计算签名
  $sign = md5($afdian_token . $kv_string);

  // 构建请求数据
  $request_data = [
    "user_id" => $afdian_user_id,
    "params" => json_encode($params),
    "ts" => $ts,
    "sign" => $sign
  ];

  // 转换为 JSON 格式
  $json_data = json_encode($request_data);

  // 设置请求的 URL
  $url = "https://afdian.com/api/open/query-order";

  // 初始化 cURL
  $curl = curl_init();

  // 设置 cURL 选项
  curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $json_data,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Content-Length: ' . strlen($json_data)
    ],
  ]);

  // 发送请求并获取响应
  $response = curl_exec($curl);

  // 关闭 cURL 资源
  curl_close($curl);

  // 处理响应数据
  if ($response) {
    $data = json_decode($response, true);

    if (isset($data['ec']) && $data['ec'] == 200) {
      $page_sponsors = $data['data']['list'];

      if (empty($page_sponsors)) {
        break;
      }

      $sponsors = array_merge($sponsors, $page_sponsors);
    } else {
      echo '查询赞助者失败: ' . $data['em'];
      break;
    }
  } else {
    echo '请求失败';
    break;
  }

  $page++;
} while (true);

// 输出赞助者信息
echo '<h1>赞助者列表</h1>';
foreach ($sponsors as $sponsor) {
  echo '<p>用户ID: ' . $sponsor['user_id'] . '</p>';
  echo '<p>OUT订单号: ' . $sponsor['out_trade_no'] . '</p>';
  echo '<p>自定义订单ID: ' . $sponsor['custom_order_id'] . '</p>';
  echo '<p>计划ID: ' . $sponsor['plan_id'] . '</p>';
  echo '<p>月数: ' . $sponsor['month'] . '</p>';
  echo '<p>总金额: ' . $sponsor['total_amount'] . '</p>';
  echo '<p>展示金额: ' . $sponsor['show_amount'] . '</p>';
  echo '<p>状态: ' . $sponsor['status'] . '</p>';
  echo '<p>备注: ' . $sponsor['remark'] . '</p>';
  echo '<p>兑换ID: ' . $sponsor['redeem_id'] . '</p>';
  echo '<p>产品类型: ' . $sponsor['product_type'] . '</p>';
  echo '<p>折扣: ' . $sponsor['discount'] . '</p>';
  echo '<p>SKU详情:</p>';
  foreach ($sponsor['sku_detail'] as $sku) {
    echo '<p>SKU ID: ' . $sku['sku_id'] . '</p>';
    echo '<p>数量: ' . $sku['count'] . '</p>';
    echo '<p>名称: ' . $sku['name'] . '</p>';
    echo '<p>相册ID: ' . $sku['album_id'] . '</p>';
    echo '<p>图片: ' . $sku['pic'] . '</p>';
  }
  echo '<p>收货人: ' . $sponsor['address_person'] . '</p>';
  echo '<p>联系电话: ' . $sponsor['address_phone'] . '</p>';
  echo '<p>收货地址: ' . $sponsor['address_address'] . '</p>';
  echo '<hr>';
}

// 预处理 SQL 语句
$insert_stmt = $conn->prepare(
  "INSERT INTO xfp_order (out_trade_no, user_id, afdian_user_id, system_user_id, total_amount, remark, discount, sku_detail, product_name, plan_id) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
     ON DUPLICATE KEY UPDATE 
     total_amount = VALUES(total_amount), 
     remark = VALUES(remark), 
     discount = VALUES(discount), 
     sku_detail = VALUES(sku_detail), 
     product_name = VALUES(product_name), 
     plan_id = VALUES(plan_id)"
);

if (!$insert_stmt) {
  die("预处理语句错误: " . $conn->error);
}

foreach ($sponsors as $sponsor) {
  $sku_detail = json_encode($sponsor['sku_detail']);
  $product_name = $sponsor['product_name'] ?? '';

  // 插入数据
  $success = $insert_stmt->bind_param(
    "ssssssssss", // 应该有10个参数对应绑定变量
    $sponsor['out_trade_no'],
    $sponsor['user_id'],
    $afdian_user_id,
    $system_user_id,
    $sponsor['total_amount'],
    $sponsor['remark'],
    $sponsor['discount'],
    $sku_detail,
    $product_name,
    $sponsor['plan_id']
  ) && $insert_stmt->execute();

  if ($success) {
    echo "第" . $list_count++ . "条";
    echo "数据插入/更新成功！<br>";
  } else {
    echo "第" . $list_count++ . "条";
    echo "数据插入/更新失败: " . $insert_stmt->error . "<br>";
  }
}

echo "总共处理" . $list_count-- . "条数据！";

// 关闭预处理语句和数据库连接
$insert_stmt->close();
$conn->close();
?>
<link rel="stylesheet" href="../../files/css/afd_paylist.css">