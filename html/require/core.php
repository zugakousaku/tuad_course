<?php
function session_check(){
    session_start();
    // ログイン状態チェック
    if (!isset($_SESSION["id"])) {
        header("Location: login.php");
        exit;
    }
    global $user_id;
    global $user_data;
    global $avater_url;
    $user_id = $_SESSION["id"];
    $user_data=getDBRow("user","*","WHERE id=$user_id");
    if(empty($user_data)){
        header("Location: logout.php");
        exit;
    }

    if(!empty($user_data["media_id"])){
        $avater_url=$user_data["thumb_url"];    
    }else{
        if(!empty($user_data["thumb_url"])){
            $avater_url="preset/thumbnail/".$user_data["thumb_url"];    
        }else{
            $avater_url="preset/thumbnail/male/male_1.png";    
        }
    }
}


//MySQLと接続
function openDB(){
    $host=HOST;
    $port=PORT;
    $user=USER;
    $password=PASS;
    $db_name=DB;
    $dsn ="mysql:host=".$host.";port=".$port.";dbname=".$db_name.";charset=utf8";
    $PDO = new PDO($dsn, $user, $password);
    try{
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $PDO;
    } catch (PDOException $e) {
        exit('データベースに接続できませんでした。' . $e->getMessage());
    }
}

//MySQLとの接続解除
function closeDB($PDO){
    $PDO = null;
}

//一つだけデータを取得
function getDBRow($table,$find,$filter){
    $getData=NULL;
    $getData=getDB($table,$find,$filter);
    $data = $getData->fetch(PDO::FETCH_BOTH);
    return $data;
}

//全てデータを取得
function getDB($table,$find,$filter){
    $getData=NULL;
    $DB=openDB();
    $sql="SELECT $find FROM $table $filter";
    $getData = $DB->query($sql);
    closeDB($DB);
    return $getData;
}


//全てデータを取得
function countDB($table,$find,$filter){
    $getData=NULL;
    $DB=openDB();
    $sql="SELECT COUNT($find) FROM $table $filter";
    $getData = $DB->query($sql);
    $count = $getData->fetch(PDO::FETCH_BOTH);
   closeDB($DB);
    return $count[0];
}


//メディアを探す
function getMedia($table,$find,$filter){
    //投稿のIDからデータを取得
    $post=getDBRow($table,$find,$filter);
    //MediaIDを持っているか？
    if(!empty($post["media_id"])){
        $media_id=$post["media_id"];
        //MediaIDからメディアを取得
        $media=getDBRow("media","*","WHERE id=$media_id");
    }else{
        $media=NULL;
    }
    return $media;
}




//DBへ保存
function saveDB($table,$data){
    try {
        date_default_timezone_set('Asia/Tokyo');
        //フォーム内のデータを取り出す
        $keylist="";
        foreach ($data as $key => $value){
            $keylist.=$key.",";
        }
        $keylist = rtrim($keylist, ",");

        $valuelist="";
        foreach ($data as $key => $value){
            $valuelist.=":".$key.",";
        }
        $valuelist = rtrim($valuelist, ",");

        $params = array();
        foreach ($data as $key => $value){
            $params[":".$key]=$data[$key];
        }
 
        //DBと接続
        $PDO=openDB();
        //PDOのエラーレポートを表示
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        // INSERT文を変数に格納。:title:categoryはプレースホルダという、値を入れるための単なる空箱
        $sql = "INSERT INTO $table ($keylist) VALUES ($valuelist)"; 
        //挿入する値は空のまま、SQL実行の準備をする
        $stmt = $PDO->prepare($sql); 
        //挿入する値が入った変数をexecuteにセットしてSQLを実行
        $stmt->execute($params); 
        // INSERTされたデータのIDを取得
        $inserted_id = $PDO->lastInsertId('id');
        //データベースを閉じる
        closeDB($PDO);
        return $inserted_id;

    } catch (PDOException $e) {
        exit('データベースに接続できませんでした。' . $e->getMessage());
    }
}



function updateDB($table,$data,$row_id){
    try {
        date_default_timezone_set('Asia/Tokyo');
        //フォーム内のデータを取り出す
        $keylist="";
        foreach ($data as $key => $value){
            $keylist.=$key." = :".$key.",";
        }
        $keylist = rtrim($keylist, ",");

        $params = array();
        foreach ($data as $key => $value){
            $params[":".$key]=$data[$key];
        }
 
        //DBと接続
        $PDO=openDB();
        //PDOのエラーレポートを表示
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $sql = "UPDATE $table SET $keylist WHERE id=$row_id"; 
        //挿入する値は空のまま、SQL実行の準備をする
        $stmt = $PDO->prepare($sql); 
        //挿入する値が入った変数をexecuteにセットしてSQLを実行
        $stmt->execute($params); 
        // INSERTされたデータのIDを取得
        $inserted_id = $PDO->lastInsertId('id');
        //データベースを閉じる
        closeDB($PDO);
        return $inserted_id;

    } catch (PDOException $e) {
        exit('データベースに接続できませんでした。' . $e->getMessage());
    }
}

//プロジェクトの削除
function deleteDB($table,$row_id){
    //DBと接続
    $PDO=openDB();
    //PDOのエラーレポートを表示
    $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    $sql = "DELETE FROM $table WHERE id=:id"; 
    $params = array(':id' => $row_id);

    //挿入する値は空のまま、SQL実行の準備をする
    $stmt = $PDO->prepare($sql); 
    //挿入する値が入った変数をexecuteにセットしてSQLを実行
    $stmt->execute($params); 
    //データベースを閉じる
    closeDB($PDO);
}

