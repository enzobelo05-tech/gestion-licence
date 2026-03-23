<?php
$serveur = "localhost";       
$utilisateur = "root";        
$mot_de_passe = "root";           
$base_de_donnees = "projet_php";   

try {
    $connexion = new PDO("mysql:host=$serveur;dbname=$base_de_donnees;charset=utf8", $utilisateur, $mot_de_passe);    
    
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}



?>
