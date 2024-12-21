function refreshCaptcha() {
  var captchaImage = document.getElementById('captcha-image');
  captchaImage.src = './app/captcha.php?' + new Date().getTime();
}

function refreshCaptcha2() {
  var captchaImage = document.getElementById('captcha-image2');
  captchaImage.src = './app/captcha.php?' + new Date().getTime();
}

$(document).ready(function () {
  function updateCaptcha() {
    $('#captcha-image').attr('src', './app/captcha.php?' + new Date().getTime());
  }

  updateCaptcha();
  $('#loading_p').css('display', 'none');

  $('#search-form').submit(function (event) {
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
      success: function (response) {
        let data = JSON.parse(response);
        if (data.error) {
          alert(data.error);
          if (data.error === '验证码错误') {
            updateCaptcha();
          }
        } else {
          let watchfacesHtml = '';
          data.watchfaces.forEach(function (watchface) {
            watchfacesHtml += `
                  <div class="result-section">
                    <h3 class="result-title">查询结果</h3>
                    <p class="result-item">表盘名称: ${watchface.watchface_name}</p>
                    <p class="result-item">表盘图片: <img src="${watchface.watchface_image}" alt="表盘图片" class="watchface-image"></p>
                    <p class="result-item">剩余激活次数: <b>${data.downloads_limit}</b></p>
                    <p class="result-item">状态: ${watchface.status == 1 ? '显示' : '隐藏'}</p>
                  </div>
                `;
          });

          $('#results').html(watchfacesHtml + `
                <form id="unlock-form" class="unlock-form">
                  <input type="hidden" name="order_no" value="${orderNumber}">
                  <label for="captcha" class="form-label">验证码：</label>
                  <input type="text" id="verification_code" name="psw" placeholder="点击下方图片可刷新" class="form-input" required>
                  <img id="captcha-image2" src="./app/captcha.php" alt="验证码" class="index-captcha" onclick="refreshCaptcha2()">
                  <label for="device_code" class="form-label">设备码:（注意大小写！）</label>
                  <input type="text" id="device_code" name="psn" class="form-input" maxlength="6" required>
                  <p style="text-align: center; font-weight: bold; color:red;">查询将会消耗可用次数，错了不补！！<br>登录即可记录历史查询密码</p>
                  <button type="submit" class="form-button">查询解锁密码</button>
                </form>
              `);
        }
      }
    });
  });

  $(document).on('submit', '#unlock-form', function (event) {
    event.preventDefault();
    const form = $(this);

    if (form.data('is-processing')) {
      alert('为了安全，你必须刷新以进行下一次提交！');
      if (confirm("是否刷新页面？")) {
        window.location.reload();
      }
      return;
    }

    if (!confirm("确定要兑换吗？这将会消耗一次数量！")) {
      window.location.reload();
      return;
    }

    form.data('is-processing', true);

    $.ajax({
      url: './app/api.php',
      type: 'POST',
      data: form.serialize(),
      success: function (response) {
        let data = JSON.parse(response);

        if (data.error) {
          alert(data.error);
        } else {
          // 清除之前的结果
          $('#unlock-password').empty();

          // 处理并显示多个解锁密码
          if (data.unlock_pwds && data.unlock_pwds.length > 0) {
            let resultHtml = `
                        <div class="result-section">
                            <h3 class="result-title">解锁密码</h3>
                            <p class="result-item" style="text-align: center; font-weight: bold; color:red;">请复制以下密码，并截图保存！</p>
                    `;

            data.unlock_pwds.forEach(item => {
              resultHtml += `
                            <p class="result-item" style="text-align: center; font-weight: bold; color:red;">
                                解锁密码: ${item.unlock_pwd}
                            </p>
                        `;
            });

            resultHtml += '</div>';
            $('#unlock-password').html(resultHtml);
          } else {
            $('#unlock-password').html(`
                        <div class="result-section">
                            <h3 class="result-title">解锁密码</h3>
                            <p class="result-item" style="text-align: center; font-weight: bold; color:red;">未找到解锁密码，请检查订单号和设备码。</p>
                        </div>
                    `);
          }
        }

        // form.data('is-processing', false);
      },
      error: function () {
        alert('请求失败，请重试。');
        // form.data('is-processing', false);
      }
    });
  });

});