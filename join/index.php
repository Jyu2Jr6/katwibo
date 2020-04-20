<?php
  session_start();
  require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/dbFunc.php');
  require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/function.php');

  // 登録ボタンが押された場合
  if (!empty($_POST)) {
    // フォーム入力値を取得
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password2'];
    $password2 = $_POST['password2'];

    // ニックネーム入力チェック
    if ($name === '') {
      $error['name'] = 'blank';
    }

    // email入力チェック
    if ($email === '') {
      $error['email'] = 'blank';
    }

    // パスワード桁数チェック
    if (strlen($password) < 4) {
      $error['password'] = 'length';
    }

    // パスワード入力チェック
    if ($password === '') {
      $error['password'] = 'blank';
    } else {
      // パスワード一致チェック
      if ($password !== $password2) {
        $error['password'] = 'diff';
      }
    }

    $filename = $_FILES['image']['name'];
    if (!empty($filename)) {
      $ext = substr($filename, -3);
      if ($ext != 'jpg' && $ext != gif && $ext != png) {
        $error['image'] = 'type';
      }
    }

    // エラーが存在しない場合はメールアドレスの重複チェックを行う
    if (empty($error)) {

      // データベース切断
      $dbh = dbConnect();

      $sql = 
        "SELECT
          count(*) as cnt
        FROM
          users
        WHERE
          email = :email";
      $userStmt = $dbh->prepare($sql);
      $userStmt->bindValue(':email', $email);
      $userStmt->execute();
      $rcd = $userStmt->fetch(PDO::FETCH_ASSOC);

      if ($rcd['cnt'] > 0) {
        $error['email'] = 'dupl';
      }

      dbClose($dbh);
    }

    // エラーが存在しない場合は入力確認画面に遷移する
    if (empty($error)) {

      if (!empty($_FILES['image']['name'])) {
        $image = date('YmdHis') . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], '../member_picture/' . $image);  
      }

      $_SESSION['join'] = $_POST;
      $_SESSION['join']['image'] = $image;
      header('Location: check.php');
      exit();
    }
  }

  if ($_REQUEST['action'] === 'rewrite' && isset($_SESSION['join'])) {
    $name = $_SESSION['join']['name'];
    $email = $_SESSION['join']['email'];
    $password = $_SESSION['join']['password'];
  }

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <title>ユーザー登録 | かついぼ</title>
</head>
<body>
<!-- ナビゲーションバー -->
<?php include($_SERVER['DOCUMENT_ROOT']  . '/katwibo/inc/header.php')?>

<div class="container mt-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <form action="" method="POST" enctype="multipart/form-data">
        <!-- ニックネーム -->
        <div class="form-group">
          <label class="form-contorl" for="name">ニックネーム</label>
          <input type="text" class="form-control" id="name" name="name" value="<?php print h($name) ?>">
        </div>          
        <!-- エラーエッセージ：ニックネーム -->
        <?php if ($error['name'] === 'blank'): ?>
          <p class="alert alert-danger">ニックネームを正しく入力してください</p>
        <?php endif ?>
        <!-- メールアドレス -->
        <div class="form-group">
        <label for="email">メールアドレス</label>
            <input type="email" class="form-control" id="email" aria-describedby="emailHelp" name="email" value="<?php print h($email) ?>">
        </div>          
        <!-- エラーメッセージ：メールアドレス -->
        <?php if ($error['email'] === 'blank'): ?>
          <p class="alert alert-danger">メールアドレスを正しく入力してください</p>
        <?php elseif ($error['email'] === 'dupl'): ?>
          <p class="alert alert-danger">指定されたメールアドレスは既に登録されています</p>
        <?php endif ?>
        <!-- パスワード -->
        <div class="form-group">
          <label for="password">パスワード</label>
          <input type="password" class="form-control" id="password" name="password">
        </div>          
        <!-- パスワード（再入力） -->
        <div class="form-group">
          <label for="password2">パスワード(再入力)</label>
          <input type="password" class="form-control" id="password2" name="password2">
        </div>          
        <!-- エラーメッセージ：パスワード -->
        <?php if ($error['password'] === 'blank'): ?>
          <p class="alert alert-danger">パスワードを正しく入力してください</p>          
        <?php elseif ($error['password'] === 'length'): ?>
          <p class="alert alert-danger">パスワードは4文字以上で入力してください</p>          
        <?php elseif ($error['password'] === 'diff'): ?>
          <p class="alert alert-danger">パスワードが一致していません</p>          
        <?php endif ?>
        <br>
        <!-- 画像 -->
        <div class="form-group">
          <label for="image">プロフィール画像</label>
          <input type="file" class="form-control-file" id="image" name="image">
        </div>
        <!-- エラーメッセージ：画像 -->
        <?php if ($error['image'] === 'type'): ?>
          <p class="alert alert-danger">画像ファイルの種類は「jpg」「gif」「png」のみです。</p>          
        <?php endif ?>
        <!-- 登録ボタン -->
        <input type="submit" class="btn btn-block btn-primary" value="登録">
        <!-- 登録ボタン -->
        <a href="../login.php" class="btn btn-block btn-secondary">戻る</a>
      </form>
    </div>
  </div>
</div>

  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>  
</body>
</html>