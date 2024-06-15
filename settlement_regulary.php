<?php

//  EPSILON 定期課金金額変更/キャンセル(PHP版)
//
//
//  このプログラムの実行には、以下のモジュールが必要です。
//  ・PHP(ver5,,,,,
//  ・PEAR:
//  ・PEAR:HTTP_Request2:
//  ・PEAR:Net_URL2:
//  ・PEAR:XML_Parser:
//  ・PEAR:XML_Serializer:


// include Libraly
// PEAR拡張モジュールの読み込み。
// 既に該当のモジュールをインストール済みの場合は適宜読み込み先パスを変更してください。
require_once "xml/Unserializer.php";
require_once "HTTP/Request2.php";

// char setting
// サーバ環境に応じ適宜変更してください。
mb_language("Japanese");
mb_internal_encoding("UTF-8");


// 変数の初期化

// FORMで送信した内容をこのプログラムファイルで受け取るために、プログラムファイルの名前を設定します。
$my_self = "settlement_regulary.php"; 

// オーダー情報送信先URL(試験用)
// 本番環境でご利用の場合は契約時に弊社からお送りするURLに変更してください。
$api_url = array(
   change => "https://beta.epsilon.jp/cgi-bin/order/regularly_amount_change.cgi",
   cancel => "https://beta.epsilon.jp/cgi-bin/order/regularly_cancel.cgi",
);

//// 以下の各項目についてご利用環境に沿った設定に変更してください

// 契約番号(8桁) オンライン登録時に発行された契約番号を入力してください。
$contract_code = '********';


// 追加情報 1,2  (入力は必須ではありません)
$memo1 = "試験用オーダー情報";
$memo2 = "";

// エラーが発生した場合のメッセージ
$err_msg;

// オーダー情報を送信した結果を格納する連想配列
$response = array();

// CGIのパラメータを取得

// ユーザー固有情報
// ここでは仮にフォームに入力してもらっていますが、ユーザーID等の値はクライアント様側で
// 管理されている値を使用してください。
$user_id = $_REQUEST['user_id'];        // ユーザID
$item_code = $_REQUEST['item_code'];    // 商品コード
$item_price = $_REQUEST['item_price'];  // メールアドレス
$mode = $_REQUEST['mode'];              // 金額変更/解除指定

// CGIの状態(入力画面から実行されたか、確認画面から実行されたか)を判別する値
$come_from = $_REQUEST['come_from'];        // CGIの状態設定

