<?php

function makeLayout_subForm($type,$post_data,$user_id,$type_sub){
    $subFormArray=initSubForm();    
    $form=$subFormArray[$type_sub]["form"];
    $contents=json_decode($post_data["contents"],true);
    $post_type=$post_data["type"];
    foreach($form as $form_item){
        $id=$form_item["id"];
        $type=$form_item["type"];
        $public=$form_item["public"];

        if(!empty($contents[$id])){
            $data=$contents[$id];

            if($public=="open"){
                switch($type){
                    case 'text':
                        layout_textarea($form_item,$data,$post_type);
                        break;
                    case 'textarea':
                        layout_textarea($form_item,$data,$post_type);
                        break;
                    case 'youtube':
                        layout_youtube($form_item,$data);
                        break;
                    case 'select':
                        layout_select($form_item,$data);
                        break;
                    }
                }
            }
    }

}


function layout_textarea($form_item,$data,$type){

    if(!empty($form_item["label-layout"])){
        $label=$form_item["label-layout"];
    }else{
        $label=$form_item["label"];
    }
    if($form_item["id"]=="tag"){
        $file="$type.php";
        echo "<div class='mb-4 badge rounded-pill' style='background-color:rgb(170,170,170);'>$label</div>";
        $data = preg_replace('/[#＃]([\w\x{05be}\x{05f3}\x{05f4}]*[\p{L}_\x{30FB}]+[\w\x{05be}\x{05f3}\x{05f4}]*)/u', 
        "<a href='$file?mode=search&text=$1' >#$1</a>", $data);
        echo "<div class='mb-2 fs-xs'>$data</div>";
    }
    else{
        $data=auto_url($data);
        echo "<div class='mb-4 badge rounded-pill' style='background-color:rgb(170,170,170);'>$label</div>";
        echo "<div class='mb-4' style='word-break: break-all;'>$data</div>";
    }
}

function layout_select($form_item,$data){
    
    if(!empty($form_item["label-layout"])){
        $label=$form_item["label-layout"];
    }else{
        $label=$form_item["label"];
    }

    echo "<div>";
    echo "<div class='mb-2 badge rounded-pill' style='background-color:rgb(170,170,170);'>$label</div>";
    echo "</div>";

    if($data=="none")$progress=0;
    else $progress=$data;

    if($form_item["id"]=="select-wip-grad"){
        $str = <<<EOM
        <div class="progress mb-4">
        <div class="progress-bar fw-medium" role="progressbar" style="width: $progress%" aria-valuenow="$progress" aria-valuemin="0" aria-valuemax="100">
        $progress%
        </div></div>
        EOM; echo $str;
    }else{
        $result="";
        foreach($form_item["option"] as $option){
            if($data==$option["value"]){
                $result=$option["label"];
            }
        }
        echo "<div class='mb-4'>$result</div>";
    }
}


function layout_youtube($form_item,$data){
    if(strpos($data,'youtube.com') !== false){
        $id=getURLParam_fromString($data,"v");
        $str = <<<EOM
        <div class="youtube ratio ratio-16x9">
        <iframe src="https://www.youtube.com/embed/$id"></iframe>
        </div>
        EOM; echo $str;
    }

    if(strpos($data,'youtu.be') !== false){
        $tmp = explode("/",$data);
        $id=$tmp[3];
        $str = <<<EOM
        <div class="youtube ratio ratio-16x9">
        <iframe src="https://www.youtube.com/embed/$id"></iframe>
        </div>
        EOM; echo $str;
    }

    if(strpos($data,'vimeo.com') !== false){
        $tmp = explode("/",$data);
        $id=$tmp[3];
        $str = <<<EOM
        <div class="vimeo ratio ratio-16x9">
        <iframe src="https://player.vimeo.com/video/$id" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
        </div>
        EOM; echo $str;
    }
}


function listup_file($post_id,$file_json,$editorFlag){
    if(!empty($file_json)){
        // echo "<ul id='file-list' class='list-group'>";
        foreach($file_json as $file){
          $file_id=$file["id"];
          $fileData=getDBRow("media","*","WHERE id=$file_id");
          $file_title=$fileData["title"];
          $file_type=$fileData["type"];
          $file_url=$fileData["url"];
          echo "<li id='file-$file_id' class='list-group-item '>";

          echo "<a class='me-2 fw-bold fs-sm' href=$file_url>";
          echo "<i class='ai-file me-1'></i>";
          echo $file_title;
          echo "</a>";
          
          echo "<button type='button' class='btn btn-info btn-sm file-download p-0 px-2 ms-2' name=$file_title href=$file_url>";
          echo "<i class='ai-download text-light fs-xs'></i></button>";


          if($editorFlag){
            echo "<button type='button' post_id=$post_id file_id=$file_id class='file-delete btn btn-outline-danger btn-sm py-1 px-2' ";
            echo "data-bs-toggle='modal' data-bs-target='#modal-delete-file'>";
            echo "削除</button>";
          }
          echo "</li>";
        }
      } 
}




function layout_file($post_id,$file_json,$editorFlag){
    if(!empty($file_json)){
        // echo "<ul id='file-list' class='list-group'>";
        foreach($file_json as $file){
          $file_id=$file["id"];
          $fileData=getDBRow("media","*","WHERE id=$file_id");
          $file_title=$fileData["title"];
          $file_type=$fileData["type"];
          $file_url=$fileData["url"];
         // echo "<li id='file-$file_id' class='list-group-item '>";
          
          switch($file_type){
            case 'image/jpeg':
                echo "<div class='gallery mb-5'>";
                echo "<a href=$file_url class='gallery-item rounded-3'>";
                echo "<img src=$file_url alt='Gallery thumbnail'>";
                echo "</a></div>";
                break;
            case 'image/png':
                echo "<div class='gallery mb-5'>";
                echo "<a href=$file_url class='gallery-item rounded-3'>";
                echo "<img src=$file_url alt='Gallery thumbnail'>";
                echo "</a></div>";
                break;
            case 'video/mp4':
                echo "<div class='mb-5'>";
                echo "<video src='$file_url' width='100%' controls preload></video>";
                echo "</div>";
            break;
            default:
                echo "<div class='text-start fw-bold fs-sm mb-2 px-2 border rounded-2 p-2'>";
                echo "<i class='ai-file me-1'></i>";
                echo  "<a class='' href='$file_url' >$file_title</a>";
                echo "<button type='button' class='btn btn-info btn-sm file-download p-0 px-2 ms-2' name=$file_title href=$file_url>";
                echo "<i class='ai-download text-light fs-xs'></i></button>";
                echo "</div>";

            }
          
          if($editorFlag){
            echo "<button type='button' post_id=$post_id file_id=$file_id class='file-delete btn btn-outline-danger btn-sm py-1 px-2' ";
            echo "data-bs-toggle='modal' data-bs-target='#modal-delete-file'>";
            echo "削除</button>";
          }
        //  echo "</li>";
        }
        // echo "</ul>";
      } 
}

?>
       