<?php
//=================================== プロジェクトの処理
function create_project(){

    //フォームからデータを作成
    $data = formToData($_POST["form-type"],$_POST["type_sub"]);

    $radio_image=get_formData("radio-image");
    if($radio_image=="upload"){
        if(!empty($_FILES["form-image"])){
            $thumb=upload_image();
            $data["media_id"]=$thumb["id"];
            $data["thumb_url"]=$thumb["url"];
        }
    }else{
        $preset_name=get_formData("preset-name");
        if(!empty($preset_name)){
            $data["thumb_url"]=$preset_name;
        }
    }
    
    if(!empty($_FILES["form-file"])){
        $file_json=upload_file_multiple();
        $data["file_json"]=json_encode($file_json);
    }
    $table="post";
    $db_id=saveDB($table,$data);
    //もしコメントだったら
    if($data["type"]=="comment"){
        commentAdd($data["parent_id"]);
    }


}



function commentAdd($id){
    //var_dump("commentAdd:$id");
    $data=getDBRow("post","*","WHERE id=$id");
    $new_count=$data["comment_count"]+1;

    $new_data=array();
    $new_data["comment_count"]=$new_count;
    $id=updateDB("post",$new_data,$id);
}

function commentDelete($id){
    //var_dump("commentDelete:$id");
    $data=getDBRow("post","*","WHERE id=$id");
    $new_count=$data["comment_count"]-1;

    $new_data=array();
    $new_data["comment_count"]=$new_count;
    $id=updateDB("post",$new_data,$id);
}



//フォームからデータを作成
function formToData($type,$type_sub){
    //基本共通データ
    $data=array();
    $data["create_date"]=date('Y-m-d H:i:s');
    $data["update_date"]=date('Y-m-d H:i:s');
    $data["type_sub"]=get_formData("type_sub");

    if($type!="user"){
        $data["owner_id"]=get_formData("form-owner_id");
        $owner_name=get_formData("form-owner_name");
        $owner_roll=get_formData("form-owner_roll");
        $owner_data=array("name"=>$owner_name,"roll"=>$owner_roll);

        $data["owner_name"]=json_encode($owner_data);
        $data["type"]=get_formData("form-type");
    }

    //親へのリンク情報がある場合のみ親リンク更新
    $parent=get_formData("form-parent_id");
    if(!empty($parent))$data["parent_id"]=$parent;

    //フォーム構造を取得
    $formArray=initForm();
    $form=$formArray[$type]["form"];
     //フォーム構造からフォームをサーチしてデータ化
    foreach($form as $item){
        $id=$item["id"];
        $formType=$item["type"];

        //コンテンツがある場合サブフォームをデータ化
        if($id=="contents"){
            $subData=subFormToData($type_sub);
            $data["contents"]=json_encode($subData);
        }
        //echo $id." ";

        //特殊なデータじゃない場合、フォームからデータ作成
        //sub-formはコンテンツのタイプ
        if($formType!="file_image" && $formType!="file" && $formType!="sub-form" && $formType!="notice" && $formType!="password" && $id!="roll"){
            if($formType=="range-date"){
                $dom="form-".$id."-start";
                $start=get_formData($dom);
                $dom="form-".$id."-end";
                $end=get_formData($dom);
                $data[$id]=$start.",".$end;
            }else{
                $dom="form-".$id;
                $data[$id]=get_formData($dom);
            }
        }
    }
   // var_dump($data);
    return $data;
}


//サブフォームのデータ作成
function subFormToData($type){
    //var_dump($type);
    $data=array();
    $formArray=initSubForm();
    $form=$formArray[$type]["form"];
    foreach($form as $item){
        $id=$item["id"];
        $formType=$item["type"];

        //特殊なデータじゃない場合、フォームからデータ作成
        //sub-formはコンテンツのタイプ
        if($formType!="file_image" && $formType!="file" && $formType!="sub-form"){

            if($formType=="range-date"){
                $dom="sub-".$id."-start";
                $start=get_formData($dom);
                $dom="sub-".$id."-end";
                $end=get_formData($dom);
                $data[$id]=$start.",".$end;
            }else{
                $dom="sub-".$id;
                $data[$id]=get_formData($dom);
            }

        }


    }
    return $data;
}




//プロジェクトを更新
//ポストも更新
function update_project($row_id){
    $table="post";
    $past_media_url=NULL;
    $media_id=NULL;
    $file_json=NULL;

    $data = formToData($_POST["form-type"],$_POST["type_sub"]);

    //DBから投稿データを取得
    $post=getDBRow($table,"*","WHERE id=$row_id");
    //MediaIDを持っているか？
    if(!empty($post["media_id"])){
        $media_id=$post["media_id"];
        //MediaIDからメディアを取得
        $media=getDBRow("media","*","WHERE id=$media_id");
        $past_media_url=$media["url"];
        $media_id=$media["id"];
    }
    
    $radio_image=get_formData("radio-image");
    if($radio_image=="upload"){
        //フォームにイメージ画像がセットされている？
        if($_FILES['form-image']['size'] > 0){
            //アップロードする
            $thumb=upload_image();
            $data["media_id"]=$thumb["id"];
            $data["thumb_url"]=$thumb["url"];

            //既存イメージ画像があるなら削除する
            if(!empty($past_media_url)){
                echo"<p>Delete Media:".$past_media_url."</p>";
                deleteDB("media",$media_id);
                delete_file($past_media_url);
            }
        }
    }else{

        $preset_name=get_formData("preset-name");
        if(!empty($preset_name)){
            $data["thumb_url"]=$preset_name;
            $data["media_id"]="";

            //既存イメージ画像があるなら削除する
            if(!empty($past_media_url)){
                echo"<p>Delete Media:".$past_media_url."</p>";
                deleteDB("media",$media_id);
                delete_file($past_media_url);
            }
        }
    }

    //既にファイルが添付されている？
    if(!empty($post["file_json"])){
        $file_json=json_decode($post["file_json"],true);
    }

    //ファイル添付がセットされていればアップロードする
    if(!empty($_FILES["form-file"])){
        $add_file_json=upload_file_multiple();
        $united_json = array_merge($file_json, $add_file_json);
        $data["file_json"]=json_encode($united_json);
    }
    
    updateDB($table,$data,$row_id);
}





//プロジェクトの削除
function delete_project($row_id){
    $table="post";
    

    $post=getDBRow($table,"*","WHERE id=$row_id");
    //MediaIDを持っているか？
    if(!empty($post["media_id"])){
        $past_media_url=NULL;
        $media_id=NULL;    
        $media_id=$post["media_id"];
        //MediaIDからメディアを取得
        $media=getDBRow("media","*","WHERE id=$media_id");
        if(!empty($media)){
            $past_media_url=$media["url"];
            $media_id=$media["id"];
        }
        if(!empty($past_media_url)){
            echo"<p>Delete Media:".$past_media_url."</p>";
            deleteDB("media",$media_id);
            delete_file($past_media_url);
        }
    }

    if(!empty($post["file_json"])){
        $file_json=json_decode($post["file_json"],true);
        if(!empty($file_json)){
            foreach($file_json as $file){
                delete_attach_file($row_id,$file["id"]);
            }
        }
    }

    deleteDB("post",$row_id);

}

//DBからテーマ一覧表示
function listup_project(){
    //データベースと接続
    $DB=openDB();
    //SQL文（themeというテーブルから全て検索するための命令文）
    $sql="SELECT * FROM post WHERE type='project'";
    //SQL文を実際にデータベースに送信
    $getData = $DB->query($sql);
    //配列の中にあるデータを全部繰り返す
    foreach ($getData as $row) {
        // echo "<li>";
        // echo "<a class='widget-link' href='project.php?id=".$row['id']."'>".$row['title']."</a></li>";

        $post_id=$row['id'];
        $link_url="project.php?id=$post_id";
        $post_title=$row['title'];
        // $media=getMedia("post","*","WHERE id=$post_id");
        $imgURL=$row["thumb_url"];
        
        $str = <<<EOM
        <div class="d-flex align-items-center pb-1 mb-3 "><a class="d-block flex-shrink-0" href="$link_url">
        <img class="rounded project-thumb-side" src="$imgURL" alt="Post" width="64"></a><div class="ps-2 ms-1">
        <h4 class="fs-md nav-heading mb-1"><a class="fw-medium" href="$link_url">$post_title</a></h4>
        <p class="fs-xs text-muted mb-0">Teacher</p></div></div>
        EOM;
        echo $str;
    }
    closeDB($DB);
}

function get_thumbURL($post){
    $result=NULL;

    if(!empty($post["media_id"])){
        $result=$post["thumb_url"];
    }else{

        if(!empty($post["thumb_url"])){
            if(substr($post["thumb_url"], 0, 4) == "http"){
                $result=$post["thumb_url"];
            }else{
                if(empty($post["thumb_url"]))$result=NULL;
                else $result="preset/1080p/".$post["thumb_url"];
            }
        }else{
            $result="data/img/blank.png";
        }
    }
    return $result;
}



function grid_user($user_data,$link_type,$post_t,$filter,$draw_type){
    //データベースと接続
    $DB=openDB();
    $sql="SELECT * FROM user $filter";
    $getData = $DB->query($sql);
    $data=array();
    foreach ($getData as $user) {
        $name=$user["name"];
        $id=$user["id"];
        $image=get_thumbURL($user);
        $link="user.php?id=$id";
        if(!empty($user["contents"])){
            $contents=json_decode($user["contents"],true);
            if(!empty($contents["copy"])){
                $copy=$contents["copy"];
            }else{
                $copy="キャッチコピー";
            }
        }else{
            $copy="キャッチコピー";
        }
        $str = <<<EOM
        <div class="mb-2">
        <div class="d-flex align-items-center">

        <div class="me-1" style="width:50px;height:50px;">
        <a class="" href="$link" style="">
        <img class="img-fluid img-thumbnail rounded-circle bg-secondary me-2" style="width:100%;height:100%;object-fit: cover;overflow:hidden;" src="$image" alt="Post" >
        </a>
        </div>

        <div class="" style="width: 80%;min-width: 100px;">
            <p class="fs-xs nav-heading mb-1" style="line-height:1.2em"><a href="$link">$copy</a></p>
            <p class="fs-xs  mb-0">$name</p>
        </div>
        </div>
        </div>
        EOM; echo $str;
    }

}

// <div>
//         <img src="$image" class="rounded-circle bg-info" style="height:100px;">$copy $name
//         </div>

