<?php
echo "测试模式未开启！无法返回数据！";
exit;
// 你的用户ID
$user_id = "4de87174330b11efa11c52540025c377";

// 生成的API Token
$token = "ry6TcNt8qWRXxbkUVjHwYh5v34gQMJBm";

// 获取当前时间戳
$ts = time();

// 构建请求参数
$params = [
  "page" => 1 // 查询第一页的赞助者
];

// 按照要求的格式拼接字符串
$kv_string = "params" . json_encode($params) . "ts" . $ts . "user_id" . $user_id;

// 计算签名
$sign = md5($token . $kv_string);

// 构建请求数据
$request_data = [
  "user_id" => $user_id,
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
    // 查询成功
    $sponsors = $data['data']['list'];
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
  } else {
    // 查询失败
    echo '查询赞助者失败: ' . $data['em']; // 这里反馈了ec状态
  }
} else {
  // 请求失败
  echo '请求失败';
}
