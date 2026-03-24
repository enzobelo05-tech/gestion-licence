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
    $moduleID = $_GET["module"] ?? "";
    $dateDebut = $_GET["dateDebut"] ?? "";
    $dateFin = $_GET["dateFin"] ?? "";

    $requetePage = "
        SELECT COUNT(*) AS total 
        FROM course
        JOIN course_instructor ON course_instructor.course_id = course.id
        WHERE course_instructor.instructor_id = :instructorId
    ";

    if (!empty($dateDebut)) $requetePage .= " AND course.start_date >= :debut";
    if (!empty($dateFin)) $requetePage .= " AND course.end_date <= :fin";
    if (!empty($moduleID)) $requetePage .= " AND course.module_id = :moduleId";

    $requetePageFinal = $connexion->prepare($requetePage);
    $requetePageFinal->bindValue(":instructorId", $instructorId, PDO::PARAM_INT);
    if (!empty($dateDebut)) $requetePageFinal->bindValue(":debut", $dateDebut);
    if (!empty($dateFin)) $requetePageFinal->bindValue(":fin", $dateFin);
    if (!empty($moduleID)) $requetePageFinal->bindValue(":moduleId", $moduleID, PDO::PARAM_INT);

    $requetePageFinal->execute();
    $total = $requetePageFinal->fetch(PDO::FETCH_ASSOC)["total"];
    $nbPage = ceil($total / 10);

    $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
    $page = max(1, $page);

    $offSet = $page * 10 - 10;

    $requete = 
        "SELECT course.start_date, course.end_date, course.remotely, module.name AS module_name, intervention_type.name AS type_name
        FROM course
        JOIN module ON module.id = course.module_id
        JOIN intervention_type ON intervention_type.id = course.intervention_type_id
        JOIN course_instructor ON course_instructor.course_id = course.id
        WHERE course_instructor.instructor_id = :instructorId";

    if (!empty($dateDebut)) $requete .= " AND course.start_date >= :debut";
    if (!empty($dateFin)) $requete .= " AND course.end_date <= :fin";
    if (!empty($moduleID)) $requete .= " AND course.module_id = :moduleId";

    $requete .= " LIMIT 10 OFFSET :offset";

    $requeteFinal = $connexion->prepare($requete);
    $requeteFinal->bindValue(":instructorId", $instructorId, PDO::PARAM_INT);
    if (!empty($dateDebut)) $requeteFinal->bindValue(":debut", $dateDebut);
    if (!empty($dateFin)) $requeteFinal->bindValue(":fin", $dateFin);
    if (!empty($moduleID)) $requeteFinal->bindValue(":moduleId", $moduleID, PDO::PARAM_INT);
    $requeteFinal->bindValue(":offset", $offSet, PDO::PARAM_INT);

    $requeteFinal->execute();
    $interventions = $requeteFinal->fetchAll(PDO::FETCH_ASSOC);

    $count = $total;
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
            <form action="" method="GET">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <div class="input-box">
                    <label for="dateDebut">Date de début</label>
                    <input type="datetime-local" name="dateDebut" value="<?= htmlspecialchars($dateDebut) ?>">
                </div>
                <div class="input-box">
                    <label for="dateFin">Date de fin</label>
                    <input type="datetime-local" name="dateFin" value="<?= htmlspecialchars($dateFin) ?>">
                </div>
                <div class="input-box">
                    <label for="module">Module</label>
                    <select name="module">
                        <option value="">Selectionnez le module</option>
                        <?php 
                            foreach ($enseignantInfo as $mod){
                                ?>
                                <option value="<?= htmlspecialchars($mod["id"]) ?>" <?= $moduleID == $mod["id"] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mod["name"]) ?>
                                </option>
                                <?php
                            }
                        ?>
                    </select>
                </div>
                <button type="submit" class="filter-btn">Filtrer</button>
            </form>
            <hr>
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
                  $dateDebutObj = new DateTime($inter["start_date"]);
                  $dateFinObj = new DateTime($inter["end_date"]);
                  $dateFormatee = $dateDebutObj->format("d/m/Y");
                  $heureDebut = $dateDebutObj->format("H\hi");
                  $heureFin = $dateFinObj->format("H\hi");

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

        <div class="pagination">
        <?php
        for ($i = 1; $i <= $nbPage; $i++){
        ?>
        <a href="?id=<?= $id ?>&page=<?= $i ?>&dateDebut=<?= urlencode($dateDebut) ?>&dateFin=<?= urlencode($dateFin) ?>&module=<?= urlencode($moduleID) ?>"
           class="pagination-child <?= $i === $page ? 'pagination-select' : '' ?>">
           <?= $i ?>
        </a>
        <?php
        }
        ?>
        </div>

      </main>
    </div>
</body>
</html>