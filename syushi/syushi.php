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
  
  // 年単位の収支を計算する
  $syushi = syushiCalc($userId, '');

  // 戻り値を分解
  $shisyutsu = $syushi['shisyutsu'];
  $syunyu = $syushi['syunyu'];

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
<?php include($_SERVER['DOCUMENT_ROOT']  . '/katwibo/inc/header.php')?>

  <div class="container mt-4">
    <div class="row justify-content-center">
      <div class="col-12">
      <a class="btn btn-block btn-secondary mb-4" href="../index.php">戻る</a>
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
          <?php foreach ((array)$shisyutsu as $key => $value): ?>
            <?php $shisyutsuVal = (int)$value ?>
            <?php $syunyuVal = (int)$syunyu[$key] ?>
            <tr>
              <th><a href="syushiYear.php?year=<?php print $key ?>"><?php print $key ?></a></th>
              <td><?php print number_format($syunyuVal) ?></td>
              <td><?php print number_format($shisyutsuVal) ?></td>
              <td><?php print number_format($syunyuVal - $shisyutsuVal) ?></td>
            </tr>
            <?php 
              $plusSum += $syunyuVal;
              $minusSum +=   $shisyutsuVal;
              $sum += $syunyuVal - $shisyutsuVal;
            ?>
          <?php endforeach ?>
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