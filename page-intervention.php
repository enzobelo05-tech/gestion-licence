<?php
session_start();

// Si l'utilisateur n'est pas connecté, on le renvoie vers la page de connexion
if (!isset($_SESSION['id'])) {
    header('Location: accueil.php');
    exit;
}

require_once "variable-connexion/config.php";

/* ============================================================
 *  Helpers
 * ============================================================ */

/** Convertit un datetime-local HTML5 (ex: "2025-09-01T08:30") en "Y-m-d H:i:s" ou null */
function toMysqlDate(string $value): ?string {
    $value = trim($value);
    if ($value === '') return null;
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $value)
       ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $value);
    return $d ? $d->format('Y-m-d H:i:s') : null;
}

/** Construit une URL de pagination en conservant les filtres GET courants */
function urlPage(int $p): string {
    $params         = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}

/* ============================================================
 *  Données partagées (alimentent à la fois les filtres et les modales)
 * ============================================================ */

$modules      = $connexion->query("SELECT id, name FROM module ORDER BY name ASC")
                          ->fetchAll(PDO::FETCH_ASSOC);
$types        = $connexion->query("SELECT id, name FROM intervention_type ORDER BY name ASC")
                          ->fetchAll(PDO::FETCH_ASSOC);
$intervenants = $connexion->query("
        SELECT ins.id AS instructor_id,
               CONCAT(UPPER(u.last_name), ' ', u.first_name) AS nom_complet
        FROM instructor ins
        JOIN user u ON ins.user_id = u.id
        ORDER BY u.last_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

/* Mapping module_id → liste d'instructor_id (pour le filtrage JS et la validation serveur) */
$mapModuleIntervenants = [];
foreach ($connexion->query("SELECT instructor_id, module_id FROM instructor_module") as $row) {
    $mapModuleIntervenants[(int)$row['module_id']][] = (int)$row['instructor_id'];
}

/* ============================================================
 *  Traitement POST : ajout / modification / suppression
 * ============================================================ */

$erreurs        = [];
$erreur_action  = '';   // pour savoir quelle modale rouvrir si erreur
$post_data      = [];   // pour repré-remplir les champs après une erreur
$post_intervenants = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ----- Suppression ----- */
    if ($action === 'supprimer' && !empty($_POST['id'])) {
        $id = (int) $_POST['id'];
        $connexion->prepare("DELETE FROM course_instructor WHERE course_id = :id")
                  ->execute([':id' => $id]);
        $connexion->prepare("DELETE FROM course WHERE id = :id")
                  ->execute([':id' => $id]);
        header("Location: page-intervention.php?success=1");
        exit;
    }

    /* ----- Ajout / Modification ----- */
    if ($action === 'ajouter' || $action === 'modifier') {
        $id          = $action === 'modifier' ? (int) ($_POST['id'] ?? 0) : null;
        $titre       = trim($_POST['titre'] ?? '');
        $debut_brut  = $_POST['date_debut'] ?? '';
        $fin_brut    = $_POST['date_fin']   ?? '';
        $module_id   = (int) ($_POST['module_id'] ?? 0);
        $type_id     = (int) ($_POST['type_intervention_id'] ?? 0);
        $visio       = isset($_POST['visio']) ? 1 : 0;
        $intervenants_post = array_map('intval', $_POST['intervenants'] ?? []);

        $debut_sql = toMysqlDate($debut_brut);
        $fin_sql   = toMysqlDate($fin_brut);

        /* --- Validations --- */
        if (mb_strlen($titre) > 255) {
            $erreurs[] = "Le titre ne doit pas dépasser 255 caractères.";
        }
        if (!$debut_sql || !$fin_sql) {
            $erreurs[] = "Les dates de début et de fin sont obligatoires.";
        } elseif (strtotime($fin_sql) <= strtotime($debut_sql)) {
            $erreurs[] = "La date de fin doit être supérieure à la date de début.";
        } elseif ((strtotime($fin_sql) - strtotime($debut_sql)) > 4 * 3600) {
            $erreurs[] = "Une intervention ne peut pas dépasser 4h.";
        }
        if ($module_id <= 0) $erreurs[] = "Le module est obligatoire.";
        if ($type_id   <= 0) $erreurs[] = "Le type d'intervention est obligatoire.";
        if (empty($intervenants_post)) {
            $erreurs[] = "Au moins un intervenant est obligatoire.";
        } else {
            $autorises = $mapModuleIntervenants[$module_id] ?? [];
            $hors      = array_diff($intervenants_post, $autorises);
            if (!empty($hors)) {
                $erreurs[] = "Certains intervenants ne sont pas affectés au module sélectionné.";
            }
        }

        if (!empty($erreurs)) {
            // On garde les données saisies pour re-remplir les champs
            $erreur_action     = $action;
            $post_data         = $_POST;
            $post_intervenants = array_map('intval', $_POST['intervenants'] ?? []);
        } else {
            if ($action === 'ajouter') {
                $req = $connexion->prepare("
                    INSERT INTO course (title, start_date, end_date, module_id, intervention_type_id, remotely)
                    VALUES (:titre, :debut, :fin, :module, :type, :visio)
                ");
                $req->execute([
                    ':titre'  => $titre !== '' ? $titre : null,
                    ':debut'  => $debut_sql,
                    ':fin'    => $fin_sql,
                    ':module' => $module_id,
                    ':type'   => $type_id,
                    ':visio'  => $visio,
                ]);
                $id = (int) $connexion->lastInsertId();
            } else {
                $connexion->prepare("
                    UPDATE course
                    SET title = :titre, start_date = :debut, end_date = :fin,
                        module_id = :module, intervention_type_id = :type, remotely = :visio
                    WHERE id = :id
                ")->execute([
                    ':titre'  => $titre !== '' ? $titre : null,
                    ':debut'  => $debut_sql,
                    ':fin'    => $fin_sql,
                    ':module' => $module_id,
                    ':type'   => $type_id,
                    ':visio'  => $visio,
                    ':id'     => $id,
                ]);
                $connexion->prepare("DELETE FROM course_instructor WHERE course_id = :id")
                          ->execute([':id' => $id]);
            }

            $reqIns = $connexion->prepare("
                INSERT INTO course_instructor (course_id, instructor_id) VALUES (:course, :ins)
            ");
            foreach ($intervenants_post as $ins_id) {
                $reqIns->execute([':course' => $id, ':ins' => $ins_id]);
            }

            header("Location: page-intervention.php?success=1");
            exit;
        }
    }
}

/* ============================================================
 *  Filtres GET + pagination
 * ============================================================ */

$filtre_date_debut = $_GET['date_debut'] ?? '';
$filtre_date_fin   = $_GET['date_fin']   ?? '';
$filtre_module     = $_GET['module']     ?? '';

$page    = max(1, (int) ($_GET['page'] ?? 1));
$par_page = 10;
$offset  = ($page - 1) * $par_page;

/* Construction dynamique du WHERE */
$where  = [];
$params = [];
if ($d = toMysqlDate($filtre_date_debut)) {
    $where[]              = "c.end_date >= :date_debut";
    $params[':date_debut'] = $d;
}
if ($f = toMysqlDate($filtre_date_fin)) {
    $where[]            = "c.start_date <= :date_fin";
    $params[':date_fin'] = $f;
}
if ($filtre_module !== '') {
    $where[]              = "c.module_id = :module_id";
    $params[':module_id'] = (int) $filtre_module;
}
$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* Total pour la pagination */
$reqCount = $connexion->prepare("SELECT COUNT(*) FROM course c $sqlWhere");
$reqCount->execute($params);
$nb_courses = (int) $reqCount->fetchColumn();
$nb_pages   = max(1, (int) ceil($nb_courses / $par_page));
$page       = min($page, $nb_pages);
$offset     = ($page - 1) * $par_page;

/* Liste paginée (interventions de la plus récente à la plus ancienne) */
$sql = "
    SELECT
        c.id, c.start_date, c.end_date, c.title, c.remotely,
        c.module_id, c.intervention_type_id,
        m.name        AS module_nom,
        it.name       AS type_nom,
        it.color      AS type_couleur,
        GROUP_CONCAT(
            DISTINCT CONCAT(UPPER(u.last_name), '. ', u.first_name)
            ORDER BY u.last_name ASC SEPARATOR ', '
        ) AS intervenants_noms,
        GROUP_CONCAT(DISTINCT ins.id) AS intervenants_ids
    FROM course c
    LEFT JOIN module            m   ON c.module_id            = m.id
    LEFT JOIN intervention_type it  ON c.intervention_type_id = it.id
    LEFT JOIN course_instructor ci  ON c.id                   = ci.course_id
    LEFT JOIN instructor        ins ON ci.instructor_id        = ins.id
    LEFT JOIN user              u   ON ins.user_id             = u.id
    $sqlWhere
    GROUP BY c.id
    ORDER BY c.start_date DESC
    LIMIT :limit OFFSET :offset
";

$req = $connexion->prepare($sql);
foreach ($params as $k => $v) {
    $req->bindValue($k, $v);
}
$req->bindValue(':limit',  $par_page, PDO::PARAM_INT);
$req->bindValue(':offset', $offset,   PDO::PARAM_INT);
$req->execute();
$courses = $req->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Interventions — Lycée Saint-Vincent</title>
    <link rel="stylesheet" href="styles.css" />
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js" defer></script>
    <script src="script.js" defer></script>
</head>
<body class="listeInterventions">

    <?php include "html-commun/aside.php"; ?>

    <div class="right-page">

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

        <main>
            <div class="top-main">
                <h1>Interventions</h1>
                <button class="bouton-ajouter" id="btn-open-popUp">
                    Ajouter une nouvelle intervention
                </button>
            </div>

            <?php if (isset($_GET['success'])) : ?>
                <div style="background:#efe; border:1px solid #6c6; padding:10px 16px; border-radius:6px; margin-bottom:16px; color:#060;">
                    Enregistrement réussi !
                </div>
            <?php endif; ?>

            <?php if (!empty($erreurs)) : ?>
                <div class="msg-erreurs" style="background:#fee; border:1px solid #f99; padding:10px 16px; border-radius:6px; margin-bottom:16px; color:#c00;">
                    <ul style="margin:0; padding-left:20px;">
                        <?php foreach ($erreurs as $e) : ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="overlay" id="overlay"></div>

            <!-- ============================
                 MODALE AJOUT
            ============================ -->
            <section class="popUp" id="popUp-ajout">
                <p class="close-btn-popUp">x</p>
                <div class="popUp-main">
                    <div class="popUp-header">
                        <img src="assets/plusSymbole.png" alt="Ajouter" class="left" />
                        <div class="right">
                            <h2>Ajouter une intervention</h2>
                            <p>Remplissez les informations ci-dessous.</p>
                        </div>
                    </div>

                    <form action="" method="POST" class="formulaire form-intervention" data-mode="ajouter">
                        <input type="hidden" name="action" value="ajouter" />

                        <div class="input-box full-width">
                            <label>Titre</label>
                            <input type="text" name="titre" maxlength="255"
                                   placeholder="Saisissez un titre sur l'intervention"
                                   value="<?= htmlspecialchars($erreur_action === 'ajouter' ? ($post_data['titre'] ?? '') : '') ?>" />
                        </div>

                        <div class="grid-content">
                            <div class="input-box">
                                <label>Date de début - champ obligatoire</label>
                                <input type="datetime-local" name="date_debut" required
                                       value="<?= htmlspecialchars($erreur_action === 'ajouter' ? ($post_data['date_debut'] ?? '') : '') ?>" />
                            </div>
                            <div class="input-box">
                                <label>Date de fin - champ obligatoire</label>
                                <input type="datetime-local" name="date_fin" required
                                       value="<?= htmlspecialchars($erreur_action === 'ajouter' ? ($post_data['date_fin'] ?? '') : '') ?>" />
                            </div>
                        </div>

                        <div class="grid-content">
                            <div class="input-box">
                                <label>Module - champ obligatoire</label>
                                <select name="module_id" class="select-module" required>
                                    <option value="">Sélectionnez un module</option>
                                    <?php foreach ($modules as $m) : ?>
                                        <option value="<?= $m['id'] ?>"
                                            <?= ($erreur_action === 'ajouter' && (string)$m['id'] === (string)($post_data['module_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="input-box">
                                <label>Type d'intervention - champ obligatoire</label>
                                <select name="type_intervention_id" required>
                                    <option value="">Sélectionnez un type</option>
                                    <?php foreach ($types as $t) : ?>
                                        <option value="<?= $t['id'] ?>"
                                            <?= ($erreur_action === 'ajouter' && (string)$t['id'] === (string)($post_data['type_intervention_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="input-box full-width">
                            <label>Intervenants - champ obligatoire</label>
                            <select name="intervenants[]" class="select-intervenants" multiple required>
                                <?php foreach ($intervenants as $i) : ?>
                                    <option value="<?= $i['instructor_id'] ?>"
                                        <?= ($erreur_action === 'ajouter' && in_array((int)$i['instructor_id'], $post_intervenants)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($i['nom_complet']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-box full-width">
                            <div class="btn-switch">
                                <label class="toggleSwitch">
                                    <input type="checkbox" name="visio" value="1"
                                           <?= ($erreur_action === 'ajouter' && !empty($post_data['visio'])) ? 'checked' : '' ?> />
                                    <span class="slider"></span>
                                </label>
                                <span>Intervention effectuée en visio</span>
                            </div>
                        </div>

                        <div class="btn-form full-width">
                            <p class="cancel-btn-popUp">Annuler</p>
                            <button type="submit" class="confirm-btn">Enregistrer les informations</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- ============================
                 MODALE MODIFICATION
            ============================ -->
            <section class="popUp" id="popUp-edit">
                <p class="close-btn-popUp">x</p>
                <div class="popUp-main">
                    <div class="popUp-header">
                        <img src="assets/plusSymbole.png" alt="Modifier" class="left" />
                        <div class="right">
                            <h2>Modifier l'intervention</h2>
                            <p>Modifiez les informations ci-dessous.</p>
                        </div>
                    </div>

                    <form action="" method="POST" class="formulaire form-intervention" data-mode="modifier">
                        <input type="hidden" name="action" value="modifier" />
                        <input type="hidden" name="id" value="<?= htmlspecialchars($post_data['id'] ?? '') ?>" />

                        <div class="input-box full-width">
                            <label>Titre</label>
                            <input type="text" name="titre" maxlength="255"
                                   value="<?= htmlspecialchars($erreur_action === 'modifier' ? ($post_data['titre'] ?? '') : '') ?>" />
                        </div>

                        <div class="grid-content">
                            <div class="input-box">
                                <label>Date de début - champ obligatoire</label>
                                <input type="datetime-local" name="date_debut" required
                                       value="<?= htmlspecialchars($erreur_action === 'modifier' ? ($post_data['date_debut'] ?? '') : '') ?>" />
                            </div>
                            <div class="input-box">
                                <label>Date de fin - champ obligatoire</label>
                                <input type="datetime-local" name="date_fin" required
                                       value="<?= htmlspecialchars($erreur_action === 'modifier' ? ($post_data['date_fin'] ?? '') : '') ?>" />
                            </div>
                        </div>

                        <div class="grid-content">
                            <div class="input-box">
                                <label>Module - champ obligatoire</label>
                                <select name="module_id" class="select-module" required>
                                    <option value="">Sélectionnez un module</option>
                                    <?php foreach ($modules as $m) : ?>
                                        <option value="<?= $m['id'] ?>"
                                            <?= ($erreur_action === 'modifier' && (string)$m['id'] === (string)($post_data['module_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="input-box">
                                <label>Type d'intervention - champ obligatoire</label>
                                <select name="type_intervention_id" required>
                                    <option value="">Sélectionnez un type</option>
                                    <?php foreach ($types as $t) : ?>
                                        <option value="<?= $t['id'] ?>"
                                            <?= ($erreur_action === 'modifier' && (string)$t['id'] === (string)($post_data['type_intervention_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="input-box full-width">
                            <label>Intervenants - champ obligatoire</label>
                            <select name="intervenants[]" class="select-intervenants" multiple required>
                                <?php foreach ($intervenants as $i) : ?>
                                    <option value="<?= $i['instructor_id'] ?>"
                                        <?= ($erreur_action === 'modifier' && in_array((int)$i['instructor_id'], $post_intervenants)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($i['nom_complet']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-box full-width">
                            <div class="btn-switch">
                                <label class="toggleSwitch">
                                    <input type="checkbox" name="visio" value="1"
                                           <?= ($erreur_action === 'modifier' && !empty($post_data['visio'])) ? 'checked' : '' ?> />
                                    <span class="slider"></span>
                                </label>
                                <span>Intervention effectuée en visio</span>
                            </div>
                        </div>

                        <div class="btn-form full-width">
                            <p class="cancel-btn-popUp">Annuler</p>
                            <button type="button" class="delete-btn" id="btn-delete-edit">Supprimer</button>
                            <button type="submit" class="confirm-btn">Enregistrer les informations</button>
                        </div>
                    </form>

                    <!-- Formulaire dédié à la suppression -->
                    <form action="" method="POST" id="form-delete" style="display:none;">
                        <input type="hidden" name="action" value="supprimer" />
                        <input type="hidden" name="id" value="" />
                    </form>
                </div>
            </section>

            <!-- ============================
                 FILTRES
            ============================ -->
            <div class="form-parent">
                <h3 class="filtres-titre">Filtres</h3>
                <form class="filtres-form" method="GET" action="page-intervention.php">
                    <div class="filtres-field">
                        <label for="date_debut">Date de début</label>
                        <div class="input-icon-wrapper">
                            <input type="datetime-local" id="date_debut" name="date_debut"
                                   value="<?= htmlspecialchars($filtre_date_debut) ?>" />
                        </div>
                    </div>
                    <div class="filtres-field">
                        <label for="date_fin">Date de fin</label>
                        <div class="input-icon-wrapper">
                            <input type="datetime-local" id="date_fin" name="date_fin"
                                   value="<?= htmlspecialchars($filtre_date_fin) ?>" />
                        </div>
                    </div>
                    <div class="filtres-field">
                        <label for="module">Module</label>
                        <select id="module" name="module">
                            <option value="">Tous les modules</option>
                            <?php foreach ($modules as $m) : ?>
                                <option value="<?= $m['id'] ?>"
                                        <?= (string)$m['id'] === (string)$filtre_module ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="filtres-btn">Filtrer</button>
                </form>
                <hr class="filtres-separator" />
            </div>

            <!-- ============================
                 RÉSULTATS
            ============================ -->
            <div class="interventions-found">
                <h3>
                    <?= $nb_courses ?> intervention<?= $nb_courses > 1 ? 's' : '' ?> trouvée<?= $nb_courses > 1 ? 's' : '' ?>
                </h3>
            </div>

            <div class="tableau">
                <div class="tableau-child">
                    <p>Date de l'intervention</p>
                    <p>Module &amp; titre</p>
                    <p>Type</p>
                    <p>Intervenants</p>
                    <p>En visio</p>
                    <p></p>
                </div>

                <?php if ($nb_courses === 0) : ?>
                    <div class="tableau-child">
                        <p style="color:#2732409d; padding: 16px 0;">
                            Aucune intervention trouvée pour ces critères.
                        </p>
                    </div>
                <?php else : foreach ($courses as $c) :
                    $debut         = new DateTime($c['start_date']);
                    $fin           = new DateTime($c['end_date']);
                    $date_formatee = $debut->format('d/m/Y H\hi') . ' à ' . $fin->format('H\hi');
                    $type_couleur  = htmlspecialchars($c['type_couleur'] ?? '#6b7a99');
                    $visio_src     = $c['remotely'] ? 'assets/visio.png' : 'assets/novisio.png';
                    $visio_alt     = $c['remotely'] ? 'Visio activée'    : 'Visio désactivée';
                ?>
                    <div class="tableau-child">
                        <p><?= htmlspecialchars($date_formatee) ?></p>
                        <p>
                            <?= htmlspecialchars($c['module_nom'] ?? '—') ?>
                            <?php if (!empty($c['title'])) : ?>
                                – <?= htmlspecialchars($c['title']) ?>
                            <?php endif; ?>
                        </p>
                        <p>
                            <span class="badge-type"
                                  style="background:<?= $type_couleur ?>22; color:<?= $type_couleur ?>;">
                                <?= htmlspecialchars($c['type_nom'] ?? '—') ?>
                            </span>
                        </p>
                        <p><?= htmlspecialchars($c['intervenants_noms'] ?? '—') ?></p>
                        <img src="<?= $visio_src ?>" alt="<?= $visio_alt ?>" class="img-visio" />
                        <button type="button" class="voirFiche btn-edit-intervention"
                                data-id="<?= (int) $c['id'] ?>"
                                data-titre="<?= htmlspecialchars($c['title'] ?? '', ENT_QUOTES) ?>"
                                data-debut="<?= $debut->format('Y-m-d\TH:i') ?>"
                                data-fin="<?= $fin->format('Y-m-d\TH:i') ?>"
                                data-module="<?= (int) $c['module_id'] ?>"
                                data-type="<?= (int) $c['intervention_type_id'] ?>"
                                data-visio="<?= (int) $c['remotely'] ?>"
                                data-intervenants="<?= htmlspecialchars($c['intervenants_ids'] ?? '', ENT_QUOTES) ?>">
                            <img src="assets/SeeMore.png" alt="" />
                            Accéder à la fiche
                        </button>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- ============================
                 PAGINATION
            ============================ -->
            <?php if ($nb_pages > 1) : ?>
                <div class="pagination">
                    <?php if ($page > 1) : ?>
                        <a href="<?= htmlspecialchars(urlPage($page - 1)) ?>" class="pagination-child">‹</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $nb_pages; $i++) : ?>
                        <a href="<?= htmlspecialchars(urlPage($i)) ?>"
                           class="pagination-child <?= $i === $page ? 'pagination-select' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $nb_pages) : ?>
                        <a href="<?= htmlspecialchars(urlPage($page + 1)) ?>" class="pagination-child">›</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- Données pour le filtrage côté client (intervenants par module) -->
    <script>
        window.MAP_MODULE_INTERVENANTS = <?= json_encode($mapModuleIntervenants, JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <?php if ($erreur_action) : ?>
    <!-- S'il y a eu une erreur de validation, on rouvre la bonne modale automatiquement -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalId = '<?= $erreur_action === 'ajouter' ? 'popUp-ajout' : 'popUp-edit' ?>';
            var modal   = document.getElementById(modalId);
            var overlay = document.getElementById('overlay');
            if (modal)   modal.style.display   = 'flex';
            if (overlay) overlay.style.display = 'block';
        });
    </script>
    <?php endif; ?>
</body>
</html>
