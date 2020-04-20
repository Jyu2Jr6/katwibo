<?php
  session_start();
  require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/dbFunc.php');
  require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/function.php');

  // ログインチェック
  $user = loginCheck();
  if ($user !== '') {
    $userId = $user['id'];
    $name = $user['name'];
    $userImage = $user['img_path'];
    $twitterUserImage = $user['twitter_img_path'];
  } else {
    header('Location: ../login.php');
  }

  // リクエストが存在しない場合は収支結果へ
  if (!empty($_REQUEST['date'])) {
    // リクエスト情報を取得
    $date = $_REQUEST['date'];

    // データベース接続
    $dbh = dbConnect();

    // SQL作成
    $sql =
      "SELECT
        *
      FROM
        posts
      WHERE
        user_id = :user_id
      AND MID(created_at, 1, 10) = :date";
    $stmtDay = $dbh->prepare($sql);
    $stmtDay->bindValue(':user_id', $userId);
    $stmtDay->bindValue(':date', $date);
    $stmtDay->execute();

    // データベース切断
    dbClose($dbh);

  } else {
    header('Location: syushi.php');
    exit();
  }
?>

<!DOCTYPE html>
<html lang="jp">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link rel="stylesheet" href="../css/style.css"> 
  <title>月詳細 | かついぼ</title>
</head>
<body>
<!-- ナビゲーションバー -->
<?php include($_SERVER['DOCUMENT_ROOT'] . '/katwibo/inc/header.php')?>

  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-12">
        <a class="btn btn-block btn-secondary mb-4" href="syushiMonth.php?month=<?php print substr($date, 0, 7) ?>">戻る</a>
        <table class="table">
          <thead>
            <tr>
              <th>日付</th>
              <th>入</th>
              <th>出</th>
              <th>計</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($post = $stmtDay->fetch()): ?>
              <?php
                if ($post['bofp_flg'] === '0') {
                  $plus = 0;
                  $minus = $post['money'];
                } else {
                  $plus = $post['money'];
                  $minus = 0;
                }
              ?>

              <tr>
                <th><a href="../update.php?id=<?php print $post['id'] ?>&origin=calc&date=<?php print $date?>"><?php print $post['created_at'] ?></a></th>
                <td><?php print number_format($plus) ?></td>
                <td><?php print number_format($minus) ?></td>
                <td><?php print number_format($plus - $minus) ?></td>
              </tr>
              <tr>
                <td colspan="4"><?php print $post['memo'] ?></td>
              </tr>
              <?php 
                $plusSum += $plus;
                $minusSum +=   $minus;
                $sum += $plus - $minus;
              ?>
              <!-- 画像 -->
              <?php if (!empty($post['image'])): ?>
                <tr>
                  <td colspan="4">
                    <img class="postImg" src="../images/<?php print h($post['image']) ?>" alt=""><br>
                  </td>
                </tr>
              <?php endif ?>            
            <?php endwhile ?>
            <tr>
              <th>合計</th>
              <td><?php print number_format($plusSum) ?></td>
              <td><?php print number_format($minusSum) ?></td>
              <td><?php print number_format($sum) ?></td>
            </tr>
          </tbody>
        </table>
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
