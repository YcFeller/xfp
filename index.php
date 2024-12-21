<?php
session_start();
echo '<div id="loading_p" style="display:block;text-align:center;"><img src="./files/imgs/loading.gif" title="加载中"><h2>稍后就好(*^▽^*)<br>第一次加载会比较慢</h2></div>';
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8">
  <title>XFP-密码查询页</title>
  <link rel="stylesheet" href="./files/css/index.css">
  <!-- 设置icon -->
  <link rel="icon" href="./files/imgs/favicon.ico" type="image/x-icon" />
  <script src="./files/js/jquery-3.6.0.min.js"></script>
  <?php
  if (isset($_SESSION['user_id'])) {
    echo '<script src="./files/js/index.js"></script>';
  };
  ?>
  <script>
    //隐藏加载动画
    $(window).on('load', function() {
      $('#loading_p').css('display', 'none');
    });
  </script>
</head>

<body>
  <!-- 引用弹窗php -->
  <?php include './app/popup_index.php'; ?>

  <noscript>请启用js后继续使用！</noscript>
  <img src="./files/imgs/logo.png" class="logo">
  <div class="container">
    <?php
    //登录显示表单
    if (isset($_SESSION['user_id'])) {
      echo '<h1 class="title">XFP-密码查询页</h1>
    <p style="text-align: center;">注意：未入驻平台的作者无法被查询密码！</p>
    <p style="text-align: center; font-weight: bold; color:red;">千万不要泄露您的订单号！被兑换了不补单！！<br>当前订单非实时更新，无法查询请等待1-10分钟！</p>
    <form id="search-form" class="search-form">
      <label for="order-number" class="form-label">请输入订单号：</label>
      <input type="text" id="order-number" name="order_number" placeholder="请在爱发电中查看！" class="form-input" required>
      <label for="captcha" class="form-label">验证码：</label>
      <input type="text" id="captcha-input" name="captcha" placeholder="点击下方图片可刷新" class="form-input" required>
      <img id="captcha-image" src="./app/captcha.php" alt="验证码" class="index-captcha" onclick="refreshCaptcha()">
      <button type="submit" class="form-button">立即查询</button>
    </form>
    <div id="results"></div>
    <div id="unlock-password"></div>
    <br>
    </form>';
    } else {
      echo '';
    }
    ?>

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
        <h3><span style="color:red;">登录后</span>才可查询解锁密码</h3>
        <p>登录后即可查询密码，并且可以查看自己的激活记录</p>
        <h4>（点击以下图标进行登录）</h4>
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
    <div class="copyright">
      <p>Copyright &copy; 2024 XFP-Yc | Just do it ! |</p>
      <p>本站只负责查询信息，如有任何表盘问题，请联系订单原作者！</p>
      <p>
        <a href="https://afdian.com/a/ycfeller" target="_blank">爱发电</a> |
        <a href="https://github.com/" target="_blank">Github(暂无)</a> |
        <a href="https://findsun.top/" target="_blank">寻日科技</a> |
        <a href="https://vip.findsun.top/" target="_blank">优惠会员权益</a>
      <p>友链申请请联系</p>
      </p>
    </div>
  </div>
  <div class="side-ads">
    <img src="./files/imgs/xct.png" style="height:300px;" alt="ad">
  </div>
  <div class="side-ads1">
    <img src="./files/imgs/xct.png" style="height:300px;" alt="ad">
  </div>
</body>

</html>