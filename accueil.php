<?php
session_start();
require_once "variable-connexion/connexion.php";

$erreur = "";

if(isset($_POST['mail']) && isset($_POST['password'])) {
    $mail = $_POST['mail'];
    $password = $_POST['password'];
    
    $requette = $connexion->prepare("SELECT id, email, password, role FROM user WHERE email = :email");
    $requette->bindParam(':email', $mail);
    $requette->execute();
    $resultat = $requette->fetch(PDO::FETCH_ASSOC);
    
    if($resultat) {
        $passwordOk = password_verify($password, $resultat['password']) 
                      || $password === $resultat['password'];
        
        if($passwordOk) {
            $_SESSION['id'] = $resultat['id'];
            $_SESSION['email'] = $resultat['email'];
            $_SESSION['role'] = $resultat['role'];
            header('Location: calendrier.php');
            exit();
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Connexion</title>
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
            <form action="" method="POST">
                <label for="mail">Email - champ obligatoire</label>
                <input type="email" id="mail" name="mail" 
                       placeholder="j.martins@mentalworks.fr" 
                       value="<?= isset($_POST['mail']) ? htmlspecialchars($_POST['mail']) : '' ?>"
                       required />
                <label for="password">Mot de passe - champ obligatoire</label>
                <input type="password" id="password" name="password" 
                       placeholder="•••••" required />
                <?php if($erreur): ?>
                    <p style="color: red;"><?= $erreur ?></p>
                <?php endif; ?>
                <button type="submit">Accèder au portail</button>
            </form>
        </div>
    </main>
</body>
</html>