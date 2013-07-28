<?php
	require_once ('JSON.php');
    // passwordは変更の必要あり


	// http://192.168.1.5/getbooklist.php?uuid=xxxxxxなどでアクセス
        $output["status"]="0";
        $output["message"]="成功";
        $output["books"]="";
        if ($_SERVER['REQUEST_METHOD'] !='GET' ){
                $output["status"]="1";
                $output["message"]="GET以外のリクエスト";
                goto end;
        }
        
        // URL引数のデータを受け取る
        if(isset($_GET['uuid'])){
                $uuid = $_GET['uuid'];
        }else{
                $output["status"]="2";
                $output["message"]="uuidが指定されていない";
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
        $result = mysql_query("SELECT C>0 AS IS_EXIST FROM(SELECT count(*) AS C FROM booklist WHERE uuid ='$uuid')AS DAMMY;");
        $row = mysql_fetch_assoc($result);
        if ($row['IS_EXIST'] == 0){
               $output["status"]="22";
               $output["message"]="データが存在しない";
               goto end;        
        }



	// 取得クエリ発行
	$result = mysql_query("SELECT book.isbn, book.title, book.titleKana, book.author, book.authorKana, book.publisher,book.salesDate, book.itemPrice, book.itemUrl,book.imageUrl,  booklist.comment, booklist.satisfaction, booklist.recorddate, booklist.readdate, booklist.unread, booklist.tag FROM book, booklist WHERE book.isbn=booklist.isbn AND  booklist.uuid='$uuid';");

        if(!$result){
                $output["status"]="24";
                $output["message"]="取得クエリ失敗";
                goto end;
        }

	// 取得した結果を取り出して連想配列に入れていく
	while($row = mysql_fetch_assoc($result)){
		$output["books"][] =array(
			'isbn' => $row['isbn'],
			'title' => $row['title'],
			'titleKana' => $row['titleKana'],
			'author' => $row['author'],
			'authorKana' => $row['authorKana'],
			'publisher' => $row['publisher'],
			'salesDate' => $row['salesDate'],
			'itemPrice' => $row['itemPrice'],
			'itemUrl' => $row['itemUrl'],
			'imageUrl' => $row['imageUrl'],
			'comment' => $row['comment'],
			'satisfaction' => $row['satisfaction'],
			'recorddate' => $row['recorddate'],
			'readdate' => $row['readdate'],
			'unread' => $row['unread'],
			'tag' => $row['tag']
		);
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

