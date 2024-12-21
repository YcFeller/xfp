<!-- 快捷工具页面 -->
<?php
session_start();
// 检查用户是否已登录
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
  <title>解锁密码生成</title>
  <link rel="stylesheet" href="../../files/css/shortcut_tool.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script>
    function generateUnlockPassword() {
      const psn = document.getElementById('psn').value;
      const wfId = document.getElementById('wfId').value;
      const psw = document.getElementById('psw').value;

      // 检查输入是否为空
      if (!psn || !wfId || !psw) {
        alert('请填写所有必填项。');
        return;
      }

      // 发送 AJAX 请求到后端
      fetch('../../app/shortcut_tool_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `psn=${encodeURIComponent(psn)}&wfId=${encodeURIComponent(wfId)}&psw=${encodeURIComponent(psw)}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            document.getElementById('result').innerHTML = `<p style="color:red;">${data.error}</p>`;
          } else {
            document.getElementById('result').innerHTML = `<p>解锁密码: <strong>${data.unlock_pwd}</strong></p>`;
          }
        })
        .catch(error => {
          console.error('请求失败:', error);
          document.getElementById('result').innerHTML = `<p style="color:red;">请求失败，请稍后重试。</p>`;
        });
    }
  </script>
</head>

<body>
  <div class="form-container">
    <h1>解锁密码生成/API</h1>
    <p class="intro">
      Watchface Locker专用，PHP源码请群内获取~
      <br>
      <span style="color: red;">注意，此网页仅供内部使用，请勿传播！</span>
      <br>
    <p>用户名：<? echo $_SESSION['username']; ?></p>
    <p>用户ID：<? echo $_SESSION['user_id']; ?></p>
    <p>用户权限：<? echo $_SESSION['user_role']; ?></p>
    <br>

    </p>
    <!-- 表单，用于输入设备码和表盘ID -->
    <?php
    if (isset($indexlock)) {
      echo '<hr><p style="color: red; font-weight: 700; font-size:18px;">请先登录！登录后解锁全部功能！</p>';
      echo '<p style="color: red; font-weight: 700; font-size:18px;">为了防止滥用，当前需要注册使用！</p>';
      echo '<p style="color: red; font-weight: 700; font-size:18px;">当前采用白名单注册，请联系使用！</p><hr><br>';
    } else {
      echo '
      <form onsubmit="event.preventDefault(); generateUnlockPassword();">
      <label for="psn">设备码:</label>
      <input type="text" id="psn" name="psn" placeholder="您的设备码" required>
      <br>
      <label for="wfId">表盘ID:</label>
      <input type="text" id="wfId" name="wfId" placeholder="表盘ID" required>
      <br>
      <label for="psw">内测验证码（废弃）现仅api需要:</label>
      <input type="text" id="psw" name="psw" placeholder="登录后可用" value="xr1688s" required>
      <br>
      <input type="submit" value="生成解锁密码" class="index-button">
    </form>
    <button id="toggleSidebar" class="index-button">显示/隐藏 API使用方式</button>';
    }
    ?>
    <?php
    if (!isset($_SESSION['user_id'])) {
      echo '<a href="../../pages/auth/login.php" class="index-button" style="margin-right:10px;">登录</a>';
    } else {
      echo '<a href="./index.php" class="index-button">回到个人中心</a>';
    }
    ?>

  </div>


  <div id="result">
    <p style="font-weight: 700; font-size:18px;">点击“生成解锁密码”按钮后将会在这里输出结果!</p>
  </div>

  <div class="sidebar" style="display: none;">
    <div class="box">
      <h2>api请求示例：</h2>
      <div class="box_demo">
        <h3>GET请求:</h3>
        <p>GET请求用于从服务器获取数据。它通常用于查询或检索数据。</p>
        <p>在URL中添加参数，参数以`?`开头，每个参数之间用`&`分隔。</p>
        <p>格式：`<span>https://api.fs0.top/?psn=<span class="demoshow">your_psn</span>&wfId=<span class="demoshow">your_wfId</span>&psw=<span class="demoshow">测试密码</span></span>`</p>
        <p>访问体验：<a href="https://api.fs0.top/wflocker/api.php?psn=123&wfId=123&psw=请先获取">https://api.fs0.top/wflocker/api.php?psn=123&wfId=123&psw=请先获取</a></p>
      </div>

      <div class="box_demo">
        <h3>POST请求:</h3>
        <p>POST请求用于向服务器发送数据。它通常用于提交表单数据或上传文件。</p>
        <p>使用`curl`命令时，使用`-d`选项来指定要发送的数据。</p>
        <p>例如：`<span>curl -X POST -d "psn=your_psn&wfId=your_wfId&psw=123456" https://api.fs0.top/</span>`</p>
      </div>

      <p>懒得整了，手机覆盖了刷新就行了</p>

    </div>
  </div>

  <script>
    /*侧边栏相关*/
    document.getElementById('toggleSidebar').addEventListener('click', function() {
      var sidebar = document.querySelector('.sidebar');
      if (sidebar.style.display === 'none') {
        sidebar.style.display = 'block';
      } else {
        sidebar.style.display = 'none';
      }
    });
  </script>

</body>

</html>