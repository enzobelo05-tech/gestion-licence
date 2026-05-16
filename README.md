# Gestion Licence — Lycée Saint-Vincent

Application web de gestion des enseignements supérieurs du Lycée Saint-Vincent.  
Projet réalisé dans le cadre du BTS SIO option SLAM.

---

## Fonctionnalités

- **Connexion** : authentification par email et mot de passe
- **Calendrier** : visualisation des interventions planifiées
- **Corps enseignant** : liste, ajout et gestion des enseignants et de leurs modules
- **Interventions** : liste paginée, ajout, modification et suppression d'interventions avec filtres
- **Modules** : gestion des modules d'enseignement
- **Types d'intervention** : gestion des catégories d'intervention (cours, TP, TD…)

---

## Stack technique

| Élément | Technologie |
|---|---|
| Langage back-end | PHP (sans framework) |
| Base de données | MySQL via PDO |
| Front-end | HTML, CSS, JavaScript vanilla |
| Multi-select | [TomSelect](https://tom-select.js.org/) (CDN) |
| Serveur local | XAMPP (Windows) / MAMP (Mac) |

---

## Prérequis

- XAMPP (Windows) ou MAMP (Mac) avec Apache + MySQL actifs
- PHP 8.0 ou supérieur
- Base de données MySQL nommée `projet_php`

---

## Installation

**1. Cloner le dépôt**
```bash
git clone https://github.com/enzobelo05-tech/gestion-licence.git
```

Placer le dossier dans :
- Windows : `C:\xampp\htdocs\`
- Mac : `/Applications/MAMP/htdocs/`

**2. Créer la base de données**

Dans phpMyAdmin, créer une base nommée `projet_php` et importer le fichier SQL fourni avec le projet.

**3. Configurer la connexion**

Le fichier `variable-connexion/config.php` détecte automatiquement l'OS :

```php
// Mac (MAMP) → mot de passe "root"
// Windows (XAMPP) → mot de passe vide ""
```

Si ta configuration est différente, modifie les valeurs `$pass` dans ce fichier.

**4. Accéder à l'application**

```
http://localhost/gestion-licence/accueil.php
```

---

## Structure du projet

```
gestion-licence/
│
├── accueil.php                        # Page de connexion
├── calendrier.php                     # Calendrier des interventions
├── corps-enseignant.php               # Liste des enseignants
├── FE-infos-generales.php             # Fiche d'un enseignant (infos + modules)
├── FE-interventions.php               # Interventions d'un enseignant
├── page-intervention.php              # Liste complète des interventions
├── module.php                         # Liste des modules
├── module_form.php                    # Ajout / modification d'un module
├── types-intervention.php             # Liste des types d'intervention
├── voir-fiche-types-intervention.php  # Fiche d'un type d'intervention
│
├── html-commun/
│   ├── aside.php                      # Menu de navigation commun
│   └── aside-enzo.php                 # Variante du menu
│
├── variable-connexion/
│   ├── config.php                     # Connexion PDO à la BDD
│   ├── connexion.php                  # Inclusion de config.php
│   ├── auth.php                       # Vérification de session (garde)
│   └── deconnexion.php                # Destruction de session
│
├── assets/                            # Images et icônes SVG/PNG
├── styles.css                         # Feuille de style globale
└── script.js                          # JavaScript (modales, TomSelect, validation)
```

---

## Base de données

Les principales tables utilisées :

| Table | Description |
|---|---|
| `user` | Utilisateurs (admins et enseignants) |
| `instructor` | Lien entre un user et son rôle d'enseignant |
| `module` | Modules d'enseignement |
| `instructor_module` | Relation enseignant ↔ module |
| `course` | Interventions planifiées |
| `course_instructor` | Relation intervention ↔ enseignant |
| `intervention_type` | Types d'intervention (cours, TP, TD…) |

---

## Équipe

Projet développé en collaboration via GitHub.  
Dépôt : [github.com/enzobelo05-tech/gestion-licence](https://github.com/enzobelo05-tech/gestion-licence)
