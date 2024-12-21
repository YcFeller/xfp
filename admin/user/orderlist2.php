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
<html lang="zh">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    rel="stylesheet" />
  <link
    href="https://ai-public.mastergo.com/gen_page/tailwind-custom.css"
    rel="stylesheet" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../files/css/orderlist.css">

  <script src="https://cdn.tailwindcss.com/3.4.5?plugins=forms@0.5.7,typography@0.5.13,aspect-ratio@0.4.2,container-queries@0.1.1"></script>
  <script
    src="https://ai-public.mastergo.com/gen_page/tailwind-config.min.js"
    data-color="#000000"
    data-border-radius="small"></script>

  <script src="../../files/js/jquery-3.6.0.min.js"></script>

</head>

<body class="bg-gray-100">
  <nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <div class="font-['Pacifico'] text-2xl text-custom">XFP-秘钥查询系统</div>
        </div>

        <div class="flex items-center space-x-8">
          <a href="./index2.php" class="nav-item !rounded-button text-gray-600 hover:text-custom">个人中心</a>
          <a href="./orderlist2.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">订单中心</a>
          <a href="./facelist2.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">我的表盘</a>
          <a href="./facelist_upload2.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">表盘上传</a>
          <a href="./shortcut_tool2.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">快捷工具</a>
          <a href="./afd_paylist2.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">订单获取</a>
          <a href="../../" class="nav-item!rounded-button text-gray-600 hover:text-custom">返回首页</a>
          <a href="../../pages/auth/logout.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">退出登录</a>

        </div>

        <div class="flex items-center space-x-4">
          <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
          <span class="text-custom px-2 py-1 rounded-full bg-custom/10 text-sm">
            <?php
            if ($_SESSION['user_role'] == 1) {
              echo "客户";
            } elseif ($_SESSION['user_role'] == 2) {
              echo "用户";
            } elseif ($_SESSION['user_role'] == 3) {
              echo "管理员";
            } else {
              echo "你从那你来？又到那里去？";
            }
            ?>
          </span>
          <!-- <img class="h-8 w-8 rounded-full" src="<?php echo htmlspecialchars($user['avatar_link']); ?>" alt="用户头像" /> -->
        </div>
      </div>
    </div>
  </nav>

  <br>

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