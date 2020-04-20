<?php
  // TwitterOAuthライブラリの読み込み
  require_once($_SERVER['DOCUMENT_ROOT'] . '/katwibo/vendor/autoload.php');
  use Abraham\TwitterOAuth\TwitterOAuth;

  //======================================================================
  // htmlspecialchars
  //======================================================================
  function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
  } 

  //======================================================================
  // ログインチェック
  //======================================================================
  function loginCheck() {
    $user = '';
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
          *
        FROM
          users
        WHERE
          id = :id";
      $stmt = $dbh->prepare($sql);
      $stmt->bindValue(':id', $userId);
      $stmt->execute();

      $user = $stmt->fetch();

      // データベース接続
      dbClose($dbh);
    }

    return $user;
  }

  //======================================================================
  // 投稿チェック
  //======================================================================
  function postCheck($post) {
    $bofpFlg = $post['bofp_flg'];
    $money   = $post['money'];
    $memo    = $post['memo'];

    
    // 金額チェック
    if ($money === '') {
      $error['money'] = "blank";
    }

    if ($money <= 0) {
      $error['money'] = "zero";
    }

    // 内容チェック
    if ($memo === '') {
      $error['memo'] = "blank";
      }
    
    if ($memo !== '') {
      if (mb_strlen($memo) >= 100) {
        $error['memo'] = "length";
      }
    }

    return $error;
  }

  //======================================================================
  // 投稿後チェック
  //======================================================================
  function afterPostCheck($postKbn) {
    switch ($_SESSION['post']) {

      case 'post':
        $postFlg = '1';
      break;

      case 'update':
        $postFlg = '2';
      break;
    }    

    // セッション削除
    unset($_SESSION['post']);

    return $postFlg;
  }

  //======================================================================
  // Twitter投稿
  //======================================================================
  function twitterPost($userId, $post, $image) {
    $bofpFlg = $post['bofp_flg'];
    $money   = $post['money'];
    $memo    = $post['memo'];

    // データベース接続
    $dbh = dbConnect();

    // ユーザー情報からTwitter token取得
    $sql = 
      "SELECT
        *
      FROM
        users
      WHERE
        id = :user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':user_id', $userId);
    $stmt->execute();

    $user = $stmt->fetch();

    $accessToken = $user['access_token'];
    $accessTokenSecret = $user['access_token_secret'];

    // Twitter接続
    $twiConnct = new TwitterOAuth(Consumer_Key, Consumer_Secret, $accessToken, $accessTokenSecret);

    // テキスト内容作成
    if ($bofpFlg === '0') {
      $syushi = '支出';
    } else {
      $syushi = '収入';
    }

    // ツイートテキスト作成
    $text = $syushi . PHP_EOL;
    $text .= '金額:' . $money . '円' . PHP_EOL;
    $text .= $memo . PHP_EOL;
    $text .= '#かついぼ';

    // Tweet作成
    // 画像が存在する場合
    if (!empty($image)) {
      // 画像をアップロードし、メディアIDを取得
        $imageId = $twiConnct->upload('media/upload', ['media' => 'images/' . $image]);

      // ツイートパラメータ作成
      $tweet = [
        'status' => $text,
        'media_ids' => implode(',', [
          $imageId->media_id_string
        ])
      ];
    } else {
      $tweet = [
        'status' => $text
      ];
    }

    // ツイート
    $res = $twiConnct->post("statuses/update", $tweet);

    // データベース接続
    dbClose($dbh);
  }

  //======================================================================
  // 収支計算処理
  //======================================================================
  function syushiCalc($userId, $cond) {

    // データベース接続
    $dbh = dbConnect();

    // 前年表示の場合
    if ($cond === '') {
      // SQL作成
      $sql =
        "SELECT
          bofp_flg,
          money,
          created_at
        FROM
          posts
        WHERE
          user_id = :user_id
        ORDER BY
        created_at desc";
    $stmt = $dbh->prepare($sql);
      $stmt->bindValue(':user_id', $userId);
      $stmt->execute();

      $sumLen = 4;

    // 年単位以下の場合
    } else {
      // SQL作成
      $sql =
        "SELECT
          bofp_flg,
          money,
          created_at
        FROM
          posts
        WHERE
          user_id = :user_id
        AND MID(created_at, 1, :condLen) = :cond
        ORDER BY
          created_at desc";
      $stmt = $dbh->prepare($sql);
      $stmt->bindValue(':user_id', $userId);
      $stmt->bindValue(':condLen', strlen($cond));
      $stmt->bindValue(':cond', $cond);
      $stmt->execute();

      $sumLen = (int)strlen($cond) + 3;
    } 

    // データベース切断
    dbClose($dbh);

    $shisyutsu = array();
    $syunyu = array();
    $oldDate = '';
    while ($post = $stmt->fetch()) {
      $date = substr($post['created_at'], 0 ,$sumLen);
      
      // 月が変わったタイミングでキーを追加
      if ($date !== $oldDate) {
        $shisyutsu += array($date => 0);
        $syunyu += array($date => 0);

        $oldDate = $date;
      }

      // 今までの合計を取得
      $shisyutsuSum = $shisyutsu[$date];
      $syunyuSum = $syunyu[$date];

      // 収支合計を産出
      if ($post['bofp_flg'] === '0') {
        $shisyutsuSum += $post['money'];
      } else {
        $syunyuSum += $post['money'];
      }

      // 合計を連想配列に格納
      $shisyutsu[$date] = $shisyutsuSum;
      $syunyu[$date] = $syunyuSum;
    }

    // 戻り値作成
    $syushi = ['shisyutsu' => $shisyutsu, 'syunyu' => $syunyu];

    return $syushi;
  }

?>