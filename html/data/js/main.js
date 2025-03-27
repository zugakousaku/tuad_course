
$(function() {



$("table").tableExport({
  formats: ["xlsx",  "csv"], //エクスポートする形式
  bootstrap: false, //Bootstrapを利用するかどうか
  exportButtons: true,
  position: "top"
});


$.extend( $.fn.dataTable.defaults, { 
  language: {
      url: "http://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Japanese.json"
  } 
}); 


hidePersonal();

$(document).on("click", ".personal-button", function () {
  $(this).hide();
  showPersonal();
});

$(document).ready( function () {
  $('#member-table').DataTable({
      paging: false
  });
} );


$(document).ready( function () {
  $('.date-picker').datepicker({
    format: 'yyyy.mm.dd',
    language:'ja'
})
});

    

    //メンバー招待ボタンが押された場合
$(document).on("click", "#member-invite", function () {
});

//プリセット画像のダイアログを開く
$(document).on("click", ".preset-selector", function () {
  $("#modal-select-image").modal("show");
});




$(document).on("click", ".youtube-thumb-get", function () {
  var url=$(this).parent().find(".youtube-thumb-url").val();


  if (url.indexOf('youtube.com') !== -1) {
    var id=getURLParam("v",url);
    var img_url="https://img.youtube.com/vi/"+id+"/maxresdefault.jpg";
    setPreset(img_url);
  }

  if (url.indexOf('youtu.be') !== -1) {
    var new_url = url.replace(/\?.*$/,"");
    var tmp=new_url.split("/");
    var id=tmp[3];
    var img_url="https://img.youtube.com/vi/"+id+"/maxresdefault.jpg";
    setPreset(img_url);
  }


  if (url.indexOf('vimeo.com') !== -1) {
    var tmp=url.split("/");
    var id=tmp[3];
    // JSON取得
    $.getJSON(
      'https://vimeo.com/api/v2/video/'+id+'.json',
      null,
      function(data) {
        // thumbnailURL格納
        console.log(data);
        var img_url=data[0]["thumbnail_large"];
        setPreset(img_url);
      }
    );
  }

});

function setPreset(img_url){
  $(".preset-name").attr("name",img_url);
  $(".preset-name").text(img_url);
  $("#preset-name").attr("value",img_url);
  $(".img-thumbnail").attr("src",img_url);

  $(".tab-upload").hide();
  $(".tab-select").show();
  $('input[name=radio-image]').val(['preset']);

}

//プリセット画像をクリック
$(document).on("click", ".preset-image-thumbnail ", function () {
  $("#modal-select-image").modal("hide");
  $(".form-file-select-image").show();
  var name=$(this).attr("name");
  var path=$(this).attr("path");
  $(".preset-name").attr("name",name);
  $(".preset-name").text(name);
  $("#preset-name").attr("value",path);

  //必要なさそう
  $("#preset-name").attr("changed","true");
  
});


    //ファイルのアップロード関連
    $(document).on("click", ".select-image-upload", function () {
      console.log("upload");
      $(this).parent().parent().find(".tab-group").find(".tab-upload").show();
      $(this).parent().parent().find(".tab-group").find(".tab-select").hide();
      $(this).parent().attr("type","upload");

      //console.log($(this).parent().parent().attr("class"));
    });

    $(document).on("click", ".select-image-preset", function () {
      console.log("select");

      $(this).parent().parent().find(".tab-group").find(".tab-upload").hide();
      $(this).parent().parent().find(".tab-group").find(".tab-select").show();
      $(this).parent().attr("type","preset");

      //console.log($(this).parent().parent().attr("class"));

      // $("#modal-select-image").modal("show");
      // $(this).parent().find(".form-file-select-image").show();
    });


    //グループの脇にある(i)をクリックした際に呼ばれる
    $(document).on("click", ".group-info-button", function () {
      var group_id=$(this).attr("group_id");
      var join_flag=$(this).attr("join_flag");
      // var parent_id=$(this).attr("parent_id");
      var group_id=$(this).attr("group_id");
      var root_id=$(this).attr("root_id");

      //join_flag is notwork
      if(join_flag){
        $("#modal-group-info").find("#join-project").hide();
      }else{
        $("#modal-group-info").find("#exit-project").hide();
      }

      $("#modal-group-info").find("#exit-project").attr("post_id",group_id);
      $("#modal-group-info").find("#join-project").attr("post_id",group_id);

      //編集のリンクを変更
      // $("#modal-group-info-edit-button").attr("href","editor.php?mode=edit&id="+root_id+"&group_id="+group_id+"&tab=post");
      $("#modal-group-info-edit-button").attr("href","editor.php?mode=edit&id="+group_id+"&root="+root_id+"&tab=post");
      $.ajax({
        type: "POST",
        url: "ajax.php?mode=group-info&group_id="+group_id+"&join_flag="+join_flag,
        processData: false,contentType: false,cache: false
      }).done(function(data){
        $("#modal-group-info-contents").html(data);
      }).fail(function(XMLHttpRequest, status, e){
        console.log(e);
      });

    });

    
    //コメントの編集ボタンが押された場合、ダイアログにコピー
    $(document).on("click", ".comment-edit", function () {
            var commentDOM=$(this).parent().parent().parent().find(".comment-text");
            var comment_text=$(commentDOM).text();
            $("#comment-form-data").find("#sub-text").val(comment_text);
            
            var comment_id=$(this).attr("comment_id");
            var parent_id=$(this).attr("parent_id");
            var owner_id=$(this).attr("owner_id");

            $("#comment-update").attr("comment_id",comment_id);
            $("#comment-update").attr("targetDOM","#comment-list");
            $("#comment-update").attr("parent",parent_id);
            $("#comment-update").attr("owner_id",owner_id);

            $("#comment-delete").attr("comment_id",comment_id);
            $("#comment-delete").attr("targetDOM","#comment-list");
            $("#comment-delete").attr("parent",parent_id);
            $("#comment-delete").attr("owner_id",owner_id);

            $("#form-post_id").attr("val",comment_id);
    });


    function formCheck(){
      var result=true;
      
      $('.form-control').each(function(index) {
        $(this).removeClass("border-danger border-3");

        if($(this).attr("require")=="true"){
         if($(this).val()==""){
           result=false;
           $(this).addClass("border-danger border-3");
         }
        }
      });

      console.log("formCheck>>>>"+result);
      return result;
    }



    
    //===========================================
    //===========================================
    //プロジェクトや投稿を追加するボタンが押された場合
    //===========================================
    //===========================================
    $(document).on("click", "#form-send", function () {
      var mode=$(this).attr("mode");
      
      //コールバック後のリロードURL（新しい投稿タイプの時に注意）
      var backurl=$(this).attr("backurl");

      if(formCheck()){
        var formData = new FormData($("#form-data").get(0));
        $(this).append('<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true">');
          $.ajax({
            type: "POST",
            url: "ajax.php?mode="+mode,
            data: formData,processData: false, contentType: false,cache: false
          }).done(function(data){
            //Ajax終了後のコールバック
            console.log(data);
            if(mode=="delete"){
              $("#modal-delete").modal("hide");
              if(backurl)window.location.href=backurl;
            }else{
              if(backurl)window.location.href=backurl;
            }
          }).fail(function(XMLHttpRequest, status, e){
            console.log(e);
          });
        }else{
          $("#toast-require").toast("show");
        }



      });

      //ファイルの削除ダイアログ
      $(document).on("click", ".file-delete", function () {
        var file_id=$(this).attr("file_id");
        var post_id=$(this).attr("post_id");
        $("#delete-file").attr("file_id",file_id);
        $("#delete-file").attr("post_id",post_id);
        //console.log(file_id);
      });

    //ファイルの削除＞Jsonの変更も忘れずに
    $(document).on("click", "#delete-file", function () {
      var mode=$(this).attr("mode");
      var file_id=$(this).attr("file_id");
      var post_id=$(this).attr("post_id");


      $.ajax({
        type: "POST",
        url: "ajax.php?mode="+mode+"&post_id="+post_id+"&file_id="+file_id,
        processData: false, contentType: false,cache: false
      }).done(function(data){
        console.log(data);
        $("#modal-delete-file").modal("hide");
        $("#file-"+file_id).remove();

      }).fail(function(XMLHttpRequest, status, e){
        console.log(e);
      }); 

    });

    
       
  //コメントを追加
  $("#comment-create").on("click", function(event){
    formCheck();

    var val=$("#form-data").find("#sub-text").val();
   

    if(val!=""){
      var mode=$(this).attr("mode");
      var targetDOM=$(this).attr("targetDOM");
      var parent=$(this).attr("parent");
      var formData = new FormData($("#form-data").get(0));
      var owner_id=$(this).attr("owner_id");

      $.ajax({
        type: "POST",
        url: "ajax.php?mode="+mode,
        data: formData,processData: false,contentType: false,cache: false
      }).done(function(data){
        listupCommentJS(targetDOM,parent,owner_id);
        clearCommentTextArea();
        // console.log(data);
      }).fail(function(XMLHttpRequest, status, e){
        console.log(e);
      });
    }else{
      $("#toast-require").toast("show");

    }
  });

  //コメントのアップデート
  $("#comment-update").on("click", function(event){
    commentEdit($(this));
   });

  //コメントのアップデート
  $("#comment-delete").on("click", function(event){
    commentEdit($(this)); 
  });
 

  //ユーザーページの参加やめるボタン
$(document).on("click", ".exit-dialog-button", function () {
  $("#modal-group-exit").modal("show");
  var post_id=$(this).attr("post_id");
  $("#exit-project").attr("post_id",post_id);
});



  //参加する
  $(document).on("click", "#join-project", function () {
    joinChangeProject($(this),);
  });
  $(document).on("click", "#exit-project", function () {
    joinChangeProject($(this));
  });

//ダウンロード===============
  $(document).on("click", ".file-download", function () {
    var url=$(this).attr("href");
    var name=$(this).attr("name");
    fileDownloadFromUrl(name,url);
  });


  //参加する際のAjax処理
  function joinChangeProject(dom){
    var mode=dom.attr("mode");
    var user_id=dom.attr("user_id");
    var post_id=dom.attr("post_id");
    $.ajax({
      type: "POST",
      url: "ajax.php?mode="+mode+"&post_id="+post_id+"&user_id="+user_id,
      processData: false, contentType: false,cache: false
    }).done(function(data){
     location.reload();
      //console.log(data);
      // $("#modal-group-info").modal("hide");

      // if(mode=="join-project"){
      //   $("#modal-join").modal("hide");
      //   $("#join-button-group").attr("join","true");
      //   adjustJoinButton();
      // }
      // if(mode=="exit-project"){
      //   $("#modal-exit").modal("hide");
      //   $("#join-button-group").attr("join","false");
      //   adjustJoinButton();
      // }
    }).fail(function(XMLHttpRequest, status, e){
      console.log(e);
    });
  }

  //ファイル添付のフォームを監視スタート
  $("document").ready(function(){
    $(".fileinput").change(fileInputChange);
    $("#form-image").change(mainImageFormChanged);
    
  });

  $(document).on('change', '#sub-select-job', function(){
    var val=$(this).val();
    if(val=="job-work"){
      $(this).parent().next().show();
    }else{
      $(this).parent().next().hide();
    }
  });

//必要なさそう
  function mainImageFormChanged(){
    $("#form-image").attr("changed","true");
  }
  
  //ファイルが添付されたら、新たなファイルフォームを追加
  function fileInputChange(){
    if($(".fileinput").last().val() != ""){
        $("#filelist").append('<li class="list-group-item"><input name="form-file[]" class="fileinput form-control fs-sm" type="file" accept="*"></li>').bind('change', fileInputChange);
    }
}



 //ユーザー情報を更新するボタン
 $(document).on("click", "#user-update", function () {
  var mode=$(this).attr("mode");
  var user_id=$(this).attr("user_id");
  var backurl=$(this).attr("backurl");
  if(formCheck()){
    var formData = new FormData($("#form-data").get(0));
    $.ajax({
      type: "POST",
      url: "ajax.php?mode="+mode+"&user_id="+user_id,
      data: formData,processData: false, contentType: false,cache: false
    }).done(function(data){
      //console.log("user-update");
     // console.log(data);
       if(backurl)window.location.href=backurl;
       else history.back();
    }).fail(function(XMLHttpRequest, status, e){
      console.log(e);
    });
  }
});


$('#lab-select').on('change', function () {
  var url=$(this).val();
  if (url) { // require a URL
    window.location = url; // redirect
}
});





});    



