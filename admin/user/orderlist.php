<?php
session_start();
require_once '../../app/config.php';

// 验证用户是否已登录
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
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>订单列表管理</title>
  <link rel="stylesheet" href="../../files/css/orderlist.css">
  <script src="../../files/js/jquery-3.6.0.min.js"></script>
  <style>
    img {
      height: 100px;
    }
  </style>
</head>

<body>
  <div class="container">
    <h1 class="title">订单列表管理</h1>
    <div class="search-bar">
      <input type="text" id="search" placeholder="搜索订单...">
      <button id="searchBtn">搜索</button>
    </div>
    <div class="filter-bar">
      <label for="planFilter">筛选计划ID:</label>
      <select id="planFilter">
        <option value="">所有计划</option>
        <!-- 计划ID选项将在这里生成 -->
      </select>
    </div>
    <div class="batch-actions">
      <label for="batchDownloadsLimit">批量修改下载限制:</label>
      <input type="number" id="batchDownloadsLimit" required>
      <button id="batchUpdateBtn">保存批量修改</button>
    </div>
    <table id="orderTable" class="table">
      <thead>
        <tr>
          <th><input type="checkbox" id="selectAll"> 全选</th>
          <th>订单号</th>
          <th>赞助者用户ID</th>
          <th>爱发电用户ID</th>
          <th>系统用户ID</th>
          <th>总金额</th>
          <th>下载限制</th>
          <th>SKU详情</th>
          <th>计划ID</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <!-- 订单数据将在这里显示 -->
      </tbody>
    </table>
  </div>

  <!-- 修改下载限制弹窗 -->
  <div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
      <h2 class="modal-title">编辑下载限制</h2>
      <form id="editForm">
        <input type="hidden" id="editOrderId">
        <label for="editDownloadsLimit">下载限制:</label>
        <input type="number" id="editDownloadsLimit" required>
        <button type="submit">保存修改</button>
      </form>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      // 加载订单数据
      function loadOrders(query = '', planId = '') {
        $.ajax({
          url: '../../app/orderlist_actions.php',
          method: 'POST',
          data: {
            action: 'fetch',
            query: query,
            plan_id: planId
          },
          success: function(data) {
            $('#orderTable tbody').html(data);
          }
        });
      }

      // 加载计划ID选项
      function loadPlans() {
        $.ajax({
          url: '../../app/orderlist_actions.php',
          method: 'POST',
          data: {
            action: 'get_plans'
          },
          success: function(data) {
            var plans = JSON.parse(data);
            var options = '<option value="">所有计划</option>';
            plans.forEach(function(plan) {
              options += '<option value="' + plan.plan_id + '">' + plan.plan_id + '</option>';
            });
            $('#planFilter').html(options);
          }
        });
      }

      // 初始化
      loadOrders();
      loadPlans();

      // 搜索功能
      $('#searchBtn').on('click', function() {
        var query = $('#search').val();
        var planId = $('#planFilter').val();
        loadOrders(query, planId);
      });

      // 计划ID筛选
      $('#planFilter').on('change', function() {
        var query = $('#search').val();
        var planId = $(this).val();
        loadOrders(query, planId);
      });

      // 编辑下载限制
      $(document).on('click', '.editBtn', function() {
        var orderId = $(this).data('order-id');
        $.ajax({
          url: '../../app/orderlist_actions.php',
          method: 'POST',
          data: {
            action: 'get',
            order_id: orderId
          },
          success: function(data) {
            var order = JSON.parse(data);
            $('#editOrderId').val(order.out_trade_no);
            $('#editDownloadsLimit').val(order.downloads_limit);
            $('#editModal').show();
          }
        });
      });

      // 保存修改
      $('#editForm').on('submit', function(e) {
        e.preventDefault();
        var orderId = $('#editOrderId').val();
        var downloadsLimit = $('#editDownloadsLimit').val();
        $.ajax({
          url: '../../app/orderlist_actions.php',
          method: 'POST',
          data: {
            action: 'update',
            order_id: orderId,
            downloads_limit: downloadsLimit
          },
          success: function(response) {
            alert(response);
            $('#editModal').hide();
            loadOrders();
          }
        });
      });

      // 批量修改下载限制
      $('#batchUpdateBtn').on('click', function() {
        var downloadsLimit = $('#batchDownloadsLimit').val();
        var selectedOrders = [];
        $('#orderTable tbody input[type="checkbox"]:checked').each(function() {
          selectedOrders.push($(this).data('order-id'));
        });

        if (selectedOrders.length === 0) {
          alert('请先选择要修改的订单');
          return;
        }

        $.ajax({
          url: '../../app/orderlist_actions.php',
          method: 'POST',
          data: {
            action: 'batch_update',
            order_ids: selectedOrders,
            downloads_limit: downloadsLimit
          },
          success: function(response) {
            alert(response);
            loadOrders();
          }
        });
      });

      // 全选/全不选
      $('#selectAll').on('click', function() {
        var isChecked = $(this).prop('checked');
        $('#orderTable tbody input[type="checkbox"]').prop('checked', isChecked);
      });
    });
  </script>
</body>

</html>