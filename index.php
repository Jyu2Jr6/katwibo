<?php
  session_start();
  require($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/function.php');
  require($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/dbFunc.php');
  require($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/const.php');
  
  // 収支初期設定
  $bofpFlg0 = "checked";
  $bofpFlg1 = "";

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

  // ページネーションの計算
  $page = $_REQUEST['page'];
  if ($page === '' || !is_numeric($page)) {
    $page = 1;
  }

  $page = max($page, 1);
  $sql = 
    "SELECT
      count(*) as cnt
    FROM
      posts";
  $stmt = $dbh->prepare($sql);
  $stmt->execute();
  
  $cnt = $stmt->fetch();
  $maxPage = ceil($cnt['cnt'] / 10);

  if ($maxPage !== 0) {
    $page = min($page, $maxPage);
  } 

  $pageStart = ($page - 1) * 10;

  if ($pageStart < 0) {
    $pageStart = 0;
  } 

  // 投稿情報取得
  $sql = 
  "SELECT
    u.name AS name,
    u.img_path AS img_path,
    u.twitter_img_path AS twitter_img_path,
    p.id AS post_id,
    p.bofp_flg AS bofp_flg,
    p.money AS money,
    p.memo AS memo,
    p.image AS image,
    p.re_post_id AS re_post_id,
    p.created_at AS created_at
  FROM 
    posts p,
    users u
  WHERE
    p.user_id = u.id
  ORDER BY 
    p.created_at desc
  LIMIT :page, 10";
  $postViewStmt = $dbh->prepare($sql);
  $postViewStmt->bindValue(":page", $pageStart, PDO::PARAM_INT);
  $postViewStmt->execute();
 
  // 投稿後のセッション情報が存在する場合
  if (!empty($_SESSION['post'])) {
    $postFlg = afterPostCheck($_SESSION['post']);
    $money = '';
    $memo = '';
  }

  // 投稿処理
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
        "INSERT INTO posts (user_id, bofp_flg, money, memo, re_post_id, image, created_at)
        VALUES (:user_id, :bofp_flg, :money, :memo, :re_post_id, :image, :created_at)";
        $istStmt = $dbh->prepare($sql);
        $istStmt->bindValue(':user_id', $userId);
        $istStmt->bindValue(':bofp_flg', $bofpFlg);
        $istStmt->bindValue(':money', $money);
        $istStmt->bindValue(':memo', $memo);
        $istStmt->bindValue(':re_post_id', '');
        $istStmt->bindValue(':image', $image);
        $istStmt->bindValue(':created_at', date('Y-m-d H:i;s'));
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
      $_SESSION['post'] = "post";

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
  <title>投稿 | かついぼ</title>
</head>
<body">
  <!-- ナビゲーションメニュー -->
  <?php include($_SERVER['DOCUMENT_ROOT']  . '/katwibo/inc/header.php')?>

  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-12">
        <!-- 投稿メッセージ -->
        <?php if ($postFlg === '1'): ?>
          <p class="alert alert-info">投稿しました。</p>
        <?php elseif ($postFlg === '2'): ?>
        <p class="alert alert-info">投稿を修正しました。</p>
        <?php endif ?>
        <form action="" method="POST" enctype="multipart/form-data">
          <!-- 収支 -->
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="bofp_flg" id="bofp_flg0" value="0" checked>
            <label class="form-check-label" for="bofp_flg0">支出</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="bofp_flg" id="bofp_flg1" value="1"?>
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
          <div class="form-group">
            <label for="image">投稿画像</label>
            <input type="file" class="form-control-file" id="image" name="image">
          </div>
          <!-- Twitterに投稿するか？ -->
          <?php if (empty($userImage)): ?>
            <input type="checkbox" name="twitter_post" value="ON" >Twitterにも投稿する<br>
          <?php endif ?>
          <!-- 投稿ボタン -->
          <input class="btn btn-block btn-primary mt-4" type="submit" value="投稿">
        </form>      
      </div>
    </div>
  </div>
  <!-- 投稿一覧 -->
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12">
        <table class="table">
          <thead>
            <tr>
              <th colspan="3"></th>
            </tr>
          </thead>
        <?php while ($post = $postViewStmt->fetch()): ?>
            <!-- 日付、収支、金額、メモ -->
            <tbody>
              <tr>
                <td colspan="3">
                <?php if (!empty($post['img_path'])): ?>
                  <img class="prfImg" src="member_picture/<?php print h($post['img_path']) ?>" alt="">
                <?php else: ?>
                  <img class="prfImg" src="<?php print h($post['twitter_img_path']) ?>" alt="">
                <?php endif ?>              
                <span class="ml-1 text-dark"><?php print $post['name'] ?></span>
                </td>
              </tr>
            <!-- 名前 -->
              <tr>
                <!-- 日付 -->
                <td class="colDateTime"><a href="update.php?id=<?php print h($post['post_id'])?>&origin=view"><?php print h($post['created_at']) ?></a></td>
                <!-- 収支 -->
                <td class="colBofp">
                  <?php if ($post['bofp_flg'] === '0'): ?>
                    出
                  <?php else: ?>
                    入
                  <?php endif ?>    
                </td class="colMoney">
                <!-- 金額 -->
                <td><?php print number_format(h($post['money'])) ?></td>
              </tr>
              <!-- メモ -->
              <tr>
                <td colspan="3"><?php print h($post['memo']) ?></td>
              </tr>
              <!-- 画像 -->
              <?php if (!empty($post['image'])): ?>
                <tr>
                  <td colspan="3">
                    <img class="postImg" src="images/<?php print h($post['image']) ?>" alt=""><br>
                  </td>
                </tr>
              <?php endif ?>            
            </tbody>
        <?php endwhile ?>   
        <!-- ページネーション -->
        </table>
        <?php if ($page >= 2): ?>
          <a class="text-center " href="index.php?page=<?php print $page - 1 ?>">前ページへ</a>
        <?php else: ?>
          <span class="text-center">前ページへ</span>
        <?php endif ?>
        <span>／</span>
        <?php if ($page < $maxPage): ?>
          <a class="text-center" href="index.php?page=<?php print $page + 1 ?>">次ページへ</a>
          <?php else: ?>
          <sapn class="text-center">次ページへ</sapn>
        <?php endif ?>
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