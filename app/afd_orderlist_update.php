<?php
session_start();

// 引入数据库配置信息
require_once './config.php';

// 检查计划任务访问的身份验证（可选）
$cron_secret = 'cnm666'; // 请将其替换为您的计划任务安全密钥
if (!isset($_GET['secret']) || $_GET['secret'] !== $cron_secret) {
  die("Unauthorized access.");
}

// 连接到数据库
try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $db_user, $db_pass);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("数据库连接失败: " . $e->getMessage());
}

// 获取所有需要更新订单的用户
$users_query = $conn->prepare("SELECT id, afdian_user_id, afdian_token FROM users WHERE role >= 2");
$users_query->execute();
$users = $users_query->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
  echo "没有需要更新的用户。<br>";
  exit;
}

foreach ($users as $user) {
  $system_user_id = $user['id'];
  $afdian_user_id = $user['afdian_user_id'];
  $afdian_token = $user['afdian_token'];

  if (!$afdian_user_id || !$afdian_token) {
    echo "用户 {$system_user_id} 缺少 afdian_user_id 或 afdian_token，跳过。<br>";
    continue;
  }

  $ts = time();
  $page = 1;
  $sponsors = [];
  $list_count = 1;

  do {
    $params = ["page" => $page];
    $kv_string = "params" . json_encode($params) . "ts" . $ts . "user_id" . $afdian_user_id;
    $sign = md5($afdian_token . $kv_string);
    $request_data = [
      "user_id" => $afdian_user_id,
      "params" => json_encode($params),
      "ts" => $ts,
      "sign" => $sign
    ];
    $json_data = json_encode($request_data);

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "https://afdian.com/api/open/query-order",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $json_data,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data)
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    if ($response) {
      $data = json_decode($response, true);
      if (isset($data['ec']) && $data['ec'] == 200) {
        $page_sponsors = $data['data']['list'];
        if (empty($page_sponsors)) break;
        $sponsors = array_merge($sponsors, $page_sponsors);
      } else {
        echo "用户 {$system_user_id} 查询订单失败: " . $data['em'] . "<br>";
        break;
      }
    } else {
      echo "用户 {$system_user_id} 请求订单数据失败<br>";
      break;
    }

    $page++;
  } while (true);

  if (!empty($sponsors)) {
    $insert_stmt = $conn->prepare(
      "INSERT INTO xfp_order (out_trade_no, user_id, afdian_user_id, system_user_id, total_amount, remark, discount, sku_detail, product_name, plan_id)
             VALUES (:out_trade_no, :user_id, :afdian_user_id, :system_user_id, :total_amount, :remark, :discount, :sku_detail, :product_name, :plan_id)
             ON DUPLICATE KEY UPDATE 
             total_amount = VALUES(total_amount), 
             remark = VALUES(remark), 
             discount = VALUES(discount), 
             sku_detail = VALUES(sku_detail), 
             product_name = VALUES(product_name), 
             plan_id = VALUES(plan_id)"
    );

    foreach ($sponsors as $sponsor) {
      $sku_detail = json_encode($sponsor['sku_detail']);
      $product_name = $sponsor['product_name'] ?? '';

      try {
        $insert_stmt->execute([
          ':out_trade_no' => $sponsor['out_trade_no'],
          ':user_id' => $sponsor['user_id'],
          ':afdian_user_id' => $afdian_user_id,
          ':system_user_id' => $system_user_id,
          ':total_amount' => $sponsor['total_amount'],
          ':remark' => $sponsor['remark'],
          ':discount' => $sponsor['discount'],
          ':sku_detail' => $sku_detail,
          ':product_name' => $product_name,
          ':plan_id' => $sponsor['plan_id']
        ]);
        echo "用户 {$system_user_id} 的第" . $list_count++ . "条数据插入/更新成功！<br>";
      } catch (PDOException $e) {
        echo "用户 {$system_user_id} 的第" . $list_count++ . "条数据插入/更新失败: " . $e->getMessage() . "<br>";
      }
    }

    echo "用户 {$system_user_id} 总共处理 " . ($list_count - 1) . " 条数据！<br>";
  }
}

// 关闭数据库连接
$conn = null;
