<?php
    require_once "variable-connexion/auth.php";
    require_once "variable-connexion/config.php";

    $id = $_GET["id"];
    $msgEnvoie = isset($_GET["success"]) ? "flex" : "none";

    //* requete pour les indos du user
    $requeteID = $connexion->prepare("SELECT * FROM user WHERE id = :id");
    $requeteID->bindParam(":id", $id);

    $requeteID->execute();
    $enseignant = $requeteID->fetch(PDO::FETCH_ASSOC);

    //* requete pour le nom et les heures des modules du user
    $requeteModUser = $connexion->prepare("SELECT module.name, module.hours_count, module.id FROM user JOIN instructor ON instructor.user_id = user.id JOIN instructor_module ON instructor_module.instructor_id = instructor.id JOIN module ON module.id = instructor_module.module_id WHERE user.id = :id");
    $requeteModUser->bindParam(":id", $id);

    $requeteModUser->execute();
    $enseignantInfo = $requeteModUser->fetchAll(PDO::FETCH_ASSOC);

    //* requete pour les id et les noms de tous les modules
    $requeteModAll = $connexion->prepare("SELECT id, name FROM module");
    $requeteModAll->execute();
    $allMod = $requeteModAll->fetchAll(PDO::FETCH_ASSOC);

    //* requete pour l'instructor id
    $requeteInstructor = $connexion->prepare("SELECT id FROM instructor WHERE user_id = :id");
    $requeteInstructor->bindParam(":id", $id);
    $requeteInstructor->execute();
    $instructor = $requeteInstructor->fetch(PDO::FETCH_ASSOC);

    if ($instructor === false) {
        $requeteCreateInstructor = $connexion->prepare("INSERT INTO instructor (user_id) VALUES (:id)");
        $requeteCreateInstructor->bindParam(":id", $id);
        $requeteCreateInstructor->execute();
        $instructorId = $connexion->lastInsertId();
    } else {
        $instructorId = $instructor["id"];
    }

    //* envoie du form
    if ($_SERVER['REQUEST_METHOD'] === 'POST'){

      $module = $_POST["module"] ?? [];
      $nom = $_POST["nom"];
      $prenom = $_POST["prenom"];
      $email = $_POST["email"];

      $requete = $connexion->prepare("UPDATE user SET last_name = :nom, first_name = :prenom, email = :email WHERE id = :id");
      $requete->bindParam(":nom", $nom);
      $requete->bindParam(":prenom", $prenom);
      $requete->bindParam(":email", $email);
      $requete->bindParam(":id", $id);
      
      $requete->execute();

      $requeteDelete = $connexion->prepare("DELETE FROM instructor_module WHERE instructor_id = :id");
      $requeteDelete->bindParam(":id", $instructorId);
      $requeteDelete->execute();

      foreach ($module as $mod){
          $requeteMod = $connexion->prepare("INSERT INTO instructor_module (instructor_id, module_id) VALUES (:id, :mod)");
          $requeteMod->bindParam(":id", $instructorId);
          $requeteMod->bindParam(":mod", $mod);
          
          $requeteMod->execute();
      }
      $msgEnvoie = "flex";
      header("Location: FE-infos-generales.php?id=" . $id . "&success=1");
      exit();
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FE-Informations générales</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
</head>
<body class="infosGenerales">
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
                <a href="">Informations générales</a>
                <a href="FE-interventions.php?id=<?= $id ?>">Interventions</a>
            </div>
            <form action="" method="POST">
                <p>Informations générales</p>
                <div class="input-form">
                    <div class="input-box">
                        <label for="nom">Nom de famille - champs obligatoire</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($enseignant["last_name"]) ?>" required>
                    </div>
                    <div class="input-box">
                        <label for="prenom">Prénom - champs obligatoire</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($enseignant["first_name"]) ?>" required>
                    </div>
                    <div class="input-box">
                        <label for="email">Email - champs obligatoire</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($enseignant["email"]) ?>" required>
                    </div>
                </div>
                <div class="input-box select-form">
                    <label for="module[]">Modules enseignés - champs obligatoire</label>
                    <select name="module[]" id="modules" multiple>
                        <?php
                            $modEnseignant = array_column($enseignantInfo, "id");

                            foreach($allMod as $mod){
                                $selected = "";
                                if(in_array($mod["id"], $modEnseignant)){
                                    $selected = "selected";
                                }
                                ?>
                                <option value="<?= $mod['id'] ?>" <?= $selected ?>><?= htmlspecialchars($mod['name']) ?></option>
                                <?php
                            }
                        ?>
                    </select>
                </div>
                <div style="display: <?= $msgEnvoie ?>;" class="msgEnvoie">Enregistrement réussis</div>
                <button type="submit">Enregistrer les Informations</button>
            </form>
        </section>
      </main>
    </div>
    <script>
        new TomSelect('#modules', {
            plugins: ['remove_button']
        });
    </script>
</body>
</html>