<?php

function initForm(){
    $json = file_get_contents("data/json/form.json");
    $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $formArray = json_decode($json,true);
    return $formArray;
}

function initSubForm(){
    $json = file_get_contents("data/json/sub_form.json");
    $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $formArray = json_decode($json,true);
    return $formArray;
}


//フォームの作成
function makeForm($type,$data,$user,$type_sub){
    $formArray=initForm();    
    $subFormArray=initSubForm();   
    if($type!="comment"){ 
        $type_sub_label=$subFormArray[$type_sub]["info"]["label"];
        echo $type_sub_label;
    }
    //  echo "type_sub:".$type;

    //共通のデータ
    form_hidden("form-type",$formArray[$type]["info"]["post-type"]);
    form_hidden("form-owner_id",$user["id"]);
    form_hidden("form-owner_name",$user["name"]);
    form_hidden("form-owner_roll",$user["roll"]);

    //書式の保存
    form_hidden("type_sub",$type_sub);
    $form=$formArray[$type]["form"];
    foreach($form as $item){
        $type=$item["type"];
        $lock=formSecureCheck($item);
        if($type != "password")echo("<div class='my-4'>");

        if(!$lock){
        switch($type){
            case 'select-year':
                form_select_year($item,$data,"form");
                break;
            case 'text':
                if($item["id"]=="number"){
                    if(getUserRollLevel($data)==0)form_text($item,$data,"form");
                }else{
                    form_text($item,$data,"form");
                }
                break;
            case 'textarea':
                form_textarea($item,$data,"form");
                break;
            case 'file_image':
                form_file_image($item,$data,"form");
                break;
            case 'file':
                form_file($item,$data,"form");
                break;
            case 'password':
                //form_password($item,$data);
                break;
            case 'sub-form':
                sub_form($item,$data,$type_sub);
                break;
            case 'select':
                form_select($item,$data,"form");
                break;
            case 'notice':
                form_notice($item,$data,"form");
                break;
        
            }
        }
           if($type!="password")echo("</div>");
    }
}

//投稿のコンテンツのフォーマット
function sub_form($item,$data,$type_sub){

    //既存データがあれば読み込む
    if(!empty($data["contents"])){
        $subData=json_decode($data["contents"],true);
    }else{
        $subData=array();
    }

    //書式JSONの取得
    $subFormArray=initSubForm();    
    $form=$subFormArray[$type_sub]["form"];

    foreach($form as $item){
        $type=$item["type"];

        $lock=formSecureCheck($item);

        if(!$lock){
            echo "<div class='my-4'>";
        switch($type){
        case 'text':
            form_text($item,$subData,"sub");
            break;
        case 'textarea':
            form_textarea($item,$subData,"sub");
            break;
        case 'select-grad':
            form_select_grad($item,$data,"sub");
            break;
        case 'select-submit-type':
        //    form_select_submit_type($item,$data,"sub");
            break;
        case 'select':
            form_select($item,$data,"sub");
            break;
        case 'youtube':
            form_youtube($item,$data,"sub");
            break;
        case 'range-date':
            form_range_date($item,$data,"sub");
            break;
         case 'notice':
            form_notice($item,$data,"sub");
            break;
         }
         echo "</div>";
     }
    }

}


function form_text($item,$data,$prefix){
    $id=$prefix."-".$item["id"];

    $form_label=getFormLabel($item);
    $label=$form_label["label"];
    $require=$form_label["require"];

    $value="";
    if(!empty($data)){
        $value="value='".$data[$item["id"]]."'";
    }
$str = <<<EOM
<div class="form-floating mb-3">
<input name="$id" id="$id"  class="form-control" type="text" id="fl-text" $value $require>
<label for="fl-text">$label</label>
</div>
EOM;
echo $str;

}


function form_youtube($item,$data,$prefix){
    $id=$prefix."-".$item["id"];
    $label=$item["label"];
    $value="";


    if(!empty($data)){
        $contents=json_decode($data["contents"],true);
        $value=$contents[$item["id"]];
    }

$str = <<<EOM
<div class=" align-items-center mb-3">
    <div class="form-floating mb-2">
        <input name="$id" id="$id"  class="form-control youtube-thumb-url" type="text" id="fl-text" value="$value">
        <label for="fl-text">$label</label>
    </div>
    <div class="btn btn-outline-secondary btn-sm youtube-thumb-get" roll="button">動画から画像取得</div>
</div>
EOM;
echo $str;

}




function form_password($item,$data,$prefix){
    $id=$prefix."-".$item["id"];
    $label=$item["label"];
    $value="";
    if(!empty($data)){
        $value=$data[$item["id"]];
    }
    // $str = <<<EOM
    // <label for="$id" class="form-label">$label</label>
    // <input name="$id" id="$id" type="password" class="form-control" placeholder="" value="" required>
    // EOM;
    // echo $str;

    $str = <<<EOM
    <div class="form-floating mb-3">
    <input name="$id" id="$id"  class="form-control" type="password" id="fl-text" value="$value">
    <label for="fl-text">$label</label>
    </div>
    EOM;
    echo $str;
}


