<?php
require_once "variable-connexion/auth.php";
require_once "variable-connexion/connexion.php";

$idType = $_GET["id"];

// Récupérer les infos
$requete = $connexion->prepare("SELECT id, name, description, color FROM intervention_type WHERE id = :id");
$requete->bindParam(':id', $idType);
$requete->execute();
$type = $requete->fetch(PDO::FETCH_ASSOC);

// Modifier les infos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $nom         = trim($_POST['nom_type']);
    $description = trim($_POST['description']);
    $color       = trim($_POST['color']);

    $requete = $connexion->prepare("UPDATE intervention_type SET name = :nom, description = :description, color = :color WHERE id = :id");
    $requete->bindParam(':nom', $nom);
    $requete->bindParam(':description', $description);
    $requete->bindParam(':color', $color);
    $requete->bindParam(':id', $idType);
    $requete->execute();

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Supprimer les infos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    
    // Vérifier si des cours sont liés à ce type d'intervention
    $check = $connexion->prepare("SELECT COUNT(*) as total FROM course WHERE intervention_type_id = :id");
    $check->bindParam(':id', $idType);
    $check->execute();
    $result = $check->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        $erreurSuppression = "Impossible de supprimer ce type : " . $result['total'] . " cours y sont liés.";
    } else {
        $requete = $connexion->prepare("DELETE FROM intervention_type WHERE id = :id");
        $requete->bindParam(':id', $idType);
        $requete->execute();
        header("Location: types-intervention.php");
        exit;
    }
}
?>

<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Interventions - Cours</title>
        <link rel="stylesheet" href="styles.css" />
        <script src="script.js" defer></script>
    </head>
    <body class="fiche-type-intervention">
        <?php require "html-commun/aside-enzo.php" ?>
        <div class="right-page">
            <header>
                <div class="filAriane">
                    <img src="assets/Home.svg" alt="Accueil" />
                    <p>></p>
                    <a href="types-intervention.php">Types d'intervention</a>
                    <p>></p>
                    <a href=""><?= htmlspecialchars($type['name']) ?></a>
                </div>
                <hr />
            </header>
            <main>
                <div class="top-main">
                    <h2><?= htmlspecialchars($type['name']) ?></h2>
                    <br>
                </div>
                <section class="form-parent">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="modifier" />
                        <div class="input-box">
                            <label for="nom_type">Nom - Champ obligatoire</label>
                            <input type="text" name="nom_type" value="<?= htmlspecialchars($type['name']) ?>" required />
                        </div>
                        <div class="input-box">
                            <label for="color">Code couleur (hexadécimal) - champ obligatoire</label>
                            <input type="text" name="color" value="<?= htmlspecialchars($type['color']) ?>" required />
                        </div>
                        <div class="input-box">
                            <label for="description">Description - champ obligatoire</label>
                            <input type="text" name="description" value="<?= htmlspecialchars($type['description']) ?>" required />
                        </div>
                        <div class="button">
                            <a href="types-intervention.php">Retour à la liste</a>
                            <a href="javascript:void(0)" class="addInter">Supprimer</a>
                            <a href="javascript:void(0)" onclick="this.closest('form').submit();">Enregistrer les informations</a>
                        </div>
                    </form>
                </section>
            </main>

            <!--popup-->
            <section class="popUp">
                <p class="close-btn-popUp">x</p>
                <div class="popUp-main">
                    <div class="popUp-header">
                        <img src="assets/plusSymbole.png" alt="Ajouter" class="left">
                        <div class="right">
                            <h2>Supprimer le type d'intervention</h2>
                            <p>Confirmation de l'action</p>
                        </div>
                    </div>
                    <div class="container">
                        <p>Vous vous apprêtez à supprimer le type d'intervention, cette action est irrévoquable. <br>A noter qu'aucune intervention ne doit être liée à ce module pour pouvoir le supprimer.</p>
                        <br>
                        <p>Confirmez-vous l'action ?</p>
                        <br>
                    </div>
                    <div class="button">
                        <a href="javascript:void(0)" class="cancel-btn-popUp">Annuler</a>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="supprimer" />
                            <button style="background-color: red;" type="submit">Confirmer</button>
                        </form>
                    </div>
                </div>
            </section>
            <div class="overlay"></div>
        </div>
    </body>
</html>