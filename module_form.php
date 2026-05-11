<?php
require_once __DIR__ . '/variable-connexion/config.php';

$isEditMode = isset($_GET['id']) && ctype_digit($_GET['id']);
$moduleId = $isEditMode ? (int) $_GET['id'] : null;
$currentParentId = null;

$errors = [];
$moduleData = [
    'code' => '',
    'name' => '',
    'description' => '',
    'hours_count' => '',
    'parent_id' => '',
    'capstone_project' => 0,
];

$parentsStmt = $connexion->query('SELECT id, name FROM module ORDER BY name ASC');
$parentModules = $parentsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($isEditMode) {
    $stmt = $connexion->prepare('SELECT * FROM module WHERE id = :id');
    $stmt->bindValue(':id', $moduleId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        header('Location: module.php');
        exit();
    }

    $moduleData = [
        'code' => $row['code'] ?? '',
        'name' => $row['name'] ?? '',
        'description' => $row['description'] ?? '',
        'hours_count' => (string) ($row['hours_count'] ?? ''),
        'parent_id' => $row['parent_id'] === null ? '' : (string) $row['parent_id'],
        'capstone_project' => (int) ($row['capstone_project'] ?? 0),
    ];
    $currentParentId = $row['parent_id'] === null ? null : (int) $row['parent_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleData['code'] = trim($_POST['code'] ?? '');
    $moduleData['name'] = trim($_POST['name'] ?? '');
    $moduleData['description'] = trim($_POST['description'] ?? '');
    $moduleData['hours_count'] = trim($_POST['hours_count'] ?? '');
    $moduleData['parent_id'] = trim($_POST['parent_id'] ?? '');
    $moduleData['capstone_project'] = isset($_POST['capstone_project']) ? 1 : 0;

    if ($moduleData['code'] === '') {
        $errors['code'] = 'Le code est obligatoire.';
    }

    if ($moduleData['name'] === '') {
        $errors['name'] = 'Le nom est obligatoire.';
    }

    if ($moduleData['description'] === '') {
        $errors['description'] = 'La description est obligatoire.';
    }

    $hoursCount = null;
    if ($moduleData['hours_count'] !== '') {
        if (!ctype_digit($moduleData['hours_count'])) {
            $errors['hours_count'] = 'Le nombre d\'heures doit etre un entier positif.';
        } else {
            $hoursCount = (int) $moduleData['hours_count'];
        }
    }

    $parentId = null;
    if ($moduleData['parent_id'] !== '') {
        if (!ctype_digit($moduleData['parent_id'])) {
            $errors['parent_id'] = 'Le parent selectionne est invalide.';
        } else {
            $parentId = (int) $moduleData['parent_id'];
            if ($isEditMode && $parentId === $moduleId) {
                $errors['parent_id'] = 'Un module ne peut pas etre son propre parent.';
            } else {
                $checkParent = $connexion->prepare('SELECT id FROM module WHERE id = :id');
                $checkParent->bindValue(':id', $parentId, PDO::PARAM_INT);
                $checkParent->execute();
                if (!$checkParent->fetch()) {
                    $errors['parent_id'] = 'Le parent selectionne n\'existe pas.';
                }
            }
        }
    }

    if (!isset($errors['code'])) {
        $sql = 'SELECT id FROM module WHERE code = :code';
        if ($isEditMode) {
            $sql .= ' AND id != :id';
        }
        $checkCode = $connexion->prepare($sql);
        $checkCode->bindValue(':code', $moduleData['code']);
        if ($isEditMode) {
            $checkCode->bindValue(':id', $moduleId, PDO::PARAM_INT);
        }
        $checkCode->execute();
        if ($checkCode->fetch()) {
            $errors['code'] = 'Ce code existe deja.';
        }
    }

    if (!isset($errors['name'])) {
        $sql = 'SELECT id FROM module WHERE name = :name';
        if ($isEditMode) {
            $sql .= ' AND id != :id';
        }
        $checkName = $connexion->prepare($sql);
        $checkName->bindValue(':name', $moduleData['name']);
        if ($isEditMode) {
            $checkName->bindValue(':id', $moduleId, PDO::PARAM_INT);
        }
        $checkName->execute();
        if ($checkName->fetch()) {
            $errors['name'] = 'Ce nom existe deja.';
        }
    }

    if (empty($errors)) {
        if ($isEditMode) {
            $stmt = $connexion->prepare('UPDATE module SET code = :code, name = :name, description = :description, hours_count = :hours_count, parent_id = :parent_id, capstone_project = :capstone_project WHERE id = :id');
            $stmt->bindValue(':id', $moduleId, PDO::PARAM_INT);
        } else {
            $stmt = $connexion->prepare('INSERT INTO module (code, name, description, hours_count, parent_id, capstone_project) VALUES (:code, :name, :description, :hours_count, :parent_id, :capstone_project)');
        }

        $stmt->bindValue(':code', $moduleData['code']);
        $stmt->bindValue(':name', $moduleData['name']);
        $stmt->bindValue(':description', $moduleData['description']);
        $stmt->bindValue(':hours_count', $hoursCount, $hoursCount === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':parent_id', $parentId, $parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':capstone_project', (int) $moduleData['capstone_project'], PDO::PARAM_INT);
        $stmt->execute();

        header('Location: module.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEditMode ? 'Modifier le module' : 'Ajouter un module'; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f6fa; margin: 0; }
        .wrap { max-width: 760px; margin: 30px auto; background: #fff; border: 1px solid #d8dce8; border-radius: 8px; padding: 24px; }
        h1 { margin-top: 0; font-size: 24px; }
        .row { margin-bottom: 14px; }
        label { display: block; font-size: 13px; margin-bottom: 6px; color: #333; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1px solid #cfd4e0; border-radius: 6px; box-sizing: border-box; }
        textarea { min-height: 110px; }
        .error { color: #c0392b; font-size: 12px; margin-top: 4px; }
        .actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn { border: 0; border-radius: 6px; padding: 10px 14px; text-decoration: none; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3b5bdb; color: #fff; }
        .btn-secondary { background: #eef0f5; color: #333; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1><?php echo $isEditMode ? 'Modifier le module' : 'Ajouter un module'; ?></h1>
        <form method="POST">
            <div class="row">
                <label for="code">Code</label>
                <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($moduleData['code']); ?>" required>
                <?php if (isset($errors['code'])): ?><div class="error"><?php echo htmlspecialchars($errors['code']); ?></div><?php endif; ?>
            </div>
            <div class="row">
                <label for="name">Nom</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($moduleData['name']); ?>" required>
                <?php if (isset($errors['name'])): ?><div class="error"><?php echo htmlspecialchars($errors['name']); ?></div><?php endif; ?>
            </div>
            <div class="row">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($moduleData['description']); ?></textarea>
                <?php if (isset($errors['description'])): ?><div class="error"><?php echo htmlspecialchars($errors['description']); ?></div><?php endif; ?>
            </div>
            <div class="row">
                <label for="hours_count">Nombre d'heures (optionnel)</label>
                <input type="number" id="hours_count" name="hours_count" min="0" value="<?php echo htmlspecialchars($moduleData['hours_count']); ?>">
                <?php if (isset($errors['hours_count'])): ?><div class="error"><?php echo htmlspecialchars($errors['hours_count']); ?></div><?php endif; ?>
            </div>
            <div class="row">
                <label for="parent_id">Module parent (optionnel)</label>
                <select id="parent_id" name="parent_id">
                    <option value="">Aucun</option>
                    <?php foreach ($parentModules as $parent): ?>
                        <?php if ($isEditMode && (int) $parent['id'] === $moduleId) { continue; } ?>
                        <option value="<?php echo (int) $parent['id']; ?>" <?php echo ((string) $parent['id'] === (string) $moduleData['parent_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($parent['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['parent_id'])): ?><div class="error"><?php echo htmlspecialchars($errors['parent_id']); ?></div><?php endif; ?>
            </div>
            <div class="row">
                <label>
                    <input type="checkbox" name="capstone_project" value="1" <?php echo !empty($moduleData['capstone_project']) ? 'checked' : ''; ?>>
                    Fil rouge
                </label>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a class="btn btn-secondary" href="module.php">Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>