function hidePersonal(){
  $('.personal-lock').each(function(index) {
    $(this).css("opacity","0");
  });  
}

function showPersonal(){
  $('.personal-lock').each(function(index) {
    // $(this).show();
    $(this).css("opacity","1");

  });  
}






function clearCommentTextArea(){
  $("#form-data").find("#sub-text").val("");
}

function adjustJoinButton(target){
  // var joinedFlag=$("#join-button-group").attr("join");
  var joinedFlag=$(target).attr("join");
  var joinButton=$(target).find(".button-join-start");
  var exitButton=$(target).find(".button-exit-start");
  
  if(joinedFlag=="false"){
    joinButton.hide();
    exitButton.show();
  }else{
    joinButton.show();
    exitButton.hide();
  }

}

function commentEdit(dom){
  var mode=dom.attr("mode");
  var comment_id=dom.attr("comment_id");
  var targetDOM=dom.attr("targetDOM");
  var parent=dom.attr("parent");
  
  var formData = new FormData($("#comment-form-data").get(0));
  var owner_id=dom.attr("owner_id");

  $.ajax({
    type: "POST",
    url: "ajax.php?mode="+mode+"&target="+comment_id,
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
  }).done(function(data){
    console.log(data);
    $("#modal-comment").modal("hide");
    listupCommentJS(targetDOM,parent,owner_id);
    clearCommentTextArea();
  }).fail(function(XMLHttpRequest, status, e){
    console.log(e);
  });
 }

