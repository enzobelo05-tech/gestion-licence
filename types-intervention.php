<?php 
  require_once "variable-connexion/connexion.php";

  $count = 0;

  if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $count = 0;

    $nom = '%' . $_POST["nom"] . '%';
    $prenom = '%' . $_POST["prenom"] . '%';
    $email = '%' . $_POST["email"] . '%';

    $requete = $connexion->prepare("SELECT id, last_name, first_name, email FROM user WHERE last_name LIKE :nom AND first_name LIKE :prenom AND email LIKE :email AND role = 'instructor'" );
    $requete->bindParam(":nom", $nom);
    $requete->bindParam(":prenom", $prenom);
    $requete->bindParam(":email", $email);

    $requete->execute();
    $typeIntervention = $requete->fetchAll(PDO::FETCH_ASSOC);
  }

?>


<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Interventions</title>
        <link rel="stylesheet" href="styles.css" />
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
                    <h2>Types d'ntervention</h2>
                    <button type="submit" value="add">Ajouter un type</button>
                </div>
                <section class="form-parent">
                    <p class="filter-txt">Filtre</p>
                    <form action="" method="POST">
                        <div class="input-box">
                            <label for="nom">Nom</label>
                            <input type="text" name="nom" placeholder="Saisissez le nom" />
                        </div>

                        <button type="submit">filtrer</button>
                    </form>
                    <hr />
                </section>
                <section class="enseignant-found">
                    <?php if (isset($enseign)){ foreach($typeIntervention  as $ens){ $count += 1; } } ?>
                    <h3><?= $count ?> types</h3>
                    <div class="tableau">
                        <div class="tableau-child">
                            <p>Nom</p>
                            <p>Descriptif</p>
                            <p>Couleur</p>
                        </div>
                        <?php if(isset($typeIntervention)){ foreach($typeIntervention as $e){ ?>
                        <div class="tableau-child">
                            <p><?= htmlspecialchars($e["name"]) ?></p>
                            <p><?= htmlspecialchars($e["descriptif"]) ?></p>
                            <p><?= htmlspecialchars($e["color"]) ?></p>

                            <p><?= $heure ?>h</p>

                            <?php } ?>
                            <div class="voirFiche">
                                <img src="assets/SeeMore.png" alt="Voir plus" />
                                <a href="infos-generales.php?id=<?= htmlspecialchars($e['id']) ?>"
                                    >Accéder à la fiche</a
                                >
                            </div>
                        </div>
                        <?php }  ?>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