function form_textarea($item,$data,$prefix){
    $id=$prefix."-".$item["id"];
    $form_label=getFormLabel($item);
    $label=$form_label["label"];
    $require=$form_label["require"];


    // $label=$item["label"];
    $value="";
    if(!empty($data)){
        $value=$data[$item["id"]];
    }

    $len=mb_strlen($value);
    if($len==0)$row=4;
    else $row=$len/20;
    if($row<4)$row=4;


    $str = <<<EOM
    <label for="$id" class="form-label">$label</label>
    <textarea name="$id" id="$id" class="form-control fs-sm" rows="$row" $require>$value</textarea>
    EOM;
    echo $str;
  

}

function form_file_image($item,$data,$prefix){
    $id=$prefix."-".$item["id"];
    $label=$item["label"];

    $media_url="";
    //新規作成の場合
    if(empty($data)){
        $folder=array("design","color","tool","shape","light");
        shuffle($folder);
        $preset_type=$folder[0];
        $preset_number=rand(1,20);
        $preset_name=$preset_type."_".$preset_number.".jpg";
        $preset_url="$preset_type/$preset_name";
        $media_url="preset/1080p/$preset_type/$preset_name";
        $changedFlag="true";
        $preset_checked="checked";
        $upload_checked="";
        $preset_visible="display: block;";
        $upload_visible="display: none;";

    }else{
        //アップロードメディアがある場合
        if(!empty($data["media_id"])){
          $media_id=$data["media_id"];
          $mediaData=getDBRow("media","*","WHERE id=$media_id");

          //thumb_urlいらないのでは？
        //   $preset_url=$data["thumb_url"];
          $preset_url="";
          $preset_name="";
          $media_url=$mediaData["url"];
          $changedFlag="false";
          $preset_checked="";
          $upload_checked="checked";
          $preset_visible="display: none;";
          $upload_visible="display: block;";
        }else{
        //アップロードメディアがない場合
        //プリセット画像が添付されている場合
            $changedFlag="false";
            $preset_checked="checked";
            $upload_checked="";        
            $preset_visible="display: block;";
            $upload_visible="display: none;";
  
            if(!empty($data["thumb_url"])){
                $preset_url=$data["thumb_url"];
                $preset_name="";
                // $media_url="preset/1080p/".$preset_url;
            }else{
            //プリセット画像が添付されていない場合
                $preset_url="";
                $preset_name="";
                // $media_url="";
            }
            $media_url=get_thumbURL($data);
        }
    }

    $str = <<<EOM
    <div class="d-flex mb-4 border rounded-2 p-3">
    <div class="col-9">
    <label for="$id" class="form-label">$label</label>
    <div class="px-4">

        <div class="form-check">
            <input class="form-check-input select-image-preset" type="radio" id="radio-image-preset" name="radio-image" value="preset" $preset_checked>
            <label class="form-check-label select-image-preset" for="radio-image-preset">プリセットから選ぶ</label>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input select-image-upload" type="radio" id="radio-image-upload" name="radio-image" value="upload" $upload_checked>
            <label class="form-check-label select-image-upload" for="radio-image-upload">アップロードする</label>
        </div>

        <div class="tab-group">
            <div class="tab-upload" style="$upload_visible">
                <input name="$id" id="$id" class="form-control form-file-image" changed="$changedFlag" type="file" accept="image/*">
            </div>
            <div class="tab-select" style="$preset_visible">
                <div class="preset-selector btn btn-outline-secondary ">プリセット画像を選択</div>
                <span class="preset-name ms-2 fs-ms">$preset_name</span>
                <input type="hidden" id="preset-name" name="preset-name" value="$preset_url">
            </div>
        </div>

    </div>

    </div>

    <div class='col-3'>
        <img src=$media_url class='img-fluid img-thumbnail'>
    </div> 

    </div>
    EOM; echo $str;

   // if(!empty($media_url)){
        $str = <<<EOM
            </div><div class='col mb-4'><img src=$media_url class='img-fluid img-thumbnail'>
            EOM;
       // echo $str;
  //  }

}


function form_file($item,$data,$prefix){
    $id=$prefix."-".$item["id"];
    $label=$item["label"];

    $str = <<<EOM
    <label for="$id" class="form-label">$label</label>
    <div class="row mb-3"><div class="col">
    <ul id="filelist" class="list-group">

    EOM;
    echo $str;

    //データが入っていた場合
    $media_url="";

    if(!empty($data["file_json"])){
        $file_json=json_decode($data["file_json"],true);

        if(!empty($file_json)){
            //  echo '<ul id="filelist" class="list-group mb-3">';
            listup_file($data["id"],$file_json,true);
            //  echo '</ul>';
        }
    }

    $str = <<<EOM
    <li class="list-group-item"><input name="form-file[]" class="fileinput form-control fs-sm" type="file" accept="*"></li>
    </ul>
    </div></div>
    EOM;
    echo $str;

 
}


function form_hidden($name,$val){
 //   $type=$info["post-type"];
    $str = <<<EOM
    <input name="$name" id="$name" type="hidden" class="form-control" value="$val">
    EOM;
    echo $str;

}