//DBからテーマ一覧表示
function grid_side($user_data,$link_type,$post_t,$filter,$draw_type,$root_id){
    //データベースと接続
    $DB=openDB();
    $sql="SELECT * FROM post $filter";
    $getData = $DB->query($sql);
    $data=array();

    foreach ($getData as $post) {
        $post_id=$post['id'];

        if(!empty($root_id))$rootLabel="&root=$root_id";
        else $rootLabel="";

        $data["link_url"]="$link_type.php?id=$post_id&type=$post_t".$rootLabel;
        $data["post_title"]=$post['title'];
        $data["img_url"]=get_thumbURL($post);
        $contents=json_decode($post["contents"],true);


        if(!empty($contents["text"])){
            $data["text"]=mb_strimwidth( strip_tags( $contents["text"] ), 0, 100, '…', 'UTF-8' );
        }else{
            $data["text"]="";
        }
        // $joinFlag=checkJoined($post,$user_data);
        $joinFlag=isJoined($post_id,$user_data);

        if($link_type=="project" || $link_type=="group"){
            if(!empty($post["member"])){
                $member=json_decode($post["member"],true);
                $data["member_count"]=count($member);
            }else{
                $data["member_count"]=0;
            }
        }else{
            $data["member_count"]=$post["comment_count"];
        }

        $owner_id=$post['owner_id'];
        $owner=getDBRow("user","*","WHERE id=$owner_id");
        $data["owner_name"]=$owner["name"];


        if($joinFlag){
            $data["join_text"]='<span class="badge rounded-pill bg-info p-1 px-2">参加中</span>';
            $data["join_group"]="joined";
        }else{
            $data["join_text"]="";
            $data["join_group"]="other";
        }

        if($draw_type=="grid")make_griditem($data);
        
        if($draw_type=="list")make_listitem($data);

    }
    closeDB($DB);
}

//グリッド表示
function grid_project2($user_data,$root_id,$filter,$draw_type){
    //データベースと接続
    $DB=openDB();
    $sql="SELECT * FROM post $filter";
    $getData = $DB->query($sql);
    $data=array();

    foreach ($getData as $post) {
        $post_id=$post['id'];
        $post_type=$post['type'];
        $parent_id=$post['parent_id'];

        if($post_type=="project")$file="project.php";
        else $file="post.php";

        if(!empty($parent_id))$parent="&parent=$parent_id";
        else $parent="";
        if(!empty($root_id))$root="&root=$root_id";
        else $root="";

        //ポストやプロジェクトに情報を渡す
        $data["link_url"]="$file?id=$post_id&type=$post_type".$root.$parent;


        $data["post_title"]=$post['title'];
        $data["update_date"]=$post['update_date'];
        $data["comment_count"]=$post['comment_count'];
        $data["type"]=$post_type;
        $data["php"]=$post_type;
        if($post_type=="company")$data["php"]="job";


        $data["img_url"]=get_thumbURL($post);
        $contents=json_decode($post["contents"],true);


        if(!empty($contents["text"])){
            $data["text"]=mb_strimwidth( strip_tags( $contents["text"] ), 0, 100, '…', 'UTF-8' );
        }else{
            $data["text"]="";
        }

        if(!empty($contents["tag"])){
            $data["tag"]=mb_strimwidth( strip_tags( $contents["tag"] ), 0, 100, '…', 'UTF-8' );
        }else{
            $data["tag"]="";
        }

        // $joinFlag=checkJoined($post,$user_data);
        $joinFlag=isJoined($post_id,$user_data);

        if(!empty($post["member"])){
            $member=json_decode($post["member"],true);
            $data["member_count"]=count($member);
        }else{
            $data["member_count"]=0;
        }

        $data["member_count"]=0;

        $owner_data=getOwnerData($post);
        $data["owner_name"]=$owner_data["name"];
        $data["owner_id"]=$post["owner_id"];

        if($joinFlag){
            $data["join_text"]='<span class="badge rounded-pill bg-info p-1 px-2">参加中</span>';
            $data["join_group"]="joined";
        }else{
            $data["join_text"]="";
            $data["join_group"]="other";
        }

        if($draw_type=="grid")make_griditem($data);
        if($draw_type=="doc")make_docitem($data);
        if($draw_type=="list")make_listitem($data);

    }
    closeDB($DB);
}


function getOwnerData($post){
    $data=array();

    if(!empty($post["owner_name"])){
        $owner_data=json_decode($post["owner_name"],true);
        $data["name"]=$owner_data["name"];
        $data["roll"]=$owner_data["roll"];
    }else{
        $owner_id=$post['owner_id'];
        $owner=getDBRow("user","*","WHERE id=$owner_id");

        if(!empty($owner["name"])){
            $data["name"]=$owner["name"];
            $data["roll"]=$owner["roll"];
        }else{
            $data["name"]="退会ユーザー";
            $data["name"]="不明";
        }
    }
    return $data;
}

//カード単体の描画
function make_griditem($data){
    $text=$data["text"];
    $type=$data["type"];
    $php=$data["php"];
    $tag=$data["tag"];
    $joinGroup=$data["join_group"];
    $imgURL=$data["img_url"];
    $link_url=$data["link_url"];
    $post_title=$data["post_title"];
    $joinText=$data["join_text"];
    $owner_name=$data["owner_name"];
    $member_count=$data["member_count"];
    $comment_count=$data["comment_count"];
    $owner_id=$data["owner_id"];
    $date=date("Y.n.j",strtotime($data["update_date"]));
    $time=date("H:i",strtotime($data["update_date"]));


    if(!empty($post_title)){
    $str = <<<EOM
    <div class="masonry-grid-item " data-groups='["$joinGroup"]'>
    <article class="card card-hover">
    EOM; echo $str;

    if(!empty($imgURL)){
    $str = <<<EOM
    <a class="card-img-top mb-1" href="$link_url" style="height:auto;max-height:10em">
    <img src="$imgURL" alt="Post thumbnail" style="object-fit: cover;">
    </a>
    EOM; echo $str;
    }

    $tag = preg_replace('/[#＃]([\w\x{05be}\x{05f3}\x{05f4}]*[\p{L}_\x{30FB}]+[\w\x{05be}\x{05f3}\x{05f4}]*)/u', 
    "<a href='$php.php?mode=search&type=$type&text=$1' >#$1</a>", $tag);
    $icon="";
    if($type=="company"){
        $icon="<i class='ai-calendar'></i>";
    }
    $str = <<<EOM
    <div class="card-body pt-2">
    <div class="nav-heading  fs-6"><a class="" href="$link_url">$icon$post_title</a></div>
    <div class="fs-xs mb-2">$date $time</div>
    <div class="fs-xs fw-normal mb-2">$text </div>
    <div class=" fw-normal " style="font-size:0.5em">$tag </div>
    <div class="mt-3  text-nowrap fs-ms">
    EOM; echo $str;

    if($type!="company"){
        make_owner($owner_id);
    }

    if($comment_count>0){
        echo "<div class='fs-xs position-absolute badge rounded-pill bg-danger' style='bottom:5px;right:5px;z-index:100;'>";
        echo "$comment_count</div>";
    }


    $str = <<<EOM
    </div></div>
    </article>
    </div>
EOM; echo $str;
    }
}


//カード単体の描画（リスト型）
function make_listitem($data){
    $text=$data["text"];
    $joinGroup=$data["join_group"];
    $imgURL=$data["img_url"];
    $link_url=$data["link_url"];
    $post_title=$data["post_title"];
    $joinText=$data["join_text"];
    $owner_name=$data["owner_name"];
    $member_count=$data["member_count"];
    if(!empty($post_title)){
    $str = <<<EOM
    <div class="d-flex align-items-center pb-2 mb-1">
    <a class="d-block flex-shrink-0" href="$link_url">
    <img class="rounded-1 project-thumb-side" width="60" src="$imgURL">
    </a>
    <div class="ps-2 ms-1">
    <h4 class="fs-sm nav-heading mb-1">
    <a class="fw-medium" href="$link_url">$post_title</a>
    </h4>
    <p class="fs-sm mb-0">$owner_name</p>
    </div>
    </div>
    EOM; echo $str;
    }
}

function make_listitem_join($data){
    $text=$data["text"];
    $joinGroup=$data["join_group"];
    $imgURL=$data["img_url"];
    $link_url=$data["link_url"];
    $post_title=$data["post_title"];
    $joinText=$data["join_text"];
    $owner_name=$data["owner_name"];
    $member_count=$data["member_count"];
    $id=$data["id"];
    if(!empty($post_title)){
    $str = <<<EOM
    <div class="d-flex align-items-center pb-2 mb-1">
    <a class="d-block flex-shrink-0" href="$link_url">
    <img class="rounded-1 project-thumb-side" src="$imgURL">
    </a>
    <div class="ps-2 ms-1">
    <h4 class="fs-xs nav-heading mb-1">
    <a class="fw-medium me-1" href="$link_url">$post_title</a>
    </h4>
    </div>
    EOM; echo $str;

    if(!empty($id)){
        echo "<div class='exit-dialog-button btn btn-outline-dark btn-sm p-1 px-2 ms-auto' post_id='$id' style='font-size:0.7em;'>退会</div>";
    }
    echo "</div>";
    }
}


//カード単体の描画（リスト型）
function make_docitem($data){
    $text=$data["text"];
    $joinGroup=$data["join_group"];
    $imgURL=$data["img_url"];
    $link_url=$data["link_url"];
    $post_title=$data["post_title"];
    $joinText=$data["join_text"];
    $owner_name=$data["owner_name"];
    $member_count=$data["member_count"];

    $date=date("Y.n.j",strtotime($data["update_date"]));
    $time=date("H:i",strtotime($data["update_date"]));



    if(!empty($post_title)){

        $str = <<<EOM
        <div class="masonry-grid-item " data-groups='["$joinGroup"]'>

        <div class="d-flex align-items-center mb-1 bg-light p-2 rounded-3">
        <a class="d-block flex-shrink-0" href="$link_url">
          <img class="rounded-1 project-thumb-side" width="60" src="$imgURL">
        </a>
        <div class="ps-2 ms-1">
          <div class="fs-sm nav-heading">
            <a class="fw-medium" href="$link_url">$post_title</a>
          </div>
          <div class="fs-xs fw-normal">
            $date $time
          </div>

         
          <p class="fs-xs mb-0">$owner_name</p>
        </div>
        </div>



        </div>
    EOM; echo $str;
    
    }
}



function make_card_button($link,$parent_id,$label,$card_type,$post_type,$year,$type_sub,$root_id){

    $parent="";
    $root="";
    if(!empty($parent_id))$parent="&parent=$parent_id";
    if(!empty($root_id))$root="&root=$root_id";
    
    $link_url="$link?mode=new$parent&type=$post_type&type_sub=$type_sub&year=$year".$root;

    $str = <<<EOM
    <div class="masonry-grid-item" data-groups='["joined"]'>
    <a class="text-widget text-white fw-bold" href="$link_url">
    <article class="card card-hover bg-success"><div class="card-body fs-sm">
    <i class="ai-plus-circle me-1"></i>$label
    </div></article></a></div>
    EOM;echo $str;
    
    }


//プロジェクトへの参加
function join_project($post_id,$user_id){
    echo "join_project";

    //ユーザー情報を取得
    $user_data=getDBRow("user","*","WHERE id=$user_id");
    //JSONを変換して配列の作成
    if (!empty($user_data["joined"])){
        $array=json_decode($user_data["joined"], true);
    }else{
        $array=array();
    }
    //参加IDを配列に追加
    array_push($array,["id" => "$post_id"]);
    //idのみ取り出し、重複削除、詰める
    $array = array_column($array, "id") ;
    $array = array_unique($array);
    $array = array_values($array);
    //新たに連想配列作成
    $joinedArray=array();
    foreach($array as $value){
        array_push($joinedArray,["id" => "$value"]);
    }
    //JSONへ変換
    $json = json_encode($joinedArray);
    //var_dump($json);

    //var_dump($json);
    //保存用データ作成
    $data = array();
    $data["joined"]=$json;
    //DBを更新
    $id=updateDB("user",$data,$user_id);
}


