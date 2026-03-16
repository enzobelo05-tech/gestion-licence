<?php 
    require_once "config.php";

    $id = $_GET["id"];
    $count = 0;

    //* requete pour les infos du user
    $requeteID = $connexion->prepare("SELECT * FROM user WHERE id = :id");
    $requeteID->bindParam(":id", $id);

    $requeteID->execute();
    $enseignant = $requeteID->fetch(PDO::FETCH_ASSOC);

    //* requete pour le nom et les heures des modules du user
    $requeteModUser = $connexion->prepare("SELECT module.name, module.hours_count, module.id FROM user JOIN instructor ON instructor.user_id = user.id JOIN instructor_module ON instructor_module.instructor_id = instructor.id JOIN module ON module.id = instructor_module.module_id WHERE user.id = :id");
    $requeteModUser->bindParam(":id", $id);

    $requeteModUser->execute();
    $enseignantInfo = $requeteModUser->fetchAll(PDO::FETCH_ASSOC);

    //* requete pour l'instructor id
    $requeteInstructor = $connexion->prepare("SELECT id FROM instructor WHERE user_id = :id");
    $requeteInstructor->bindParam(":id", $id);
    $requeteInstructor->execute();
    $instructor = $requeteInstructor->fetch(PDO::FETCH_ASSOC);
    $instructorId = $instructor["id"];

    //* requete pour les id et les noms de tous les modules
    $requeteModAll = $connexion->prepare("SELECT id, name FROM module");
    $requeteModAll->execute();
    $allMod = $requeteModAll->fetchAll(PDO::FETCH_ASSOC);

    //* envoie du form
    if ($_SERVER["REQUEST_METHOD"] === "POST"){

        $dateDebut = $_POST["dateDebut"];
        $dateFin = $_POST["dateFin"];
        $moduleID = $_POST["module"];

       // var_dump($dateDebut);)

        $requete = $connexion->prepare("
            SELECT course.start_date, course.end_date, course.remotely, module.name AS module_name, intervention_type.name AS type_name
            FROM course
            JOIN module ON module.id = course.module_id
            JOIN intervention_type ON intervention_type.id = course.intervention_type_id
            JOIN course_instructor ON course_instructor.course_id = course.id
            WHERE course_instructor.instructor_id = :instructorId
            AND course.start_date >= :debut
        ");
        $requete->bindParam(":instructorId", $instructorId);
        $requete->bindParam(":debut", $dateDebut);;

        $requete->execute();
        $interventions = $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    //* requete pour les filtres du tableau

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interventions</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="interventions">
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
          <a href="corps-enseignant.php">Corps enseignant</a>
          <p>></p>
          <p><?= htmlspecialchars($enseignant["first_name"]) ?> <?= htmlspecialchars($enseignant["last_name"]) ?></p>
          <p>></p>
          <a href="">Informations générales</a>
        </div>
        <hr />
      </header>
      <main>
        <div class="mod-ens">
            <h2><?= htmlspecialchars($enseignant["first_name"]) ?> <?= htmlspecialchars($enseignant["last_name"]) ?></h2>
            <p class="sub-title">Modules enseignés</p>
            <?php
                if($enseignantInfo){
                    $heure = 0;
                    foreach($enseignantInfo as $en){
                    ?>
                        <p><?= htmlspecialchars($en["name"]) ?>:  <?= htmlspecialchars($en["hours_count"]) ?>h00</p>
                    <?php
                    }
                }
            ?>
            <hr>
        </div>
        <section class="infos-ens">
            <div class="onglet">
                <a href="infos-generales.php?id=<?= $id ?>">Informations générales</a>
                <a href="">Interventions</a>
            </div>
            <p class="form-title">Filtrer les interventions</p>
            <form action="" method="POST">
                <div class="input-box">
                    <label for="dateDebut">Date de début</label>
                    <input type="datetime-local" name="dateDebut">
                </div>
                <div class="input-box">
                    <label for="dateFin">Date de fin</label>
                    <input type="datetime-local" name="dateFin">
                </div>
                <div class="input-box">
                    <label for="module">Module</label>
                    <select name="module">
                        <?php 
                            foreach ($allMod as $mod){
                                ?>
                                <option value="<?= htmlspecialchars($mod["id"]) ?>"><?= htmlspecialchars($mod["name"]) ?></option>
                                <?php
                            }
                        ?>
                    </select>
                </div>
                <button type="submit" class="filter-btn">Filtrer</button>
            </form>
            <hr>
            <?php
            if (isset($interventions)){
              foreach($interventions as $int){
                $count += 1;
              } 
            }
          ?>
          <h3><?= $count ?> enseignant trouvé(s)</h3>
          <div class="tableau">
            <div class="tableau-child">
              <p>Date de l'intervention</p>
              <p>Module</p>
              <p>Type</p>
              <p>Intervention</p>
              <p>En visio</p>
            </div>
            <?php 
              if(isset($interventions)){

                foreach($interventions as $inter){
                  ?>
                  <div class="tableau-child">
                    <p><?= var_dump($inter["start_date"])?></p>
                    <p><?= htmlspecialchars($inter["module_name"]) ?></p>
                    <p>
                  <?php

                  $idMod = $e["module_id"];
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
      </main>
    </div>
</body>
</html>