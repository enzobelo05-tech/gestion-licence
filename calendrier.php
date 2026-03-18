<?php
    require_once "variable-connexion/config.php";

    $dateActuel = date('Y-m-d H:i:s', strtotime('monday this week'));
    $dateFin = date('Y-m-d H:i:s', strtotime('sunday this week 23:59:59'));

    $requete = $connexion->prepare("
    SELECT course.id AS course_id, start_date, end_date, title, module.name AS module_name, intervention_type.name AS type_name, remotely
    FROM course 
    JOIN module ON module.id = course.module_id 
    JOIN intervention_type ON intervention_type.id = course.intervention_type_id
    JOIN course_instructor ON course_instructor.course_id = course.id
    WHERE start_date >= :dateActuel
    AND end_date <= :dateFin
    ORDER BY start_date DESC
    ");
    $requete->bindParam(":dateActuel", $dateActuel);
    $requete->bindParam(":dateFin", $dateFin);

    $requete->execute();
    $inter = $requete->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>calendrier</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="calendrier">
    <?php require "html-commun/aside.html"; ?>
    <div class="right-page">
        <header>
        <div class="filAriane">
          <img src="assets/Home.svg" alt="Accueil" />
          <p>></p>
          <a href="">Calendrier</a>
        </div>
        <hr />
      </header>
      <main>
        <div class="top-main">
          <h2>Calendrier</h2>
          <button type="submit" value="add" class="addInter">Ajouter une nouvelle intervention</button>
        </div>
        <h3>intervention de la semaine</h3>
        <div class="tableau">
            <div class="tableau-child">
              <p>Date de l'intervention</p>
              <p>Module & titre</p>
              <p>type</p>
              <p>intervenants</p>
              <p>En vision</p>
            </div>
            <?php 
                foreach($inter as $i){
                    $dateDebut = new DateTime($i["start_date"]);
                    $dateFin = new DateTime($i["end_date"]);
                    $dateFormatee = $dateDebut->format("d/m/Y");
                    $heureDebut = $dateDebut->format("h\hi");
                    $heureFin = $dateFin->format("h\hi");

                    ?>
                    <div class="tableau-child">
                    <p><?= htmlspecialchars($dateFormatee) ?> <br> <?= htmlspecialchars($heureDebut) . " à " . htmlspecialchars($heureFin) ?></p>
                    <p><?= htmlspecialchars($i["module_name"]) ?> / <?= htmlspecialchars($i["title"]) ?></p>
                    <p><?= htmlspecialchars($i["type_name"]) ?></p>
                    <p>
                    <?php

                    $requeteEns = $connexion->prepare("
                        SELECT user.last_name, user.first_name
                        FROM course
                        JOIN course_instructor ON course_instructor.course_id = course.id
                        JOIN instructor ON instructor.id = course_instructor.instructor_id
                        JOIN user ON user.id = instructor.user_id
                        WHERE course.id = :courseId
                    ");
                    $requeteEns->bindParam(":courseId", $i["course_id"]);

                    $requeteEns->execute();
                    $enseignants = $requeteEns->fetchAll(PDO::FETCH_ASSOC);

                    foreach($enseignants as $en){
                        $initial = $en["first_name"];
                        ?>
                            <?= htmlspecialchars($initial[0]) ?>. <?= htmlspecialchars($en["last_name"]) ?>
                        <?php
                    }
                    ?>
                        </p>                        
                    <?php
                        if ($i["remotely"]){
                            ?>
                            <img src="assets/visio.png" alt="En visio-conférences" class="visioImg">
                            <?php
                        }else{
                            ?>
                            <img src="assets/novisio.png" alt="Pas en visio-conférences" class="visioImg">
                            <?php
                        }
                    ?>
                    <div class="voirFiche">
                        <img src="assets/SeeMore.png" alt="Voir plus">
                        <a href="">Accéder à la fiche</a>
                    </div>
                    </div>
                    <?php
                }
            ?>
          </div>
      </main>
    </div>
</body>
</html>