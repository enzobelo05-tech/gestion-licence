<?php
require_once "variable-connexion/connexion.php";

$idType = $_GET["id"];
?>


<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Interventions - Cours</title>
        <link rel="stylesheet" href="styles.css" />
        <script src="script.js" defer></script>
    </head>
    <body class="ajout-type-intervention">
        <?php require "html-commun/aside-enzo.php" ?>
        <div class="right-page">
            <header>
                <div class="filAriane">
                    <img src="assets/Home.svg" alt="Accueil" />
                    <p>></p>
                    <a href="">Types d'intervention</a>
                    <p>></p>
                    <a href="">Cours</a>
                </div>
                <hr />
            </header>
            <main>
                <div class="top-main">
                    <h2>Cours</h2>
                    <br>
                </div>
                <section class="form-parent">
                    <form action="" method="POST">
                        <div class="input-box">
                            <label for="nom">Nom - Champ obligatoire</label>
                            <input type="text" name="nom" placeholder="Cours" />
                        </div>
                        <div class="input-box">
                            <label for="nom">Code couleur (hexadécimal) - champ obligatoire</label>
                            <input type="text" name="color" placeholder="#6705403" />
                        </div>
                        <div class="input-box">
                            <label for="nom">Description - champ obligatoire</label>
                            <textarea name="description" id="description" placeholder="Cours dispensé par un ou plusieurs intervenants" required></textarea>
                       </div>  
                    <div class="button">
                        <a href="types-intervention.php">Retour à la liste</a>
                        <a href="javascript:void(0)" class="addInter">Supprimer</a>
                        <a href="javascript:void(0)" onclick="this.closest('form').submit();">Enregistrer les informations</a>
                    </div>       
                    </form> 
                </section>
            </main>

            <!--popup-->
            <section class="popUp">
                <p class="close-btn-popUp">x</p>
                <div class="popUp-main">
                    <div class="popUp-header">
                    <img src="assets/plusSymbole.png" alt="Ajouter" class="left">
                    <div class="right">
                        <h2>Supprimer le type d’intervention</h2>
                        <p>Confirmation de l’action</p>
                    </div>
                    </div>
                    <div class="container">
                        <p>Vous vous apprêtez à supprimer le type d’intervention, cette action est irrévoquable. <br>A noter qu’aucune intervention ne doit être liée à ce module pour pouvoir le supprimer.</p>
                        <br>
                        <p>Confirmez-vous l’action ?</p>
                    </div>
                    <div class="button">
                        <a href="" >Annueler</a>
                        <a href="">Confirmez</a>
                    </div> 
                </div>
            </section>
            <div class="overlay"></div>
        </div>
        
    </body>
</html>
