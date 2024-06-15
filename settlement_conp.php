<?php

//  EPSILON クレジットカード認証完了、コンビニ受付番号発行完了
//  クライアント側プログラム(PHP版)
//
//  このプログラムの実行には、以下のモジュールが必要です。
//  ・PHP(ver5,,,,,
//  ・PEAR:
//  ・PEAR:HTTP_Request2:
//  ・PEAR:Net_URL2:
//  ・PEAR:XML_Parser:
//  ・PEAR:XML_Serializer:
//

// include Libraly
// PEAR拡張モジュールの読み込み。
// 既に該当のモジュールをインストール済みの場合は適宜読み込み先パスを変更してください。

require 'vendor/autoload.php';
// require_once "HTTP/Request2.php";
// require_once "xml/Unserializer.php";
// Kiểm tra lỗi và cảnh báo
error_reporting(E_ALL);
ini_set('display_errors', 0);

// char setting
// サーバ環境に応じ適宜変更してください。
mb_language("Japanese");
mb_internal_encoding("UTF-8");

// 変数の設定

// オーダー情報確認CGIのURL(試験用)
// オーダー情報確認CGIについては2種類ありますのでご注意お願いいたします。
// 詳細はCGI設定マニュアルの「オーダー情報確認CGI-1,2」をご確認願います。
// 各CGIの説明
// CGI-1:認証にパスワード要/発信元IP無制限
// CGI-2:認証にパスワード不要/発信元IPの制限有

// 以下にはCGI-1と2で設定パラメータが相違しますので利用されるCGIによって変更してください。

// 接続先URLの設定

// CGI-1利用の場合 本番環境への接続の場合は契約後弊社からご連絡いたしますURLに変更してください
//$getsales_url = 'https://beta.epsilon.jp/client/getsales.cgi';
// CGI-2利用の場合 本番環境への接続の場合は契約後弊社からご連絡いたしますURLに変更してください
//my $getsales_url = 'https://beta.epsilon.jp/cgi-bin/order/getsales2.cgi';

// 発行されたユーザーID(契約コード)を入力してください
// CGI-1,2共通
$user_id = $_REQUEST['user_id'];

// 発行されたパスワードを入力してください。
// CGI-1利用の場合 弊社からお知らせしたパスワードを設定してください。
// CGI-2利用の場合 当値は利用しませんので適当な値を設定してください。txZaDA9o,j6ralKCA
$passwd  = '';

// エラーが発生した場合のメッセージ
$err_msg;

// オーダー情報取得CGIを実行した結果を格納する連想配列
$response = array();

// 各支払い方法
$payment_name = array(
    1 => "VISA/MASTER/DINERS",
    2 => "JCB/AMEX",
    3 => "コンビニエンスストア",
    4 => "ジャパンネット銀行",
    5 => "楽天銀行",
    6 => "カンガルー代引",
    7 => "ペイジー",
    8 => "ウェブマネー",
    9 => "Yahoo!ウォレット",
    11 => "PayPal",
    12 => "BitCash",
    13 => "電子マネーちょコム",
    14 => "ゆうパックコレクト",
    15 => "スマートフォンキャリア決済",
    16 => "JCB　PREMO",
    17 => "SBIネット銀行",
    18 => "GMO後払い",
    19 => "多通貨クレジットカード決済",
);

