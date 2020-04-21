<?php
  session_start();
  require(__DIR__ . '/util/dbFunc.php');
  require(__DIR__ . '/util/function.php');
  require(__DIR__ . '/util/const.php');
  // TwitterOAuthライブラリの読み込み
  require_once('vendor/autoload.php');
  use Abraham\TwitterOAuth\TwitterOAuth;

  // データベース接続
  $dbh = dbConnect();

  // ログインチェック
  $user = loginCheck();
  if ($user !== '') {
    $userId = $user['id'];
    $name = $user['name'];
    $userImage = $user['img_path'];
    $twitterUserImage = $user['twitter_img_path'];
  } else {
    header('Location: login.php');
  }

  // URLにidが指定されていない場合は登録一覧へ
  if (!empty($_REQUEST['id'])) {
    // URLパラメータ取得
    $postId = $_REQUEST['id'];
    $originKbn = $_REQUEST['origin'];
    $date = $_REQUEST['date'];
    
    // 投稿内容を取得
    $sql = 
    "SELECT
      u.name,
      u.img_path,
      p.*
    FROM
      users u,
      posts p
    WHERE
      p.id = :id
    AND
      p.user_id = u.id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':id', $postId);
    $stmt->execute();

    $post = $stmt->fetch();

    // データベースから取得した内容を変数に格納
    $money = $post['money'];
    $memo = $post['memo'];
    $image = $post['image'];

    // 収支判断
    if ($post['bofp_flg'] === '0') {
      $bofpFlg0 = 'checked';
      $bofpFlg1 = '';
    } else {
      $bofpFlg0 = '';
      $bofpFlg1 = 'checked';
    }

    // ツイッター投稿判断
    if ($twitterPost === 'ON') {
      $twitterPostOn = 'checked';
    } else {
      $twitterPostOn = '';
    }
  } else {
    header('Location: index.php');
  }

  // 修正ボタンがおされた場合
  if (!empty($_POST)) {
    $post = $_POST;
    $bofpFlg = $_POST['bofp_flg'];
    $money = $_POST['money'];
    $memo = $_POST['memo'];
    $twitterPost = $_POST['twitter_post'];

    // 投稿チェック処理
    $error = postCheck($post);

    if (empty($error)) {

      $image = '';
      if (!empty($_FILES['image']['name'])) {
        // ファイルアップロード
        $image = date('YmdHis') . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], 'images/' . $image);
      }

      try {
        // トランザクション開始
        $dbh->beginTransaction();

        // 投稿情報登録
        $sql = 
          "UPDATE 
            posts 
          SET 
            bofp_flg = :bofp_flg,
            money = :money,
            memo = :memo,
            image = :image
          WHERE
            id = :post_id";
        $istStmt = $dbh->prepare($sql);
        $istStmt->bindValue(':bofp_flg', $bofpFlg);
        $istStmt->bindValue(':money', $money);
        $istStmt->bindValue(':memo', $memo);
        $istStmt->bindValue(':image', $image);
        $istStmt->bindValue(':post_id', $_REQUEST['id']);
        $istStmt->execute();

        // コミット
        $dbh->commit();

      } catch (PDOException $e) {
        // ロールバック
        $dbh->rollBack();

        // エラーメッセージ出力
        echo $e->getMessage();
        die();
      }

      // Twitterにも投稿する場合
      if ($twitterPost === 'ON') {
        twitterPost($userId, $post, $image);
      }
      
      // セッション情報に投稿したことを登録
      $_SESSION['post'] = "update";

      header('Location: index.php');
      exit();
    }
  }
  
  // データベース切断 
  dbClose($dbh);
?>
  
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link rel="stylesheet" href="css/style.css"> 
  <title>投稿修正 | かついぼ</title>
</head>
<body">
  <!-- ナビゲーションメニュー -->
  <?php include(__DIR__  . '/inc/header.php')?>

  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-12">
        <form action="" method="POST" enctype="multipart/form-data">
          <!-- 収支 -->
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="bofp_flg" id="bofp_flg0" value="0" <?php print $bofpFlg0 ?>>
            <label class="form-check-label" for="bofp_flg0">支出</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="bofp_flg" id="bofp_flg1" value="1" <?php print $bofpFlg1 ?>>
            <label class="form-check-label" for="bofp_flg1">収入</label>
          </div>
          <!-- 金額 -->
          <div class="form-group">
            <label for="number">金額</label>
            <input type="number" class="form-control money" id="money" name="money" value="<?php print $money ?>">
          </div>          
          <!-- エラーエッセージ：金額 -->
          <?php if ($error['money'] === 'blank'): ?>
            <p class="alert alert-danger">金額を正しく入力してください</p>
          <?php elseif ($error['money'] === 'zero'): ?>
            <p class="alert alert-danger">金額は0より大きい値を入力してください</p>
          <?php endif ?>
          <!-- 内容 -->
          <div class="form-group">
            <label for="memo">内容</label>
            <textarea class="form-control" name="memo" id="memo" rows="3"><?php print $memo ?></textarea>
          </div>
          <!-- エラーエッセージ：内容 -->
          <?php if ($error['memo'] === 'blank'): ?>
            <p class="alert alert-danger">内容を正しく入力してください</p>
          <?php elseif ($error['memo'] === 'length'): ?>
            <p class="alert alert-danger">内容を100文字以下で入力してください</p>
          <?php endif ?>
          <!-- 画像 -->
          <?php if (!empty($image)): ?>
            <label for="image">投稿画像</label>
            <img class="postImg" src="images/<?php print h($image) ?>" alt="">
          <?php endif ?>
          <div class="form-group">
            <input type="file" class="form-control-file" id="image" name="image">
          </div>
          <!-- Twitterに投稿するか？ -->
          <?php if (empty($userImage)): ?>
            <input type="checkbox" name="twitter_post" value="ON" <?php print $twitterPostOn ?>>Twitterにも投稿する<br>
          <?php endif ?>
          <!-- 投稿ボタン -->
          <?php if ($originKbn === 'view'): ?>
            <a class="btn btn-block btn-secondary mt-2" href="index.php">戻る</a>
          <?php elseif ($originKbn === 'calc'): ?>
            <a class="btn btn-block btn-secondary mt-2" href="syushi/syushiDay.php?date=<?php print $date ?>">戻る</a>
          <?php else: ?>
            <a class="btn btn-block btn-secondary mt-2" href="syushi/syushiDay.php?date=<?php print $date ?>">戻る</a>
          <?php endif ?>
          <input class="btn btn-block btn-primary mt-2" type="submit" value="修正">
        </form>      
        <hr>
      </div>
    </div>
  </div>

  <!-- フッター -->
  <?php include($_SERVER['DOCUMENT_ROOT']  . '/katwibo/inc/footer.php')?>

  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>  
</body>
</html>