<?php
session_start(); 
session_destroy(); //niszczenie bieżącej sesji
header("Location: index.php");
exit;