// コンビニ支払の場合の支払い方法の簡単な説明
$setsumei = array(
    # セブンイレブン
    11 => "以下の払込票ページをプリントアウトされるか、払込票番号をメモして<br>" .
          "最寄りのセブンイレブンのレジにてお支払いください。<br>" ,
    # ファミリーマート
    21 => "ファミリーマート店頭にございます Famiポート／ファミネットにて<br>" .
          "以下の２つの数字をご入力頂き、発行されるFamiポート申込券をレジまで<br>" .
          "お持ちになり代金をお支払いください。<br>",
    # ローソン
    31 => "ローソンの店内に設置してあるLoppiのトップ画面の中から、「インターネット受付」<br>" .
          "をお選びください。次画面のジャンルの中から「インターネット受付」をお選び頂き、<br>" .
          "画面に従って以下の「お支払い受付番号」と、ご登録頂いた「電話番号」をご入力下さい。<br>" ,
    # セイコーマート
    32 => "セイコーマートの店内に設置してあるセイコーマートクラブステーション（情報端末）の<br>" .
          "トップ画面の中から、「インターネット受付」をお選び下さい。画面に従って以下の<br>" .
          "「お支払い受付番号」と、ご登録頂いた「電話番号」をご入力下さい。<br>" ,
    # ミニストップ
    33 =>  "ミニストップの店内に設置してあるLoppiのトップ画面の中から、「各種番号受付をお持ちの方」<br>" +
           "をお選びください。<br>画面に従って以下の「お支払い受付番号」と、ご登録頂いた「電話番号」をご入力下さい。<br>" ,
    # サークルK
    35 => "サークルKの店内に設置してあるKステーション（情報端末）の<br>" +
          "トップ画面の中から、「各種支払い」をお選び下さい。<br>" +
          "「6桁の番号をお持ちの方」をお選びください。<br>オンライン決済番号を入力してお支払いをお選びください<br> "+
          "画面にしたがって以下の「お支払い受付番号」と、ご登録頂いた「電話番号」をご入力下さい。<br>" ,
    # サンクス
    36 => "サンクスの店内に設置してあるKステーション（情報端末）の<br>" +
          "トップ画面の中から、「各種支払い」をお選び下さい。<br>" +
          "「6桁の番号をお持ちの方」をお選びください。<br>オンライン決済番号を入力してお支払いをお選びください<br> "+
          "画面にしたがって以下の「お支払い受付番号」と、ご登録頂いた「電話番号」をご入力下さい。<br>"
);

// パラメータとして渡される(GET)トランザクションコードを取得します。
$trans_code = $_REQUEST['trans_code'];


// 結果問い合わせ用HTTPリクエスト送信

// CGI-1利用の場合 
// ※オーダー情報確認CGIの実行にはベーシック認証が必要です。 
$request = new HTTP_Request2($getsales_url, HTTP_Request2::METHOD_POST);
$request->setConfig(array(
  'ssl_verify_peer' => false,
#  'ssl_verify_peer' => true,
  'ssl_cafile' => '/etc/ssl/certs/ca-bundle.crt', //ルートCA証明書ファイルを指定
));
$request->setHeader("Content-Type","application/x-www-form-urlencoded");
$request->setBody("trans_code=" . $trans_code);

$request->setAuth($user_id, $passwd,HTTP_Request2::AUTH_BASIC);
$response = $request->send();


// CGI-2利用の場合 
//$request = new HTTP_Request2($getsales_url, HTTP_Request2::METHOD_POST);
//$request->setHeader("Content-Type","application/x-www-form-urlencoded");
//$request->setBody("trans_code=" . $trans_code . "&contract_code=" . $user_id);
//$response = $request->send();

// 以降はCGI-1,2どちらも共通です。

if (PEAR::isError($response)) {
  // インターフェイスCGIの実行に失敗した場合
  	$err_msg = "データの送信に失敗しました<br><br>";
  	$err_msg .= "<br />res_statusCd=" . $response->getStatus();
  	$err_msg .= "<br />res_status=" . $response ->getHeader('Status');
	echo $err_msg;
    exit;
}


// CGIの実行に成功した場合、応答内容(XML)を解析します
// 応答内容(XML)の解析

$res_code = $response ->getStatus();
$res_content = $response ->getBody();
#fputs(STDERR, $res_content );
//xml unserializer
$temp_xml_res = $res_content;#str_replace("x-sjis-cp932", "Shift-JIS", $res_content);
$unserializer = new XML_Unserializer();
$unserializer->setOption('parseAttributes', TRUE);
$unseriliz_st = $unserializer->unserialize($temp_xml_res);
if ($unseriliz_st === true) {
	//xmlを解析
	$res_array = $unserializer->getUnserializedData();
	//error check
	if($res_array['result']['result'] == "0"){
		echo "処理に失敗しました<br><br>";
    	exit(1);
	}

	$res_param_array = array();
	//pram setting
	foreach($res_array['result'] as $uns_k => $uns_v){
		list($result_atr_key, $result_atr_val) = each($uns_v);
		$res_param_array[$result_atr_key] =  mb_convert_encoding(urldecode($result_atr_val), "UTF-8" ,"auto");
	}
	$debug_printj .=  "<br />xml_memo2_msg=" . $xml_memo2_msg;
	
}else{
	//xml parser error
  	echo "xml parser error<br><br>";
    exit(1);
}
$result_html;

