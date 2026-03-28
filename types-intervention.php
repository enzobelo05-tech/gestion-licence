<?php
require_once "variable-connexion/connexion.php";


$count = 0;
$typeIntervention = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = '%' . $_POST["nom"] . '%';

    $requete = $connexion->prepare(
        "SELECT id, name, description, color 
         FROM intervention_type 
         WHERE name LIKE :nom"
    );
    $requete->bindParam(":nom", $nom);
    $requete->execute();
    $typeIntervention = $requete->fetchAll(PDO::FETCH_ASSOC);

} else {
    $requete = $connexion->prepare(
        "SELECT id, name, description, color FROM intervention_type"
    );
    $requete->execute();
    $typeIntervention = $requete->fetchAll(PDO::FETCH_ASSOC);
}

$count = count($typeIntervention); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom         = $_POST['nom'];
    $description = $_POST['description'];
    $couleur     = $_POST['couleur'];
}
?>


<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Interventions</title>
        <link rel="stylesheet" href="styles.css" />
        <script src="script.js" defer></script>
    </head>
    <body class="type-intervention">
        <?php require "html-commun/aside-enzo.php" ?>
        <div class="right-page">
            <header>
                <div class="filAriane">
                    <img src="assets/Home.svg" alt="Accueil" />
                    <p>></p>
                    <a href="">Types d'intervention</a>
                </div>
                <hr />
            </header>
            <main>
                <div class="top-main">
                    <h2>Types d'intervention</h2>
                    <a href="javascript:void(0)" class="addInter">Ajouter un type</a>
                </div>
                <section class="form-parent">
                    <p class="filter-txt">Filtre</p>
                    <form action="" method="POST">
                        <div class="input-box">
                            <label for="nom">Nom</label>
                            <input type="text" name="nom" placeholder="Saisissez le nom" />
                        </div>

                        <button type="submit">Filtrer</button>
                    </form>
                    <hr />
                </section>
                <section class="enseignant-found">
                    <h3><?= $count ?> type(s)</h3>
                    <div class="tableau">
                        <div class="tableau-child">
                            <p>Nom</p>
                            <p>description</p>
                            <p>Couleur</p>
                        </div>
                        <?php foreach($typeIntervention as $e){?>
                        <div class="tableau-child">
                            <p><?= htmlspecialchars($e["name"]) ?></p>
                            <p><?= htmlspecialchars($e["description"]) ?></p>
                            <p style="color: <?= htmlspecialchars($e["color"]) ?>;"><?= htmlspecialchars($e["color"]) ?></p>

                            <div class="voirFiche">
                                <img src="assets/SeeMore.png" alt="Voir plus" />
                                <a href="voir-fiche-types-intervention.php?id=<?= htmlspecialchars($e['id']) ?>"
                                    >Accéder à la fiche</a
                                >
                            </div>
                        </div>
                   <?php } ?>
                    </div>
                </section>
            </main>
        </div>
        <section class="popUp">
      <p class="close-btn-popUp">x</p>
      <div class="popUp-main">
        <div class="popUp-header">
          <img src="assets/plusSymbole.png" alt="Ajouter" class="left">
          <div class="right">
            <h2>Ajouter une intervention</h2>
            <p>Remplissez les informations ci-dessous</p>
          </div>
        </div>
        <form action="" method="POST" class="formulaire">
          <div class="input-box">
            <label for="titre">Nom - Champ obligatoire</label>
            <input type="text" name="titre" placeholder="Cours" required>
          </div>
          <br>
          
            <div class="input-box">
            <label for="titre">Description - Champ obligatoire</label>
            <input type="text" name="titre" placeholder="Cours dispensé par..." required>
          </div>
          <br>
          <div class="input-box">
            <label for="titre">Code couleur (hexadécimal) - champ obligatoire</label>
            <input type="text" name="titre" placeholder="#38480" required>
          </div>
        
          <br>
          <div class="btn-form">
            <p class="cancel-btn-popUp">Annuler</p>
            <button type="submit" class="confirm-btn">Confirmer</button>
          </div>
        </form>
      </div>
    </section>
    <div class="overlay"></div>
    </body>
</html>