//プロジェクトへの参加
function add_member($post_id,$user_id){

    //ユーザー情報を取得
    $post=getDBRow("post","*","WHERE id=$post_id");
    //JSONを変換して配列の作成
    if (!empty($post["member"])){
        $array=json_decode($post["member"], true);
    }else{
        $array=array();
    }
    //参加IDを配列に追加
    array_push($array,["id" => "$user_id"]);
    //idのみ取り出し、重複削除、詰める
    $array = array_column($array, "id") ;
    $array = array_unique($array);
    $array = array_values($array);
    //新たに連想配列作成
    $memberArray=array();
    foreach($array as $value){
        array_push($memberArray,["id" => "$value"]);
    }
    //JSONへ変換
    $json = json_encode($memberArray);
    //var_dump($json);

    //var_dump($json);
    //保存用データ作成
    $data = array();
    $data["member"]=$json;
    //DBを更新
    $id=updateDB("post",$data,$post_id);

    var_dump($data["member"]);
}


//プロジェクトからメンバーを外す
function remove_member($post_id,$user_id){
    echo "remove_member\n";

    //ポスト情報を取得
    $post=getDBRow("post","*","WHERE id=$post_id");
    //JSONを変換して配列の作成
    if (!empty($post["member"])){
        $array=json_decode($post["member"], true);
    }else{
        $array=array();
    }


    //参加IDを配列に追加
    //idのみ取り出し、重複削除、詰める
    $array = array_column($array, "id") ;
    $array = array_unique($array);
    $array = array_values($array);


    //新たに連想配列作成
    $memberArray=array();
    foreach($array as $value){
        //この下の行がエラー発生
        if(!empty($value)){
            if($user_id != $value){
            array_push($memberArray,["id" => "$value"]);
            }
        }

        //echo "$value\n";
    }
    
    //JSONへ変換
    $json = json_encode($memberArray);

    //保存用データ作成
    $data = array();
    $data["member"]=$json;
    var_dump($data);

    //DBを更新
    $id=updateDB("post",$data,$post_id);
}



//プロジェクトからの退会
function exit_project($post_id,$user_id){

    $user_data=getDBRow("user","*","WHERE id=$user_id");
    //JSONを変換して配列の作成
    if (!empty($user_data["joined"])){
        $array=json_decode($user_data["joined"], true);
    }else{
        $array=array();
    }
    $joinedArray=array();
    foreach($array as $value){
        if($post_id != $value["id"]){
            array_push($joinedArray,["id" => $value["id"]]);
        }
    }
    //JSONへ変換
    $json = json_encode($joinedArray);
    //保存用データ作成
    $data = array();
    $data["joined"]=$json;
    //DBを更新
    $id=updateDB("user",$data,$user_id);
}

function delete_member($post_id,$user_id){
    $post=getDBRow("post","*","WHERE id=$post_id");
    //JSONを変換して配列の作成
    if (!empty($post["member"])){
        $array=json_decode($post["member"], true);
    }else{
        $array=array();
    }

    //var_dump($array);
    echo "<br><br>";
    $memberArray=array();
    foreach($array as $value){
        if($user_id != $value["id"]){
            array_push($memberArray,["id" => $value["id"]]);
        }
    }
    //JSONへ変換
    $json = json_encode($memberArray);
    //var_dump($json);
    //保存用データ作成
    $data = array();
    $data["member"]=$json;
    //DBを更新
    $id=updateDB("post",$data,$post_id);
}




function isJoined($id,$user_data){
    $joinArray= json_decode($user_data["joined"] , true);
    $joinedFlag=false;
    if(!empty($joinArray)){
    foreach($joinArray as $row){
        if($id==$row["id"]){
            $joinedFlag=true;
        }
    }
    }
    return $joinedFlag;
}



//コメントの一覧を表示
//ポストを表示中にAjaxから呼ばれる
function listup_comment($parent_id,$user_id){
    $postArray=getDB("post","*","WHERE type='comment' and parent_id=$parent_id");
    // var_dump("listup_database_php:".$user_id);
    foreach($postArray as $row){
        $comment_id=$row['id'];
        $owner_id=$row['owner_id'];
        $owner_data=getDBRow("user","*","WHERE id=$owner_id");

        if(!empty($owner_data["name"]))$owner_name=$owner_data["name"];
        else $owner_name="退会ユーザー";
        if(!empty($owner_data["roll"]))$owner_roll=$owner_data["roll"];
        else $owner_roll="none";

        $owner_thumb_url=get_thumbURL($owner_data);
        
        $json=json_decode($row['contents'],true);
        $commnet_text=$json['text'];
        
        echo "<div class='card card-hover mb-3 comment-item'><div class='card-body p-3'>";
        $str = <<<EOM
        <p class="fs-sm comment-text">$commnet_text</p>
        <div class="d-flex align-items-center">
        EOM;
        echo $str;
        make_owner($owner_id);
        make_date($row["update_date"]);

        //自分の記事のみ編集可能
        if($user_id==$owner_id){
            $str = <<<EOM
            <div class="fs-sm ms-auto">
            <button type='button' class='comment-edit btn btn-outline-success btn-sm px-2 py-1'
            data-bs-toggle='modal' data-bs-target='#modal-comment' owner_id=$owner_id parent_id=$parent_id comment_id=$comment_id >編集</button>
            </div>
            EOM;
            echo $str;
        }

        echo "</div>";
        echo "</div></div>";
    }
}




   
//プロジェクトの削除
function delete_comment($row_id){
    $data=getDBRow("post","*","WHERE id=$row_id");
    commentDelete($data["parent_id"]);
    deleteDB("post",$row_id);
}


function delete_attach_file($post_id,$media_id){
    $file=getDBRow("media","*","WHERE id=$media_id");
    $url=$file["url"];
    //ファイルの削除
   delete_file($url);
  //DBからメディアデータの削除
   deleteDB("media",$media_id);
    $post=getDBRow("post","*","WHERE id=$post_id");
    //アタッチファイルを調整
    if(!empty($post["file_json"])){
        $json=json_decode($post["file_json"],true);
        $new_json=array();
        foreach($json as $item){
            if($item["id"]!=$media_id){
                array_push($new_json,$item);
            }
        }
        $data=array();
        $data["file_json"]=json_encode($new_json);
        updateDB("post",$data,$post_id);
    }
}


//プロジェクトを編集
function update_comment($row_id){
    $table="post";
    $data = array();
    $data = formToData("comment","comment-text");
    //$data["title"]=get_formData("form-title");
    //$data["text"]=get_formData("form-text");
    $data["update_date"]=date('Y-m-d H:i:s');
    updateDB($table,$data,$row_id);
}


//プロジェクトの削除
function delete_group($row_id){
    $table="post";

    $past_media_url="";
    $media_id="";
    $media=getMedia($table,"*","WHERE id=$row_id");
    if(!empty($media)){
        $past_media_url=$media["url"];
        $media_id=$media["id"];
    }
    if(!empty($past_media_url)){
        echo"<p>Delete Media:".$past_media_url."</p>";
        deleteDB("media",$media_id);
       delete_file($past_media_url);
       }

    deleteDB("post",$row_id);

}



//参加しているメンバー一覧からリストを作成
//グループの検索処理が必要
function listup_member2($data,$parent_id,$filter_group_id){
    //グループを検索
    $groupArray=getDB("post","*","WHERE type='group' and parent_id=$parent_id");
    $groupList=array();

    foreach($groupArray as $group){
        $group_id=$group["id"];
        $group_name=$group["title"];
        $groupList[$group_id]=$group_name;
    }

    $str = <<<EOM
    <div class="table-responsive fs-sm">
    <table id="member-table" class="table table-striped">
    <thead>
    <tr>
    <th>名前</th><th>グループ</th><th>完成度</th><th>提出数</th>
    </tr>
    </thead>
    <tbody>
    EOM;echo $str;

    //ポスト内に登録されている参加メンバーを配列化
    if (!empty($data["member"])){
        $memberArray=json_decode($data["member"], true);
    }else{
        $memberArray=array();
    }

    $group_name="";
    $total_count=1;
    foreach($memberArray as $member){
        $member_id=$member["id"];
        $owner=getDBRow("user","*","WHERE id=$member_id");

        if(!empty($owner)){
            $owner_id=$owner["id"];
            $owner_name=$owner["name"];
            $owner_thumb_url=$owner["thumb_url"];
            $json=$owner["joined"];

            //総数をカウント
            // $owner_post_count=countDB("post","*","WHERE owner_id=$owner_id and type='post' and parent_id=$parent_id");
           // if($owner_post_count>0){ 
                $g=make_allgroup_filter($parent_id);
                $group_filter="parent_id ".$g." and ";
                $owner_post_count=countDB("post","*","WHERE $group_filter owner_id='$owner_id' and type_sub='grad-text'");

                $last_post=getDBRow("post","*","WHERE $group_filter owner_id='$owner_id' and type_sub='grad-text' ORDER BY create_date DESC LIMIT 1");

                if(!empty($last_post)){
                    $last_contents=json_decode($last_post["contents"],true);
                    $w=$last_contents["select-wip-grad"];
                }else{
                    $w="none";
                }
                $wip_array=array("none"=>"未着手","10"=>"10%","20"=>"20%","30"=>"30%","40"=>"40%","50"=>"50%","60"=>"60%","70"=>"70%","80"=>"80%","90"=>"90%","100"=>"100%");
                $wip=$wip_array[$w];
                //グループのラベルの生成
                $owner_join_array=json_decode($owner["joined"],true);
                $groupLabel=detectJoin($groupList,$owner_join_array);
                
                // $displayFlag=false;
                // if(!empty($owner_join_array)){
                //     foreach($owner_join_array as $join){
                //         if($filter_group_id==$join["id"])$displayFlag=true;
                //     }
                // }

                // if(empty($filter_group_id))$displayFlag=true;
                // $json
                //if($displayFlag){
                // detect_joined_group($owner_join_id,);
                $str = <<<EOM
                <tr>
                <th scope="row">$total_count $owner_name</th>
                <td>$groupLabel</td>
                <td>$wip</td>
                <td>$owner_post_count</td>
                </tr>
                EOM;echo $str;
                //}
           // }
        }

        $total_count++;

    }
    echo"</tbody></table>";
}


//ポストのメンバー情報と、ユーザーの参加情報を照らし合わせる
//listup_member2から呼ばれる
function detectJoin($groupArray,$joinArray){
    // echo"<br><br>";
    $resultArray=array();
    $resultStr="";
    if(!empty($joinArray)&&!empty($groupArray)){
        foreach($joinArray as $join){
            $id=$join["id"];
            if ( array_key_exists("$id", $groupArray) ) {
                $name=$groupArray["$id"];
                $resultStr.=$name." ";
            }
        }
    }
    return $resultStr;
}

