<?php
require_once "variable-connexion/connexion.php";

session_start(); 

if(isset($_POST['mail']) && isset($_POST['password'])) {
    $mail = $_POST['mail'];
    $password = $_POST['password'];


    $requette = $con->prepare("SELECT email, password FROM user WHERE email = :email");
    

    $requette->bindParam(':email', $mail);
    $requette->execute(); 
    $resultat = $requette->fetch(PDO::FETCH_ASSOC); 

    if($resultat && password_verify($password, $resultat['password'])){
        $_SESSION['email'] = $resultat['email']; 
        header('Location: dashboard.php');
        exit(); 
    } else {

        echo "Identifiants incorrects.";
    }
}
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Document</title>
        <link rel="stylesheet" href="styles.css" />
    </head>
    <body class="connexion">
        <aside>
            <nav>
                <div class="logo">
                    <img src="assets/logo.png" alt="Logo Lycée" />
                    <div class="logo-container">
                        <p>Lycée Saint-Vincent</p>
                        <span>Enseignement Supérieur</span>
                    </div>
                </div>
            </nav>
        </aside>

        <main>
            <div>
                <h1>Gestion du supérieur</h1>
                <h2>Connexion au portail</h2>
                <section></section>

                <form action="" method="POST"> 
                    <label for="mail">Email - champ obligatoire</label>
                    <input type="email" id="mail" name="mail" placeholder="j.martins@mentalworks.fr" required />

                    <label for="password">Mot de passe - champ obligatoire</label>
                    <input type="password" id="password" name="password" placeholder="•••••" required />

                    <button type="submit">Accèder au portail</button>
                </form>
            </div>
        </main>
    </body>
</html>
