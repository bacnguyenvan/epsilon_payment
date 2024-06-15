<?php
header('Content-Type: text');
//  EPSILON 入金結果通知受信
//
//
//  このプログラムの実行には、以下のモジュールが必要です。
//  ・PHP(ver5,,,,,
//  ・PEAR:
//  ・PEAR:HTTP_Request2:
//  ・PEAR:Net_URL2:
//  ・PEAR:XML_Parser:
//  ・PEAR:XML_Serializer:

// char setting
// サーバ環境に応じ適宜変更してください。
mb_language("Japanese");
mb_internal_encoding("UTF-8");
$output_file = "./status_change.txt";

$request = array();
foreach( $_REQUEST as $k => $v ){
    array_push( $request, sprintf("%s = %s", $k, $v ));
}

$fio = fopen( $output_file,"a" );
fwrite( $fio,sprintf("%s %s\n", date("Ymd H:i:s"), join(",",$request ) ) );
fclose($fio);

# 成功パターン
echo "1\n";

# 失敗パターン
# echo "0 999 DB_ERROR\n";


exit(0);

?>