//参加しているメンバー一覧からリストを作成
//グループの検索処理が必要
function listup_member_analyse($data,$parent_id,$filter_group_id){
    //グループを検索
    $groupArray=getDB("post","*","WHERE type='group' and parent_id=$parent_id");
    $groupList=array();

    foreach($groupArray as $group){
        $group_id=$group["id"];
        $group_name=$group["title"];
        $groupList[$group_id]=$group_name;
    }

    $str = <<<EOM
    <div class="table-responsive fs-xs member-table">
    <table id="member-table" class="table table-striped table-bordered">
    <thead>
    <tr>
    <th>名前</th><th>ゼミ</th><th>提出日</th><th>提出数</th><th>進路</th><th>就活レベル</th><th>就活状況</th>
    </tr>
    </thead>
    <tbody>
    EOM;echo $str;

    //ポスト内に登録されている参加メンバーを配列化
    if (!empty($data["member"])){
        $memberArray=json_decode($data["member"], true);
    }else{
        $memberArray=array();
    }
    
    $group_name="";
    $count=0;

    foreach($memberArray as $member){
        $member_id=$member["id"];
        
        $owner_name="";
        $groupLabe="";
        $wip="";
        $owner_post_count="";
        $select_job="";
        $select_wip_job="";
        $text_job="";
        $update_date="";

        //投稿者の情報を取得
        $owner=getDBRow("user","*","WHERE id=$member_id");
        
        if(!empty($owner)){
            //投稿者の常用
            $owner_id=$owner["id"];


            //$owner_name=$owner["name"];
            // $o_name = str_replace(array(" ", "　"), "", $owner["name"] );
            // $owner_name="<a href='analyse_student.php?project=$parent_id&student=$owner_id'>".$o_name."</a>";


            $owner_thumb_url=$owner["thumb_url"];
            
            $json=$owner["joined"];
            
            $g=make_allgroup_filter($parent_id);
            $group_filter="parent_id ".$g." and ";

            //投稿数のカウント
            $owner_post_count=countDB("post","*","WHERE $group_filter owner_id='$owner_id' and type_sub='grad-text' ");
            //最新の投稿を取得
            $last_post=getDBRow("post","*","WHERE $group_filter owner_id='$owner_id' and type_sub='grad-text' ORDER BY create_date DESC LIMIT 1");
            
            $count++;
           // $owner_name="<a href='analyse_student.php?project=$parent_id&student=$owner_id'>".$owner["name"]."</a>";
            $o_name = str_replace(array(" ", "　"), "", $owner["name"] );
            $owner_name="<a href='analyse_student.php?project=$parent_id&student=$owner_id'>".$o_name."</a>";

            if(!empty($last_post)){
                $last_post_id=$last_post["id"];

                $last_contents=json_decode($last_post["contents"],true);
                $w=$last_contents["select-wip-grad"];

                $select_job_key=$last_contents["select-job"];
                $select_job_array=array("job-work"=>"1.就職する","job-freelance"=>"2.就職しない","job-school"=>"3.進学する");
                $select_job=$select_job_array[$select_job_key];

                $select_wip_job_key=$last_contents["select-wip-job"];
                $select_wip_job_array=array("job-wip-danger"=>"1.未活動","job-wip-profile"=>"2.書類準備","job-wip-entry"=>"3.エントリー済み","job-wip-judge"=>"4.面接","job-wip-success"=>"5.内定","job-wip-complete"=>"5.内定");
                $select_wip_job_color_array=array("job-wip-danger"=>"text-danger fw-bold","job-wip-profile"=>"text-danger fw-bold","job-wip-entry"=>"text-warning fw-bold","job-wip-judge"=>"text-success fw-bold","job-wip-success"=>"text-info fw-bold","job-wip-complete"=>"text-info fw-bold");
                
                if(!empty($select_wip_job_color_array[$select_wip_job_key])){
                    $select_wip_job_color=$select_wip_job_color_array[$select_wip_job_key];
                }else{
                    $select_wip_job_color="";
                }

                $select_wip_job="<span class='$select_wip_job_color'>";

                if(!empty($select_wip_job_array[$select_wip_job_key])){
                    $select_wip_job.=$select_wip_job_array[$select_wip_job_key];
                }

                $select_wip_job.="</span>";

                if($select_job_key=="job-school")$select_wip_job="<span class='text-info fw-bold'>6.進学する</span>";
                if($select_job_key=="job-freelance")$select_wip_job="<span class='text-info fw-bold'>7.就職希望しない</span>";

                // $update_date=date('Y.n.d H:i',strtotime($last_post["update_date"]));
                $update_date=date('Y.n.j',strtotime($last_post["update_date"]));
                $text_job=$last_contents["text-job"];
                if($text_job=="")$text_job="-";

            }else{
                $w="none";
            }
            $wip_array=array("none"=>"未着手","10"=>"10%","20"=>"20%","30"=>"30%","40"=>"40%","50"=>"50%","60"=>"60%","70"=>"70%","80"=>"80%","90"=>"90%","100"=>"100%");
            $wip=$wip_array[$w];

            //グループのラベルの生成
            $owner_join_array=json_decode($owner["joined"],true);
            $groupLabel=detectJoin($groupList,$owner_join_array);
            
            $displayFlag=false;
            if(!empty($owner_join_array)){
                foreach($owner_join_array as $join){
                    if($filter_group_id==$join["id"])$displayFlag=true;
                }
            }

            if(empty($filter_group_id))$displayFlag=true;

            // $json
            if($displayFlag){
            // detect_joined_group($owner_join_id,);
            $str = <<<EOM
            <tr>
            <th scope="row">$owner_name</th>
            <td>$groupLabel</td>
            <td>$update_date</td>
            <td>$owner_post_count</td>
            <td>$select_job</td>
            <td>$select_wip_job</td>
            <td>$text_job</td>
            </tr>
            EOM;echo $str;
            }
           // }
        }
    }
    echo"</tbody></table>";

}

//学生個人の卒業制作と就活の分析=================
function student_analyse($data,$parent_id,$student_id,$filter_group_id){
    //グループを検索
    $groupArray=getDB("post","*","WHERE type='group' and parent_id=$parent_id");
    $groupList=array();

    foreach($groupArray as $group){
        $group_id=$group["id"];
        $group_name=$group["title"];
        $groupList[$group_id]=$group_name;
    }

    $str = <<<EOM
    <div class="table-responsive fs-xs member-table">
    <table id="member-table" class="table table-striped table-bordered">
    <thead>
    <tr>
    <th>名前</th><th>ゼミ</th><th>提出日</th><th>提出順</th><th>進路</th><th>就活レベル</th><th>就活状況</th>
    </tr>
    </thead>
    <tbody>
    EOM;echo $str;

    //ポスト内に登録されている参加メンバーを配列化
    if (!empty($data["member"])){
        $memberArray=json_decode($data["member"], true);
    }else{
        $memberArray=array();
    }
    
    $group_name="";
    $count=0;

    //foreach($memberArray as $member){
        $member_id=$student_id;
        
        $owner_name="";
        $groupLabe="";
        $wip="";
        $owner_post_count="";
        $select_job="";
        $select_wip_job="";
        $text_job="";
        $update_date="";

        //投稿者の情報を取得
        $owner=getDBRow("user","*","WHERE id=$member_id");
        
        if(!empty($owner)){
            //投稿者の常用
            $owner_id=$owner["id"];
            $owner_name=$owner["name"];
            $owner_thumb_url=$owner["thumb_url"];
            
            $json=$owner["joined"];
            
            $g=make_allgroup_filter($parent_id);
            $group_filter="parent_id ".$g." and ";

            //投稿数のカウント
            $owner_post_count=countDB("post","*","WHERE $group_filter owner_id='$owner_id' and type_sub='grad-text' ");
            //最新の投稿を取得
            $student_post=getDB("post","*","WHERE $group_filter owner_id='$owner_id' and type_sub='grad-text' ORDER BY create_date DESC");
            
            $o_name = str_replace(array(" ", "　"), "", $owner["name"] );
            $owner_name="<a href='analyse_student.php?project=$parent_id&student=$owner_id'>".$o_name."</a>";

            foreach($student_post as $last_post){
            if(!empty($last_post)){
                $last_post_id=$last_post["id"];

                $last_contents=json_decode($last_post["contents"],true);
                $w=$last_contents["select-wip-grad"];

                $select_job_key=$last_contents["select-job"];
                $select_job_array=array("job-work"=>"1.就職する","job-freelance"=>"2.就職しない","job-school"=>"3.進学する");
                $select_job=$select_job_array[$select_job_key];

                $select_wip_job_key=$last_contents["select-wip-job"];
                $select_wip_job_array=array("job-wip-danger"=>"1.未活動","job-wip-profile"=>"2.書類準備","job-wip-entry"=>"3.エントリー済み","job-wip-judge"=>"4.面接","job-wip-success"=>"5.内定");
                $select_wip_job_color_array=array("job-wip-danger"=>"text-danger fw-bold","job-wip-profile"=>"text-warning fw-bold","job-wip-entry"=>"text-warning fw-bold","job-wip-judge"=>"text-success fw-bold","job-wip-success"=>"text-info fw-bold");
                
                if(!empty($select_wip_job_color_array[$select_wip_job_key])){
                    $select_wip_job_color=$select_wip_job_color_array[$select_wip_job_key];
                }else{
                    $select_wip_job_color="";
                }

                $select_wip_job="<span class='$select_wip_job_color'>";

                if(!empty($select_wip_job_array[$select_wip_job_key])){
                 $select_wip_job.=$select_wip_job_array[$select_wip_job_key];
                }


                $select_wip_job.="</span>";

                if($select_job_key=="job-school")$select_wip_job="<span class='text-info fw-bold'>6.進学する</span>";
                if($select_job_key=="job-freelance")$select_wip_job="<span class='text-info fw-bold'>7.就職希望しない</span>";

                // $update_date=date('Y.n.d H:i',strtotime($last_post["update_date"]));
                $update_date=date('Y.n.j',strtotime($last_post["update_date"]));
                $text_job=$last_contents["text-job"];
                if($text_job=="")$text_job="-";

            }else{
                $w="none";
            }
            $wip_array=array("none"=>"未着手","10"=>"10%","20"=>"20%","30"=>"30%","40"=>"40%","50"=>"50%","60"=>"60%","70"=>"70%","80"=>"80%","90"=>"90%","100"=>"100%");
            $wip=$wip_array[$w];

            //グループのラベルの生成
            $owner_join_array=json_decode($owner["joined"],true);
            $groupLabel=detectJoin($groupList,$owner_join_array);
            
            $displayFlag=false;
            if(!empty($owner_join_array)){
                foreach($owner_join_array as $join){
                    if($filter_group_id==$join["id"])$displayFlag=true;
                }
            }

            if(empty($filter_group_id))$displayFlag=true;

            // $json
            if($displayFlag){
            // detect_joined_group($owner_join_id,);
            $str = <<<EOM
            <tr>
            <th scope="row">$owner_name</th>
            <td>$groupLabel</td>
            <td>$update_date</td>
            <td>$owner_post_count</td>
            <td>$select_job</td>
            <td>$select_wip_job</td>
            <td>$text_job</td>
            </tr>
            EOM;echo $str;
            $owner_post_count--;
            }
         }
        }
    //}
    echo"</tbody></table>";

}