// パラメータの確認
if ($come_from == 'here'){
    # 入力チェック
    if( empty($item_code) ){
        $err_msg = "商品コードを入力してください。";
    }
    if( empty( $user_id ) ){
        $err_msg = "ユーザ名を入力してください。";
    }
    if( $mode =='change' && empty( $item_price ) ){
        $err_msg = "金額変更の場合変更後金額を入力してください。";
    }
    if (!empty($err_msg)){
      // パラメータに異常がある場合は、もう一度入力画面を表示します。
      order_form();
      exit(1);
    }
    else{
      // パラメータを正常に受け取れた場合は、購入確認画面を表示します。
      kakunin();
      exit(0);
    }
}
elseif ($come_from == 'kakunin'){  // 購入確認画面で[確認]ボタンを押した場合
	
	
  //EPSILONに情報を送信します。

  // httpリクエスト用のオプションを指定
  $option = array(
    "timeout" => "20", // タイムアウトの秒数指定
  //    "allowRedirects" => true, // リダイレクトの許可設定(true/false)
  //    "maxRedirects" => 3, // リダイレクトの最大回数
  );

  // HTTP_Requestの初期化

  $request = new HTTP_Request2($api_url[$mode], HTTP_Request2::METHOD_POST, $option);
  $request->setConfig(array(
     'ssl_verify_peer' => false,
#     'ssl_verify_peer' => true,
#     'ssl_cafile' => '/etc/ssl/certs/ca-bundle.crt', // ルートCA証明書ファイルを指定
  ));  
  // HTTPのヘッダー設定
  //$http->addHeader("User-Agent", "xxxxx");
  //$http->addHeader("Referer", "xxxxxx");

  //set post data
  $request->addPostParameter('contract_code', $contract_code);
  $request->addPostParameter('user_id', $user_id);
  $request->addPostParameter('item_code', $item_code);
  $request->addPostParameter('charset', 'UTF8' );
  $request->addPostParameter('version', '2' );
  $request->addPostParameter('xml', '1');
  if ( $mode == 'change' ){
      $request->addPostParameter('item_price', $item_price);
  }
 
  // HTTPリクエスト実行
  $response = $request->send();
  if (!PEAR::isError($response)) {

    // 応答内容(XML)の解析
  	$res_code = $response->getStatus();
  	$res_content = $response->getBody();

		//xml unserializer
		$temp_xml_res = str_replace("x-sjis-cp932", "UTF-8", $res_content);
		$unserializer =& new XML_Unserializer();
		$unserializer->setOption('parseAttributes', TRUE);
		$unseriliz_st = $unserializer->unserialize($temp_xml_res);
		if ($unseriliz_st === true) {
			//xmlを解析
			$res_array = $unserializer->getUnserializedData();
			$is_xml_error = false;
			$err_code = "";
			$err_detail = "";
            $result = "";
			foreach($res_array['result'] as $uns_k => $uns_v){
				//$debug_printj .=  "<br />k=" . $uns_k;
	    		list($result_atr_key, $result_atr_val) = each($uns_v);
				//$debug_printj .=  "<br />result_atr_key=" . $result_atr_key;
				//$debug_printj .=  "<br />result_atr_val=" . $result_atr_val;

			    switch ($result_atr_key) {
                  case 'result':
                    $result = mb_convert_encoding(urldecode($result_atr_val), "UTF-8" ,"auto");
                    break;
			      case 'err_code':
                    $err_code = mb_convert_encoding(urldecode($result_atr_val), "UTF-8" ,"auto");
					break;
				  case 'err_detail':
                    $err_detail = mb_convert_encoding(urldecode($result_atr_val), "UTF-8" ,"auto");
					break;
				  default:
			        break;
    			}
			}
			
		}else{
			//xml parser error
		  	$err_msg = "xml parser error<br><br>";
  			order_form();
		    exit(1);
		}
	
	
	
  }else{ //http error
  	$err_msg = "データの送信に失敗しました<br><br>";
  	$err_msg .= "<br />res_statusCd=" . $request->getResponseCode();
  	$err_msg .= "<br />res_status=" . $request->getResponseHeader('Status');
	$err_msg .= "<br .>ErrorMessage" . $response->getMessage();
  	
  	order_form();
    exit(1);
	
  }
  if ( $result != 1 ) {

      # データ送信結果が失敗だった場合、オーダー入力画面に戻し、エラーメッセージを表示します。
      $err_msg = sprintf("%s:%s", $err_code, $err_detail);
      result_page();
      exit(1);
  }else{
      result_page( $result );
      exit(0);
  }

}
order_form();


// オーダー入力フォーム表示
// 以下はお客様がご購入の際閲覧するWeb画面となります。画面イメージ等は貴社のポリシーに沿った形で変更願います。


