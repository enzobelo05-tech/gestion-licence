<?php 
  require_once "config.php";

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
    $enseignant = $requete->fetchAll(PDO::FETCH_ASSOC);
  }

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Corps Enseignant</title>
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body class="corpsEnseignant">
    <aside class="aside-page">
      <nav>
        <div class="logo">
          <img src="assets/logo.png" alt="Logo Lycée" />
          <div class="logo-container">
            <p>Lycée Saint-Vincent</p>
            <span>Enseignement Supérieur</span>
          </div>
        </div>
        <div class="navigation">
          <div class="menu">
            <p class="nav-title">MENU</p>
            <div class="menu-nav">
              <div class="menu-child">
                <img src="assets/Calendrier.svg" alt="Calendrier" />
                <p>Calendrier</p>
              </div>
              <div class="menu-child">
                <img src="assets/Intervention.svg" alt="Interventions" />
                <p>Interventions</p>
              </div>
              <div class="menu-child">
                <img src="assets/CorpsEnseignant.svg" alt="Corps Enseignant" />
                <p>Corps enseignant</p>
              </div>
            </div>
          </div>
          <div class="parametrage">
            <p class="nav-title">PARAMETRAGE</p>
            <div class="parametrage-nav">
              <div class="parametrage-child">
                <img src="assets/Module.svg" alt="Modules" />
                <p>Modules</p>
              </div>
              <div class="parametrage-child">
                <img src="assets/Intervention.svg" alt="Types Interventions" />
                <p>Types d'intervention</p>
              </div>
            </div>
          </div>
        </div>
      </nav>
    </aside>
    <div class="right-page">
      <header>
        <div class="filAriane">
          <img src="assets/Home.svg" alt="Accueil" />
          <p>></p>
          <p>Corps enseignant</p>
        </div>
        <hr />
      </header>
      <main>
        <div class="top-main">
          <h2>Corps enseignant</h2>
          <button type="submit" value="add">Ajouter un enseignant</button>
        </div>
        <section class="form-parent">
          <p class="filter-txt">Filtre</p>
          <form action="" method="POST">
            <div class="input-box">
              <label for="nom">Nom de famille</label>
              <input
                type="text"
                name="nom"
                placeholder="Saisissez le nom de famille"
              />
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
          <?php  
            foreach($enseignant as $ens){
                $count += 1;
              } 
          ?>
          <h3><?= $count ?> enseignant trouvé(s)</h3>
          <div class="tableau">
            <div class="tableau-child">
              <p>Nom de famille</p>
              <p>Prénom</p>
              <p>Modules enseignés</p>
              <p>Nombre d'heures</p>
            </div>
            <?php 
              if($enseignant){

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
                    ?>
                    <?php
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
                    <p>voir</p>
                  </div>
                  <?php
                }
              }
            ?>
          </div>
        </section>
      </main>
    </div>
  </body>
</html>
