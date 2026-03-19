<?php
  require_once "variable-connexion/connexion.php";

  $requete = $con->prepare("SELECT * FROM user WHERE id = 1");

  $requete->execute();
  $user = $requete->fetch(PDO::FETCH_ASSOC);
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
          <a href="" class="menu-child">
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
          <a href="" class="parametrage-child">
            <img src="assets/Module.svg" alt="Modules" />
            <p>Modules</p>
          </a>
          <a href="" class="parametrage-child">
            <img src="assets/Intervention.svg" alt="Types Interventions" />
            <p>Types d'intervention</p>
          </a>
        </div>
      </div>
    </div>
    <div class="userConnexion">
      <img src="assets/pdpUser-removebg-preview.png" alt="user">
      <div class="userInfo">
        <p><?= htmlspecialchars($user["first_name"]) ?> <?= htmlspecialchars($user["last_name"]) ?>⏷</p>
        <p><?= htmlspecialchars($user["role"]) ?></p>
      </div>
    </div>
  </nav>
</aside>