function getURLParam(name, url) {
      if (!url) url = window.location.href;
      name = name.replace(/[\[\]]/g, "\\$&");
      var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
          results = regex.exec(url);
      if (!results) return null;
      if (!results[2]) return '';
      return decodeURIComponent(results[2].replace(/\+/g, " "));
  }

//コメント一覧を表示
function listupCommentJS(targetDOM,parent_id,user_id){
  console.log("listupCommentJS:"+user_id);
    $.ajax({
      type: "POST",
      url: "ajax.php?mode=listup-comment&parent_id="+parent_id+"&user_id="+user_id,
      processData: false,
      contentType: false,
      cache: false
    }).done(function(data){
      $(targetDOM).html(data);
    }).fail(function(XMLHttpRequest, status, e){
      console.log(e);
    });
  }


  function youtube_header(targetDOM){
    var movieObj=$(targetDOM).find(".youtube");
    if(movieObj){
      var name=$("#main-image").attr("src");
      if (name.match(/img.youtube.com/)) {
        $("#main-image").hide();
        movieObj.insertAfter("#main-image");
      }
    }

    var movieObj=$(targetDOM).find(".vimeo");
    if(movieObj){
      var name=$("#main-image").attr("src");
      if ( name.match(/i.vimeocdn.com/)) {
        $("#main-image").hide();
        movieObj.insertAfter("#main-image");
      }
    }


      
  }
