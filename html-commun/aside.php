<?php
  require_once "variable-connexion/connexion.php";

  if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
  }

  $userId = isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 1;

  $requete = $connexion->prepare("SELECT first_name, last_name, role FROM user WHERE id = :id");
  $requete->bindValue(":id", $userId, PDO::PARAM_INT);

  $requete->execute();
  $user = $requete->fetch(PDO::FETCH_ASSOC);
  $nomComplet = trim(($user["last_name"] ?? "") . " " . ($user["first_name"] ?? ""));
?>

<aside class="aside-page">
  <nav>
    <div class="logo">
      <img src="assets/logo.png" alt="Logo Lycée" />
      <div class="logo-container">
        <p>Lycée Saint-Vincent</p>
        <span>Enseignement Supérieur</span>
      </div>
    </div>
    <div class="navigation">
      <div class="menu">
        <p class="nav-title">MENU</p>
        <div class="menu-nav">
          <a href="calendrier.php" class="menu-child">
            <img src="assets/Calendrier.svg" alt="Calendrier" />
            <p>Calendrier</p>
          </a>
          <a href="page-intervention.php" class="menu-child">
            <img src="assets/Intervention.svg" alt="Interventions" />
            <p>Interventions</p>
          </a>
          <a href="corps-enseignant.php" class="menu-child">
            <img src="assets/CorpsEnseignant.svg" alt="Corps Enseignant" />
            <p>Corps enseignant</p>
          </a>
        </div>
      </div>
      <div class="parametrage">
        <p class="nav-title">PARAMETRAGE</p>
        <div class="parametrage-nav">
          <a href="module.php" class="parametrage-child">
            <img src="assets/Module.svg" alt="Modules" />
            <p>Modules</p>
          </a>
          <a href="types-intervention.php" class="parametrage-child">
            <img src="assets/Intervention.svg" alt="Types Interventions" />
            <p>Types d'intervention</p>
          </a>
        </div>
      </div>
    </div>
    <div class="userConnexion">
      <img src="assets/pdpUser-removebg-preview.png" alt="user">
      <div class="userInfo">
        <button type="button" class="userName userDropdownToggle" aria-expanded="false" aria-controls="userDropdownMenu">
          <span><?= htmlspecialchars($nomComplet) ?></span>
          <span class="userArrow">&#8964;</span>
        </button>
        <p><?= htmlspecialchars($user["role"] ?? "") ?></p>
        <div class="userDropdown" id="userDropdownMenu">
          <a href="variable-connexion/deconnexion.php">Déconnexion</a>
        </div>
      </div>
    </div>
  </nav>
</aside>
<script>
  const userDropdownToggle = document.querySelector(".userDropdownToggle");
  const userDropdownMenu = document.querySelector(".userDropdown");

  if (userDropdownToggle && userDropdownMenu) {
    userDropdownToggle.addEventListener("click", function () {
      const isOpen = userDropdownToggle.getAttribute("aria-expanded") === "true";

      userDropdownToggle.setAttribute("aria-expanded", String(!isOpen));
      userDropdownMenu.classList.toggle("is-open", !isOpen);
    });

    document.addEventListener("click", function (event) {
      if (!event.target.closest(".userInfo")) {
        userDropdownToggle.setAttribute("aria-expanded", "false");
        userDropdownMenu.classList.remove("is-open");
      }
    });
  }
</script>
