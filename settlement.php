<?php
//  EPSILON オーダー情報送信プログラム(PHP版)
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
require 'vendor/autoload.php';
// require_once "xml/Unserializer.php";
// require_once "HTTP/Request2.php";


// char setting
// サーバ環境に応じ適宜変更してください。
mb_language("Japanese");
mb_internal_encoding("UTF-8");


// 変数の初期化


// FORMで送信した内容をこのプログラムファイルで受け取るために、プログラムファイルの名前を設定します。
$my_self = "settlement.php"; 


// オーダー情報送信先URL(試験用)
// 本番環境でご利用の場合は契約時に弊社からお送りするURLに変更してください。
$order_url = "https://beta.epsilon.jp/cgi-bin/order/receive_order3.cgi";


// 注文結果取得CGI(試験用)
// 展開環境に応じて適宜変更してください。
$confirm_url = "./settlement_conp.php";


// 以下の各項目についてご利用環境に沿った設定に変更してください
// 契約番号(8桁) オンライン登録時に発行された契約番号を入力してください。
$contract_code = "74885520";


// 注文番号(注文毎にユニークな番号を割り当てます。ここでは仮に乱数を使用しています。)
$order_number = rand(0,99999999);

// 決済区分 (使用したい決済方法を指定してください。登録時に申し込まれていない決済方法は指定できません。)
// 指定方法はCGI設定マニュアルの「決済区分について」を参照してください。
$st_code = array( 'normal'  => '10100-0000-00000-00010-00000-00000-00000',
                  'card'    => '10000-0000-00000-00000-00000-00000-00000',
                  'conveni' => '00100-0000-00000-00000-00000-00000-00000',
                  'atobarai'=> '00000-0000-00000-00010-00000-00000-00000',
);


// 追加情報 1,2  (入力は必須ではありません)
$memo1 = "試験用オーダー情報";
$memo2 = "";


// 商品コード (商品毎に識別コードを指定してください。ここでは仮に固定の値を指定しています。)
$item_code = "abc12345";


// 商品リストサンプル
$goods = array( 'mouse'    => array('name' => 'マウス', 'price' => '800')
              , 'keyboard' => array('name' => 'キーボード', 'price' => '2980')
              , 'disp'     => array('name' => 'ディスプレイ', 'price' => '19800')
              , 'printer'  => array('name' => 'プリンタ', 'price' => '34800')
			  , 'camera'   => array('name' => 'デジカメ', 'price' => '42000')
             );


// 課金区分
$mission_item = array(
    1 => '1回課金',
    21 => '定期課金1',
    22 => '定期課金2',
    23 => '定期課金3',
    24 => '定期課金4',
    25 => '定期課金5',
    26 => '定期課金6',
    27 => '定期課金7',
    28 => '定期課金8',
    29 => '定期課金9',
    30 => '定期課金10',
    31 => '定期課金11',
    32 => '定期課金12',
);

// 処理区分
$process_item = array(
    1 => '初回課金',
    2 => '登録済み課金',
    3 => '登録のみ',
    4 => '登録内容変更',
    7 => '退会取消',
    9 => '退会',
);

// コンビニコード
$conveni_item = array(
     0 => '-',
    11 => 'セブンイレブン',
    21 => 'ファミリーマート',
    31 => 'ローソン',
    32 => 'セイコーマート',
    33 => 'ミニストップ',
    35 => 'サークルK',
    36 => 'サンクス',
);
$pref_list = array(    
    11 => '北海道',
    12 => '青森県',
    13 => '岩手県',
    14 => '宮城県',
    15 => '秋田県',
    16 => '山形県',
    17 => '福島県',
    18 => '茨城県',
    19 => '栃木県',
    20 => '群馬県',
    21 => '埼玉県',
    22 => '千葉県',
    23 => '東京都',
    24 => '神奈川県',
    25 => '新潟県',
    26 => '富山県',
    27 => '石川県',
    28 => '福井県',
    29 => '山梨県',
    30 => '長野県',
    31 => '岐阜県',
    32 => '静岡県',
    33 => '愛知県',
    34 => '三重県',
    35 => '滋賀県',
    36 => '京都府',
    37 => '大阪府',
    38 => '兵庫県',
    39 => '奈良県',
    40 => '和歌山県',
    41 => '鳥取県',
    42 => '島根県',
    43 => '岡山県',
    44 => '広島県',
    45 => '山口県',
    46 => '徳島県',
    47 => '香川県',
    48 => '愛媛県',
    49 => '高知県',
    50 => '福岡県',
    51 => '佐賀県',
    52 => '長崎県',
    53 => '熊本県',
    54 => '大分県',
    55 => '宮崎県',
    56 => '鹿児島県',
    57 => '沖縄県'
);
// 変更設定ここまで


