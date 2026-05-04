<?php
$isMac = strtoupper(PHP_OS) === 'DARWIN';

if ($isMac) {
    $host   = "127.0.0.1";
    $user   = "root";
    $pass   = "root";       // ← ton mot de passe MAMP
    $dbName = "projet_php";
} else {
    $host   = "127.0.0.1";
    $user   = "root";
    $pass   = "";           // ← leur mot de passe XAMPP
    $dbName = "projet_php";
}

try {
    $connexion = new PDO(
        "mysql:host=$host;dbname=$dbName;charset=utf8",
        $user,
        $pass
    );
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    die();
}

?>