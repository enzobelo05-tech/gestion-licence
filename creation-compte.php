<?php
require_once "connexion.php";

if(isset($_POST['login']) && isset($_POST['password'])) {

  $email = $_POST['login']; 
  $password = password_hash($_POST['password'], PASSWORD_ARGON2I);
  
  $role = "admin";
  $last_name = "Inconnu";
  $first_name = "Inconnu";

  $requette = $con->prepare("INSERT INTO user (role, email, last_name, first_name, password) VALUES (:role, :email, :last_name, :first_name, :password)");
  
  $requette->bindParam(':role', $role);
  $requette->bindParam(':email', $email);
  $requette->bindParam(':last_name', $last_name);
  $requette->bindParam(':first_name', $first_name);
  $requette->bindParam(':password', $password);

  $requette->execute();
}
?>

<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Enzo Belo | Création de compte</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="style.css" />
    </head>
    <body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0;">

        <main>
            <div class="carte-connexion" id="carteConnexion">

                <form id="formulaireConnexion" action="" method="POST">

                    <div class="groupe-champ">
                        <label for="login">Mail :</label>
                        <div class="conteneur-champ">
                            <input
                                type="text"
                                id="login"
                                name="login"
                                placeholder="admin@email.com"
                                required
                            />
                        </div>
                    </div>

                    <div class="groupe-champ">
                        <label for="password">Mot de passe :</label>
                        <div class="conteneur-champ">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                required
                            />
                        </div>
                    </div>

                    <button type="submit" class="bouton-connexion">
                        Créer le compte
                    </button>

                </form>

            </div>
        </main>
    </body>
</html>