//ユーザー情報の更新
function update_user($row_id){
    $table="user";
    $past_media_url=NULL;
    $media_id=NULL;
    $file_json=NULL;

    $data = formToData("user","user-text");
    //MediaIDを持っていたらぞ情報取得
    if(!empty($post["media_id"])){
        $media_id=$post["media_id"];
        //MediaIDからメディアを取得
        $media=getDBRow("media","*","WHERE id=$media_id");
        $past_media_url=$media["url"];
        $media_id=$media["id"];
    }

    //プリセットかアップロードを判断
    $radio_image=get_formData("radio-image");
    //アップロードの場合
    if($radio_image=="upload"){
        //フォームにイメージ画像がセットされている？
        if($_FILES['form-image']['size'] > 0){
            //アップロードする
            $thumb=upload_image();
            $data["media_id"]=$thumb["id"];
            $data["thumb_url"]=$thumb["url"];
            //既存イメージ画像があるなら削除する
            if(!empty($past_media_url)){
                echo"<p>Delete Media:".$past_media_url."</p>";
                deleteDB("media",$media_id);
                delete_file($past_media_url);
            }
        }
    }else{
        //プリセットの場合
        $preset_name=get_formData("preset-name");

        if(!empty($preset_name)){
            $data["thumb_url"]=$preset_name;
            $data["media_id"]="";
            //既存イメージ画像があるなら削除する
            if(!empty($past_media_url)){
                echo"<p>Delete Media:".$past_media_url."</p>";
                deleteDB("media",$media_id);
                delete_file($past_media_url);
            }
        }
    }
    //var_dump($table);
    updateDB($table,$data,$row_id);
}






//戻る
function make_back($table,$post,$root_id){
    $root_name="";
    $post_id="";
    $parent_name="";
    $parent_id="";
    $url_param="";
    $back_url="";
    $return_url="";
    $result=array();

    $post_type=$post["type"];
    $post_type_sub=$post["type_sub"];
    if(!empty($post["id"]))$post_id=$post["id"];

    //親を探す
    $parent_id=$post["parent_id"];
    //親のデータを取得
    if(!empty($parent_id)){
        $parent_data=getDBRow($table,"*","WHERE id=$parent_id");
        if(!empty($parent_data)){
            $parent_name=$parent_data["title"];
            $root_id=$parent_data["parent_id"];
            $root_data=getDBRow($table,"*","WHERE id='$root_id'");
            if(!empty($root_data)){
                $root_name=$root_data["title"];
            }
        }
    }
    
    $files=array("doc"=>"project.php","post"=>"project.php","job"=>"job.php","share"=>"share.php","office"=>"office.php","group"=>"project.php","project"=>"index.php","annual"=>"index.php","company"=>"user.php","personal"=>"user.php");
    $labels=array("doc"=>"まなぶ","job"=>"はたらく","share"=>"おすすめ","office"=>"準備室","project"=>"トップ","annual"=>"トップ");
    $filename=$files[$post_type];

 

    //リンクを作成
    if($post_type=="doc" || $post_type=="post"){
        $back_root_url="$filename?id=$root_id&tab=post";
        $url_param="id=$root_id&group_id=$parent_id&tab=post";
        $back_url="$filename?$url_param";
        $str = <<<EOM
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"> <a href="$back_root_url"> <i class="ai-flag"></i>$root_name</a></li>
        <li class="breadcrumb-item"><a href="$back_url">$parent_name</a></li>
        </ol></nav>
        EOM;echo $str;

    }


    if($post_type=="job" || $post_type=="share" ||$post_type=="office"||$post_type=="project"||$post_type=="annual"){
        $label=$labels[$post_type];
        $back_url=$filename;
        $str = <<<EOM
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"> <a href="$back_url"> <i class="ai-flag"></i>$label</a></li>
        </ol></nav>
        EOM;echo $str;
    }

    //========= RETURN
    if($post_type=="company" || $post_type=="personal"){
        $user_id = $_SESSION["id"];
        $back_url="user.php?id=$user_id";
        $return_url="user.php?id=$user_id";
    }

    if($post_type=="job" || $post_type=="share" ||$post_type=="office"){
        $return_url="post.php?id=$post_id&type=$post_type";
    }
    if($post_type=="project"){
        $return_url="project.php?id=$post_id&type=$post_type";
    }

    if($post_type=="annual"){
        $return_url=$filename;
    }
    if($post_type=="group"){
        $back_url="project.php?id=$parent_id&tab=post";
        $return_url="project.php?id=$parent_id&tab=post&group_id=$post_id";
    }

    if($post_type=="doc" || $post_type=="post"){
        $return_url="post.php?id=$post_id&type=$post_type&root=$root_id&parent=$parent_id";
    }

    $result["back_url"]=$back_url;
    $result["url_param"]=$url_param;

    $result["return_url"]=$return_url;
    if(empty($post_id))$result["return_url"]=$back_url;


    return $result;

}




function make_date($date){
    $date_form=date('Y.n.j H:i',strtotime($date));
    $str = <<<EOM
    <div class="meta-link fs-xs">
    <i class="ai-calendar me-1 mt-n1">
    </i>$date_form
    </div>
    EOM;echo $str;
}

function make_owner($owner_id){
    $owner=getDBRow("user","*","WHERE id=$owner_id");

    if(!empty($owner["name"]))$owner_name=$owner["name"];
    else $owner_name="退会ユーザー";
    if(!empty($owner["roll"]))$owner_roll=$owner["roll"];
    else $owner_roll="none";

    $owner_thumb=get_thumbURL($owner);


    $str = <<<EOM
    <div class="navbar-tool">
    <a class="navbar-tool-icon-box" href="user.php?id=$owner_id">
      <img class="navbar-tool-icon-box-img img-thumbnail rounded-circle square-image" src="$owner_thumb">
    </a>
    <a class="navbar-tool-label px-2" href="user.php?id=$owner_id"><small>
    $owner_roll</small>$owner_name</a>
    </div>
    EOM;echo $str;
    return $owner;
}


function make_owner_info($owner_id){
    $owner=getDBRow("user","*","WHERE id=$owner_id");

    if(!empty($owner["name"]))$owner_name=$owner["name"];
    else $owner_name="退会ユーザー";
    if(!empty($owner["roll"]))$owner_roll=$owner["roll"];
    else $owner_roll="none";

    $owner_thumb=get_thumbURL($owner);

    return $owner;
}




function make_allgroup_filter($parent_id){
    $filter="WHERE type='group' and parent_id='$parent_id'";
    $DB=openDB();
    $sql="SELECT * FROM post $filter";
    $getData = $DB->query($sql);
    $count=0;
        $result="IN (";
        foreach ($getData as $post) {
            $result.=$post["id"].",";
            $count++;
        }
        $result = rtrim($result, ",");
        $result.=")";

   if($count==0)$result=NULL;

    return $result;
}

//DBからテーマ一覧表示
function listup_group($user_data,$parent_id,$tabType,$filter,$focus_id){
    //データベースと接続
    $DB=openDB();
    $sql="SELECT * FROM post $filter";
    $getData = $DB->query($sql);
    $user_id=$user_data["id"];
    if(empty($focus_id))$active="list-group-item-info";
    else $active="";
    $join_count=0;
    foreach ($getData as $post) {
        $post_id=$post['id'];
        $link_url="project.php?id=$parent_id&tab=$tabType&group_id=$post_id";
        $post_title=$post['title'];
        $imgURL=$post["thumb_url"];
        $contents=json_decode($post["contents"],true);
        $text=mb_strimwidth( strip_tags( $contents["text"] ), 0, 100, '…', 'UTF-8' );
        $joinFlag=isJoined($post_id,$user_data);

        if($post_id==$focus_id)$active="list-group-item-info";
        else $active="";

        if(!empty($post["member"])){
            $member=json_decode($post["member"],true);
            $member_count=count($member);
        }else{
            $member_count=0;
        }
        $owner_id=$post['owner_id'];
        $owner=getDBRow("user","*","WHERE id=$owner_id");
        $owner_name=$owner["name"];

        if($joinFlag){
            $joinLabel='<span class="badge rounded-pill bg-info p-1 px-2">所属中</span>';
            $joinGroup="joined";
            $join_count++;
        }else{
            $joinLabel="";
            $joinGroup="other";
        }
        $str = <<<EOM
        <div class='d-flex justify-content-between fw-bold list-group-item  $active'>
        <a class="" href="$link_url" >$post_title</a>
        <div class="group-info-button" group_id="$post_id" join_flag="$joinFlag" data-bs-toggle="modal" data-bs-target="#modal-group-info">
        $joinLabel </div>
        </button>
        </div>
        EOM; echo $str;
    }
    closeDB($DB);
    return $join_count;
}



//DBからテーマ一覧表示
function listup_group2($user_data,$parent_id,$tabType,$filter,$focus_id){
    //データベースと接続
    $DB=openDB();
    $sql="SELECT * FROM post $filter";
    $getData = $DB->query($sql);
    $user_id=$user_data["id"];
    if(empty($focus_id))$active="list-group-item-info";
    else $active="";

    $root_id=$parent_id;
    $join_count=0;
    $total_count=0;
    $html="";

    foreach ($getData as $post) {
        $total_count++;
        $post_id=$post['id'];
        $link_url="project.php?id=$parent_id&tab=$tabType&group_id=$post_id";
        $post_title=$post['title'];
        $imgURL=$post["thumb_url"];
        $contents=json_decode($post["contents"],true);
        $text=mb_strimwidth( strip_tags( $contents["text"] ), 0, 100, '…', 'UTF-8' );
        $joinFlag=isJoined($post_id,$user_data);

        if($post_id==$focus_id)$active="list-group-item-info";
        else $active="";

        if(!empty($post["member"])){
            $member=json_decode($post["member"],true);
            $member_count=count($member);
        }else{
            $member_count=0;
        }
        $owner_id=$post['owner_id'];
        $owner=getDBRow("user","*","WHERE id=$owner_id");

        $order="5";
        if(!empty($contents["order"])){
            $order=$contents["order"];
        }
        
        $border="";
        if(!empty($contents["border"])){
            if($contents["border"]=="border")$border="mb-3";

        }

        if($joinFlag){
            $joinLabel='<span class="badge rounded-pill bg-info p-1 px-2">所属中</span>';
            $joinGroup="joined";
            $join_count++;
        }else{
            $joinLabel="";
            $joinGroup="other";
        }


        $str = <<<EOM
        <div class='group-list-item d-flex align-item-center justify-content-between fw-bold list-group-item py-2 ps-2 pe-2 border-0 rounded-2 $border $active' order='$order'>

        <a class="flex-fill" style="text-decoration:none;" href="$link_url" ><i class="ai-folder me-1"></i>$post_title</a>
        <div join_flag="$joinFlag" root_id="$root_id" group_id="$post_id" data-bs-target="#modal-group-info">
        $joinLabel </div>
        </div>
        EOM; 

        $html.=$str;
    }
    closeDB($DB);

    $result=array("html"=>$html,"total_count"=>$total_count,"join_count"=>$join_count);

    return $result;
}




