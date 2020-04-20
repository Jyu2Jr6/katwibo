<?php
  session_start();

  // TwiiterOAut読み込み
  require "vendor/autoload.php";
  use Abraham\TwitterOAuth\TwitterOAuth;

  // Twitter API Key
  define("Consumer_Key", "Fwx2K55oruja6dyjsXS4Jpz3d");
  define("Consumer_Secret", "b1XL9E3IE3vEpKeqxoACw0p4IpeNkLVWum9PQ3N6ySyasdclvh");  
  //Callback URL
  define('Callback', 'https://jyu2-engineer.com/katwibo/callback.php');

  //TwitterOAuthのインスタンスを生成し、Twitterからリクエストトークンを取得する
  $connection = new TwitterOAuth(Consumer_Key, Consumer_Secret);
  $request_token = $connection->oauth("oauth/request_token", array("oauth_callback" => Callback));

  //リクエストトークンはcallback.phpでも利用するのでセッションに保存する
  $_SESSION['oauth_token'] = $request_token['oauth_token'];
  $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

  // Twitterの認証画面へリダイレクト
  $url = $connection->url("oauth/authorize", array("oauth_token" => $request_token['oauth_token']));
  header('Location: ' . $url);
?>