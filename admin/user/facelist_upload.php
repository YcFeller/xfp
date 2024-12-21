<?php
session_start();
echo '<div id="loading_p" style="display:block;text-align:center;"><img src="../../files/imgs/loading.gif" title="加载中"><h2>我知道你很急<br>但是你先别急</h2></div>';
?>

<?php

// 上传表单页面 
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

require_once '../../app/config.php';

$system_user_id = $_SESSION['user_id'];
$conn = new mysqli($servername, $db_user, $db_pass, $dbname);

if ($conn->connect_error) {
  die("数据库连接失败: " . $conn->connect_error);
}

// 获取 plan_id 的选项
$plan_id_options = [];
$stmt = $conn->prepare("SELECT DISTINCT plan_id FROM xfp_order WHERE system_user_id = ?");
$stmt->bind_param("i", $system_user_id);

if ($stmt->execute()) {
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $plan_id_options[] = $row['plan_id'];
  }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8">
  <title>上传表盘</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.2/css/bootstrap.min.css">
  <script>
    // 隐藏id为loading_p的div
    document.getElementById('loading_p').style.display = 'none';
  </script>
  <style>
    .container {
      max-width: 600px;
      margin: 0 auto;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    body {
      font-family: Arial, sans-serif;
      color: #333;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 600px;
      margin: 0 auto;
      padding: 20px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    h1 {
      color: #007bff;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    label {
      display: block;
      margin-bottom: .5rem;
      font-weight: bold;
    }

    input[type="text"],
    select {
      width: 100%;
      padding: .375rem .75rem;
      border: 1px solid #ced4da;
      border-radius: .25rem;
    }

    #imageContainer {
      width: 300px;
      height: 300px;
      border: 1px solid #ccc;
      border-radius: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
    }

    button {
      padding: .5rem 1rem;
      color: #fff;
      background-color: #007bff;
      border: none;
      border-radius: .25rem;
      cursor: pointer;
    }

    button:hover {
      background-color: #0056b3;
    }

    #responseMessage {
      margin-top: 1rem;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      .container {
        padding: 10px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <h1 class="mt-5">上传表盘</h1>
    <form id="uploadForm" enctype="multipart/form-data">
      <div class="form-group">
        <label for="name">表盘名称:(建议直接复制)</label>
        <input type="text" id="name" name="name" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="watchface_id">表盘ID:(用于计算秘钥的ID)</label>
        <input type="text" id="watchface_id" name="watchface_id" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="status">状态:(暂不可用)</label>
        <select id="status" name="status" class="form-control">
          <option value="1">显示</option>
          <option value="0">隐藏</option>
        </select>
      </div>

      <div class="form-group">
        <label for="plan_id">计划ID:(每个计划id对应一个表盘且不可重复)</label>
        <select id="plan_id" name="plan_id" class="form-control">
          <?php foreach ($plan_id_options as $option): ?>
            <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="image_link">表盘图片链接:（可选）</label>
        <input type="text" id="image_link" name="image_link" class="form-control" placeholder="请输入图片链接">

      </div>

      <div class="form-group">
        <label>表盘图片预览</label>
        <div id="imageContainer"></div>
      </div>

      <button type="submit" class="btn btn-primary">上传表盘</button>
      <div id="responseMessage" class="mt-3"></div>
    </form>
  </div>

  <script src="../../files/js/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    $(document).ready(function() {
      $('#uploadForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
          url: '../../app/facelist_upload.php',
          type: 'POST',
          data: new FormData(this),
          processData: false,
          contentType: false,
          success: function(response) {
            $('#responseMessage').html(response);
            if (response.includes('成功')) {
              $('#uploadForm')[0].reset();
              // 成功后跳转./facelist.php
              window.location.href = './facelist.php';
            }
          },
          error: function() {
            $('#responseMessage').html('服务器错误，请重试。');
            if (confirm('服务器错误，是否刷新页面重试?')) {
              location.reload();
            }
          }
        });
      });
    });

    // 图片预览逻辑
    document.getElementById('image_link').addEventListener('input', function() {
      const imageUrl = this.value;
      document.getElementById('imageContainer').innerHTML = imageUrl ? `<img src="${imageUrl}" alt="从URL加载的图片">` : '';
    });
  </script>
</body>

</html>