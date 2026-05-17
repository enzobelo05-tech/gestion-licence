<?php
require_once "variable-connexion/config.php";

$isEditMode = isset($_GET['id']) && ctype_digit($_GET['id']);
$moduleId = $isEditMode ? (int) $_GET['id'] : null;

$errors = [];
$deleteErrors = [];
$showDeleteModal = false;
$moduleData = [
    'code' => '',
    'name' => '',
    'description' => '',
    'hours_count' => '',
    'parent_id' => '',
    'capstone_project' => 0,
];

function redirectToModules(): void
{
    header('Location: module.php');
    exit();
}

$parentsStmt = $connexion->query('SELECT id, name FROM module ORDER BY name ASC');
$parentModules = $parentsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($isEditMode) {
    $stmt = $connexion->prepare('SELECT * FROM module WHERE id = :id');
    $stmt->bindValue(':id', $moduleId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        redirectToModules();
    }

    $moduleData = [
        'code' => $row['code'] ?? '',
        'name' => $row['name'] ?? '',
        'description' => $row['description'] ?? '',
        'hours_count' => (string) ($row['hours_count'] ?? ''),
        'parent_id' => $row['parent_id'] === null ? '' : (string) $row['parent_id'],
        'capstone_project' => (int) ($row['capstone_project'] ?? 0),
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete' && $isEditMode) {
        $showDeleteModal = true;

        $childrenStmt = $connexion->prepare('SELECT COUNT(*) FROM module WHERE parent_id = :id');
        $childrenStmt->bindValue(':id', $moduleId, PDO::PARAM_INT);
        $childrenStmt->execute();
        if ((int) $childrenStmt->fetchColumn() > 0) {
            $deleteErrors[] = 'Impossible de supprimer : ce module a des sous-modules.';
        }

        $coursesStmt = $connexion->prepare('SELECT COUNT(*) FROM course WHERE module_id = :id');
        $coursesStmt->bindValue(':id', $moduleId, PDO::PARAM_INT);
        $coursesStmt->execute();
        if ((int) $coursesStmt->fetchColumn() > 0) {
            $deleteErrors[] = 'Impossible de supprimer : des interventions sont liées à ce module.';
        }

        if (empty($deleteErrors)) {
            $deleteInstructorModules = $connexion->prepare('DELETE FROM instructor_module WHERE module_id = :id');
            $deleteInstructorModules->bindValue(':id', $moduleId, PDO::PARAM_INT);
            $deleteInstructorModules->execute();

            $deleteModule = $connexion->prepare('DELETE FROM module WHERE id = :id');
            $deleteModule->bindValue(':id', $moduleId, PDO::PARAM_INT);
            $deleteModule->execute();

            redirectToModules();
        }
    } else {
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

        if ($moduleData['hours_count'] === '') {
            $errors['hours_count'] = 'Le nombre d\'heures est obligatoire.';
            $hoursCount = null;
        } elseif (!ctype_digit($moduleData['hours_count'])) {
            $errors['hours_count'] = 'Le nombre d\'heures doit être un entier positif.';
            $hoursCount = null;
        } else {
            $hoursCount = (int) $moduleData['hours_count'];
        }

        $parentId = null;
        if ($moduleData['parent_id'] !== '') {
            if (!ctype_digit($moduleData['parent_id'])) {
                $errors['parent_id'] = 'Le parent sélectionné est invalide.';
            } else {
                $parentId = (int) $moduleData['parent_id'];
                if ($isEditMode && $parentId === $moduleId) {
                    $errors['parent_id'] = 'Un module ne peut pas être son propre parent.';
                } else {
                    $checkParent = $connexion->prepare('SELECT id FROM module WHERE id = :id');
                    $checkParent->bindValue(':id', $parentId, PDO::PARAM_INT);
                    $checkParent->execute();
                    if (!$checkParent->fetch()) {
                        $errors['parent_id'] = 'Le parent sélectionné n\'existe pas.';
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
                $errors['code'] = 'Ce code existe déjà.';
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
                $errors['name'] = 'Ce nom existe déjà.';
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
            $stmt->bindValue(':hours_count', $hoursCount);
            $stmt->bindValue(':parent_id', $parentId, $parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':capstone_project', (int) $moduleData['capstone_project'], PDO::PARAM_INT);
            $stmt->execute();

            redirectToModules();
        }
    }
}

$pageTitle = $isEditMode && $moduleData['name'] !== '' ? $moduleData['name'] : 'Ajouter un module';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="modulesPage moduleFormPage">
    <?php require_once "html-commun/aside.php"; ?>
    <div class="right-page">
        <header>
            <div class="filAriane">
                <img src="assets/Home.svg" alt="Accueil" />
                <p>></p>
                <a href="module.php">Modules</a>
                <p>></p>
                <a href=""><?php echo htmlspecialchars($pageTitle); ?></a>
            </div>
            <hr />
        </header>

        <main class="module-form-main">
            <h2><?php echo htmlspecialchars($pageTitle); ?></h2>

            <?php if (!empty($errors)) : ?>
                <div class="module-alert module-alert-error">
                    <?php foreach ($errors as $error) : ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="module-form">
                <input type="hidden" name="action" value="save">

                <div class="module-form-grid">
                    <div class="module-field">
                        <label for="code">Code - champ obligatoire</label>
                        <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($moduleData['code']); ?>" required>
                    </div>

                    <div class="module-field">
                        <label for="name">Nom - champ obligatoire</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($moduleData['name']); ?>" required>
                    </div>

                    <div class="module-field">
                        <label for="hours_count">Nombre d'heures - champ obligatoire</label>
                        <input type="number" id="hours_count" name="hours_count" min="0" value="<?php echo htmlspecialchars($moduleData['hours_count']); ?>" required>
                    </div>

                    <div class="module-field">
                        <label for="parent_id">Parent</label>
                        <select id="parent_id" name="parent_id">
                            <option value="">Aucun</option>
                            <?php foreach ($parentModules as $parent) : ?>
                                <?php if ($isEditMode && (int) $parent['id'] === $moduleId) { continue; } ?>
                                <option value="<?php echo (int) $parent['id']; ?>" <?php echo ((string) $parent['id'] === (string) $moduleData['parent_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="module-field module-field-full">
                    <label for="description">Description - champ obligatoire</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($moduleData['description']); ?></textarea>
                </div>

                <label class="module-switch-row">
                    <span class="module-switch">
                        <input type="checkbox" name="capstone_project" value="1" <?php echo !empty($moduleData['capstone_project']) ? 'checked' : ''; ?>>
                        <span></span>
                    </span>
                    <span>Module effectué sur le projet fil rouge</span>
                </label>

                <div class="module-form-actions">
                    <a class="module-btn module-btn-secondary" href="module.php">Retour à la liste</a>
                    <?php if ($isEditMode) : ?>
                        <button type="button" class="module-btn module-btn-danger" id="openDeleteModal">Supprimer</button>
                    <?php endif; ?>
                    <button type="submit" class="module-btn module-btn-primary">Enregistrer les informations</button>
                </div>
            </form>
        </main>
    </div>

    <?php if ($isEditMode) : ?>
        <div class="module-modal <?php echo $showDeleteModal ? 'is-open' : ''; ?>" id="deleteModal" aria-hidden="<?php echo $showDeleteModal ? 'false' : 'true'; ?>">
            <div class="module-modal-backdrop" data-close-modal></div>
            <div class="module-modal-content" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
                <button type="button" class="module-modal-close" data-close-modal aria-label="Fermer">&times;</button>

                <div class="module-modal-heading">
                    <div class="module-modal-icon">&times;</div>
                    <div>
                        <h3 id="deleteModalTitle">Supprimer le module</h3>
                        <p>Confirmation de l'action</p>
                    </div>
                </div>

                <div class="module-modal-body">
                    <p>Vous vous apprêtez à supprimer le module, cette action est irrévocable.</p>
                    <p>A noter qu'aucune intervention ne doit être liée à ce module pour pouvoir le supprimer.</p>
                    <p class="module-modal-question">Confirmez-vous l'action ?</p>

                    <?php if (!empty($deleteErrors)) : ?>
                        <div class="module-alert module-alert-error">
                            <?php foreach ($deleteErrors as $error) : ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" class="module-modal-actions">
                    <input type="hidden" name="action" value="delete">
                    <button type="button" class="module-btn module-btn-secondary" data-close-modal>Annuler</button>
                    <button type="submit" class="module-btn module-btn-danger">Confirmer</button>
                </form>
            </div>
        </div>

        <script>
            const deleteModal = document.getElementById("deleteModal");
            const openDeleteModal = document.getElementById("openDeleteModal");
            const closeDeleteModalButtons = document.querySelectorAll("[data-close-modal]");

            function setDeleteModalState(isOpen) {
                deleteModal.classList.toggle("is-open", isOpen);
                deleteModal.setAttribute("aria-hidden", String(!isOpen));
            }

            if (deleteModal && openDeleteModal) {
                openDeleteModal.addEventListener("click", function () {
                    setDeleteModalState(true);
                });

                closeDeleteModalButtons.forEach(function (button) {
                    button.addEventListener("click", function () {
                        setDeleteModalState(false);
                    });
                });
            }
        </script>
    <?php endif; ?>
</body>
</html>
