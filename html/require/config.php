<?php

if(strpos($_SERVER['HTTP_HOST'],'localhost') !== false){
    define('HOST', 'localhost');
    define('PORT', '8889');
    define('USER', 'root');
    define('PASS', 'root');
    define('DB', 'tuad');
}else{
    define('HOST', 'mysql57.tuad-eizo-class.sakura.ne.jp');
    define('PORT', '');
    define('USER', 'tuad-eizo-class');
    define('PASS', '4dT9jk45');
    define('DB', 'tuad-eizo-class_portal');
}

define('PAGE_LIMIT', 10);

?>
