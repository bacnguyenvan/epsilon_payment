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
require_once "HTTP/Request2.php";
require_once "xml/Unserializer.php";

// char setting
// サーバ環境に応じ適宜変更してください。
mb_language("Japanese");
mb_internal_encoding("UTF-8");

// 変数の設定
// オーダー情報取得CGIを実行した結果を格納する連想配列
$response = array();

// パラメータとして渡される(GET)トランザクションコードを取得します。
$trans_code = $_REQUEST['trans_code'];
$user_id = $_REQUEST['user_id'];
$result = $_REQUEST['result'];
$order_number = $_REQUEST['order_number'];


print_html($trans_code, $user_id, $result, $order_number);
function print_html($trans_code, $user_id, $result, $order_number){
# HTML出力
  $result_str = $result == 1 ? "正常終了" : "異常終了";
echo <<<EOM
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML lang="ja"><head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
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
イプシロン側トランザクションコード：${trans_code}<br>
お客様側注文番号: ${order_number}<br>
処理対象ユーザID:${user_id}<br>
結果: ${result_str}(${result})<br><br>
</td></tr>
</table>
</BODY>
</HTML>
EOM;
return(1);
}
exit(1);