// エラーが発生した場合のメッセージ
$err_msg;


// オーダー情報を送信した結果を格納する連想配列
$response = array();

// 商品名、価格
$item_name = "";
$item_price = 0;

// CGIのパラメータを取得
$item = $_REQUEST['item']; // 商品の番号(このphpの中でのみ使用する値です)

if ($item){
    // 商品リストサンプルの連想配列から、商品名と価格を取り出しています。
    // 商品名と価格
    $item_name = $goods[$item]['name'];
    $item_price = $goods[$item]['price'];
}



// 課金区分 (1:一回のみ 21～32:定期課金)
// 定期課金について契約がない場合は利用できません。また、定期課金を設定した場合決済区分はクレジットカード決済のみとなります。
$mission_code = $_REQUEST['mission_code'];
if( empty( $mission_code ) ){
	$mission_code = 1;
}
// 処理区分 (1:初回課金 2:登録済み課金 3:登録のみ 4:登録変更 )
// 各処理区分のご利用に関してはCGI設定マニュアルの「処理区分について」を参照してください。
$process_code = $_REQUEST['process_code'];
if( empty( $process_code ) ){
	$process_code = 1;
}

// ユーザー固有情報
// ここでは仮にフォームに入力してもらっていますが、ユーザーID等の値はクライアント様側で
// 管理されている値を使用してください。
$conveni_code = $_REQUEST['conveni_code'];
$user_tel = $_REQUEST['user_tel'];          // ユーザ電話番号
$user_name_kana = $_REQUEST['user_name_kana']; // ユーザー名(カナ)

$user_id = $_REQUEST['user_id'];            // ユーザーID
$user_name = $_REQUEST['user_name'];        // ユーザー氏名
$user_mail_add = $_REQUEST['user_mail_add'];// メールアドレス
$st = $_REQUEST['st'];
$consignee_pref    = $_REQUEST['consignee_pref'];
$consignee_postal  = $_REQUEST['consignee_postal'];
$consignee_name    = $_REQUEST['consignee_name'];
$consignee_address = $_REQUEST['consignee_address'];
$consignee_tel     = $_REQUEST['consignee_tel'];
$orderer_pref      = $_REQUEST['orderer_pref'];
$orderer_postal    = $_REQUEST['orderer_postal'];
$orderer_name      = $_REQUEST['orderer_name'];
$orderer_address   = $_REQUEST['orderer_address'];
$orderer_tel       = $_REQUEST['orderer_tel'];

// CGIの状態(入力画面から実行されたか、確認画面から実行されたか)を判別する値
$come_from = $_REQUEST['come_from'];        // CGIの状態設定

