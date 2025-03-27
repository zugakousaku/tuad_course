<?php
header("Content-type: text/plain; charset=UTF-8");
require "config.php";

require "core.php";
require "database.php";
require "form.php";

if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
  ) {
    //$_POST;
    $result="";
    $mode = getURLParam("mode");
    if($mode=="create"){
      //投稿が作られた場合。コメントも含まれる。
      //投稿タイプは関数内でdomから取得している
      create_project();
    }
    if($mode=="update"){
      update_project($_POST["form-post_id"]);
    }

    if($mode=="delete"){
      delete_project($_POST["form-post_id"]);
    }

    if($mode=="join-project"){
      $post_id = getURLParam("post_id");
      $user_id = getURLParam("user_id");
      join_project($post_id,$user_id);
      add_member($post_id,$user_id);
    }

    if($mode=="exit-project"){
      $post_id = getURLParam("post_id");
      $user_id = getURLParam("user_id");
      exit_project($post_id,$user_id);
      remove_member($post_id,$user_id);

    }

    if($mode=="comment-update"){
      $target = getURLParam("target");
      update_comment($target);
    }
    if($mode=="comment-delete"){
      $target = getURLParam("target");
      delete_comment($target);
    }

    if($mode=="listup-comment"){
      //パラーメーターURLについていない場合がある？
      $parent_id = getURLParam("parent_id");
      $user_id = getURLParam("user_id");
      listup_comment($parent_id,$user_id);
    }

    //ファイルの削除（JSONの更新も忘れずに）
    if($mode=="delete-file"){
      $post_id = getURLParam("post_id");
      $file_id = getURLParam("file_id");
      delete_attach_file($post_id,$file_id);
    }

    if($mode=="user-update"){
      $user_id = getURLParam("user_id");
      update_user($user_id);
    }

    if($mode=="group-info"){
      $group_id = getURLParam("group_id");
      $join_flag = getURLParam("join_flag");
      get_group_info($group_id,$join_flag);
    }

    // if($mode=="password-reset"){
    //   password_reset();

    // }
    // echo json_encode($data);
   // echo $data;
  }

?>