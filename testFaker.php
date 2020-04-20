<?php
  // faker読み込み
  require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/dbFunc.php');
  require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/util/function.php');
  require_once('vendor/fzaninotto/faker/src/autoload.php');

  // フェイクデータを生成するジェネレータを作成
  $faker = Faker\Factory::create('ja_JP');

  // データベース接続
  $dbh = dbConnect();

  // テストデータ作成
  for ($i = 0; $i < 999999; $i++) {
    // ユーザーID
    $userId = $faker->numberBetween(15, 20);
    // 収支フラグ
    $bofpFlg = $faker->numberBetween(0, 1);
    // 金額
    $money = $faker->numberBetween(1, 99999);
    // メモ
    $memo = $faker->realText($faker->numberBetween(10,20));
    // 作成日時
    $date = $faker->date;
    $time = $faker->time;
    $dateTime = $date . ' ' . $time;

    // インサート
    $sql = 
      "INSERT INTO posts (user_Id, bofp_flg, money, memo, created_at)
      VALUES (:userId, :bofpFlg, :money, :memo, :dateTime)";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':userId',  $userId);
    $stmt->bindValue(':bofpFlg',  $bofpFlg);
    $stmt->bindValue(':money',  $money);
    $stmt->bindValue(':memo',  $memo);
    $stmt->bindValue(':dateTime',  $dateTime);
    $stmt->execute();
  }

  // データベース切断
  dbClose($dbh);

  echo "テストデータ作成完了";
?>


