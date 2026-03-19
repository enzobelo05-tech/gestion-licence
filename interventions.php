<?php 
    require_once "variable-connexion/config.php";

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

    //* envoie du form
    if ($_SERVER["REQUEST_METHOD"] === "POST"){

        $moduleID = $_POST["module"];
        $dateDebut = $_POST["dateDebut"];
        $dateFin = $_POST["dateFin"];

        $requete = 
            "SELECT course.start_date, course.end_date, course.remotely, module.name AS module_name, intervention_type.name AS type_name
            FROM course
            JOIN module ON module.id = course.module_id
            JOIN intervention_type ON intervention_type.id = course.intervention_type_id
            JOIN course_instructor ON course_instructor.course_id = course.id
            WHERE course_instructor.instructor_id = :instructorId";

        (!empty($_POST["dateDebut"])) ? $requete = $requete . " AND course.start_date >= :debut" : "";
        (!empty($_POST["dateFin"])) ? $requete = $requete . " AND course.end_date <= :fin" : "";
        (!empty($_POST["module"])) ? $requete = $requete . " AND course.module_id = :moduleId" : "";

        $requeteFinal = $connexion->prepare($requete);
        $requeteFinal->bindParam(":instructorId", $instructorId);
        (!empty($_POST["dateDebut"])) ? $requeteFinal->bindValue(":debut", $dateDebut) : "";
        (!empty($_POST["dateFin"])) ? $requeteFinal->bindValue(":fin", $dateFin) : "";
        (!empty($_POST["module"])) ? $requeteFinal->bindValue(":moduleId", $moduleID) : "";

        $requeteFinal->execute();
        $interventions = $requeteFinal->fetchAll(PDO::FETCH_ASSOC);
    }
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
    <?php require "html-commun/aside.php"; ?>
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
                        <option value="">Selectionnez le module</option>
                        <?php 
                            foreach ($enseignantInfo as $mod){
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
              <p>Intervenant</p>
              <p>En visio</p>
            </div>
            <?php 
              if(isset($interventions)){

                foreach($interventions as $inter){
                  $dateDebut = new DateTime($inter["start_date"]);
                  $dateFin = new DateTime($inter["end_date"]);
                  $dateFormatee = $dateDebut->format("d/m/Y");
                  $heureDebut = $dateDebut->format("h\hi");
                  $heureFin = $dateFin->format("h\hi");

                  $initial = $enseignant["first_name"]; 
                  ?>
                  <div class="tableau-child">
                    <p><?= htmlspecialchars($dateFormatee) ?> <br> <?= htmlspecialchars($heureDebut) . " à " . htmlspecialchars($heureFin) ?></p>
                    <p><?= htmlspecialchars($inter["module_name"]) ?></p>
                    <p><?= htmlspecialchars($inter["type_name"]) ?></p>
                    <p><?= htmlspecialchars($initial[0]) ?>. <?= htmlspecialchars($enseignant["last_name"]) ?></p>

                    <?php
                        if ($inter["remotely"]){
                            ?>
                            <img src="assets/visio.png" alt="En visio-conférences" class="visioImg">
                            <?php
                        }else{
                            ?>
                            <img src="assets/novisio.png" alt="Pas en visio-conférences" class="visioImg">
                            <?php
                        }
                    ?>

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