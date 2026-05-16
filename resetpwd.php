<?php
$con = new PDO("mysql:host=localhost;charset=utf8", "root", "root");
$con->exec("ALTER USER 'root'@'localhost' IDENTIFIED BY ''");
echo "Mot de passe réinitialisé !";