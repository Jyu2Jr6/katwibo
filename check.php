<?php
  session_start();
  require(__DIR__ . 'util/dbFunc.php');
  require(__DIR__ . 'util/function.php');
  require(__DIR__ . 'util/const.php');

  // TwitterOAuthライブラリの読み込み
  require_once('vendor/autoload.php');
  use Abraham\TwitterOAuth\TwitterOAuth;

  // ログイン済みかチェック
  if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    // ログイン済み
    $userId = $_SESSION['id'];
    $_SESSION['time'] = time();

    // データベース接続
    $dbh = dbConnect();

    // ユーザー情報取得
    $sql =
      "SELECT
        name,
        img_path
      FROM
        users
      WHERE
        id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':id', $userId);
    $stmt->execute();

    $user = $stmt->fetch();
    $name = $user['name'];
    $userImage = $user['img_path'];
  }

  // index.phpを通過してきていない場合
  if(!isset($_SESSION['register'])) {
    header('Location: index.php');
  // index.phpを通過してきた場合
  } else {
    $bofpFlg = $_SESSION['register']['bofp_flg'];
    $money = $_SESSION['register']['money'];
    $memo = $_SESSION['register']['memo'];
    $twitterPost = $_SESSION['register']['twitter_post'];
    $image = $_SESSION['register']['image'];

    // 収支判断
    if ($bofpFlg === 0) {
      $bofpName = '支出';
    } else {
      $bofpName = '収入';
    }

    // Twitter投稿判断
    if ($twitterPost === 'ON') {
      $twitterPostName = 'する';
    } else {
      $twitterPostName = 'しない';
    }
  }

  // 登録ボタンが押された場合
  if (!empty($_POST)) {
    $bofpFlg = $_POST['bofpFlg'];
    $money = $_POST['money'];
    $memo = $_POST['memo'];
    $image = $_POST['image'];
    $twitterPost = $_POST['twitter_post'];
    echo "twitterPost" . $twitterPost;

    // データベース接続
    $dbh = dbConnect();

    // ユーザー情報登録
    $sql = 
      "INSERT INTO posts (user_id, bofp_flg, money, memo, re_post_id, image)
      VALUES (:user_id, :bofp_flg, :money, :memo, :re_post_id, :image)";
    $istStmt = $dbh->prepare($sql);
    $istStmt->bindValue(':user_id', $userId);
    $istStmt->bindValue(':bofp_flg', $bofpFlg);
    $istStmt->bindValue(':money', $money);
    $istStmt->bindValue(':memo', $memo);
    $istStmt->bindValue(':re_post_id', '');
    $istStmt->bindValue(':image', $image);
    $istStmt->execute();

    // データベース切断
    dbConnect();

    // Twitterにも投稿する場合
    if ($twitterPost === 'ON') {
      // ユーザー情報からTwitter token取得
      $sql = 
        "SELECT
          *
        FROM
          users
        WHERE
          id = :id";
      $stmt = $dbh->prepare($sql);
      $stmt->bindValue(':id', $userId);
      $stmt->execute();

      $user = $stmt->fetch();

      $accessToken = $user['access_token'];
      $accessTokenSecret = $user['access_token_secret'];

      // Twitter接続
      $twiConnct = new TwitterOAuth(Consumer_Key, Consumer_Secret, $accessToken, $accessTokenSecret);

      // テキスト内容作成
      if ($bofpFlg === 0) {
        $syushi = '支出';
      } else {
        $syushi = '収入';
      }

      $text = $syushi . PHP_EOL;
      $text .= '金額:' . $money . '円' . PHP_EOL;
      $text .= $memo . PHP_EOL;
      $text .= '#かついぼ';

      // Tweet作成
      // 画像が存在する場合
      if (!empty($image)) {
        echo "画像ある:" . $image . '<br>';
        // 画像をアップロードし、メディアIDを取得
          $imageId = $twiConnct->upload('media/upload', ['media' => 'images/' . $image]);
        // var_dump($imageId);
        // exit();

        // ツイートパラメータ作成
        $tweet = [
          'status' => $text,
          'media_ids' => implode(',', [
            $imageId->media_id_string
          ])
        ];
      } else {
        echo "画像ない:" . $image;
        $tweet = [
          'status' => $text
        ];
      }

      // ツイート
      // $res = $twiConnct->post("statuses/update", array("status" => $memo));
      $res = $twiConnct->post("statuses/update", $tweet);

      // var_dump($res);
      // exit();
    }

    // セッション削除
    unset($_SESSION['register']);

    // 投稿画面に遷移
    header('Location: index.php');
    exit();
  }
?>

<!DOCTYPE html>
<html lang="jp">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link rel="stylesheet" href="css/style.css"> 
  <title>投稿内容確認 | かついぼ</title>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-dark">
    <a class="navbar-brand text-white " href="#">かついぼ</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <img class="prfImg" src="./member_picture/<?php print h($userImage) ?>" alt="">
    <sapn class="text-black text-white ml-1"><?php print h($name) ?></sapn>
  </nav>

  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-6">
        <p class="text-danger">投稿内容に問題はありませんか？</p>
        <p class="text-secondary">収支</p>
        <p class="p-3 mb-2 bg-light text-dark"><?php print h($bofpName) ?></p>

        <p class="text-secondary">金額</p>
        <p class="p-3 mb-2 bg-light text-dark"><?php print h($money) ?></p>

        <p class="text-secondary">内容</p>
        <p class="p-3 mb-2 bg-light text-dark"><?php print h($memo) ?></p>

        <p class="text-secondary">Twitter投稿</p>
        <p class="p-3 mb-2 bg-light text-dark"><?php print h($twitterPostName) ?></p>

        <p class="text-secondary">投稿写真</p>
        <?php if (!empty($image)): ?>
          <img class="postImg mb-2" src="images/<?php print h($image) ?>" alt="">
        <?php endif ?>
        <br>

        <form action="" method="POST">
          <input type="hidden" name="bofpFlg" value="<?php print h($bofpFlg) ?>">
          <input type="hidden" name="money" value="<?php print h($money) ?>">
          <input type="hidden" name="memo" value="<?php print h($memo) ?>">
          <input type="hidden" name="image" value="<?php print h($image) ?>">
          <input type="hidden" name="twitter_post" value="<?php print h($twitterPost) ?>">

          <a class="btn btn-secondary" href="index.php?action=rewrite">書き直す</a>
          <input class="btn btn-primary" type="submit" value="登録する">
        </form>
      </div>
    </div>
  </div>


  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>  
</body>
</html>