//画像のアップロード
function upload_image(){
    //ファイル名を生成
    $imageName = md5(uniqid(mt_rand(), true));
    $imageName .= '.' . substr(strrchr($_FILES['form-image']['name'], '.'), 1);
    $uploadImagePath="upload/images/";
    $fileName = "$uploadImagePath/$imageName";
    $image_id="";

    if (!empty($_FILES['form-image']['name'])) {
        //アップロード処理
        move_uploaded_file($_FILES['form-image']['tmp_name'], $uploadImagePath . $imageName);

        if (exif_imagetype($fileName)) {
            $data = array();
            $data["title"]=$_FILES['form-image']['name'];
            $data["url"]=$uploadImagePath.$imageName;
            $data["type"]=mime_content_type($fileName);
            $image_id=saveDB("media",$data);
            $message = '画像をアップロードしました';
        } else {
            $message = '画像ファイルではありません';
        }
    }
    
    $result = array('id'=>$image_id, 'url'=>$uploadImagePath.$imageName);
    return $result;
}


function upload_file_multiple(){
    $uploadPath="upload/files";
    // アップロードされたファイル件を処理
    $fileArray=array();
    for($i = 0; $i < count($_FILES["form-file"]["name"]); $i++ ){
        $fileName = md5(uniqid(mt_rand(), true));
        $fileName .= '.' . substr(strrchr($_FILES['form-file']['name'][$i], '.'), 1);
        $fullPath = "$uploadPath/$fileName";

        if (!empty($_FILES['form-file']['tmp_name'][$i])) {
            move_uploaded_file($_FILES['form-file']['tmp_name'][$i], $fullPath);

            $data = array();
            $data["title"]=$_FILES['form-file']['name'][$i];
            $data["url"]=$fullPath;
            $data["type"]=mime_content_type($fullPath);
            $file_id=saveDB("media",$data);
            array_push($fileArray,array('id'=>$file_id));
        }
    }
    //var_dump($fileArray);
    return $fileArray;
}


function delete_file($url){
    unlink($url);
}

//ポストからデータ取得
function get_formData($key){
    $data=NULL;

    // echo $key."\n";
    if(!empty($_POST[$key])){
        $data = $_POST[$key];
    }
    return $data;
}

function getURLParam($key){
    $result = isset($_GET[$key]) ? htmlspecialchars($_GET[$key]) : null;
    return $result;
}
function getURLParam_fromString($url,$key){
    $components = parse_url($url);
    parse_str($components['query'], $results);
    return $results[$key];
}


// if(mb_send_mail($mailTo, $title, $body, $header)){      

function sendMail($mailAdress, $title, $message, $headers){
    $result=false;
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");
    if(mb_send_mail($mailAdress, $title, $message, $headers))
    {
        $result=true;
    }
    return $result;
}

function getRootURL(){

    if(strpos($_SERVER['HTTP_HOST'],'localhost') !== false){
        $url="http://localhost:8888/tuad_portal/portal";

    }else{    
        $url="https://tuad-eizo-class.com/portal";
    }
    return $url;
}
function getUserRollLevel($user_data){
    
    //学生 0
    $level=0;

    //非常勤講師 1
    if($user_data["roll"]=="guest"){
        $level=1;
    }

    //専任教員 2
    if($user_data["roll"]=="teacher"){
        $level=2;
    }
    
    //管理者 副手 3
    if($user_data["roll"]=="admin" || $user_data["roll"]=="assistant"){
        $level=3;
    }
    return $level;
    
}

// function auto_url($mojiretu){
    
//     $mojiretu = htmlspecialchars($mojiretu,ENT_QUOTES);
//     $mojiretu = nl2br($mojiretu);
//     //文字列にURLが混じっている場合のみ下のスクリプト発動
//         if(preg_match("/(http|https):\/\/[-\w\.]+(:\d+)?(\/[^\s]*)?/",$mojiretu)){
//             preg_match_all("/(http|https):\/\/[-\w\.]+(:\d+)?(\/[^\s]*)?/",$mojiretu,$pattarn);
//                 foreach ($pattarn[0] as $key=>$val){
//                     $replace[] = '<a href="'.$val.'" target="_blank">'.$val.'</a>';
//                 }
//         $mojiretu = str_replace($pattarn[0],$replace,$mojiretu);
//         }
//     return $mojiretu;

// }

function auto_url($body, $link_title = null)
{
    $pattern = '/(href=")?https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+/';
    $body = preg_replace_callback($pattern, function($matches) use ($link_title) {
        // 既にリンクの場合や Markdown style link の場合はそのまま
        if (isset($matches[1])) return $matches[0];
        $link_title = $link_title ?: $matches[0];
        return "<a class='fs-ms' target='_blank' href=\"{$matches[0]}\">$link_title</a>";
    }, $body);

    $body = nl2br( $body ) ;

    $body=auto_decoration($body);
    return $body;
}


function auto_decoration($str){

    $str = str_replace("[[", "<span class='badge rounded-pill bg-info mb-3'>", $str);
    $str = str_replace("]]", "</span>", $str);
    return $str;
}


function isOnlineServer(){
    if(strpos($_SERVER['HTTP_HOST'],'localhost') !== false){
        $onlineFlag=false;
    }else{
        $onlineFlag=true;
    }
    return $onlineFlag;
}
?>
