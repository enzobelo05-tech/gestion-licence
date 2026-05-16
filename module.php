<?php
require_once __DIR__ . '/variable-connexion/config.php';

$requete = $connexion->query(
    "SELECT name, hours_count, id, parent_id
     FROM module
     ORDER BY COALESCE(parent_id, id), CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END, id"
);
$tousLesModules = $requete->fetchAll(PDO::FETCH_ASSOC);

function comparerModules(array $gauche, array $droite): int
{
    return (int) $gauche['id'] <=> (int) $droite['id'];
}

$modules = [];

foreach ($tousLesModules as $module) {
    $modules[$module['id']] = $module;
    $modules[$module['id']]['enfants'] = [];
}

$arbre = [];

foreach ($modules as $id => $module) {
    if ($module['parent_id'] === null || $module['parent_id'] === '' || empty($module['parent_id'])) {
        $arbre[] = &$modules[$id];
    } else {
        if (isset($modules[$module['parent_id']])) {
            $modules[$module['parent_id']]['enfants'][] = &$modules[$id];
        }
    }
}

usort($arbre, 'comparerModules');

foreach ($modules as &$module) {
    if (!empty($module['enfants'])) {
        usort($module['enfants'], 'comparerModules');
    }
}

function afficherNoeud(array $module): void {

    $heures = $module['hours_count'];
    ?>
    <li class="modules-node">
        <div class="modules-node-line">
            <span class="modules-node-text">
                <span class="modules-node-name"><?php echo htmlspecialchars($module['name']); ?></span>
                <?php if ($heures !== null && $heures !== ''): ?>
                    <span class="modules-node-hours">(<?php echo (int) $heures; ?>h)</span>
                <?php endif; ?>
            </span>
            <a href="module_form.php?id=<?php echo (int) $module['id']; ?>" class="modules-edit-link" title="Modifier">Modifier</a>
        </div>
        <?php if (!empty($module['enfants'])): ?>
            <ul class="modules-tree-children">
                <?php foreach ($module['enfants'] as $enfant): ?>
                    <?php afficherNoeud($enfant); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modules</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="modulesPage">
    <?php require __DIR__ . "/aside.php"; ?>
    <div class="right-page">
        <header>
            <div class="filAriane">
                <img src="assets/Home.svg" alt="Accueil" />
                <p>></p>
                <a href="">Modules</a>
            </div>
            <hr />
        </header>

        <main>
            <div class="modules-topbar">
                <h2>Modules</h2>
                <a href="module_form.php" class="modules-add-btn">Ajouter un module</a>
            </div>

            <?php if (empty($arbre)) : ?>
                <p class="modules-empty">Aucun module pour le moment.</p>
            <?php else : ?>
                <div class="modules-tree-card">
                    <ul class="modules-tree-root">
                        <?php foreach ($arbre as $module) : ?>
                            <?php afficherNoeud($module); ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>