if ($res_param_array['payment_code'] == 3){
  // コンビニ支払の場合
  if ($res_param_array['conveni_code'] == 11){
    // セブンイレブンの場合
    $result_html = $setsumei[11] . "<br><br>\n";
    $result_html .= "払込票 : <a href=\"" . $res_param_array['haraikomi_url'] . "\">ここをクリック</a>  <br>\n";
    $result_html .= "払込票番号 : " . $res_param_array['receipt_no'] . "<br>\n";
  }
  elseif ($res_param_array['conveni_code'] == 21){
    // ファミリーマートの場合
    $result_html = $setsumei[21] . "<br><br>\n";
    $result_html .="企業コード： " . $res_param_array['kigyou_code'] . "<br>\n";
    $result_html .= "注文番号 : " . $res_param_array['receipt_no'] . "<br>\n";
  }
  elseif ( 31 <= $res_param_array['conveni_code']  && $res_param_array['conveni_code'] <= 36 ){
    // ローソン、セイコーマートの場合
    $result_html = $setsumei{$res_param_array{'conveni_code'}} . "<br><br>\n";
    $result_html .= "お支払い受付番号 : " . $res_param_array['receipt_no'] .  "<br>\n";
  }
  else {  // 不明(異常)
    print_html("支払情報の取得に失敗しました : " . $res_param_array{'conveni_code'});
    exit(0);
  }
  $conveni_limit_date = split("-",$res_param_array['conveni_limit']);
  $result_html .= "<br>支払期限：" . $conveni_limit_date[0] . "年"
                . $conveni_limit_date[1] . "月" . $conveni_limit_date[2] . "日<br>\n";
  print_html("",$payment_name[$res_param_array['payment_code']],$res_param_array['item_name'],$res_param_array['item_price'],$result_html);
  exit (0);
}
else {
  // それ以外の決済の場合
  print_html("",$payment_name[$res_param_array['payment_code']],$res_param_array['item_name'],$res_param_array['item_price'],"ご注文ありがとうございました。");
  exit(0);
}

// HTML出力
function print_html($err_msg,$payment_name,$item_name,$item_price,$result_setsumei){
echo <<<EOM
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML lang="ja"><head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=EUC-JP">
<title>EPSILON・クライアント側サンプル</title>
<STYLE TYPE="text/css">
<!--
TABLE.S1 {font-size: 9pt; border-width: 0px; background-color: #FAE9E6; font-size: 9pt;}
TD.S1   {  border-width: 0px; background-color: #FAE9E6;color: #505050; font-size: 9pt;}
TH.S1   {  border-width: 0px; background-color: #DC9485;color: #FAE9E6; font-size: 9pt;}
TABLE {  border-style: solid;  border-width: 1px;  border-color: #DC9485; font-size: 8pt;}
TD   {  text-align: center; border-style: solid;  border-width: 2px; 
        border-color: #FFFFFF #CCCCCC #CCCCCC #FFFFFF; color: #505050; font-size: 8pt;}
TH   {  background-color: #DC9485;border-style: solid;  border-width: 2px;
        border-color: #DDDDDD #AAAAAA #AAAAAA #DDDDDD; color: #FAE9E6; font-size: 8pt;}
-->
</STYLE>
</HEAD>
<BODY BGCOLOR="#FAE9E6" text="#505050" link="#555577" vlink="#555577" alink="#557755">
<BR>
<table class=S1 width="500" border="0" cellpadding="0" cellspacing="0">
<tr class=S1><td class=S1>
<table class=S1 width="100%" cellpadding="6" align=center>
<tr class=S1><th class=S1 align=left><big> 完了画面 (試験用サンプル画面)</big></th></tr>
</table>

<br>
決済方法：{$payment_name}<br><br>
商品名: {$item_name}<br>
価格: {$item_price}円<br><br>

{$result_setsumei}
<br>{$err_msg}
</td></tr>
</table>
</BODY>
</HTML>
EOM;
return(1);
}

exit(1);
