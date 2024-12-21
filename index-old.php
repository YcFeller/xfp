<?php
echo "测试模式未开启！无法返回数据！";
exit;
session_start();
echo '<div id="loading_p" style="display:block;text-align:center;"><img src="./files/imgs/loading.gif" title="加载中"><h2>稍后就好(*^▽^*)<br>第一次加载会比较慢</h2></div>';
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8">
  <title>XFP-密码查询页</title>
  <link rel="stylesheet" href="./files/css/index.css">
  <script src="./files/js/jquery-3.6.0.min.js"></script>
  <!-- <script src="./files/js/websafe.js"></script> -->
  <script>
    // 验证码点击刷新功能
    function refreshCaptcha() {
      var captchaImage = document.getElementById('captcha-image');
      captchaImage.src = './app/captcha.php?' + new Date().getTime();
    }

    function refreshCaptcha2() {
      var captchaImage = document.getElementById('captcha-image2');
      captchaImage.src = './app/captcha.php?' + new Date().getTime();
    }

    $(document).ready(function() {
      // 更新验证码图像

      function updateCaptcha() {
        $('#captcha-image').attr('src', './app/captcha.php?' + new Date().getTime());
      }

      // 页面加载时更新验证码
      updateCaptcha();

      //设置id=loading_p的display为none
      $('#loading_p').css('display', 'none');


      $('#search-form').submit(function(event) {
        event.preventDefault();
        let orderNumber = $('#order-number').val();
        let captcha = $('#captcha-input').val();
        $.ajax({
          url: './app/inquire_api.php',
          type: 'POST',
          data: {
            order_number: orderNumber,
            captcha: captcha
          },
          success: function(response) {
            let data = JSON.parse(response);
            if (data.error) {
              alert(data.error);
              if (data.error === '验证码错误') {
                updateCaptcha(); // 更新验证码
              }
            } else {
              $('#results').html(`
                <div class="result-section">
                  <h3 class="result-title">查询结果</h3>
                  <p class="result-item">表盘名称: ${data.watchface_name}</p>
                  <p class="result-item">表盘图片: <img src="${data.watchface_image}" alt="表盘图片" class="watchface-image"></p>
                  <p class="result-item">剩余激活次数: <b>${data.downloads_limit}</b></p>
                  <form id="unlock-form" class="unlock-form">
                    <input type="hidden" name="order_no" value="${orderNumber}">
                    <label for="captcha" class="form-label">验证码：</label>
                    <input type="text" id="verification_code" name="psw" placeholder="点击下方图片可刷新" class="form-input" required>
                    <img id="captcha-image2" src="./app/captcha.php" alt="验证码" class="index-captcha" onclick="refreshCaptcha2()">
                    <label for="device_code" class="form-label">设备码:（注意大小写！）</label>
                    <input type="text" id="device_code" name="psn" class="form-input" required>
                    <p style="text-align: center; font-weight: bold; color:red;">查询将会消耗可用次数，错了不补！！<br>登录即可记录历史查询密码</p>
                    <button type="submit" class="form-button">查询解锁密码</button>
                  </form>
                </div>
              `);
            }
          }
        });
      });

      $(document).on('submit', '#unlock-form', function(event) {
        event.preventDefault();

        // 检查是否正在处理中
        if ($(this).data('is-processing')) {
          alert('为了安全，你必须刷新以进行下一次提交！');
          if (confirm("是否刷新页面？")) {
            window.location.reload();
          }
          return;
        }

        // 提示用户确认是否要兑换
        if (!confirm("确定要兑换吗？这将会消耗一次数量！")) {
          window.location.reload();
          return;
        }

        // 设置正在处理标志
        $(this).data('is-processing', true);

        $.ajax({
          url: './app/api.php',
          type: 'POST',
          data: $(this).serialize(),
          success: function(response) {
            let data = JSON.parse(response);
            if (data.error) {
              alert(data.error);
            } else {
              $('#unlock-password').html(`
                    <div class="result-section">
                        <h3 class="result-title">解锁密码</h3>
                        <p class="result-item" style="text-align: center; font-weight: bold; color:red;">请复制以下密码，并截图保存！</p>
                        <p class="result-item" style="text-align: center; font-weight: bold; color:red;">${data.unlock_pwd}</p>
                    </div>
                `);
            }

            // 重置正在处理标志
            $(this).data('is-processing', false);
          }
        });
      });


    });
  </script>
</head>

<body>
  <noscript>请启用js后继续使用！</noscript>
  <img src="./files/imgs/logo.png" class="logo">
  <div class=" container">
    <h1 class="title">XFP-密码查询页</h1>
    <p style="text-align: center;">注意：未入驻平台的作者无法被查询密码！</p>
    <p style="text-align: center; font-weight: bold; color:red;">千万不要泄露您的订单号！被兑换了不补单！！<br>当前订单非实时更新，无法查询请等待1-10分钟！</p>
    <form id="search-form" class="search-form">
      <label for="order-number" class="form-label">请输入订单号：</label>
      <input type="text" id="order-number" name="order_number" class="form-input" required>
      <label for="captcha" placeholder="点击下方图片可刷新" class="form-label">验证码：</label>
      <input type="text" id="captcha-input" name="captcha" class="form-input" required>
      <img id="captcha-image" src="./app/captcha.php" alt="验证码" class="index-captcha" onclick="refreshCaptcha()">
      <button type="submit" class="form-button">立即查询订单</button>
    </form>

    <div id="results"></div>
    <div id="unlock-password"></div>
    <br>
    <?php
    // 登录显示表单
    if (isset($_SESSION['user_id'])) {
      echo "<p>您的用户名为：" . $_SESSION['username'] . "</p>";
      if ($_SESSION['user_role'] >= 2) {
        echo "欢迎你，尊敬的";
        if ($_SESSION['user_role'] == 2) {
          echo "用户";
        } else {
          echo "管理员";
        }
        echo "！<br><br>";
        echo '<a href="./admin/" class="form-button">管理中心</a>';
      } else {
        echo "欢迎你！<br><br>";
      }
    } else {
      echo '<div class="afd-login-button" style="text-align: center;">
        <h3><span style="color:red;">登录后</span>才可记录历史查询记录</h3>
        <a href="../../app/oauth_login.php">
          <img src="../../files/imgs/afdlogo.png" width="70px" height="70px" title="使用爱发电登录/注册？" style="border-radius: 50%; margin:10px;">
        </a><br><br>
        <a href="./pages/auth/login.php" class="form-button">或使用账号密码？</a>
      </div>';
    }
    ?>
    <?php
    if (isset($_SESSION['user_id'])) {
      echo '
      <a href="./pages/auth/logout.php" class="form-button">登出</a>
      <!--<a href="./admin/" class="form-button">个人中心</a>-->
      <a href="./admin/user/activation_records.php/" class="form-button">历史激活记录</a>
      ';
    }
    ?>
    <br>
    <br>
  </div>
  <div class="copyright">
    <p>© 2024|XFP-Yc|Just to do !</p>
    <p>本站只负责查询信息，如有任何表盘问题，请联系订单原作者！</p>
    <p>
      <a href="https://afdian.com/a/ycfeller" target="_blank">爱发电</a> |
      <a href="https://github.com/" target="_blank">Github(暂无)</a> |
      <a href="https://findsun.top/" target="_blank">寻日科技</a> |
      <a href="https://vip.findsun.top/" target="_blank">优惠会员权益</a>
    </p>
  </div>
</body>

</html>