<?php
  session_start();
  require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/dbFunc.php');
  require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/function.php');

  // 既にログインされている場合は一覧画面へ
  // ログインチェック
  $user = loginCheck();
  if ($user !== '') {
    header('Location: index.php');
  }

  if ($_COOKIE['email'] !== '') {
    $email = $_COOKIE['email'];
  }

  // フォームが送信された場合
  if (!empty($_POST)) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    var_dump($_POST);

    if ($email !== '' && $password !== '') {
      $dbh = dbConnect();

      // データベース接続
      $sql =
        "SELECT
          *
        FROM
          users
        WHERE
          email = :email
        AND
          password = :password";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':password', sha1($password));
        $stmt->execute();

        $user = $stmt->fetch();

        if ($user) {
          $_SESSION['id'] = $user['id'];
          $_SESSION['time'] = time();

          if ($_POST['save'] === 'ON') {
            setcookie('email', $email, time()+60*60*24*14);
          }

          header('Location: index.php');
        } else {
          $error['login'] = "failed";
        }

        $dbh = dbClose($dbh);
    } else {
      $error['login'] = 'blank';
    }
  } 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <title>ログイン | かついぼ</title>
</head>
<body>
  <?php include($_SERVER['DOCUMENT_ROOT'] . '/katwibo/inc/header.php')?>

  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-12">
        <form action="" method="POST">
          <!-- メールアドレス -->
          <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" class="form-control" id="email" aria-describedby="emailHelp" name="email" value="<?php print h($email) ?>">
          </div>          
          <!-- パスワード -->
          <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" class="form-control" id="password" name="password">
          </div>
          <!-- エラーメッセージ：パスワード -->
          <?php if ($error['login'] === 'blank'): ?>
            <p class="alert alert-danger">メールアドレス、パスワードを入力してください。</p>
          <?php elseif ($error['login'] === 'failed'): ?>
            <p class="alert alert-danger">メールアドレスもしくはパスワードが異なります。。</p>
          <?php endif ?>
          <!-- チェック -->
          <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" name="save" id="save" value="ON">次回からメールアドレスの入力を省略する
            <label class="form-check-label" for="save"></label>
          </div>
          <!-- ログインボタン -->
          <input type="submit" class="btn btn-block btn-primary" value="ログイン">
          <a class="btn btn-block btn-primary" href="loginTwitter.php">Twitterでログイン</a>
        </form>          
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>  
</body>
</html>