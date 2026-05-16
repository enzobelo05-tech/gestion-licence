<?php
    require_once "variable-connexion/auth.php";
    require_once "variable-connexion/config.php";

    /** Convertit un datetime-local HTML5 en "Y-m-d H:i:s" ou null */
    function toMysqlDateTime(?string $value): ?string {
        if ($value === null || trim($value) === '') return null;
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $value)
           ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $value);
        return $d ? $d->format('Y-m-d H:i:s') : null;
    }

    $id = (int) ($_GET["id"] ?? 0);
    if ($id <= 0) {
        die("ID d'enseignant manquant ou invalide.");
    }

    //* Requete pour les infos du user
    $requeteID = $connexion->prepare("SELECT * FROM user WHERE id = :id");
    $requeteID->bindParam(":id", $id);

    $requeteID->execute();
    $enseignant = $requeteID->fetch(PDO::FETCH_ASSOC);

    //* requete pour le nom et les heures des modules du user
    $requeteModUser = $connexion->prepare("SELECT m.name, m.hours_count, m.id FROM module m JOIN instructor_module im ON m.id = im.module_id JOIN instructor i ON i.id = im.instructor_id WHERE i.user_id = :id ORDER BY m.name ASC");
    $requeteModUser->bindParam(":id", $id);

    $requeteModUser->execute();
    $enseignantInfo = $requeteModUser->fetchAll(PDO::FETCH_ASSOC);

    //* requete pour l'instructor id
    $requeteInstructor = $connexion->prepare("SELECT id FROM instructor WHERE user_id = :id");
    $requeteInstructor->bindParam(":id", $id);
    $requeteInstructor->execute();
    $instructor = $requeteInstructor->fetch(PDO::FETCH_ASSOC);
    $instructorId = $instructor ? (int)$instructor["id"] : 0;

    //* Filtres et Pagination
    $filtre_date_debut = $_GET["dateDebut"] ?? "";
    $filtre_date_fin   = $_GET["dateFin"]   ?? "";
    $filtre_module     = $_GET["module"]    ?? "";

    $page     = max(1, (int) ($_GET["page"] ?? 1));
    $par_page = 10;
    $offset   = ($page - 1) * $par_page;

    /* Construction dynamique du WHERE */
    $where  = ["ci.instructor_id = :instructorId"];
    $params = [':instructorId' => $instructorId];

    if ($d = toMysqlDateTime($filtre_date_debut)) {
        $where[]         = "c.start_date >= :debut";
        $params[':debut'] = $d;
    }
    if ($f = toMysqlDateTime($filtre_date_fin)) {
        $where[]       = "c.end_date <= :fin";
        $params[':fin'] = $f;
    }
    if (!empty($filtre_module)) {
        $where[]             = "c.module_id = :moduleId";
        $params[':moduleId'] = (int)$filtre_module;
    }
    $sqlWhere = "WHERE " . implode(' AND ', $where);

    /* Total pour la pagination */
    $reqCount = $connexion->prepare("SELECT COUNT(DISTINCT c.id) FROM course c JOIN course_instructor ci ON c.id = ci.course_id $sqlWhere");
    $reqCount->execute($params);
    $total = (int) $reqCount->fetchColumn();
    $nbPage = max(1, (int) ceil($total / $par_page));
    $page = min($page, $nbPage);
    $offset = ($page - 1) * $par_page;

    /* Requête principale */
    $sql = "SELECT c.start_date, c.end_date, c.remotely, m.name AS module_name, it.name AS type_name
            FROM course c
            JOIN module m ON m.id = c.module_id
            JOIN intervention_type it ON it.id = c.intervention_type_id
            JOIN course_instructor ci ON ci.course_id = c.id
            $sqlWhere
            GROUP BY c.id
            ORDER BY c.start_date DESC
            LIMIT :limit OFFSET :offset";

    $req = $connexion->prepare($sql);
    // Bind des paramètres du WHERE
    foreach ($params as $key => $value) {
        $req->bindValue($key, $value);
    }
    // Bind des paramètres de pagination
    $req->bindValue(':limit', $par_page, PDO::PARAM_INT);
    $req->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $req->execute();
    $interventions = $req->fetchAll(PDO::FETCH_ASSOC);

    function urlPage(int $p): string {
        $params = $_GET;
        $params['page'] = $p;
        return '?' . http_build_query($params);
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
                <a href="FE-infos-generales.php?id=<?= $id ?>">Informations générales</a>
                <a href="">Interventions</a>
            </div>
            <p class="form-title">Filtrer les interventions</p>
            <form action="" method="GET">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <div class="input-box">
                    <label for="dateDebut">Date de début</label>
                    <input type="datetime-local" name="dateDebut" value="<?= htmlspecialchars($filtre_date_debut) ?>">
                </div>
                <div class="input-box">
                    <label for="dateFin">Date de fin</label>
                    <input type="datetime-local" name="dateFin" value="<?= htmlspecialchars($filtre_date_fin) ?>">
                </div>
                <div class="input-box">
                    <label for="module">Module</label>
                    <select name="module">
                        <option value="">Selectionnez le module</option>
                        <?php 
                            foreach ($enseignantInfo as $mod){
                                ?>
                                <option value="<?= htmlspecialchars($mod["id"]) ?>" <?= (string)$filtre_module === (string)$mod["id"] ? 'selected' : '' ?>>
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
          <h3><?= $total ?> intervention<?= $total > 1 ? 's' : '' ?> trouvée<?= $total > 1 ? 's' : '' ?></h3>
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
        if ($nbPage > 1) {
            for ($i = 1; $i <= $nbPage; $i++){
                ?>
                <a href="<?= htmlspecialchars(urlPage($i)) ?>" class="pagination-child <?= $i === $page ? 'pagination-select' : '' ?>"><?= $i ?></a>
                <?php
            }
        }
        ?>
        </div>

      </main>
    </div>
</body>
</html>