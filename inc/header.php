<?php
  // echo $_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/dbFunc.php' . '<br>';
  // echo __DIR__ . '/../util/dbFunc.php' . '<br>';

  // require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/dbFunc.php');
  require_once(__DIR__ . '/../util/dbFunc.php');
  
  $dbh = dbConnect();

  $sql = 
    "SELECT
      *
    FROM
      users
    WHERE
      id = :id";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':id', $_SESSION['id']);
  $stmt->execute();

  $user = $stmt->fetch();

  // ユーザー情報取得
  $name = $user['name'];
  $userImage = $user['img_path'];
  $twitterImage = $user['twitter_img_path'];

  dbClose($dbh);
?>

<header>
  <nav class="navbar navbar-expand-lg navbar-light bg-info sticky-top">
    <a class="navbar-brand text-white" href="<?php $_SERVER['DOCUMENT_ROOT'] ?>/katwibo/index.php">かついぼ</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <?php if (empty($name)): ?>
          <li class="nav-item active">
            <a class="nav-link text-white" href="<?php $_SERVER['DOCUMENT_ROOT'] ?>/katwibo/join/index.php">登録する</a>
          </li>
        <?php else: ?>
          <li class="nav-item active">
          <a class="nav-link text-white " href="<?php $_SERVER['DOCUMENT_ROOT'] ?>/katwibo/syushi/syushi.php">収支確認</a>
        </li>
        <li class="nav-item active">
          <a class="nav-link text-white " href="<?php $_SERVER['DOCUMENT_ROOT'] ?>/katwibo/index.php">投稿一覧</a>
        </li>
        <li class="nav-item active">
          <a class="nav-link text-white " href="<?php $_SERVER['DOCUMENT_ROOT'] ?>/katwibo/logout.php">ログアウト</a>
        </li>
        <?php endif ?>
      </ul>
    </div>
  </nav>  
  <div class="container">
    <div class="row">
      <div class="col-12">
      <?php if (!empty($userImage)): ?>
        <img class="prfImg" src="<?php $_SERVER['DOCUMENT_ROOT'] ?>/katwibo/member_picture/<?php print $userImage ?>" alt="">
      <?php else: ?>
        <img class="prfImg" src="<?php print $twitterImage ?>" alt="">
      <?php endif ?>    
      <span class="text-black"><?php print $name ?></span>
      </div>
    </div>
  </div>
</header>
