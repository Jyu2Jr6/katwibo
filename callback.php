<?php
session_start();
require(__DIR__ . '/util/dbFunc.php');
require(__DIR__ . '/util/function.php');
require(__DIR__ . '/util/const.php');
 
// TwitterOAuthを読み込み
require "vendor/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;
 
//oauth_tokenとoauth_verifierを取得
if($_SESSION['oauth_token'] == $_GET['oauth_token'] and $_GET['oauth_verifier']){
	
	//Twitterからアクセストークンを取得する
	$connection = new TwitterOAuth(Consumer_Key, Consumer_Secret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
	$access_token = $connection->oauth('oauth/access_token', array('oauth_verifier' => $_GET['oauth_verifier'], 'oauth_token'=> $_GET['oauth_token']));
 
	//取得したアクセストークンでユーザ情報を取得
	$user_connection = new TwitterOAuth(Consumer_Key, Consumer_Secret, $access_token['oauth_token'], $access_token['oauth_token_secret']);
	$user_info = $user_connection->get('account/verify_credentials');	
	
	// ユーザ情報の展開
  // var_dump($user_info);
  // echo $user_info->id . "<br>";
  // echo $access_token['oauth_token'] . "<br>";
  // echo $access_token_secret['oauth_token_secret'] . "<br>";
  // echo $user_info->profile_image_url_https . "<br>";
  // exit();
  
	// ユーザ情報を取得
	// $id = $user_info->id;
	// $name = $user_info->name;
	// $screen_name = $user_info->screen_name;
	// $profile_image_url_https = $user_info->profile_image_url_https;
  // $text = $user_info->status->text;
  
  // ユーザー情報をDBに格納
  $dbh = dbConnect();

  // ユーザーを取得（重複チェック）
  $sql = 
    "SELECT
      *
    FROM
      users
    WHERE
      access_token = :access_token";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':access_token', $access_token['oauth_token']);
  $stmt->execute();

  $user = $stmt->fetch();

  // ユーザーマスターに登録されていない場合
  if (!$user) {
      $sql = 
      "INSERT INTO users (name, access_token, access_token_secret, twitter_img_path)
      VALUES (:name, :access_token, :access_token_secret, :twitter_img_path)";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':name', $user_info->name);
    $stmt->bindValue(':access_token', $access_token['oauth_token']);
    $stmt->bindValue(':access_token_secret', $access_token['oauth_token_secret']);
    $stmt->bindValue(':twitter_img_path', $user_info->profile_image_url_https);
    $stmt->execute();

    // 登録されたユーザーを取得
    $sql = 
      "SELECT
        *
      FROM
        users
      WHERE
      access_token = :access_token";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':access_token', $access_token['oauth_token']);
    $stmt->execute();

    $user = $stmt->fetch();
  }

  // ユーザー情報をセッションに格納
  $_SESSION['id'] = $user['id'];
  $_SESSION['time'] = time();

  // データベース切断
  dbClose($dbh);

	header('Location: index.php');
	exit();
}else{
	header('Location: index.php');
  exit();
}