function listup_group_analyse($user_data,$parent_id,$tabType,$filter,$focus_id){
    //データベースと接続
    $DB=openDB();
    $sql="SELECT * FROM post $filter";
    $getData = $DB->query($sql);
    $user_id=$user_data["id"];
    if(empty($focus_id))$active="list-group-item-info";
    else $active="";

    $root_id=$parent_id;
    $join_count=0;
    $total_count=0;
    $html="";

    foreach ($getData as $post) {
        $total_count++;
        $post_id=$post['id'];
        $link_url="project.php?id=$parent_id&tab=$tabType&group_id=$post_id";
        $post_title=$post['title'];
        $imgURL=$post["thumb_url"];
        $contents=json_decode($post["contents"],true);
        $text=mb_strimwidth( strip_tags( $contents["text"] ), 0, 100, '…', 'UTF-8' );

        $joinFlag=isJoined($post_id,$user_data);

        if($post_id==$focus_id)$active="list-group-item-info";
        else $active="";

        if(!empty($post["member"])){
            $member=json_decode($post["member"],true);
            $member_count=count($member);
        }else{
            $member_count=0;
        }
        $owner_id=$post['owner_id'];
        $owner=getDBRow("user","*","WHERE id=$owner_id");
        $owner_name=$owner["name"];

        if($joinFlag){
            $join_count++;
        }else{
        }
        $str = <<<EOM
        <div class=' list-group-item fs-xs py-1 px-0'>
        $post_title $member_count
        </div>
        EOM; 

        $html.=$str;
    }
    closeDB($DB);

    $result=array("html"=>$html,"total_count"=>$total_count,"join_count"=>$join_count);

    return $result;
}


//グループメンバーが投稿した適時一覧
//プロジェクトページで使用される
function grid_group_post($id,$member_data){
    $memberArray=json_decode($member_data["member"],true);
    if(!empty($memberArray)){

    $uniteArray=array();
    foreach($memberArray as $member){
        $owner_id=$member["id"];
        $postArray=getDB("post","*","WHERE type='post' and parent_id=$id and owner_id=$owner_id");
        foreach($postArray as $post){
            array_push($uniteArray,$post);
        }
    }
    foreach($uniteArray as $post){
        $data=array();
        $post_id=$post['id'];
        $data["link_url"]="post.php?id=$post_id";
        $data["post_title"]=$post['title'];
        $data["img_url"]=get_thumbURL($post);
        $data["text"]=$post["text"];
        $data["join_group"]="0";
        $data["join_text"]="0";
        $data["member_count"]="0";
        $owner_id=$post['owner_id'];
        $owner=getDBRow("user","*","WHERE id=$owner_id");
        $data["owner_name"]=$owner["name"];

        make_griditem($data);
    }
}
}

//グループリストの情報ボタンからAjax経由で呼ばれる
//グループの情報をゲットしてダイアログに表示
function get_group_info($group_id,$join_flag){
    $data=getDBRow("post","*","WHERE type='group' and id=$group_id");
    $contents=json_decode($data["contents"],true);
    $title=$data["title"];
    $text=$contents["text"];

    if(!empty($data["media_id"])){
        $image=$data["thumb_url"];
    }else{
        $image="preset/1080p/".$data["thumb_url"];
    }


    $str = <<<EOM
    <div class="d-flex align-items-center pb-2 mb-1">
    <img class="rounded" width="100" src="$image"/>
    <div class="ps-2 ms-1">
    <h4 class="fs-md nav-heading mb-1">
     $title
    </h4>
    <p class="fs-sm mb-0">$text</p>
    </div>
    </div>
  EOM; echo $str;
}


//チャートのための統計処理
function grad_analyze($year){
    //年度データの取得
    $data=getDBRow("post","*","WHERE year='$year' and type='annual'");
    $member_find_data_count=0;

    $analyze=NULL;
    if(!empty($data)){
        $contents=json_decode($data["contents"],true);
        $grad_id=$contents["grad"];
        //統計先の卒業研究を取得
        $grad_data=getDBRow("post","*","WHERE id='$grad_id'");
        if(!empty($grad_data)){
        //全メンバーを取得
         $member_array=json_decode($grad_data["member"],true);

        if(!empty($grad_data["member"])){
        
        $analyze=array("job-wip-nocount"=>0,"job-wip-danger"=>0,"job-wip-profile"=>0,"job-wip-entry"=>0,"job-wip-judge"=>0,"job-wip-success"=>0);
        $analyze["total"]=count($member_array);
        $analyze["project_id"]=$grad_id;

        foreach($member_array as $member){
            $member_id=$member["id"];

            //ここのParent_idがプロジェクトではなく
            //複数のグループIDである必要あり
            $g=make_allgroup_filter($grad_id);
            if(!empty($g)){
                $group_filter="parent_id ".$g." and ";
                $member_data=getDBRow("post","*","WHERE $group_filter owner_id='$member_id' and type_sub='grad-text' ORDER BY create_date DESC LIMIT 1");
                //メンバー情報を取得
                if($member_data){
                    $member_find_data_count++;
                    $member_contents=json_decode($member_data["contents"],true);
                    //就職を希望する
                    if($member_contents["select-job"]=="job-work"){
                        $member_status=$member_contents["select-wip-job"];
                        $analyze[$member_status]++;
                    }else{
                        //就職を希望しない
                        $analyze["job-wip-nocount"]++;
                    }

                }else{
                    $analyze["job-wip-danger"]++;
                }
            }
        }
        //if($analyze["total"]==0)$analyze=NULL;

    }

    }
    }
    $analyze["find-total"]=$member_find_data_count;
    // $analyze["find-total"]=$count;

    return $analyze;
}


//チャートのための統計処理
function grad_analyze2($year){

    $analyze=array("job-wip-empty"=>0,"job-wip-school"=>0,"job-wip-nocount"=>0,"job-wip-danger"=>0,"job-wip-profile"=>0,"job-wip-entry"=>0,"job-wip-judge"=>0,"job-wip-complete"=>0,"job-wip-success"=>0,"naitei"=>0);
    
    //年度データで設定されている授業の取得
    $data=getDBRow("post","*","WHERE year='$year' and type='annual'");
    if(!empty($data)){
        $contents=json_decode($data["contents"],true);
        $grad_id=$contents["grad"];
        $analyze["project_id"]=$grad_id;

        //統計先の卒業研究を取得
        $grad_data=getDBRow("post","*","WHERE id='$grad_id'");


    $post_id=$grad_data["id"];
    //var_dump($grad_data["id"]);
    //授業に含まれるグループを検索
    $groupArray=getDB("post","*","WHERE type='group' and parent_id=$post_id");

    $groupList=array();
    foreach($groupArray as $group){
        $group_id=$group["id"];
        $group_name=$group["title"];
        $groupList[$group_id]=$group_name;
    }

    //ポスト内に登録されている参加メンバーを配列化
    if (!empty($grad_data["member"])){
        $memberArray=json_decode($grad_data["member"], true);
    }else{
        $memberArray=array();
    }
    $count=0;


    //授業に参加しているユーザーを全て処理
    foreach($memberArray as $member){
        $member_id=$member["id"];
        // $owner=getDBRow("user","*","WHERE roll='student' and id=$member_id");
        $owner=getDBRow("user","*","WHERE id=$member_id");

        //ユーザーが見つかった場合
        if(!empty($owner)){
            $count++;
            $owner_id=$owner["id"];
            $owner_name=$owner["name"];
            $owner_thumb_url=$owner["thumb_url"];
            $json=$owner["joined"];

            //全グループを対象にユーザーの最新の投稿一件を探す
            $g=make_allgroup_filter($post_id);
            $group_filter="parent_id ".$g." and ";
            $last_post=getDBRow("post","*","WHERE $group_filter owner_id='$owner_id' and type_sub='grad-text' ORDER BY create_date DESC LIMIT 1");
            
            //投稿が見つかった場合
            if(!empty($last_post)){
                $last_contents=json_decode($last_post["contents"],true);
                $job_type=$last_contents["select-job"];

                //就職を希望する
                if($job_type=="job-work"){
                    $job_wip=$last_contents["select-wip-job"];
                   $analyze[$job_wip]++;
                }

                if(!empty($last_contents["select-naitei"])){
                    if($last_contents["select-naitei"]=="job-naitei"){
                       $analyze["naitei"]++;
                    }
    
                }

                //就職を希望しない
                if($job_type=="job-freelance"){
                    $analyze["job-wip-nocount"]++;
                }

                if($job_type=="job-school"){
                    $analyze["job-wip-school"]++;
                }

            }else{
                //投稿が見つからない場合
                  $analyze["job-wip-empty"]++;
                }
        }
    }
}
    $analyze["total"]=$count;
    $analyze["find-total"]=$count;

    return $analyze;
}


//ページネーション
function pagenation($page_current,$page_limit,$page_total,$link,$type){
    
    if(empty($page_current))$page_current=1;
    $page_offset=$page_limit*($page_current-1);

    $page_link_prev=$page_current-1;
    if($page_link_prev<1)$page_link_prev_visible=0;
    else $page_link_prev_visible=100;

    $page_link_next=$page_current+1;
    if($page_link_next>$page_total)$page_link_next_visible=0;
    else $page_link_next_visible=100;

    $html="";

    $html.= "<nav aria-label='Page navigation example'>";
    $html.= "<ul class='pagination'>";

    $html.= "<li class='page-item'>";
    if($page_link_prev_visible)$html.= "<a href='$link"."page=$page_link_prev' class='page-link'>";
    else $html.= "<div class='page-link'>";
    $html.=  "<i class='ai-chevron-left opacity-$page_link_prev_visible'></i>";
    if($page_link_prev_visible)$html.= "</a>";
    else $html.="</div>";
    $html.= "</li>";

    for ( $i = 1; $i <= $page_total; $i++ ) {
        if($i==$page_current)$active="active";
        else $active="";

        $html.= "<li class='page-item $active' >";
        $html.= "<a href='$link"."page=$i&type=$type' class='page-link'>$i</a>";
        $html.= "</li>";
    }
    $html.= "<li class='page-item'>";
    if($page_link_next_visible)$html.= "<a href='$link"."page=$page_link_next' class='page-link'>";
    else $html.= "<div class='page-link'>";
    $html.=  "<i class='ai-chevron-right opacity-$page_link_next_visible'></i>";
    if($page_link_next_visible)$html.= "</a>";
    else $html.="</div>";
    $html.= "</li>";
    $html.= "</ul></nav>";

    $result=array("page_offset"=>$page_offset,"html"=>$html);
    return $result;
}


function formSecureCheck($item){
    $lock=false;
    if(!empty($item["secure"])){
        if($item["secure"]=="true"){
            $user_id = $_SESSION["id"];
            $user_data=getDBRow("user","*","WHERE id=$user_id");
            if($user_data["roll"]=="admin"){
                $lock=false;
            }else{
                $lock=true;
            }
        }
    }

    return $lock;
}


function getFormLabel($item){
    
    $label=array();

    if(!empty($item["require"])){
        if($item["require"]){
            $label["require"]="require='true'";
            $label["label"]=$item["label"]." <span class='text-danger fw-bold'>[必須]</s>";
        }else{
            $label["require"]="require='false'";
            $label["label"]=$item["label"];
        }
    }else{
        $label["require"]="";
        $label["label"]=$item["label"];
    }


    return $label;
}

