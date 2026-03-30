<?php 
  require_once "variable-connexion/config.php";

  //* assignation variables
  $nom = $_GET["nom"] ?? "";
  $prenom = $_GET["prenom"] ?? "";
  $email = $_GET["email"] ?? "";

  //* requete pagination
  $requetePage = "SELECT COUNT(*) AS total FROM user WHERE role = 'instructor' ";

  if (!empty($nom)) $requetePage .= "AND last_name LIKE :nom ";
  if (!empty($prenom)) $requetePage .= "AND first_name LIKE :prenom ";
  if (!empty($email)) $requetePage .= "AND email LIKE :email ";

  $requetePageFinal = $connexion->prepare($requetePage);

  if (!empty($nom)) $requetePageFinal->bindValue(":nom", "%$nom%");
  if (!empty($prenom)) $requetePageFinal->bindValue(":prenom", "%$prenom%");
  if (!empty($email)) $requetePageFinal->bindValue(":email", "%$email%");

  $requetePageFinal->execute();
  $total = $requetePageFinal->fetch(PDO::FETCH_ASSOC)["total"];
  $nbPage = ceil($total / 10);

  $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
  $page = max(1, $page);

  $offSet = $page * 10 - 10;

  //* requete form
  $requete = "SELECT * FROM user WHERE role = 'instructor' ";

  if (!empty($nom)) $requete .= "AND last_name LIKE :nom ";
  if (!empty($prenom)) $requete .= "AND first_name LIKE :prenom ";
  if (!empty($email)) $requete .= "AND email LIKE :email ";

  $requete .= " ORDER BY last_name ASC LIMIT 10 OFFSET :offset";

  $requeteFinal = $connexion->prepare($requete);

  if (!empty($nom)) $requeteFinal->bindValue(":nom", "%$nom%");
  if (!empty($prenom)) $requeteFinal->bindValue(":prenom", "%$prenom%");
  if (!empty($email)) $requeteFinal->bindValue(":email", "%$email%");
  $requeteFinal->bindValue(":offset", $offSet, PDO::PARAM_INT);

  $requeteFinal->execute();
  $enseignant = $requeteFinal->fetchAll(PDO::FETCH_ASSOC);

  $count = $total;

  //* requete pop up
  if(isset($_POST["nomIns"], $_POST["prenomIns"], $_POST["emailIns"], $_POST["mdpIns"])){

      $nom = $_POST["nomIns"];
      $prenom = $_POST["prenomIns"];
      $email = $_POST["emailIns"];
      $mdp = password_hash($_POST["mdpIns"], PASSWORD_ARGON2I);

      $requete = $connexion->prepare("
        INSERT INTO user 
        (role, last_name, first_name, email, password) 
        VALUES ('instructor', :nom, :prenom, :email, :mdp)
      ");

      $requete->bindParam(":nom", $nom);
      $requete->bindParam(":prenom", $prenom);
      $requete->bindParam(":email", $email);
      $requete->bindParam(":mdp", $mdp);

      $requete->execute();

      $newId = $connexion->lastInsertId();

      $requeteIns = $connexion->prepare("INSERT INTO instructor (user_id) VALUES (:id)");
      $requeteIns->bindParam(":id", $newId);
      $requeteIns->execute();
  }
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Corps Enseignant</title>
    <link rel="stylesheet" href="styles.css" />
    <script src="script.js" defer></script>
  </head>
  <body class="corpsEnseignant">
    <?php require "html-commun/aside.php"; ?>
    <div class="right-page">
      <header>
        <div class="filAriane">
          <img src="assets/Home.svg" alt="Accueil" />
          <p>></p>
          <a href="">Corps enseignant</a>
        </div>
        <hr />
      </header>
      <main>
        <div class="top-main">
          <h2>Corps enseignant</h2>
          <button type="submit" value="add" class="addInstructor">Ajouter un enseignant</button>
        </div>
        <section class="form-parent">
          <p class="filter-txt">Filtre</p>
          <form action="" method="GET">
            <div class="input-box">
              <label for="nom">Nom de famille</label>
              <input type="text" name="nom" placeholder="Saisissez le nom de famille" />
            </div>
            <div class="input-box">
              <label for="prenom">Prénom</label>
              <input type="text" name="prenom" placeholder="Saisissez le prénom" />
            </div>
            <div class="input-box">
              <label for="email">Email</label>
              <input type="text" name="email" placeholder="Saisissez l'Email" />
            </div>
            <button type="submit">filtrer</button>
          </form>
          <hr />
        </section>
        <section class="enseignant-found">
          <h3><?= $count ?> enseignant trouvé(s)</h3>
          <div class="tableau">
            <div class="tableau-child">
              <p>Nom de famille</p>
              <p>Prénom</p>
              <p>Modules enseignés</p>
              <p>Nombre d'heures</p>
            </div>
            <?php 
              if(isset($enseignant)){
                foreach($enseignant as $e){
                  ?>
                  <div class="tableau-child">
                    <p><?= htmlspecialchars($e["last_name"]) ?></p>
                    <p><?= htmlspecialchars($e["first_name"]) ?></p>
                    <p>
                  <?php

                  $id = $e["id"];
                  $requete = $connexion->prepare("SELECT module.name, module.hours_count FROM user JOIN instructor ON instructor.user_id = user.id JOIN instructor_module ON instructor_module.instructor_id = instructor.id JOIN module ON module.id = instructor_module.module_id WHERE user.id = :id");
                  $requete->bindParam(":id", $id);
                  $requete->execute();
                  $enseignantInfo = $requete->fetchAll(PDO::FETCH_ASSOC);

                  if($enseignantInfo){
                    $heure = 0;
                    foreach($enseignantInfo as $en){
                      ?>
                          <?= htmlspecialchars($en["name"]) ?>
                      <?php
                      $heure += $en["hours_count"];
                    }
                    ?>
                        </p>
                        <p><?= $heure ?>h</p>
                    <?php
                  }
                  ?>
                    <div class="voirFiche">
                      <img src="assets/SeeMore.png" alt="Voir plus">
                      <a href="infos-generales.php?id=<?= htmlspecialchars($e['id']) ?>">Accéder à la fiche</a>
                    </div>
                  </div>
                  <?php
                }
              }
            ?>
          </div>
        </section>
        <div class="pagination">
            <?php
                if (isset($nbPage)){  
                  for ($i = 1; $i <= $nbPage; $i++){
                      ?>
                      <a href="corps-enseignant.php?page=<?= $i ?>&nom=<?= urlencode($nom) ?>&prenom=<?= urlencode($prenom) ?>&email=<?= urlencode($email) ?>"
                        class="pagination-child <?= $i === $page ? 'pagination-select' : '' ?>">
                        <?= $i ?>
                      </a>
                      <?php
                  }
                }
            ?>
          </div>
      </main>
    </div>
    <section class="popUp">
      <p class="close-btn-popUp">x</p>
      <div class="popUp-main">
        <div class="popUp-header">
          <img src="assets/plusSymbole.png" alt="Ajouter" class="left">
          <div class="right">
            <h2>Ajouter un enseignant</h2>
            <p>Remplissez les informations ci-dessous</p>
          </div>
        </div>
        <form action="" method="POST" class="formulaire">
          <div class="input-box">
            <label for="nomIns">Nom de l'enseignant</label>
            <input type="text" name="nomIns" placeholder="Legrand" required>
          </div>
          <div class="input-box">
            <label for="prenomIns">Prénom de l'enseignant</label>
            <input type="text" name="prenomIns" placeholder="Paul" required>
          </div>
          <div class="input-box">
            <label for="emailIns">Email de l'enseignant</label>
            <input type="email" name="emailIns" placeholder="Paul.Legrand@gmail.com">
          </div>
          <div class="input-box">
            <label for="mdpIns">Mot de passe de l'enseignant</label>
            <input type="password" name="mdpIns" placeholder="••••••••" required>
          </div>
          <p class="cancel-btn-popUp">Annuler</p>
          <button type="submit" class="confirm-btn">Confirmer</button>
        </form>
      </div>
    </section>
    <div class="overlay"></div>
  </body>
</html>