<style>
  /* 弹窗样式 */
  .modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    /* overflow: auto; */
    background-color: rgb(0, 0, 0);
    background-color: rgba(0, 0, 0, 0.4);
    /* 过场动画 */
    animation: fadeIn 0.5s;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }

  /* 弹窗内容 */
  .modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    /* 15%距离顶部和底部 */
    padding: 30px;
    border: 1px solid #888;
    width: 500px;
    height: 600px;
    border-radius: 20px;
    /* 限制高度进行滚动浏览 */
    overflow: auto;
  }

  /* 美化一下滚动条 */
  ::-webkit-scrollbar {
    width: 8px;
  }

  ::-webkit-scrollbar-thumb {
    background-color: #808080;
    border-radius: 5px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background-color: #555;
  }

  /* 关闭按钮 */
  .close1 {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
  }

  .close1:hover,
  .close1:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
  }

  .modal-content p {
    font-size: 20px;

  }

  .modal-content img {
    width: 100%;
    height: auto;
  }
</style>

<div id="myModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span>(可向上下滑动查看完整公告,点击背景可关闭弹窗)</span>
    <span class="close1">&times;</span>
    <h2>网站公告：</h2>
    <p>网站有问题请反馈！表盘问题请找原作者！</p>
    <p>注意：未入驻平台的作者无法被查询密码！<br>
      千万不要泄露您的订单号！被兑换了不补单！！<br>
      当前订单非实时更新，无法查询请等待1-10分钟！
    </p>
    <h2>宣传部分：</h2>
    <p>喜迎国庆,普天同庆！</p>
    <img src="https://xfp.fs0.top/files/imgs/gqj.png" alt="图片">
    <p>2024/10/1</p>
    <p>YcFeller全新表盘发布，链接：<a href="https://www.bandbbs.cn/resources/2117/">https://www.bandbbs.cn/resources/2117/</a></p>
    <img src="" alt="图片">
    <p>2024/9/30</p>

  </div>
</div>

<script>
  // 获取弹窗
  var modal = document.getElementById("myModal");

  // 获取按钮
  var btn = document.getElementById("myBtn");

  // 获取 <span> 元素，用于关闭弹窗
  var span = document.getElementsByClassName("close1")[0];

  //5秒自动弹出
  setTimeout(function() {
    modal.style.display = "block";
  }, 2000);

  // 点击 <span> (x), 关闭弹窗
  span.onclick = function() {
    modal.style.display = "none";
  }

  // 点击弹窗外部，关闭弹窗
  window.onclick = function(event) {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  }
</script>