function form_select($item,$data,$prefix){
    $selected="";
    if(!empty($data)){
        if($prefix=="sub"){
            $contents=json_decode($data["contents"],true);
            if(!empty($contents[$item["id"]])){
                $selected=$contents[$item["id"]];
            }
        }

        if($prefix=="form"){
            $selected=$data[$item["id"]];
        }
    }

    $selectArray=$item["option"];
    $id=$prefix."-".$item["id"];

    $form_label=getFormLabel($item);
    $label=$form_label["label"];
    $require=$form_label["require"];


    // $label=$item["label"];

    $str = <<<EOM
    <div class="mb-4">
    <label for="$id" class="form-label">$label</label>
    <select name="$id" id="$id" class="form-select form-control" $require aria-label="Default select example">
    EOM;
    echo $str;

    foreach ($selectArray as $selectItem) {
        $select="";
        $label=$selectItem["label"];
        $value=$selectItem["value"];

        if($value==$selected)$select="selected";
        else $select="";

        echo "<option value=$value $select>$label</option>";
    }      
    
    echo "</select></div>";

}



function form_select_year($item,$data,$prefix){
    $selected_year=date('Y');
    if(!empty($data)){
        $selected_year=$data[$item["id"]];
    }else{
        $selected_year=getURLParam("year");
    }

    $id=$prefix."-".$item["id"];
    $label=$item["label"];

    $str = <<<EOM
    <label for="$id" class="form-label">$label</label>
    <select name="$id" id="$id" class="form-select" aria-label="Default select example">
    EOM;
    echo $str;


    for($i = 0; $i < 5; $i++){
      $y=$i+date('Y')-1;
      $select="";
      if(strval($y)==$selected_year)$select="selected";
     echo "<option value='".$y."' ".$select.">".$y."年度</option>";
   }
   echo"</select>";
}


//卒業制作の統計表示をする授業のセレクト
function form_select_grad($item,$data,$prefix){
    //var_dump($data["year"]);
    if(!empty($data)){
        $contents=json_decode($data["contents"],true);
        $selected=$contents["grad"];
    }else{
        $selected="";
    }

    $id=$prefix."-".$item["id"];
    $label=$item["label"];

    $str = <<<EOM
    <label for="$id" class="form-label">$label</label>
    <select name="$id" id="$id" class="form-select" aria-label="Default select example">
    EOM;
    echo $str;

    $year=$data["year"];

    $DB=openDB();
    $sql="SELECT * FROM post WHERE year='$year' and type='project'";
    $getData = $DB->query($sql);
    foreach ($getData as $post) {
        $title=$post["title"];
        $id=$post["id"];
        if($id==$selected)$select="selected";
        else $select="";
        echo "<option value=$id $select>$title</option>";
    }

    echo "</select>";


}


// function form_select_submit_type($item,$data,$prefix){
//     $rollArray=array("post-text"=>"受講レポート","grad-text"=>"卒制・就活レポート");
//     if(!empty($data)){
//         $contents=json_decode($data["contents"],true);
//         $selected=$contents["submit-type"];
//     }else{
//         $selected="";
//     }

//     $id=$prefix."-".$item["id"];
//     $label=$item["label"];

//     $str = <<<EOM
//     <div class="mb-4">
//     <label for="$id" class="form-label">$label</label>
//     <select name="$id" id="$id" class="form-select" aria-label="Default select example">
//     EOM;
//     echo $str;

//     foreach ($rollArray as $key => $value) {
//         if($key==$selected)$select="selected";
//         else $select="";
//         echo "<option value=$key $select>$value</option>";
//     }     
//     echo "</select></div>";
// }



function form_range_date($item,$data,$prefix){
    $id=$prefix."-".$item["id"];

    $form_label=getFormLabel($item);
    $label=$form_label["label"];
    $require=$form_label["require"];

    $value="";
    $value_start="";
    $value_end="";
    if(!empty($data)){
        $contents=json_decode($data["contents"],true);
        $range=$contents[$item["id"]];
        $date=explode(",", $range);

        $value_start="value='".$date[0]."'";
        $value_end="value='".$date[1]."'";

    }
    $str = <<<EOM
    <div class="form-label me-2">$label</div>

    <div class="d-flex align-items-center">
    <div class="form-floating mb-3">
    <input name="$id-start" id="$id-start"  class="form-control date-picker" type="text" id="fl-text" $value_start $require>
    <label for="fl-text">開始</label>
    </div>
    <div class=" fs-sm mx-1">から</div>
    <div class="form-floating mb-3">
    <input name="$id-end" id="$id-end"  class="form-control date-picker" type="text" id="fl-text" $value_end $require>
    <label for="fl-text">終了</label>
    </div>
    </div>

    EOM; echo $str;

}

function form_notice($item,$data,$prefix){
    $label=$item["label"];
    $label=auto_url($label);

    $str = <<<EOM
    <div class="form-label  mt-5 mb-2 fw-normal fs-xs">
    $label
    </div>
    EOM; echo $str;

}

?>
