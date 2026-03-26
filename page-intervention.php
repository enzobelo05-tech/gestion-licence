<?php /*** connexion & requêtes ***/

  require_once "variable-connexion/config.php";

  /*** utilisateur connecté ***/
  $requete = $connexion->prepare("SELECT * FROM user WHERE id = 1");
  $requete->execute();
  $user = $requete->fetch(PDO::FETCH_ASSOC);

  /*** modules pour le filtre ***/
  $requete_modules = $connexion->prepare("SELECT id, name FROM module ORDER BY name ASC");
  $requete_modules->execute();
  $modules = $requete_modules->fetchAll(PDO::FETCH_ASSOC);

  /*** filtres get ***/
  $filtre_date_debut = $_GET['date_debut'] ?? date('d/m/Y') . ' 8:30';
  $filtre_date_fin   = $_GET['date_fin']   ?? date('d/m/Y', strtotime('+16 days')) . ' 8:30';
  $filtre_module     = $_GET['module']     ?? '';

  $sql = "
    SELECT
      c.id,
      c.start_date,
      c.end_date,
      c.title,
      c.remotely,
      m.name          AS module_nom,
      it.name         AS type_nom,
      it.color        AS type_couleur,
      GROUP_CONCAT(
        CONCAT(UPPER(u.last_name), '. ', u.first_name)
        ORDER BY u.last_name ASC
        SEPARATOR ', '
      ) AS intervenants
    FROM course c
    LEFT JOIN module            m   ON c.module_id            = m.id
    LEFT JOIN intervention_type it  ON c.intervention_type_id = it.id
    LEFT JOIN course_instructor ci  ON c.id                   = ci.course_id
    LEFT JOIN instructor        ins ON ci.instructor_id        = ins.id
    LEFT JOIN user              u   ON ins.user_id             = u.id
    WHERE c.start_date <= :date_fin
    AND c.end_date   >= :date_debut
  ";

  $params = [
    ':date_debut' => DateTime::createFromFormat('d/m/Y H:i', $filtre_date_debut)?->format('Y-m-d H:i:s') ?? '',
    ':date_fin'   => DateTime::createFromFormat('d/m/Y H:i', $filtre_date_fin)?->format('Y-m-d H:i:s') ?? '',
  ];

  if ($filtre_module !== '') {
    $sql .= " AND c.module_id = :module_id";
    $params[':module_id'] = $filtre_module;
  }

  $sql .= " GROUP BY c.id ORDER BY c.start_date ASC";

  $requete_courses = $connexion->prepare($sql);
  $requete_courses->execute($params);
  $courses    = $requete_courses->fetchAll(PDO::FETCH_ASSOC);
  $nb_courses = count($courses);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Interventions — Lycée Saint-Vincent</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body class="listeInterventions">

  <!-- /*** sidebar ***/ -->
  <?php include "html-commun/aside.php"; ?>

  <!-- /*** contenu principal ***/ -->
  <div class="right-page">

    <!-- /*** fil d'ariane ***/ -->
    <header>
      <div class="filAriane">
        <a href="accueil.php">
          <img src="assets/Home.svg" alt="Accueil" style="width:16px; vertical-align:middle;" />
        </a>
        <span>›</span>
        <p>Interventions</p>
      </div>
      <hr />
    </header>
    <!-- /*** fin fil d'ariane ***/ -->

    <main>

      <!-- /*** en-tête de page ***/ -->
      <div class="top-main" style="display:flex; flex-direction:row; justify-content:space-between; align-items:center; width:100%; margin-bottom:24px;">
        <h1 style="margin:0;">Interventions</h1>
        <a href="intervention-nouvelle.php" class="bouton-ajouter" >
          Ajouter une nouvelle intervention
        </a>
      </div>
      <!-- /*** fin en-tête de page ***/ -->

      <!-- /*** filtres ***/ -->
      <div class="form-parent">
        <p class="filter-txt">Filtres</p>
        <form method="GET" action="page-intervention.php">
          <div class="input-box">
            <label for="date_debut">Date de début</label>
            <input type="text" id="date_debut" name="date_debut"
                   value="<?= htmlspecialchars($filtre_date_debut) ?>" />
          </div>
          <div class="input-box">
            <label for="date_fin">Date de fin</label>
            <input type="text" id="date_fin" name="date_fin"
                   value="<?= htmlspecialchars($filtre_date_fin) ?>" />
          </div>
          <div class="input-box">
            <label for="module">Module</label>
            <select id="module" name="module">
              <option value="">Sélectionnez le module</option>
              <?php foreach ($modules as $module) : ?>
                <option value="<?= $module['id'] ?>" <?= $module['id'] == $filtre_module ? 'selected' : '' ?>> <?= htmlspecialchars($module['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="bouton-filtrer">Filtrer</button>
        </form>
        <hr />
      </div>
      <!-- /*** fin filtres ***/ -->

      <!-- /*** compteur de résultats ***/ -->
      <div class="interventions-found">
        <h3>
          <?= $nb_courses ?> intervention<?= $nb_courses > 1 ? 's' : '' ?> trouvée<?= $nb_courses > 1 ? 's' : '' ?>
        </h3>
      </div>
      <!-- /*** fin compteur de résultats ***/ -->

      <!-- /*** tableau des interventions ***/ -->
      <div class="tableau">

        <!-- /*** en-têtes du tableau ***/ -->
        <div class="tableau-child">
          <p>Date de l'intervention</p>
          <p>Module &amp; titre</p>
          <p>Type</p>
          <p>Intervenants</p>
          <p>En visio</p>
          <p></p>
        </div>
        <!-- /*** fin en-têtes du tableau ***/ -->

        <!-- /*** lignes du tableau ***/ -->
        <?php if ($nb_courses === 0) : ?>
          <div class="tableau-child">
            <p style="color:#2732409d; padding: 16px 0;">
              Aucune intervention trouvée pour ces critères.
            </p>
          </div>

        <?php else : ?>
          <?php foreach ($courses as $course) : ?>
            <?php
              $debut         = new DateTime($course['start_date']);
              $fin           = new DateTime($course['end_date']);
              $date_formatee = $debut->format('d/m/Y H\hi') . ' à ' . $fin->format('H\hi');
              $type_couleur  = htmlspecialchars($course['type_couleur'] ?? '#6b7a99');
              $visio_src     = $course['remotely'] ? 'assets/visio.png' : 'assets/novisio.png';
              $visio_alt     = $course['remotely'] ? 'Visio activée'    : 'Visio désactivée';
            ?>
            <div class="tableau-child">
              <p><?= htmlspecialchars($date_formatee) ?></p>

              <p>
                <?= htmlspecialchars($course['module_nom'] ?? '—') ?>
                <?php if (!empty($course['title'])) : ?>
                  – <?= htmlspecialchars($course['title']) ?>
                <?php endif; ?>
              </p>

              <p>
                <span class="badge-type"
                      style="background:<?= $type_couleur ?>22; color:<?= $type_couleur ?>;">
                  <?= htmlspecialchars($course['type_nom'] ?? '—') ?>
                </span>
              </p>

              <p><?= htmlspecialchars($course['intervenants'] ?? '—') ?></p>

              <img src="<?= $visio_src ?>" alt="<?= $visio_alt ?>" class="img-visio" />

              <a href="page-intervention.php?id=<?= (int)$course['id'] ?>" class="voirFiche">
                <img src="assets/SeeMore.png" alt="" />
                Accéder à la fiche
              </a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <!-- /*** fin lignes du tableau ***/ -->
      </div>
    </main>
</body>
</html>