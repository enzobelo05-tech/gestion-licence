<?php
    session_start();
    require_once "variable-connexion/config.php";
    

    $erreurHeure = "none";

    $dateActuel = date('Y-m-d H:i:s', strtotime('monday this week'));
    $dateFin = date('Y-m-d H:i:s', strtotime('sunday this week 23:59:59'));

    $requetePage = $connexion->prepare("
    SELECT COUNT(*) AS total
    FROM (
    SELECT course.id AS course_id, start_date, end_date, title, module.name AS module_name, intervention_type.name AS type_name, remotely
    FROM course 
    JOIN module ON module.id = course.module_id 
    JOIN intervention_type ON intervention_type.id = course.intervention_type_id
    JOIN course_instructor ON course_instructor.course_id = course.id
    WHERE start_date >= :dateActuel
    AND end_date <= :dateFin
    GROUP BY course.id
    ORDER BY start_date DESC
    ) AS sub
    ");
    $requetePage->bindParam(":dateActuel", $dateActuel);
    $requetePage->bindParam(":dateFin", $dateFin);

    $requetePage->execute();
    $nbPage = ceil(($requetePage->fetch(PDO::FETCH_ASSOC)["total"]) / 10);
    

    if(isset($_GET["page"])){
        $page = (int)$_GET["page"];
    }else{
        $page = 1;
    }
    $offSet = $page * 10 - 10;

    // Requete intervention de la semaine 

    $requete = $connexion->prepare("
    SELECT c.id AS course_id, c.start_date, c.end_date, c.title, m.name AS module_name, it.name AS type_name, c.remotely,
           GROUP_CONCAT(DISTINCT CONCAT(SUBSTRING(u.first_name, 1, 1), '. ', u.last_name) SEPARATOR ', ') AS intervenants_noms
    FROM course c
    JOIN module m ON m.id = c.module_id 
    JOIN intervention_type it ON it.id = c.intervention_type_id
    LEFT JOIN course_instructor ci ON ci.course_id = c.id
    LEFT JOIN instructor i ON i.id = ci.instructor_id
    LEFT JOIN user u ON u.id = i.user_id
    WHERE c.start_date >= :dateActuel
    AND c.end_date <= :dateFin
    GROUP BY c.id
    ORDER BY c.start_date DESC
    LIMIT 10 
    OFFSET :offset
    ");
    $requete->bindParam(":dateActuel", $dateActuel);
    $requete->bindParam(":dateFin", $dateFin);
    $requete->bindValue(":offset", $offSet, PDO::PARAM_INT);

    $requete->execute();
    $inter = $requete->fetchAll(PDO::FETCH_ASSOC);


    // Requete tous les modules
    $requeteMod = $connexion->prepare("SELECT id, name FROM module");
    $requeteMod->execute();
    $allMod = $requeteMod->fetchAll(PDO::FETCH_ASSOC);

    // Requete tous les type d'interventions
    $requeteType = $connexion->prepare("SELECT id, name FROM intervention_type");
    $requeteType->execute();
    $allType = $requeteType->fetchAll(PDO::FETCH_ASSOC);

    // Requete tous les instructor
    $requeteInstru = $connexion->prepare("
        SELECT instructor.id, user.last_name, user.first_name 
        FROM user 
        JOIN instructor ON instructor.user_id = user.id
        WHERE user.role = 'instructor'
    ");

    $requeteInstru->execute();
    $allInstru = $requeteInstru->fetchAll(PDO::FETCH_ASSOC);

    // Envoie du form
    if ($_SERVER["REQUEST_METHOD"] === "POST"){

        $titre = $_POST["titre"];
        $dateDebut = $_POST["dateDebut"];
        $dateFin = $_POST["dateFin"];
        $module = $_POST["module"];
        $type = $_POST["typeInter"];
        $intervenant = $_POST["intervenant"];
        $remotely = isset($_POST["remotely"]) ? 1 : 0;

        $debut = new DateTime($dateDebut);
        $fin = new DateTime($dateFin);
        $diff = $debut->diff($fin);
        $diffHeures = $diff->h + ($diff->days * 24);

        if ($diffHeures <= 4 && $diffHeures > 0){
    
            $requeteAddCourse = $connexion->prepare("
                INSERT INTO course (title, start_date, end_date, remotely, intervention_type_id, module_id) 
                VALUES (:titre, :dateDebut, :dateFin, :remotely, :type, :module)
            ");
            $requeteAddCourse->bindParam(":titre", $titre);
            $requeteAddCourse->bindParam(":dateDebut", $dateDebut);
            $requeteAddCourse->bindParam(":dateFin", $dateFin);
            $requeteAddCourse->bindParam(":remotely", $remotely);
            $requeteAddCourse->bindParam(":type", $type);
            $requeteAddCourse->bindParam(":module", $module);

            $requeteAddCourse->execute();

            $newCourseId = $connexion->lastInsertId();

            foreach ($intervenant as $int){
                // Lier instructor à la course
                $requeteAddCourseInstructor = $connexion->prepare("
                    INSERT INTO course_instructor (course_id, instructor_id) 
                    VALUES (:courseId, :instruId)
                ");
                $requeteAddCourseInstructor->bindParam(":courseId", $newCourseId);
                $requeteAddCourseInstructor->bindParam(":instruId", $int);

                $requeteAddCourseInstructor->execute();

                // Verif si instructor a deja le module
                $requeteCheckModule = $connexion->prepare("
                    SELECT instructor_id FROM instructor_module 
                    WHERE instructor_id = :instruId AND module_id = :moduleId
                ");
                $requeteCheckModule->bindParam(":instruId", $int);
                $requeteCheckModule->bindParam(":moduleId", $module);

                $requeteCheckModule->execute();

                // Ajout module si non présent
                if ($requeteCheckModule->rowCount() === 0){
                    $requeteAddModule = $connexion->prepare("
                        INSERT INTO instructor_module (instructor_id, module_id) 
                        VALUES (:instruId, :moduleId)
                    ");
                    $requeteAddModule->bindParam(":instruId", $int);
                    $requeteAddModule->bindParam(":moduleId", $module);

                    $requeteAddModule->execute();
                }
            }
            header("Location: calendrier.php?page=" . $page);
            exit();
        }else{
            $erreurHeure = "flex";
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>calendrier</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
</head>
<body class="calendrier">
    <?php require "html-commun/aside.php"; ?>
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
                    $heureDebut = $dateDebut->format("H\hi");
                    $heureFin = $dateFin->format("H\hi");
                    ?>
                    <div class="tableau-child">
                    <p><?= htmlspecialchars($dateFormatee) ?> <br> <?= htmlspecialchars($heureDebut) . " à " . htmlspecialchars($heureFin) ?></p>
                    <p><?= htmlspecialchars($i["module_name"]) ?> / <?= htmlspecialchars($i["title"]) ?></p>
                    <p><?= htmlspecialchars($i["type_name"]) ?></p>
                    <p><?= htmlspecialchars($i["intervenants_noms"] ?? "") ?></p>                        
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
          <div class="pagination">
            <?php  
                for ($i = 1; $i <= $nbPage; $i++){
                    ?>
                    <a href="calendrier.php?page=<?= $i ?>" class="pagination-child <?= $i === $page ? 'pagination-select' : '' ?>"><?= $i ?></a>
                    <?php
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
            <h2>Ajouter une intervention</h2>
            <p>Remplissez les informations ci-dessous</p>
          </div>
        </div>
        <form action="" method="POST" class="formulaire">
          <div class="input-box">
            <label for="titre">Titre</label>
            <input type="text" name="titre" placeholder="Saisissez un titre sur l'intervention" required>
          </div>
          <div class="grid-content">
            <div class="input-box">
                <label for="dateDebut">Date de début - champs obligatoire</label>
                <input type="datetime-local" name="dateDebut" required>
            </div>
            <div class="input-box">
                <label for="dateFin">Date de fin - champs obligatoire</label>
                <input type="datetime-local" name="dateFin" required>
            </div>
            <div class="input-box">
                <label for="module">Module - champs obligatoire</label>
                <select name="module" required>
                    <option value="">Sélectionez le module</option>
                    <?php 
                        foreach ($allMod as $m){
                            ?>
                            <option value="<?= htmlspecialchars($m["id"]) ?>"><?= $m["name"] ?></option>
                            <?php
                        }
                    ?>
                </select>
            </div>
            <div class="input-box">
                <label for="typeInter">Type d'intervention - champs obligatoire</label>
                <select name="typeInter" required>
                    <option value="">Sélectionez le type</option>
                    <?php 
                        foreach ($allType as $t){
                            ?>
                            <option value="<?= htmlspecialchars($t["id"]) ?>"><?= $t["name"] ?></option>
                            <?php
                        }
                    ?>
                </select>
            </div>
          </div>
          <div class="input-box select-form">
              <label for="intervenant[]">Intervenants - champs obligatoire</label>
              <select name="intervenant[]" id="intervenants" multiple required>
                  <?php
                      foreach($allInstru as $in){
                          ?>
                          <option value="<?= $in['id'] ?>"><?= htmlspecialchars($in['last_name']) ?> <?= htmlspecialchars($in['first_name']) ?></option>
                          <?php
                      }
                  ?>
              </select>
          </div>
          <div class="btn-switch">
            <label class="toggleSwitch">
                <input type="checkbox" name="remotely">
                <span class="slider"></span>
            </label>
            <p>Interventions effectuée en visio</p>
          </div>
          <div class="errorTime" style="display: <?= $erreurHeure ?>;">La durée d'intervention doit être inférieur à 4h</div>
          <div class="btn-form">
            <p class="cancel-btn-popUp">Annuler</p>
            <button type="submit" class="confirm-btn">Confirmer</button>
          </div>
        </form>
      </div>
    </section>
    <div class="overlay"></div>
    <script>
        new TomSelect('#intervenants', {
            plugins: ['remove_button'],
        });
    </script>
    <script>
        <?php if($erreurHeure === "flex"){ ?>
            document.querySelector(".popUp").style.display = "flex";
            document.querySelector(".overlay").style.display = "block";
        <?php } ?>
    </script>
</body>
</html>