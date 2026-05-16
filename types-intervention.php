<?php
    require_once "variable-connexion/auth.php";
    require_once "variable-connexion/connexion.php";

    $count = 0;
    $typeIntervention = [];

    // Ajout popup
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
        $nom         = trim($_POST['nom_type']);
        $description = trim($_POST['description']); // TRIM enlève les espaces inutiles
        $color       = trim($_POST['color']);

        $requete = $connexion->prepare("INSERT INTO intervention_type (name, description, color) VALUES (:nom, :description, :color)");
        $requete->bindParam(':nom', $nom);
        $requete->bindParam(':description', $description);
        $requete->bindParam(':color', $color);
        $requete->execute();
        header("Location: " . $_SERVER['PHP_SELF']); // évite de revalider le form en cas de rechargement de page.
        exit;
    }

    $nomFiltre = isset($_GET['nom']) ? trim($_GET['nom']) : '';
    $nomP = $nomFiltre . '%';

    $requetePage = $connexion->prepare("SELECT COUNT(*) AS total FROM intervention_type WHERE name LIKE :nom");
    $requetePage->bindParam(":nom", $nomP);
    $requetePage->execute();
    $nbPage = ceil($requetePage->fetch(PDO::FETCH_ASSOC)["total"] / 10);

    $page   = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
    $offSet = $page * 10 - 10;

    $requete = $connexion->prepare(
        "SELECT id, name, description, color 
         FROM intervention_type 
         WHERE name LIKE :nom
         ORDER BY name ASC
         LIMIT 10 OFFSET :offset"
        
    );
    $requete->bindParam(":nom", $nomP);
    $requete->bindParam(":offset", $offSet, PDO::PARAM_INT);
    $requete->execute();
    $typeIntervention = $requete->fetchAll(PDO::FETCH_ASSOC);
    $count = count($typeIntervention);
?>

<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Interventions</title>
        <link rel="stylesheet" href="styles.css" />
        <script src="script.js" defer></script>
    </head>
    <body class="type-intervention">
        <?php require "html-commun/aside.php" ?>
        <div class="right-page">
            <header>
                <div class="filAriane">
                    <img src="assets/Home.svg" alt="Accueil" />
                    <p>></p>
                    <a href="">Types d'intervention</a>
                </div>
                <hr />
            </header>
            <main>
                <div class="top-main">
                    <h2>Types d'intervention</h2>
                    <a href="javascript:void(0)" class="addInter">Ajouter un type</a>
                </div>
                <section class="form-parent">
                    <p class="filter-txt">Filtre</p>
                    <!-- GET pour conserver le filtre lors des changements de page -->
                    <form action="" method="GET">
                        <div class="input-box">
                            <label for="nom">Nom</label>
                            <input
                                type="text"
                                name="nom"
                                placeholder="Saisissez le nom"
                                value="<?= htmlspecialchars($nomFiltre) ?>"
                            />
                        </div>
                        <button type="submit">Filtrer</button>
                    </form>
                    <hr />
                </section>
                <section class="enseignant-found">
                    <h3><?= $count ?> type(s)</h3>
                    <div class="tableau">
                        <div class="tableau-child">
                            <p>Nom</p>
                            <p>Description</p>
                            <p>Couleur</p>
                        </div>
                        <?php foreach ($typeIntervention as $e) { ?>
                            <div class="tableau-child">
                                <p><?= htmlspecialchars($e["name"]) ?></p>
                                <p><?= htmlspecialchars($e["description"]) ?></p>
                                <p style="color: <?= htmlspecialchars($e["color"]) ?>;">
                                    <?= htmlspecialchars($e["color"]) ?>
                                </p>
                                <div class="voirFiche">
                                    <img src="assets/SeeMore.png" alt="Voir plus" />
                                    <a href="voir-fiche-types-intervention.php?id=<?= htmlspecialchars($e['id']) ?>">
                                        Accéder à la fiche
                                    </a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </section>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $nbPage; $i++) { ?>
                        <a
                            href="types-intervention.php?page=<?= $i ?>&nom=<?= urlencode($nomFiltre) ?>"
                            class="pagination-child <?= $i === $page ? 'pagination-select' : '' ?>"
                        ><?= $i ?></a>
                    <?php } ?>
                </div>
            </main>
        </div>

        <section class="popUp">
            <p class="close-btn-popUp">x</p>
            <div class="popUp-main">
                <div class="popUp-header">
                    <img src="assets/plusSymbole.png" alt="Ajouter" class="left" />
                    <div class="right">
                        <h2>Ajouter une intervention</h2>
                        <p>Remplissez les informations ci-dessous</p>
                    </div>
                </div>
                <form action="" method="POST" class="formulaire">
                    <input type="hidden" name="action" value="ajouter" />
                    <div class="input-box">
                        <label for="titre">Nom - Champ obligatoire</label>
                        <input type="text" name="nom_type" placeholder="Cours" required />
                    </div>
                    <br />
                    <div class="input-box">
                        <label for="description">Description - Champ obligatoire</label>
                        <input type="text" name="description" placeholder="Cours dispensé par..." required />
                    </div>
                    <br />
                    <div class="input-box">
                        <label for="color_hex">Couleur</label>
                        <div class="color-input">
                            <input
                                type="color"
                                id="color_picker"
                                name="color_picker"
                                value="#2c416e"
                                oninput="document.getElementById('color_hex').value = this.value"
                                required
                            />
                            <input
                                type="text"
                                id="color_hex"
                                name="color"
                                placeholder="#2c416e"
                                maxlength="7"
                                pattern="^#([A-Fa-f0-9]{6})$"
                                oninput="if(/^#[A-Fa-f0-9]{6}$/.test(this.value)) document.getElementById('color_picker').value = this.value"
                                required
                            />
                        </div>
                    </div>
                    <br />
                    <div class="btn-form">
                        <p class="cancel-btn-popUp">Annuler</p>
                        <button type="submit" class="confirm-btn">Confirmer</button>
                    </div>
                </form>
            </div>
        </section>

        <div class="overlay"></div>
    </body>
</html>