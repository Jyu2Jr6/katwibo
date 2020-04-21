<?php
  session_start();
  require(__DIR__ . '/../util/dbFunc.php');
  require(__DIR__ . '/../util/function.php');

  // index.phpを通過してきていない場合
  if(!isset($_SESSION['join'])) {
    header('Location: index.php');
  // index.phpを通過してきた場合
  } else {
    $userName = $_SESSION['join']['name'];
    $email = $_SESSION['join']['email'];
    $password = $_SESSION['join']['password'];
    $image = $_SESSION['join']['image'];
  }

  // 登録ボタンが押された場合
  if (!empty($_POST)) {
    $userName = $_POST['name'];
    $email = $_POST['email'];
    $password = sha1($_POST['password']);
    $image = $_POST['image'];

    // データベース接続
    $dbh = dbConnect();

    try {
      // トランザクション開始
      $dbh->beginTransaction();

      // ユーザー情報登録
      $sql = 
        "INSERT INTO users (name, email, password, img_path)
        VALUES (:name, :email, :password, :img_path)";
      $stmt = $dbh->prepare($sql);
      $stmt->bindValue(':name', $userName);
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':password', $password);
      $stmt->bindValue(':img_path', $image);
      $stmt->execute();

      // コミット
      $dbh->commit();

    } catch (PDOException $e) {
      // ロールバック
      $dbh->rollBack();

      // エラーメッセージ出力
      echo $e->getMessage();
      die();
    }

    // データベース切断
    dbConnect();

    // セッション削除
    unset($_SESSION['join']);

    // 完了画面に遷移
    header('Location: thanks.php');
    exit();
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link rel="stylesheet" href="../css/style.css"> 
  <title>入力内容確認 | かついぼ</title>
</head>
<body>
<!-- ナビゲーションバー -->
<?php include(__DIR__  . '/../inc/header.php')?>

  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-6">
        <p class="text-secondary">ニックネーム</p>
        <p class="p-3 mb-2 bg-light text-dark"><?php print h($userName) ?></p>

        <p class="text-secondary">メールアドレス</p>
        <p class="p-3 mb-2 bg-light text-dark"><?php print h($email) ?></p>

        <p class="text-secondary">パスワード</p>
        <p class="p-3 mb-2 bg-light text-dark">【表示されません】</p>

        <p class="text-secondary">プロフィール写真</p>
        <?php if ($_SESSION['join']['image'] !== ''): ?>
          <img class="prfImgConfirm" src="../member_picture/<?php print h($image) ?>" alt="">
        <?php endif ?>
        <br>

        <form action="" method="POST">
          <input type="hidden" name="name" value="<?php print h($userName) ?>">
          <input type="hidden" name="email" value="<?php print h($email) ?>">
          <input type="hidden" name="password" value="<?php print h($password) ?>">
          <input type="hidden" name="image" value="<?php print h($image) ?>">

          <a class="btn btn-block btn-secondary" href="index.php?action=rewrite">書き直す</a>
          <input class="btn btn-block btn-primary" type="submit" value="登録する">
        </form>
      </div>
    </div>
  </div>


  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>  
</body>
</html>