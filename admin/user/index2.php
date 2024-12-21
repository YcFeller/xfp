<?php
session_start();

$user_role = $_SESSION['user_role'] ?? null;
$required_role = 2;

if ($user_role === null) {
  header("Location: ../../pages/auth/login.php");
  exit;
} elseif ($user_role < $required_role) {
  header("Location: ../../index.php");
  exit;
}

require_once '../../app/config.php';

$user_id = $_SESSION['user_id'];

$conn = new mysqli($servername, $db_user, $db_pass, $dbname);
if ($conn->connect_error) {
  die("连接失败: " . $conn->connect_error);
}

$sql = "SELECT username, afdian_user_id, afdian_token, email, avatar_link FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
  die('用户信息未找到');
}

$conn->close();
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
          <a href="./index.php" class="nav-item !rounded-button text-gray-600 hover:text-custom">个人中心</a>
          <a href="./orderlist.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">订单中心</a>
          <a href="./facelist.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">我的表盘</a>
          <a href="./facelist_upload.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">表盘上传</a>
          <a href="./shortcut_tool.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">快捷工具</a>
          <a href="./afd_paylist.php" class="nav-item!rounded-button text-gray-600 hover:text-custom">订单获取</a>
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
          <img class="h-8 w-8 rounded-full" src="<?php echo htmlspecialchars($user['avatar_link']); ?>" alt="用户头像" />
        </div>
      </div>
    </div>
  </nav>

  <main class="max-w-7xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="flex items-start space-x-8 mb-8">
        <img class="w-32 h-32 rounded-lg object-cover" src="<?php echo htmlspecialchars($user['avatar_link']); ?>" alt="用户头像" />
        <div class="space-y-2">
          <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
          <p class="text-gray-600">用户ID:<?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
          <p class="text-custom font-medium">管理员权限</p>
        </div>
      </div>

      <form id="profileForm" class="space-y-6 max-w-2xl">
        <div class="space-y-2">
          <label class="block text-gray-700">邮箱地址</label>
          <input type="email" id="email" name="email" class="w-full px-4 py-2 border rounded-lg" value="<?php echo htmlspecialchars($user['email']); ?>" />
        </div>

        <div class="space-y-2">
          <label class="block text-gray-700">爱发电用户ID</label>
          <input type="text" id="afdian_user_id" name="afdian_user_id" class="w-full px-4 py-2 border rounded-lg" value="<?php echo htmlspecialchars($user['afdian_user_id']); ?>" />
        </div>

        <div class="space-y-2">
          <label class="block text-gray-700">爱发电Token</label>
          <input type="password" id="afdian_token" name="afdian_token" class="w-full px-4 py-2 border rounded-lg" value="<?php echo htmlspecialchars($user['afdian_token']); ?>" />
        </div>

        <button class="!rounded-button bg-custom text-white px-6 py-2 hover:bg-custom/90">保存修改</button>
      </form>
      <div id="message" class="message"></div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-bold mb-4">快速教程</h3>
      <div class="flex flex-wrap gap-4">
        <button class="!rounded-button flex items-center space-x-2 px-4 py-2 bg-gray-100 hover:bg-gray-200">
          <i class="fas fa-book"></i>
          <span>新手入门</span>
        </button>
        <button class="!rounded-button flex items-center space-x-2 px-4 py-2 bg-gray-100 hover:bg-gray-200">
          <i class="fas fa-upload"></i>
          <span>上传教程</span>
        </button>
        <button class="!rounded-button flex items-center space-x-2 px-4 py-2 bg-gray-100 hover:bg-gray-200">
          <i class="fas fa-tools"></i>
          <span>工具使用</span>
        </button>
        <button class="!rounded-button flex items-center space-x-2 px-4 py-2 bg-gray-100 hover:bg-gray-200">
          <i class="fas fa-question-circle"></i>
          <span>常见问题</span>
        </button>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-bold mb-4 flex items-center">
        <i class="fas fa-bullhorn mr-2 text-custom"></i>
        网站公告
      </h3>
      <div class="space-y-4 max-h-48 overflow-y-auto">
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-gray-600">【系统更新】XFP密码激活系统已完成升级维护，新增多项功能优化...</p>
          <p class="text-sm text-gray-400 mt-2">2024-01-20</p>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-gray-600">【重要通知】关于近期系统安全策略调整的说明...</p>
          <p class="text-sm text-gray-400 mt-2">2024-01-15</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-bold mb-4">免责声明</h3>
      <div class="p-4 bg-gray-50 rounded-lg text-gray-600">
        <p class="mb-2">1. 本系统仅供学习交流使用，请勿用于商业用途。</p>
        <p class="mb-2">2. 用户需遵守相关法律法规，对自己的行为负责。</p>
        <p class="mb-2">3. 系统运营方保留随时修改服务内容的权利。</p>
      </div>
    </div>
  </main>

  <script>
    $(document).ready(function() {
      $('#profileForm').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
          url: '../../app/userback_action.php',
          method: 'POST',
          data: formData,
          success: function(response) {
            $('#message').html('<p>' + response + '</p>');
          },
          error: function(xhr) {
            $('#message').html('<p>发生错误: ' + xhr.statusText + '</p>');
          }
        });
      });
    });
  </script>
</body>

</html>