//未使用
function form_require_check(){
    // $data = formToData($_POST["form-type"],$_POST["type_sub"]);
    // var_dump($data);
}


function isEditable($editable,$owner_data,$user_data){
    $editFlag=false;

    if(!empty($owner_data["id"]))$owner_id=$owner_data["id"];
    else $owner_id=NULL;
    if(!empty($owner_data["roll"]))$owner_roll=$owner_data["roll"];
    else $owner_roll="none";

    $user_id=$user_data["id"];
    $user_roll=$user_data["roll"];

    if($editable=="co-edit"){
        if($owner_roll==$user_roll)$editFlag=true;
            if($owner_roll=="admin"){
                if($user_roll=="teacher")$editFlag=true;
            }
    }

    //作った本人なら編集可能
    if($owner_id == $user_id)$editFlag=true;

    //書類の権限がフリーの場合は、全員が編集可能
    if($editable=="free")$editFlag=true;

    //ユーザーが管理者なら全て編集可能
    if($user_roll=="admin")$editFlag=true;

    return $editFlag;

}


function makeProgressBar($contents){
    if(!empty($contents["start-end"])){
        $range=$contents["start-end"];
        $range = str_replace(".", "-", $range);
        $range_array=explode(",",$range);
        
        if(empty($range_array[0]) || empty($range_array[1])){
            $emptyFlag=true;
        }else $emptyFlag=false;

        if(!$emptyFlag){
            $day_start = new DateTime($range_array[0]);
            $day_end = new DateTime($range_array[1]);
            $day_today = new DateTime(date("Y-m-d"));

            // $period_total_diff = $day_start->diff($day_end)->format('%a');
            $period_total_diff = $day_start->diff($day_end);
            $period_today_diff = $day_start->diff($day_today);
            
            $period_total=$period_total_diff->format('%a');
            if(($period_total_diff->invert)==1)$period_total*=-1;
            
            $period_today=$period_today_diff->format('%a');
            if(($period_today_diff->invert)==1)$period_today*=-1;
            
            $period_left=$period_total-$period_today;
            //  echo "$range 総日数:$period_total 経過:$period_today 残：$period_left";
            if($period_total!=0){
            $period_value = ($period_today/$period_total)*100;
            }
            echo "<span class='fs-xs ms-1'>(残り$period_left"."日)</span>";
            echo "<div class='progress mb-3 mt-2'>";
            $str = <<<EOM
            <div class="progress-bar fw-medium bg-info" role="progressbar" style="width: $period_value%" aria-valuenow="$period_today" aria-valuemin="0" aria-valuemax="$period_total">
            </div>
            EOM; echo $str;
            echo "</div>";

        }

    }

}


function makeJobChart($year,$user_data){
    // $analyze=grad_analyze($year);
    $analyze=grad_analyze2($year);
    $total=$analyze["total"];
    $chart_status_json=NULL;
    $level=getUserRollLevel($user_data);

    if($analyze["find-total"]>0){
        $chart_status=array();
        $chart_status["empty"]=$analyze["job-wip-empty"];
        $chart_status["nocount"]=$analyze["job-wip-nocount"];
        $chart_status["school"]=$analyze["job-wip-school"];
        $chart_status["danger"]=$analyze["job-wip-danger"];
        $chart_status["prepare"]=$analyze["job-wip-profile"];
        $chart_status["active"]=$analyze["job-wip-entry"];
        $chart_status["judge"]=$analyze["job-wip-judge"];
        $chart_status["success"]=$analyze["job-wip-success"]+$analyze["job-wip-complete"];


        $chart_nocount=$analyze["job-wip-nocount"];
        $chart_school=$analyze["job-wip-school"];
        $chart_danger=$analyze["job-wip-danger"];
        $chart_prepare=$analyze["job-wip-profile"];
        $chart_active=$analyze["job-wip-entry"];
        $chart_judge=$analyze["job-wip-judge"];
        $chart_success=$analyze["job-wip-success"]+$analyze["job-wip-complete"];
        $chart_empty=$analyze["job-wip-empty"];

        $chart_naitei=$analyze["naitei"];
        if($chart_success<$chart_naitei){
            $chart_success=$chart_naitei;
            $chart_status["success"]=$chart_naitei;
        }

        $chart_status_json=json_encode($chart_status);

        }

        if($analyze["find-total"]>0){
            $str = <<<EOM
            <div class="rounded-3 bg-light p-4 my-3">

            <div class="text-center mb-2 fw-bold">
            4年生の進路 ($total<span class="fs-xs">名</span>)
            </div>

            <canvas id="grad-chart"></canvas>
            <script>drawChart($chart_status_json);</script>
            
            <div class="d-flex justify-content-center mt-4">
            <div class="badge  rounded-pill bg-danger px-2 py-1 me-1">未活:$chart_danger</div>
            <div class="badge  rounded-pill  px-2 py-1 me-1" style="background-color:rgb(255,120,80);">準備:$chart_prepare</div>
            <div class="badge  rounded-pill bg-warning px-2 py-1 me-1">活動:$chart_active</div>
            </div>
            <div class="d-flex justify-content-center mt-2">
            <div class="badge  rounded-pill bg-success px-2 py-1">面接:$chart_judge</div>
            <div class="badge  rounded-pill bg-info px-2 py-1 ms-1">内定:$chart_success</div>
            </div>

            <div class="d-flex justify-content-center mt-2">
            <div class="badge rounded-pill px-2 py-1 me-1" style="background-color:rgb(100,100,130)">希望せず:$chart_nocount</div>
            <div class="badge rounded-pill px-2 py-1 me-1" style="background-color:rgb(150,150,180)">進学:$chart_school</div>
            <div class="badge rounded-pill px-2 py-1 me-1" style="background-color:rgb(200,200,200)">未答:$chart_empty</div>
            </div>

            <div class="d-flex justify-content-center mt-4">
            EOM; echo $str;
            //管理者のみ
            if($level>2){
                $project_id=$analyze["project_id"];
               echo "<a class='btn btn-outline-dark btn-sm fs-xs px-2 py-1 mx-1' roll='button' href='editor.php?mode=new&type=annual&year=$year&type_sub=annual-data'>統計設定</a>";
            }
            //専任教員以上
            if($level>1){
                $project_id=$analyze["project_id"];

                echo "<a class='btn btn-outline-danger btn-sm fs-xs px-2 py-1 mx-1' roll='button' href='analyse.php?id=$project_id'>分析(専任のみ)</a>";
             }
            echo "</div>";
            echo "</div>";
        }
    }


    function makeJobChart2($year,$user_data){
        // $analyze=grad_analyze($year);
        // $total=$analyze["total"];
        // $chart_status_json=NULL;
        // $level=getUserRollLevel($user_data);
    
        // if($analyze["find-total"]>0){
        //     $chart_status=array();
        //     $chart_status["nocount"]=$analyze["job-wip-nocount"];
        //     $chart_status["danger"]=$analyze["job-wip-danger"];
        //     $chart_status["active"]=$analyze["job-wip-profile"]+$analyze["job-wip-entry"]+$analyze["job-wip-judge"];
        //     $chart_status["success"]=$analyze["job-wip-success"];
        //     $chart_status_json=json_encode($chart_status);
    
        //     $chart_nocount=$analyze["job-wip-nocount"];
        //     $chart_danger=$analyze["job-wip-danger"];
        //     $chart_active=$analyze["job-wip-profile"]+$analyze["job-wip-entry"]+$analyze["job-wip-judge"];
        //     $chart_success=$analyze["job-wip-success"];
    
        //     }
    
        //     if($analyze["find-total"]>0){
    
        // $str = <<<EOM
        // <!-- Pie chart: Multiple slices of different color + Legend -->

        // <!-- Legend -->
        // <div class="d-flex flex-wrap justify-content-center fs-sm">
        // <div class="border rounded-1 py-1 px-1 me-2 mb-2">
        // <div class="d-inline-block align-middle me-0" style="width: .75rem; height: .75rem; background-color: #6a9bf4;"></div>
        // <span class="d-inline-block align-middle fs-xs">内定$chart_success</span>
        // </div>
        // <div class="border rounded-1 py-1 px-2 me-2 mb-2">
        // <div class="d-inline-block align-middle me-0" style="width: .75rem; height: .75rem; background-color: #16c995;"></div>
        // <span class="d-inline-block align-middle fs-xs">活動$chart_active</span>
        // </div>
        // <div class="border rounded-1 py-1 px-2 me-2 mb-2">
        // <div class="d-inline-block align-middle me-0" style="width: .75rem; height: .75rem; background-color: #f74f78;"></div>
        // <span class="d-inline-block align-middle fs-xs">未活動$chart_danger</span>
        // </div>
        // <div class="border rounded-1 py-1 px-2 me-2 mb-2">
        // <div class="d-inline-block align-middle me-0" style="width: .75rem; height: .75rem; background-color: #999999;"></div>
        // <span class="d-inline-block align-middle fs-xs">その他$chart_nocount</span>
        // </div>
        // </div>

        // <!-- Chart -->
        // <div class="ct-chart ct-perfect-fourth" data-pie-chart='{"series": [$chart_success, $chart_active, $chart_danger,$chart_nocount]}', data-series-color='{"colors": ["#6a9bf4", "#16c995", "#f74f78","#999999"]}'></div>
        // EOM; echo $str;
        //     }

    }


    function make_search($file,$flag,$keyword,$type){
        $str = <<<EOM
        <div class="masonry-grid-item" data-groups='["joined"]'>
        <div class="widget">
        <form class="search-form" action="$file" method="get" name="search-form">
        <div class="input-group flex-nowrap">
        <input class="form-control rounded-start fs-xs" type="text" name="text" placeholder="検索" required value="$keyword">
        <input type="hidden" name="mode" value="search">
        <input type="hidden" name="type" value="$type">
        <button class="btn btn-dark" type="submit"><i class="ai-search"></i></button>
        </div>
        </form>
        EOM;echo $str;

        if($flag){
        $str = <<<EOM
            <a href="$file" class='btn btn-outline-dark btn-sm fs-xs py-1 px-2 my-2'>すべて表示</a>
        EOM;echo $str;
        }

        $str = <<<EOM
        </div>
        </div>
        EOM;echo $str;
    
    }