// パラメータの確認
if ($come_from == 'here'){
    if( $process_code == 1 || $process_code == 2 ){
        if (empty($item_name)){
            $err_msg = "購入する商品を選択してください ";
        }
        elseif (empty($user_id)){
            $err_msg = "ユーザーIDを入力してください ";
        }
        elseif (empty($user_name)){
          $err_msg = "氏名を入力してください ";
        }
        elseif (empty($user_mail_add)){
          $err_msg = "メールアドレスを入力してください。 ";
        }
		# コンビニ決済のみかつ、コンビニコード指定時のみ処理
        if( $st == 'conveni' ){
          $process_code = 1;
          $mission_code = 1;
          if( $conveni_code ){
              if( empty($user_tel) ){
                  $err_msg = "ユーザ電話番号が未指定です";
              }elseif( empty($user_name_kana) ){
                  $err_msg = "ユーザー名(カナ)が未指定です";
              }
          }
        }elseif( ( $st == 'normal' || $st == 'atobarai') 
                 && strlen($consignee_pref.$consignee_postal.$consignee_name.$consignee_address.$consignee_tel.
                           $orderer_pref.$orderer_postal.$orderer_name.$orderer_address.$orderer_tel) ){
            /* ここを直し中 */
            if ( !preg_match("/^\d+$/", $consignee_postal  )){
                $err_msg = "送り先郵便番号の入力が異常です";
            }
            if ( !$consignee_name ) {
                $err_msg = "送り先名が未入力です";
            }
            if ( !$pref_list[$consignee_pref] ) {
                $err_msg = "送り先住所(都道府県)が異常です";
            }
            if ( !$consignee_address ) {
                $err_msg = "送り先住所が未入力です";
            }
            if ( !preg_match("/^\d+$/", $consignee_tel  )){
                $err_msg = "送り先電話番号が異常です";
            }
            if ( !preg_match("/^\d+$/", $orderer_postal  )){
                $err_msg = "注文主郵便番号の入力が異常です";
            }
            if ( !$orderer_name ) {
                $err_msg = "注文主名が未入力です";
            }
            if ( !$pref_list[$orderer_pref] ) {
                $err_msg = "注文主住所(都道府県)が異常です";
            }
            if ( !$orderer_address ) {
                $err_msg = "注文主住所が未入力です";
            }
            if ( !preg_match("/^\d+$/", $orderer_tel  )){
                $err_msg = "注文主電話番号が異常です";
            }
        }
    }elseif( $process_code == 3 || $process_code == 4 ){
        # ユーザ登録 || ユーザ変更

        # ユーザIDチェック
        if ( !preg_match("/^[a-zA-Z0-9\.\-\+\/\@]+$/", $user_id  )){
            $err_msg = "ユーザIDにご利用できない文字が含まれています。";
        }
        # ユーザ名が入っていない
        if( !$user_name  ){
            $err_msg = "ユーザ名が未入力です。";
        }
        # ユーザメールアドレスチェック
        if( !preg_match( "/^[a-zA-Z0-9\.\-\_\@]+$/", $user_mail_add ) ){
            $err_msg = "ユーザメールアドレスにご利用できない文字が含まれています。";
        }
    }elseif( $process_code == 7 || $process_code == 9 ){
        # ユーザIDチェック
        if ( !preg_match("/^[a-zA-Z0-9\.\-\+\/\@]+$/", $user_id  )){
            $err_msg = "ユーザIDにご利用できない文字が含まれています。";
        }
	}else{
		$process_code = 1;
		$err_msg = "処理区分の指定が異常です。";
	}
echo  "<br /><br />" . $err_msg;
    
  
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

  $request = new HTTP_Request2($order_url, HTTP_Request2::METHOD_POST, $option);
  $request->setConfig(array(
     'ssl_verify_peer' => false,
#     'ssl_verify_peer' => true,
#     'ssl_cafile' => '/etc/ssl/certs/ca-bundle.crt', //ルートCA証明書ファイルを指定
  ));  
  // HTTPのヘッダー設定
  //$http->addHeader("User-Agent", "xxxxx");
  //$http->addHeader("Referer", "xxxxxx");

    //set post data
    if ( $process_code == "1" || $process_code == "2" ){
        $request->addPostParameter('version', '2' );
        $request->addPostParameter('contract_code', $contract_code);
        $request->addPostParameter('user_id', $user_id);
        $request->addPostParameter('user_name', mb_convert_encoding($user_name, "UTF-8", "auto"));
        $request->addPostParameter('user_mail_add', $user_mail_add);
        $request->addPostParameter('item_code', $item_code);
        $request->addPostParameter('item_name', mb_convert_encoding($item_name, "UTF-8", "auto"));
        $request->addPostParameter('order_number', $order_number);
        $request->addPostParameter('st_code', $st_code[$st]);
        $request->addPostParameter('mission_code', $mission_code);
        $request->addPostParameter('item_price', $item_price);
        $request->addPostParameter('process_code', $process_code);
        $request->addPostParameter('memo1', $memo1);
        $request->addPostParameter('memo2', $memo2);
        $request->addPostParameter('xml', '1');
        $request->addPostParameter('character_code', 'UTF8' );

        if ( $st == "conveni" && $conveni_code != 0 ){
            $request->addPostParameter('conveni_code' , $conveni_code );
            $request->addPostParameter('user_tel', $user_tel );
            $request->addPostParameter('user_name_kana', mb_convert_encoding( $user_name_kana, "UTF-8", "auto" ) );
        }elseif( ( $st == "normal" || $st == "atobarai" ) && $consignee_postal ){
            $request->addPostParameter('delivery_code',99);
            $request->addPostParameter('consignee_postal',$consignee_postal);
            $request->addPostParameter('consignee_name', $consignee_name);
            $request->addPostParameter('consignee_address',sprintf( "%s%s", $pref_list[$consignee_pref], $consignee_address));
            $request->addPostParameter('consignee_tel', $consignee_tel);
            $request->addPostParameter('orderer_postal', $orderer_postal);
            $request->addPostParameter('orderer_name',  $orderer_name );
            $request->addPostParameter('orderer_address', sprintf( "%s%s", $pref_list[$orderer_pref], $orderer_address));
            $request->addPostParameter('orderer_tel', $orderer_tel);
        }
  }elseif ( $process_code == "3" || $process_code == "4" ){
            # process_code  3 or 4 ( 登録のみ、登録内容変更)の場合のみ以下の項目を設定
            $request->addPostParameter('version', '2' );
            $request->addPostParameter('contract_code', $contract_code);
            $request->addPostParameter('user_id', $user_id);
            $request->addPostParameter('user_name', mb_convert_encoding($user_name, "UTF-8", "auto"));
            $request->addPostParameter('user_mail_add', $user_mail_add);
            $request->addPostParameter('st_code', "10000-0000-00000-00000-00000-00000-00000"); // 登録時は固定、変更時はこちらか以下
            // $request->addPostParameter('st_code', "00000-0000-00000-00000-00000-00000-00000");
            $request->addPostParameter('process_code', $process_code);
            $request->addPostParameter('memo1', $memo1);
            $request->addPostParameter('memo2', $memo2);
            $request->addPostParameter('charset', 'UTF8' );
            $request->addPostParameter('xml', '1');
  }elseif( $process_code == "7" || $process_code == "9" ){
            $request->addPostParameter('contract_code', $contract_code);
            $request->addPostParameter('user_id', $user_id);
            $request->addPostParameter('process_code', $process_code);
            $request->addPostParameter('memo1', $memo1);
            $request->addPostParameter('memo2', $memo2);
            $request->addPostParameter('xml', '1');
  }

  // HTTPリクエスト実行
  $response = $request->send();

  // 応答内容(XML)の解析
  if (!PEAR::isError($response)) {

  	
  	$res_code = $response->getStatus();
  	$res_content = $response->getBody();

		//xml unserializer
		$temp_xml_res = str_replace("x-sjis-cp932", "UTF-8", $res_content);
		$unserializer = new XML_Unserializer();
		$unserializer->setOption('parseAttributes', TRUE);
		$unseriliz_st = $unserializer->unserialize($temp_xml_res);
		if ($unseriliz_st === true) {
			//xmlを解析
			$res_array = $unserializer->getUnserializedData();
			$is_xml_error = false;
			$xml_redirect_url = "";
			$xml_error_cd = "";
			$xml_error_msg = "";
			$xml_memo1_msg = "";
			$xml_memo2_msg = "";
            $result = "";
            $trans_code = "";
			foreach($res_array['result'] as $uns_k => $uns_v) {
                foreach ($uns_v as $result_atr_key => $result_atr_val) {
                    switch ($result_atr_key) {
                        case 'redirect':
                            $xml_redirect_url = rawurldecode($result_atr_val);
                            break;
                        case 'err_code':
                            $is_xml_error = true;
                            $xml_error_cd = $result_atr_val;
                            break;
                        case 'err_detail':
                            $xml_error_msg = mb_convert_encoding(urldecode($result_atr_val), "UTF-8", "auto");
                            break;
                        case 'memo1':
                            $xml_memo1_msg = mb_convert_encoding(urldecode($result_atr_val), "UTF-8", "auto");
                            break;
                        case 'memo2':
                            $xml_memo2_msg = mb_convert_encoding(urldecode($result_atr_val), "UTF-8", "auto");
                            break;
                        case 'result':
                            $result = mb_convert_encoding(urldecode($result_atr_val), "UTF-8", "auto");
                            break;
                        case 'trans_code':
                            $trans_code = mb_convert_encoding(urldecode($result_atr_val), "UTF-8", "auto");
                            break;
                        default:
                            break;
                    }
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


  if($is_xml_error){
    // データ送信結果が失敗だった場合、オーダー入力画面に戻し、エラーメッセージを表示します。
  	$err_msg = "エラー : " . $xml_error_cd . $xml_error_msg;
  	order_form();
    exit(1);
  }else{
    if( !empty( $xml_redirect_url ) ){
        // データ送信に成功した場合、リダイレクト先URLへリダイレクトさせてください。
    	header("Location: " . $xml_redirect_url);
        exit(0);
    }elseif( !empty($trans_code ) ){
        header("Location: ".$confirm_url."?trans_code=".$trans_code);
        exit();
    }else{
        result_page( $result );
        exit(0);
    }
  }
}
order_form();


// オーダー入力フォーム表示
// 以下はお客様がご購入の際閲覧するWeb画面となります。画面イメージ等は貴社のポリシーに沿った形で変更願います。


function order_form(){

global $my_self, $item, $item_name, $item_price, $user_name, $user_id, $user_mail_add, $goods, $err_msg;
global $mission_code, $process_code, $st, $debug_printj, $mission_item, $process_item, $conveni_item, $conveni_code,$user_name_kana, $user_tel;
global $consignee_pref, $consignee_postal, $consignee_name, $consignee_address, $consignee_tel, $orderer_pref, $orderer_postal, $orderer_name;
global $orderer_address, $orderer_tel,$pref_list;
//echo "debugmsgSTAT<br / ><br / ><br / >" . $debug_printj;
    $mission_code_item = "";
    $process_code_item = "";
    $conveni_code_item = "";
    $consignee_pref_item = "";
    $orderer_pref_item = "";
	ksort($mission_item );
    foreach ( $mission_item as $k => $v ){
        $mission_code_item .= sprintf("<option value=\"%s\" %s>%s</option>\r\n", $k, ( $k == $mission_code ? "selected" : "" ), $v );
    }
	ksort( $process_item );
    foreach ( $process_item as $k => $v ){
        $process_code_item .= sprintf("<option value=\"%s\" %s>%s</option>\r\n", $k, ( $k == $process_code ? "selected" : "" ), $v );
    }
	ksort( $conveni_item );
    foreach ( $conveni_item as $k => $v){
        $conveni_code_item .= sprintf("<option value=\"%s\" %s>%s</option>\r\n", $k,( $k == $conveni_code ? "selected" : "" ),  $v );
    }
	ksort( $pref_list );
    foreach ( $pref_list as $k => $v){
        $consignee_pref_item .= sprintf("<option value=\"%s\" %s>%s</option>\r\n", $k,( $k == $consignee_pref ? "selected" : "" ),  $v );
        $orderer_pref_item .= sprintf("<option value=\"%s\" %s>%s</option>\r\n", $k,( $k == $orderer_pref ? "selected" : "" ),  $v );
    }

    $st_normal = "";
    $st_conveni = "";
    $st_card = "";
    $st_atobarai = "";
    if ( $st == 'conveni' ){
        $st_conveni = "checked";
    }elseif( $st == 'card' ){
        $st_card = "checked";
    }elseif( $st == 'atobarai' ){
        $st_atobarai = "checked";
    }else{
        $st_normal = "checked";
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
<script>
function switching(flag,flag2,flag3) {
    document.getElementsByName('conveni_code')[0].disabled=flag
    document.getElementsByName('user_name_kana')[0].disabled=flag
    document.getElementsByName('user_tel')[0].disabled=flag
    document.getElementsByName('mission_code')[0].disabled=flag3
    document.getElementsByName('process_code')[0].disabled=flag3
    document.getElementsByName('consignee_postal')[0].disabled=flag2
    document.getElementsByName('consignee_name')[0].disabled=flag2
    document.getElementsByName('consignee_pref')[0].disabled=flag2
    document.getElementsByName('consignee_address')[0].disabled=flag2
    document.getElementsByName('consignee_tel')[0].disabled=flag2
    document.getElementsByName('orderer_postal')[0].disabled=flag2
    document.getElementsByName('orderer_name')[0].disabled=flag2
    document.getElementsByName('orderer_pref')[0].disabled=flag2
    document.getElementsByName('orderer_address')[0].disabled=flag2
    document.getElementsByName('orderer_tel')[0].disabled=flag2
}
function init(){
    var funcItem = {}
    funcItem["normal"] = function(){ switching(true,false,false) }
    funcItem["card"] = function(){ switching(true,true,false) }
    funcItem["conveni"] = function(){ switching(false,true,true) }
    funcItem["atobarai"] = function(){ switching(true,false,true) }
    var items = document.getElementsByName('st');
    for( var i = 0; i < items.length; i++ ){
        if( items[i].checked ){
            funcItem[items[i].value]();
            return;
        }
    }
}
</script>
</HEAD>
<BODY BGCOLOR="#E6ECFA" text="#505050" link="#555577" vlink="#555577" alink="#557755" onload="init()">
<BR>
<form action="{$my_self}" method="post">
<table class=S1 width="400" border="0" cellpadding="0" cellspacing="0">
<tr class=S1><td class=S1>

<table class=S1 width="100%" cellpadding="6" align=center>
<tr class=S1><th class=S1 align=left><big>商品購入サンプル</big></th></tr>
</table>


<table class=S1 width="90%" align=center>
 <tr class=S1>
 
 <td class=S1>
 <font color="#EE5555"> {$err_msg} </font>
 <br>購入する商品を選択してください。<br>
EOM;

echo "   <table cellspacing=4 cellpadding=4 align=\"left\">\n";
echo "     <tr><th>商品名</th><th>価格</th></tr>\n";

// 商品リストの表示
foreach($goods as $key => $value){
  $checked = ($key == $item)? "checked" : "";
  echo "<tr>
  <td><input type=\"radio\" name=\"item\" value=\"{$key}\" $checked/>{$value['name']}</td>
  <td>{$value['price']}円</td>
  </tr>  \n";
}
echo "</table><br><br>\n";

echo <<<EOM

  </td>
 </tr>
 <tr class=S1>
  <td class=S1>
    <br>以下の項目を入力してください<br>
   <table cellspacing=4 cellpadding=4 align="left">
    <tr>
     <td>ユーザーID</td>
     <td><input type="text" name="user_id" value="{$user_id}"></td>
    </tr>
    <tr>
     <td>氏名</td>
     <td><input type="text" name="user_name" value="{$user_name}"></td>
    </tr>
    <tr>
     <td>メールアドレス</td>
     <td><input type="text" name="user_mail_add" value="{$user_mail_add}"></td>
    </tr>
    <tr>
        <td>決済区分</td>
        <td><label><input type="radio" name="st" value="normal" onclick="switching(true,false,false)" {$st_normal}>指定無し</label>
        　　<label><input type="radio" name="st" value="card" onclick="switching(true,true,false)" {$st_card}>カード決済</label>
        　　<label><input type="radio" name="st" value="conveni" onclick="switching(false,true,true)" {$st_conveni}>コンビニ決済</label>
        　　<label><input type="radio" name="st" value="atobarai" onclick="switching(true,false,true)" {$st_atobarai}>GMO後払い</label>
        </td>
    </tr>
    <tr>
        <td>コンビニコード</td>
        <td><select name="conveni_code">
                {$conveni_code_item}
            </select>
        </td>
    </tr>
    <tr>
        <td>ユーザ名(カナ)</td>
        <td><input type="text" name="user_name_kana" value="{$user_name_kana}"></td>
    </tr>
    <tr>
        <td>ユーザ電話番号</td>
        <td><input type="text" name="user_tel" value="{$user_tel}"></td>
    </tr>
    <tr>
        <td>送り先郵便番号</td>
        <td><input type="text" name="consignee_postal" value="{$consignee_postal}"></td>
    </tr>
    <tr>
        <td>送り先住所</td>
        <td><select name="consignee_pref">{$consignee_pref_item}</select>
        <input type="text" name="consignee_address" value="{$consignee_address}"></td>
    </tr>
    <tr>
        <td>送り先名</td>
        <td><input type="text" name="consignee_name" value="{$consignee_name}"></td>
    </tr>
    <tr>
        <td>送り先電話番号</td>
        <td><input type="text" name="consignee_tel" value="{$consignee_tel}"></td>
    </tr>
    <tr>
        <td>注文主郵便番号</td>
        <td><input type="text" name="orderer_postal" value="{$orderer_postal}"></td>
    </tr>
    <tr>
        <td>注文主住所</td>
        <td><select name="orderer_pref">{$orderer_pref_item}</select>
        <input type="text" name="orderer_address" value="{$orderer_address}"></td>
    </tr>
    <tr>
        <td>注文主名</td>
        <td><input type="text" name="orderer_name" value="{$orderer_name}"></td>
    </tr>
    <tr>
        <td>注文主電話番号</td>
        <td><input type="text" name="orderer_tel" value="{$orderer_tel}"></td>
    </tr>

    <tr>
        <td>課金区分</td>
        <td><select name="mission_code">
            {$mission_code_item}
            </select>
        </td>
    </tr>
    <tr>
        <td>処理区分</td>
        <td><select name="process_code">
            {$process_code_item}
        </select>
        </td>
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

global $my_self, $item, $item_name, $item_price, $user_name, $user_id, $user_mail_add, $goods, $err_msg;
global $mission_code, $process_code, $st, $debug_printj, $mission_item, $process_item, $conveni_item, $conveni_code,$user_name_kana, $user_tel;
global $consignee_pref, $consignee_postal, $consignee_name, $consignee_address, $consignee_tel, $orderer_pref, $orderer_postal, $orderer_name;
global $orderer_address, $orderer_tel,$pref_list;

//echo "debugmsgSTAT<br / ><br / ><br / >" . $debug_printj;
    $confirm_text = "";
    # 確認内容
    # 通常の場合には課金区分
	if( $st == 'conveni' ){
	    # 商品名、価格
	    $confirm_text .= sprintf("商品名: %s<br>\n価格: %d円<br>\n",$item_name, $item_price );
	    # コンビニ指定決済の場合、1回課金のみ
	    $confirm_text .= sprintf("課金区分: %s<br>\n", $mission_item[1]);
	    # コンビニ指定決済の場合、初回課金のみ
	    $confirm_text .= sprintf("処理区分: %s<br>\n", $process_item[1]);
	    # 指定がない場合は入力画面のURLがEPSILON側から返されます。
	    $confirm_text .= sprintf("ユーザ名(カナ): %s 様<br>\nユーザ電話番号: %s<br>\n", $user_name_kana, $user_tel );
	    # 2、コンビニ決済のみなので明示
	    $confirm_text .= "決済方法：コンビニ決済<br>\n";
		if( $conveni_code != 0 ){
		    $confirm_text .= sprintf("コンビニコード: %s<br>\n", $conveni_item[$conveni_code] );
		}
    }elseif( $st == "atobarai" ){
        # 商品名、価格
        $confirm_text .= sprintf( "商品名: %s<br>\n価格: %d円<br>\n",
            $item_name, $item_price );

        # 後払い指定決済の場合、1回課金のみ
        $confirm_text
            .= sprintf( "課金区分: %s<br>\n", $mission_item[1] );
        # 後払い指定決済の場合、初回課金のみ
        $confirm_text
            .= sprintf( "処理区分: %s<br>\n", $process_item[1] );
        $confirm_text .= "決済方法：GMO後払い<br>\n";
        # いずれかが入力されている場合必須入力でチェックを行っているので
        # 郵便番号が入力されていた場合は画面に入力パラメータを表示する
        # ここで指定がない場合はEPSILON側で入力画面が出力される
        if( $consignee_postal ){
            $confirm_text .= sprintf("%s: %s<br>\n","送り先郵便番号",$consignee_postal);
            $confirm_text .= sprintf("%s: %s<br>\n","送り先名",$consignee_name);
            $confirm_text .= sprintf("%s: %s%s<br>\n","送り先住所", $pref_list[$consignee_pref], $consignee_address);
            $confirm_text .= sprintf("%s: %s<br>\n","送り先電話番号",$consignee_tel);
            $confirm_text .= sprintf("%s: %s<br>\n","注文主郵便番号",$orderer_postal);
            $confirm_text .= sprintf("%s: %s<br>\n","注文主名",$orderer_name);
            $confirm_text .= sprintf("%s: %s%s<br>\n","注文主住所", $pref_list[$orderer_pref], $orderer_address);
            $confirm_text .= sprintf("%s: %s<br>\n","注文主電話番号",$orderer_tel);
        }
	}else{
	    if( $process_code == 1 || $process_code == 2 ){
	        $confirm_text .= sprintf("商品名: %s<br>\n価格: %d円<br>\n",$item_name, $item_price );
	        $confirm_text .= sprintf("課金区分: %s<br>\n", $mission_item[$mission_code]);
	        $confirm_text .= sprintf("処理区分: %s<br>\n", $process_item[$process_code]);
	        $confirm_text .= sprintf("ユーザID: %s<br>\n", $user_id );
	    }else{
	        # 処理区分3からは何がしかの処理
	        $confirm_text .= sprintf("処理区分: %s<br>\n", $process_item[$process_code]);
	        $confirm_text .= sprintf("ユーザID: %s<br>\n", $user_id );
	    }
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
    <br>以下の商品を注文します。<br>
    よろしければ[確認]ボタンを押してください。<br><br>
	{$confirm_text}
    <br>
    <table class=S1 align=center width="50%">
     <tr class=S1>
      <td class=S1>
       <form action="{$my_self}" method="post">
        <input type="hidden" name="item" value="{$item}">
        <input type="hidden" name="item_name" value="{$item_name}">
        <input type="hidden" name="item_price" value="{$item_price}">
        <input type="hidden" name="user_name" value="{$user_name}">
        <input type="hidden" name="user_id" value="{$user_id}">
        <input type="hidden" name="user_mail_add" value="{$user_mail_add}">
        <input type="hidden" name="conveni_code" value="{$conveni_code}">
        <input type="hidden" name="st" value="{$st}">
        <input type="hidden" name="user_name_kana" value="{$user_name_kana}">
        <input type="hidden" name="user_tel" value="{$user_tel}">
        <input type="hidden" name="mission_code" value="{$mission_code}">
        <input type="hidden" name="process_code" value="{$process_code}">
        <input type="submit" name="go" value="戻る">
       </form>
      </td>
      <td class=S1>
       <form action="{$my_self}" method="post">
        <input type="hidden" name="item" value="{$item}">
        <input type="hidden" name="item_name" value="{$item_name}">
        <input type="hidden" name="item_price" value="{$item_price}">
        <input type="hidden" name="user_name" value="{$user_name}">
        <input type="hidden" name="user_id" value="{$user_id}">
        <input type="hidden" name="user_mail_add" value="{$user_mail_add}">
        <input type="hidden" name="conveni_code" value="{$conveni_code}">
        <input type="hidden" name="st" value="{$st}">
        <input type="hidden" name="user_name_kana" value="{$user_name_kana}">
        <input type="hidden" name="user_tel" value="{$user_tel}">
        <input type="hidden" name="mission_code" value="{$mission_code}">
        <input type="hidden" name="process_code" value="{$process_code}">
        <input type="hidden" name="come_from" value="kakunin">
        <input type="hidden" name="consignee_postal" value="{$consignee_postal}">
        <input type="hidden" name="consignee_name" value="{$consignee_name}">
        <input type="hidden" name="consignee_pref" value="{$consignee_pref}">
        <input type="hidden" name="consignee_address" value="{$consignee_address}">
        <input type="hidden" name="consignee_tel" value="{$consignee_tel}">
        <input type="hidden" name="orderer_postal" value="{$orderer_postal}">
        <input type="hidden" name="orderer_name" value="{$orderer_name}">
        <input type="hidden" name="orderer_pref" value="{$orderer_pref}">
        <input type="hidden" name="orderer_address" value="{$orderer_address}">
        <input type="hidden" name="orderer_tel" value="{$orderer_tel}">
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
  <tr class=S1><th class=S1 align=left><big>商品購入サンプル</big></th></tr>
  </table>

  <table class=S1 width="90%" align=center>
    <tr class=S1><td class=S1>
       {$result}
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
