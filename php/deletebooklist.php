<?php
    // passwordは変更の必要あり
	require_once ('JSON.php');

	$output["status"]="0";
        $output["message"]="成功";
        if ($_SERVER['REQUEST_METHOD'] !='DELETE' ){
                $output["status"]="1";
                $output["message"]="DELETE以外のリクエスト";
                goto end;
        }

	// ボディーの内容確認
	$body = file_get_contents("php://input");


	$tmpbody = explode('&',$body);
	$bodyArray = array();

	foreach ($tmpbody as $arg){
		$key_val=explode('=',$arg.2);
		$bodyArray["$key_val[0]"] = $key_val[1];
	}
	if (strlen($bodyArray["uuid"]) == 0){	
                $output["status"]="2";
                $output["message"]="uuidが指定されていない";
                goto end;
	}
	if (strlen($bodyArray["isbn"]) == 0){
                $output["status"]="2";
                $output["message"]="isbnが指定されていない";
                goto end;
        }

        $link = mysql_connect('localhost', 'root', 'password');
        if (!$link){
                $output["status"]="10";
                $output["message"]="DBへの接続失敗";
                goto end;
        }

        // MySQLに対する処理
        $db_selected = mysql_select_db('booklogAppDB',$link);
        if(!$db_selected){
               $output["status"]="11";
               $output["message"]="DBの選択失敗";
               goto end;
        }
	mysql_set_charset('utf8');

	// 検索クエリ発行
	$result = mysql_query("SELECT C>0 AS IS_EXIST FROM(SELECT count(*) AS C FROM booklist WHERE isbn ='$bodyArray[isbn]' AND uuid='$bodyArray[uuid]')AS DAMMY;");

	$row = mysql_fetch_assoc($result);
        if ($row['IS_EXIST'] != 1){
               $output["status"]="22";
               $output["message"]="isbn= '$bodyArray[isbn]' uuid='$bodyArray[uuid]'は存在しない";
               goto end;        
        }
	

	// 削除のクエリ発行
	$result_flage = mysql_query("DELETE FROM booklist WHERE uuid='$bodyArray[uuid]' AND isbn='$bodyArray[isbn]';");
	if(!$result_flage){
                $output["status"]="26";
                $output["message"]="削除クエリ失敗";
                goto end;
	}

	$close_flag = mysql_close($link);
        if(!$close_flag){
                $output["status"]="12";
                $output["message"]="DBの切断失敗";
                goto end;
        }
end:
        // JSONで出力
        $json = new Services_JSON;
        $encode = $json->encode($output);
        header('Content-type: text/javascript; charset=utf-8');
        echo unicode_encode($encode);

function unicode_encode($str)
{
  return preg_replace_callback("/\\\\u([0-9a-zA-Z]{4})/", "encode_callback", $str);
}
function encode_callback($matches) {
  return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UTF-16");
}
?>

