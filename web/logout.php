<?php require 'inc.php';api('auth','logout',['token'=>$_SESSION['token']??'']);session_destroy();header('Location: index.php');