//グラフを書く
  function drawChart(json){
    const data = {
      labels: ['未回答','未定','準備','活動中','面接','内定','進学','就職希望せず'],
      datasets: [{
        backgroundColor: [
          'rgba(200, 200, 200, 1)',
          'rgba(255, 99, 132, 1)',
          'rgba(255, 120, 30, 1)',
          'rgba(250, 180, 0, 1)',
          'rgba(86, 200, 170, 1)',
          'rgba(54, 162, 235, 1)',
          'rgba(150, 150, 180, 1)',
          'rgba(100, 100, 130, 1)',
      ],
        data: [json["empty"],json["danger"], json["prepare"],json["active"], json["judge"],json["success"],json["school"],json["nocount"]],
      }]
    };
    const config = {
      type: 'doughnut',
      data: data,
      options: {
        plugins: {
          legend: {
            display: false
          }
        }
      }
    }
    const myChart = new Chart(
      document.getElementById('grad-chart'),config
    );
  
  }



  function drawChart_Syokusyu(json,dom_id){
    const data = {
      labels: ['一般・その他','クリエイティブ'],
      datasets: [{
        backgroundColor: [
          'rgba(200, 200, 200, 1)',
          'rgba(100, 150, 255, 1)',
      ],
        data: [json["etc"],json["creative"]],
      }]
    };
  
    const config = {
      type: 'doughnut',
      data: data,
      options: {
        plugins: {
          legend: {
            display: false
          }
        }
      }
    }
    const myChart = new Chart(
      document.getElementById(dom_id),config
    );
  
  }



  function drawChart_Naitei(json,dom_id,color){
    const data = {
      labels: ['3月','4月','5月','6月','7月','8月','9月','10月','11月','12月','1月','2月'],
      datasets: [{
        backgroundColor: [
          color,
      ],
        data: [json["3"],json["4"],json["5"],json["6"],json["7"],json["8"],json["9"],json["10"],json["11"],json["12"],json["1"],json["2"]],
      }]
    };
  
    const config = {
      type: 'bar',
      data: data,
      options: {
        plugins: {
          legend: {
            display: false
          }
        }
      }
    }
    const myChart = new Chart(
      document.getElementById(dom_id),config
    );
  
  }


  function drawChart_Area(json,dom_id){
    const data = {
      labels: ['東京都','山形県','宮城県','その他東北','関西','その他'],
      datasets: [{
        backgroundColor: [
          'rgba(100, 150, 255, 1)',
          'rgba(100, 200, 120, 1)',
          'rgba(255, 150, 100, 1)',
          'rgba(255, 150, 255, 1)',
          'rgba(180, 50, 150, 1)',
          'rgba(200, 200, 200, 1)',
      ],
        data: [json["tokyo"],json["yamagata"],json["miyagi"],json["tohoku"],json["kansai"],json["etc"]],
      }]
    };
  
    const config = {
      type: 'doughnut',
      data: data,
      options: {
        plugins: {
          legend: {
            display: false
          }
        }
      }
    }
    const myChart = new Chart(
      document.getElementById(dom_id),config
    );
  
  }



  //list-group-item
  function sortProject(){
    $(document).ready(function() {
      $('#projects').html(
        $('.list-group-item').sort(function(a, b) {
          var x = Number($(a).attr("order"));
          var y = Number($(b).attr("order"));
          return x - y;
        })
        );
  });
  
  }

  
  async function fileDownloadFromUrl(fileName, fileUrl) {
    const response = await fetch(fileUrl);
    const blob = await response.blob();
    const newBlob = new Blob([blob]);
    const objUrl = window.URL.createObjectURL(newBlob);
    const link = document.createElement("a");
    link.href = objUrl;
    link.download = fileName;
    link.click();
    // For Firefox it is necessary to delay revoking the ObjectURL.
    setTimeout(() => {
      window.URL.revokeObjectURL(objUrl);
    }, 250);
  }