//参加しているメンバー一覧からリストを作成
//グループの検索処理が必要
function listup_magazine($data,$parent_id,$filter_group_id){

    $memberResult=array();
    //プロジェクト内の全グループを検索して配列化
    $groupArray=getDB("post","*","WHERE type='group' and parent_id=$parent_id");
    $groupList=array();
    foreach($groupArray as $group){
        $group_id=$group["id"];
        $group_name=$group["title"];
        $groupList[$group_id]=$group_name;
    }
    //shuffle($groupList);
    //ポスト内に登録されている参加メンバーを配列化
    if (!empty($data["member"])){
        $memberArray=json_decode($data["member"], true);
    }else{
        $memberArray=array();
    }
   shuffle($memberArray);

   $group_name="";

    // メンバーを全て処理
    foreach($memberArray as $member){
        
        $member_id=$member["id"];
        //参加メンバーの情報取得
        $owner=getDBRow("user","*","WHERE id=$member_id");
        if(!empty($owner)){
            $owner_id=$owner["id"];
            $owner_name=$owner["name"];
            $owner_mail=$owner["mail"];
            $owner_thumb_url=$owner["thumb_url"];
            $json=$owner["joined"];
        
            
            //グループのラベルの生成
            $owner_join_array=json_decode($owner["joined"],true);
            //参加メンバーの参加情報とグループのマッチングを調査
            $groupLabel=detectJoin($groupList,$owner_join_array);
            $displayFlag=true;
            $joinedCount=0;

            if(!empty($filter_group_id)){
                $displayFlag=false;
                if(!empty($owner_join_array)){
                    foreach($owner_join_array as $join){
                        if($filter_group_id==$join["id"]){
                            $displayFlag=true;
                            $joinedCount++;
                        }
                    }
                }
            }

            //echo($joinedCount);
            if($joinedCount>0){
                $owner_obj=array("id"=>$owner_id,"name"=>$owner_name,"mail"=>$owner_mail);
                array_push($memberResult,$owner_obj);
            }


            if(empty($filter_group_id)){
                $displayFlag=true;
            }

            $displayFlag=true;

            if(!empty($filter_group_id)){
                //参加者がこの「グループ内」で投稿した総数をカウントする方式
                $owner_post_count=countDB("post","*","WHERE parent_id=$filter_group_id and owner_id='$owner_id' and type='post' ");
                $last_post=getDBRow("post","*","WHERE parent_id=$filter_group_id and owner_id='$owner_id' and type='post' ORDER BY create_date DESC LIMIT 1");
            }else{
                $g=make_allgroup_filter($parent_id);
                $group_filter="parent_id ".$g." and ";
                $owner_post_count=countDB("post","*","WHERE $group_filter owner_id='$owner_id' and type='post'");
                $last_post=getDBRow("post","*","WHERE $group_filter owner_id='$owner_id' and type='post' ORDER BY create_date DESC LIMIT 1");
            }

            //投稿が見つかった場合
            if(!empty($last_post)){
                $last_post_id=$last_post["id"];
                $last_contents=json_decode($last_post["contents"],true);

                if($last_post["type_sub"]=="grad-text"){
                    $w=$last_contents["select-wip-grad"];
                    $wip_array=array("none"=>"未着手","10"=>"10%","20"=>"20%","30"=>"30%","40"=>"40%","50"=>"50%","60"=>"60%","70"=>"70%","80"=>"80%","90"=>"90%","100"=>"100%");
                    $wip=$wip_array[$w];
                }else{
                    $wip="";
                }

                // $json
                if($displayFlag){
                    if(empty($filter_group_id)){
                        $link_group_id=$last_post["parent_id"];
                    }else{
                        $link_group_id=$filter_group_id;
                    }
                    $data["title"]=$last_post["title"];
                    $data["text"]=$last_contents["text"];
                    $data["update_date"]=$last_post["update_date"];
                    $data["img_url"]=get_thumbURL($last_post);
                    $data["link_url"]="stream.php?id=$last_post_id&group=$link_group_id&root=$parent_id";
                    $data["owner_name"]=$owner_name;
                    $data["owner_id"]=$last_post["owner_id"];
                    $data["comment_count"]=$last_post["comment_count"];
                    $data["member_count"]=$owner_post_count;
                    $data["join_group"]=$groupLabel;
                    $data["teams"]=$memberResult;
                    $data["file_json"]=json_decode($last_post["file_json"],true);
                    make_magazine_item($data);
                }
            }
        }
    }


    return $memberResult;
}



//カード単体の描画
function make_magazine_item($data){
    $text=$data["text"];
    // $date=$data["date"];
    $year=date("Y",strtotime($data["update_date"]));
    $date=date("n.j",strtotime($data["update_date"]));
    $time=date("H:i",strtotime($data["update_date"]));

    $joinGroup=$data["join_group"];
    $imgURL=$data["img_url"];
    $link_url=$data["link_url"];
    $post_title=$data["title"];
    $owner_name=$data["owner_name"];
    $member_count=$data["member_count"];
    $join_group=$data["join_group"];
    $comment_count=$data["comment_count"];
    if(!empty($post_title)){
    $str = <<<EOM
    <div class="masonry-grid-item " data-groups='["$joinGroup"]'>
    <article class="card card-hover">
    EOM; echo $str;

    if(!empty($imgURL)){
    $str = <<<EOM
    <a class="card-img-top" href="$link_url" style="height:auto;max-height:10em">
    <img src="$imgURL" alt="Post thumbnail" style="object-fit: cover;">
    </a>
    <div class="position-absolute badge rounded-1 bg-dark p-2 border border-white" style="top:-4px;left:-4px;z-index:100;color:black;">
    <div class='fs-3 '>$member_count</div>
    <div class='' style='font-size:0.5em;'>$year</div>
    <div class='' style='font-size:0.5em;'>$date</div>
    </div>

    EOM; echo $str;
    }

    $str = <<<EOM
    <div class="card-body pt-3">
    <div class="nav-heading fs-6"><a href="$link_url">$post_title</a></div>
    <div class="fs-xs fw-normal mb-2">$time $join_group</div>
    <div class="text-nowrap fs-ms">
    EOM; echo $str;

    $owner_data=make_owner($data["owner_id"]);
    if($comment_count>0){
        echo "<div class='fs-xs position-absolute badge rounded-pill bg-danger border border-white' style='bottom:5px;right:5px;z-index:100;'>";
        echo "$comment_count</div>";
    }


    echo"</div>";
    
    echo"<div class='fs-xs mt-3 fw-normal'>$text</div>";
    if(!empty($data["file_json"]))echo"<div class='fw-normal fs-xs mt-2 text-info fw-bold'><i class='ai-file-plus fs-5 me-1'></i>添付あり</div>";

    echo"</div></article></div>";

    }
}

//ストリームの作成

function make_stream($root_id,$group_id,$owner_data,$user_data){

    $owner_id=$owner_data["id"];
    $count=countDB("post","*","WHERE owner_id=$owner_id and parent_id=$group_id ORDER BY update_date DESC");
    $postArray=getDB("post","*","WHERE owner_id=$owner_id and parent_id=$group_id ORDER BY update_date DESC");
    foreach($postArray as $post){
        make_stream_item($post,$count,$root_id,$group_id,$owner_data,$user_data);
        $count--;
    }
}


function make_stream_item($post,$count,$root_id,$group_id,$owner_data,$user_data){
    $id=$post["id"];
    $title=$post["title"];
    $date=date("Y.n.j",strtotime($post["update_date"]));
    $time=date("H:i",strtotime($post["update_date"]));
    $owner_id=$post["owner_id"];
    $type_sub=$post["type_sub"];
    $type=$post["type"];
    $media_url=get_thumbURL($post);

    if(!empty($post["file_json"])){
        $file_json=json_decode($post["file_json"],true);
      }else{
        $file_json=NULL;
      }
    
       $editFlag=isEditable($post["editable"],$owner_data,$user_data);
       $url_param="?mode=edit&id=$id&parent=$group_id&root=$root_id";

       $link="post.php?id=$id&type=post";
       $edit_link="editor.php$url_param";
   

       if($editFlag){
           $edit_html="<a href=$edit_link class='btn btn-sm btn-outline-success' roll='button'>編集</a>";
       }else{
            $edit_html="";
       }
    
    $str = <<<EOM
    <div class="row bg-light rounded-3 p-3 p-lg-4 mb-5">
    <div class="gallery col-md m-0 p-0 mb-3">
    <a href=$link><img class="rounded-1 m-0 p-0" src=$media_url class="img-fluid"></a>

    EOM; echo $str;

    if(!empty($file_json)){
        echo "<div class='mt-4 mb-2 badge rounded-pill' style='background-color:rgb(170,170,170);'>添付ファイル</div>";
        echo "<ul class='list-group  mb-5'>";
        listup_file($id,$file_json,false);
        echo "</ul>";
    }
    $str = <<<EOM

    </div>
    <div class="col m-0 px-1 px-lg-4">
    

    <div class="d-flex align-items-center mb-3">
    <div class='fs-1 m-0 p-0 me-2'>$count</div>
    <div class='me-4'><div class="fs-xs fw-bold lh-1">$date</div><div class="fs-xs fw-bold lh-1">$time</div></div>
    <div class='me-1 ms-auto'>
    EOM; echo $str;

    $owner_data=make_owner($post["owner_id"]);


    $str = <<<EOM
    </div>
    </div>

    <h3 class="mb-3"><a href=$link>$title</a></h3>
    EOM; echo $str;


    $str = <<<EOM
    <div id="sub-form" class="mt-3">
    EOM; echo $str;
    makeLayout_subForm($type,$post,$owner_id,$type_sub);

    echo "<div class='fs-xs mb-5'>$edit_html</div>";

    $commentCount=countDB("post","*","WHERE type='comment' and parent_id=$id ORDER BY update_date DESC");
    $commentArray=getDB("post","*","WHERE type='comment' and parent_id=$id ORDER BY update_date DESC");

    // echo "<div class='border border-1 border-sedondary  p-4 rounded-2 fs-sm '>";
    if($commentCount>0){
        echo "<div class='badge rounded-pill mt-3 mb-2 me-1' style='background-color:rgb(170,170,170);'>コメント</div>";
        echo "<div class='badge rounded-pill bg-danger border border-white '>$commentCount</div>";

    echo "<div class='mb-3 fs-sm border rounded-2 p-2 p-lg-4'>";
      foreach($commentArray as $comment){
          if(!empty($comment)){
            $comment_contents=json_decode($comment["contents"],true);
            $comment_owner=json_decode($comment["owner_name"],true);
            $comment_owner_id=json_decode($comment["owner_id"],true);
          }

          echo "<div class='mb-3 fs-sm'>";
          echo "<i class='ai-message-circle me-1 lh-base'></i>";
          echo $comment_contents["text"]."<span class='fs-sm ms-2'>";
          echo "<a href='user.php?id=$comment_owner_id'><i class='ai-user me-1'></i>".$comment_owner["name"]."</a></span></div>";
        }
        echo "</div>";
    }
        echo "<a href=$link class='btn btn-sm btn-success fs-xs py-1 px-2' roll='button'><i class='ai-plus-circle me-1'></i>コメントする</a>";
        // echo "</div>";

    echo "</div></div></div>";
}

function listUpTeacher($filter,$lab,$userID,$postID){
    $DB=openDB();
    $sql="SELECT * FROM post $filter";
      $getData = $DB->query($sql);
      $data=array();
      foreach ($getData as $user) {
        $title=$user["title"];
        $id=$user["id"];
        $active="";
        if($id==$postID)$active="teacher-list-active";
      
        $str = <<<EOM
        <div class='d-flex align-item-center justify-content-between fw-bold fs-xs  py-1 ps-2 pe-2 border-0 rounded-2 $active'>
        <a class="flex-fill" style="text-decoration:none;" href="teacher.php?lab=$lab&user=$userID&post=$id" >$title</a>
        </div>
      EOM; echo $str;
      }
      closeDB($DB);
  
    }
  
?>