function order_form(){

global $my_self, $item_code, $user_id, $mode, $item_price, $user_id, $err_msg;
	$mode_change = "";
	$mode_cancel = "";
	if( $mode == "cancel" ){
		$mode_cancel = "checked";
	}else{
		$mode_change = "checked";
	}
echo <<<EOM
<html lang="ja"><head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
<title>定期課金金額変更/削除画面</title>
<STYLE TYPE="text/css">
<!--
TABLE.S1 {font-size: 9pt; border-width: 0px; background-color: #E6ECFA; font-size: 9pt;}
TD.S1   {  border-width: 0px; background-color: #E6ECFA;color: #505050; font-size: 9pt;}
TH.S1   {  border-width: 0px; background-color: #7B8EB4;color: #E6ECFA; font-size: 9pt;}
TABLE {  border-style: solid;  border-width: 1px;  border-color: #7B8EB4; font-size: 8pt;}
TD   {  text-align: center; border-style: solid;  border-width: 2px; border-color: #FFFFFF #CCCCCC #CCCCCC #FFFFFF; color: #505050; font-size: 8pt;}
TH   {  background-color: #7B8EB4;border-style: solid;  border-width: 2px; border-color: #DDDDDD #AAAAAA #AAAAAA #DDDDDD; color: #E6ECFA; font-size: 8pt;}
-->
</STYLE>
<script>
function init(){
if( !document.getElementsByName('mode')[0].checked ){
      document.getElementsByName('item_price')[0].disabled=true;
   }
}
</script>
</HEAD>
<BODY BGCOLOR="#E6ECFA" text="#505050" link="#555577" vlink="#555577" alink="#557755" onload="init()">
<BR>
<form action="${my_self}" method="post">
<table class=S1 width="400" border="0" cellpadding="0" cellspacing="0">
<tr class=S1><td class=S1>

<table class=S1 width="100%" cellpadding="6" align=center>
<tr class=S1><th class=S1 align=left><big>定期課金金額変更/削除サンプル</big></th></tr>
</table>

<font color="#EE5555"> ${err_msg} </font><br>

<table class=S1 width="90%" align=center>
 <tr class=S1>
  <td class=S1>
    <br>以下の項目を入力してください<br>
   <table cellspacing=4 cellpadding=4 align="left">
    <tr>
        <td>処理対象</td>
        <td><label><input type="radio" name="mode" value="change" onclick="document.getElementsByName('item_price')[0].disabled=false;" ${mode_change}>金額変更</label>
        　　<label><input type="radio" name="mode" value="cancel" onclick="document.getElementsByName('item_price')[0].disabled=true;" ${mode_cancel}>定期課金解除</label>
        </td>
    </tr>
    <tr>
     <td>ユーザーID</td>
     <td><input type="text" name="user_id" value="${user_id}"></td>
    </tr>
    <tr>
     <td>商品コード</td>
     <td><input type="text" name="item_code" value="${item_code}"></td>
    </tr>
    <tr>
     <td>商品価格</td>
     <td><input type="text" name="item_price" value="${item_price}"></td>
    </tr>
   </table>
  </td>
 </tr>
 <tr class=S1>
  <td class=S1>
    <br>
    <input type="hidden" name="come_from" value="here">
    <input type="submit" name="go" value="送信">
  </td>
 </tr>
</table>
  </td>
 </tr>
</table>
</form>
</BODY>
</HTML>
EOM;
return(1);
}

// 購入確認画面表示
function kakunin(){

global $my_self, $item_code, $item_price, $user_id, $mode, $err_msg;
    $confirm_text = "";

    $exec = "キャンセル";
    if ( $mode == 'change' ) {
        $confirm_text = sprintf("変更後金額: %d円<br>\n", $item_price );
        $exec = "金額変更";
    }


echo <<<EOM
<html lang="ja"><head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
<title>商品購入サンプル画面</title>
<STYLE TYPE="text/css">
<!--
TABLE.S1 {font-size: 9pt; border-width: 0px; background-color: #E6ECFA; font-size: 9pt;}
TD.S1   {  border-width: 0px; background-color: #E6ECFA;color: #505050; font-size: 9pt;}
TH.S1   {  border-width: 0px; background-color: #7B8EB4;color: #E6ECFA; font-size: 9pt;}
TABLE {  border-style: solid;  border-width: 1px;  border-color: #7B8EB4; font-size: 8pt;}
TD   {  text-align: center; border-style: solid;  border-width: 2px; border-color: #FFFFFF #CCCCCC #CCCCCC #FFFFFF; color: #505050; font-size: 8pt;}
TH   {  background-color: #7B8EB4;border-style: solid;  border-width: 2px; border-color: #DDDDDD #AAAAAA #AAAAAA #DDDDDD; color: #E6ECFA; font-size: 8pt;}
-->
</STYLE>
</HEAD>
<BODY BGCOLOR="#E6ECFA" text="#505050" link="#555577" vlink="#555577" alink="#557755">
<BR>
<table class=S1 width="400" border="0" cellpadding="0" cellspacing="0">
<tr class=S1><td class=S1>

<table class=S1 width="100%" cellpadding="6" align=center>
<tr class=S1><th class=S1 align=left><big>商品購入サンプル</big></th></tr>
</table>

<table class=S1 width="90%" align=center>
 <tr class=S1>
  <td class=S1>
  <br>以下の定期課金を${exec}します。<br>
      よろしければ[確認]ボタンを押してください。<br><br>
      ユーザID: ${user_id} <br>
      商品コード: ${item_code}<br>
	${confirm_text}
    <br>
    <table class=S1 align=center width="50%">
     <tr class=S1>
      <td class=S1>
       <form action="${my_self}" method="post">
        <input type="hidden" name="item" value="${item}">
        <input type="hidden" name="item_code" value="${item_code}">
        <input type="hidden" name="item_price" value="${item_price}">
        <input type="hidden" name="user_id" value="${user_id}">
        <input type="hidden" name="mode" value="${mode}">
        <input type="submit" name="go" value="戻る">
       </form>
      </td>
      <td class=S1>
       <form action="${my_self}" method="post">
        <input type="hidden" name="item" value="${item}">
        <input type="hidden" name="item_code" value="${item_code}">
        <input type="hidden" name="item_price" value="${item_price}">
        <input type="hidden" name="user_id" value="${user_id}">
        <input type="hidden" name="mode" value="${mode}">
        <input type="hidden" name="come_from" value="kakunin">
        <input type="submit" name="go" value="確認">
       </form>
      </td>
     </tr>
    
  </td>
 </tr>
</table>
  </td>
 </tr>
</table>
</form>
</BODY>
</HTML>
EOM;
return(1);
}
# process_code 3以降用確認画面
function result_page($result) {
	global $err_msg;
    $result = $result == 1 ? "正常終了<br>" : "異常終了<br>";
echo<<<EOM
  <html lang="ja"><head>
  <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
  <title>商品購入サンプル画面</title>
  <STYLE TYPE="text/css">
  <!--
  TABLE.S1 {font-size: 9pt; border-width: 0px; background-color: #E6ECFA; font-size: 9pt;}
  TD.S1   {  border-width: 0px; background-color: #E6ECFA;color: #505050; font-size: 9pt;}
  TH.S1   {  border-width: 0px; background-color: #7B8EB4;color: #E6ECFA; font-size: 9pt;}
  TABLE {  border-style: solid;  border-width: 1px;  border-color: #7B8EB4; font-size: 8pt;}
  TD   {  text-align: center; border-style: solid;  border-width: 2px; border-color: #FFFFFF #CCCCCC #CCCCCC #FFFFFF; color: #505050; font-size: 8pt;}
  TH   {  background-color: #7B8EB4;border-style: solid;  border-width: 2px; border-color: #DDDDDD #AAAAAA #AAAAAA #DDDDDD; color: #E6ECFA; font-size: 8pt;}
  -->
  </STYLE>
  </HEAD>
  <BODY BGCOLOR="#E6ECFA" text="#505050" link="#555577" vlink="#555577" alink="#557755">
  <BR>
  <table class=S1 width="400" border="0" cellpadding="0" cellspacing="0">
  <tr class=S1><td class=S1>

  <table class=S1 width="100%" cellpadding="6" align=center>
  <tr class=S1><th class=S1 align=left><big>定期課金変更サンプル</big></th></tr>
  </table>
	${err_msg}<br>
  <table class=S1 width="90%" align=center>
    <tr class=S1><td class=S1>
       ${result}
    </td>
    </tr>
  </table>
</table>
</form>
</BODY>
</HTML>
EOM;
}
exit(0);

?>
