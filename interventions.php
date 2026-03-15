<?php 
    require_once "config.php";

    $id = $_GET["id"];

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
          <p>Corps enseignant</p>
          <p>></p>
          <p><?= htmlspecialchars($enseignant["first_name"]) ?> <?= htmlspecialchars($enseignant["last_name"]) ?></p>
          <p>></p>
          <p>Informations générales</p>
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
        </section>
      </main>
    </div>
</body>
</html>