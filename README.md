# Root-Me PRO — Test technique : Développement web

Stack : Laravel / Inertia.js / Vue.js

## Objectif du test

Ce test évalue vos compétences en **développement web full stack** dans un environnement **Laravel (v12) + Inertia.js (v2) + Vue.js (v3)**.\
Vous travaillerez sur une application existante afin de **mettre en œuvre des fonctionnalités concrètes, structurer votre code et justifier vos choix techniques**.

Durée indicative : entre **3 et 6 heures**.

## Installation du projet

### Pré-requis

Assurez-vous d’avoir installé :

- PHP >= 8.5
- Composer
- Node.js >= 24
- NPM >= 11.6.2

### Étapes d’installation

1. Cloner le repository du projet
2. Installer les dépendances et initialiser l’environnement :
    ```
    composer run setup
    ```
    Ce script :
    - installe les dépendances PHP et JavaScript
    - configure le fichier .env
    - et génère la clé d’application Laravel
3. Démarrer le serveur de développement :
    ```
    composer run dev
    ```
    Vous pouvez vous rendre sur l'application à l'adresse : http://localhost:8000.

## Présentation du projet

L’application est un **gestionnaire de tâches par équipe**.\
Elle contient **3 équipes et 6 utilisateurs**. Chaque utilisateur appartient à une équipe.\
Des comptes de test sont disponibles :
| Utilisateur | E-mail | Mot de passe |
| ----------- | ------------------- | ------------ |
| User 1 | test1@example.com | password |
| User 2 | test2@example.com | password |
| ... | test{N}@example.com | password |
| User 6 | test6@example.com | password |

Vous pouvez également créer votre propre compte.

## Tâches à réaliser

Réalisez les tâches dans l’ordre indiqué.\
Chaque fonctionnalité doit être **fonctionnelle, testable, et conforme au style du projet existant**.

### 1. Gestion de l’appartenance à une équipe

- Créer un **middleware** pour vérifier si l’utilisateur est rattaché à une équipe
- Si ce n’est pas le cas, rediriger vers une **page de sélection d’équipe**
- Créer la page correspondante avec un formulaire permettant de rejoindre une équipe existante

**Critère de validation :** un utilisateur sans équipe ne peut pas accéder aux pages "Dashboard" et "Tasks".

### 2. CRUD des tâches

Implémenter un CRUD complet pour la liste des tâches de l’équipe :

- Afficher uniquement les tâches **de l’équipe de l’utilisateur**
- Permettre la **modification** d’une tâche (titre, date d'échéance, statut "completed")
- Permettre la **suppression** d’une tâche

_La création d'une tâche a déjà été implémentée pour vous faire gagner du temps._

**Critère de validation :** les actions CRUD doivent fonctionner via Inertia.js (sans rechargement complet de la page).

### 3. Ajout du champ `created_by`

- Ajouter un champ `created_by` dans le modèle et la table `tasks`
- Lier cette donnée à l’utilisateur actuellement connecté lors de la création de la tâche
- Afficher le nom de l'utilisateur sur chaque tâche

### 4. Job de mise à jour du compteur

- Créer un **Job** qui s’exécute dans la **queue** (déjà configurée)
- Lorsqu’une tâche est marquée comme terminée, ce job doit **incrémenter** la colonne `count_completed_tasks` de l’équipe correspondante

## Rendu attendu

Vos modifications doivent être **enregistrées dans le repository local**.\
N’envoyez pas le code sur un dépôt distant (GitHub, GitLab…).

## Justification technique

Renseignez ici vos **choix techniques**.\
Ex : architecture, gestion des états, optimisation, sécurité, performance, etc.

### Backend

- Mise en place des Form Requests pour une validation centralisée (`TaskCreateRequest`, `TaskUpdateRequest`, `TeamJoinRequest`)
- Création d'un alias (`hasTeam`) pour le middleware pour une utilisation plus simple dans les routes
- Le job `IncrementTeamCompletedTasks` se déclenche lorsque le champ `completed` passe de false à true (j'aurais pu aller plus loin en gérant le cas où on repasse de true à false, mais j'ai préféré rester simple)
- Vérification systématique de l'appartenance à l'équipe avant update/delete d'une task, en renvoyant une erreur 403 si non autorisé
- Utilisation d'une pagination (5 éléments/page) pour limiter la charge
- Queue jobs asynchrones pour ne pas bloquer les réponses

**Tests**

- `TaskTest` : 11 tests (CRUD, relations, autorisations)
- `JoinTeamTest` : 5 tests (middleware, sélection équipe)
- `IncrementTeamCompletedTasksTest` : 2 tests (job queue)
- Génération des factories pour la création des données pour les tests

### Frontend

- Création de composants réutilisables : `TaskCreateDialog`, `TaskDeleteDialog`, `TaskEditDialog`, etc.. (dans le cadre d'un vrai projet, j'aurais pu aller plus loin en faisant un composant Form commun aux dialogues de création et d'édition, que j'aurais pu utiliser pas seulement pour les tâches mais aussi pour d'autres pages, idem pour les dialogues de suppression)
- Composants métier organisés par domaine `tasks/TaskItem`, `tasks/TaskEditDialog`
- Toast notifications (vue-sonner) pour un feedback utilisateur l'orsqu'une action est effectuée (création, modification, suppression)
- Modal de confirmation pour actions dangereuses pour éviter les erreurs

#### Améliorations possibles (non implémentées) :

- Utilisation de Resource pour formater les réponses API
- Déplacement de la logique métier des contrôleurs vers des services dédiés
- Ajout de tests frontend
- Mise en place d'un système de cache pour les requêtes fréquentes exemple : la liste des tâches
- Dockerisation de l'application pour une installation et déploiement plus simple

## Analyse du code existant

Expliquez dans cette section votre **évaluation du code fourni** :

- Forces et bonnes pratiques observées :
    - Backend :
        - Utilisation des Form Requests pour la validation
        - Routes organisées par domaine
        - Utilisation de laravel Fortify pour la 2FA, login, register etc..
        - Un début de tests unitaires pour la mantenance et sécurité du code
    - Frontend :
        - Composants et layouts regroupés dans des dossiers dédiés ce qui facilite la réutilisation
        - Utilisation de Tailwind CSS pour plus de facilité et rapidité dans le design
        - Gestion des états via Inertia pour une navigation fluide
        - Configuration TS pour un typage strict et une meilleure maintenabilité
    - Outils et configuration :
        - Utilisation de Prettier et d'Eslint pour formatter et linter correctement le code
        - Workflows GitHub Actions pour automatiser les tests et la qualité du code
- Points à améliorer (organisation, lisibilité, performance, logique métier) :
    - Backend :
        - Compléter les tests unitaires pour couvrir davantage de cas
        - Logique métier dans les contrôleurs à déplacer vers des services dédiés, pour une meilleure séparation des responsabilités
        - Logique incomplète pour l'index du TaksController, ça renvoie pas les vraies données
        - Le store du TaskController est à revoir, car si l'utilisateur n'a pas de team ça va échouer, le middleware EnsureUserHasTeam devrait empêcher ça mais pour l'instant il n'est pas implémenté
        - Au lieu de renvoyer des données brutes depuis le contrôleur on pourrait utiliser des Resource pour formater les réponses
        - Dans le modèle Task il manque le champ completed, car dans le CRUD on doit pouvoir modifier ce champ
        - Le seeder DatabaseSeeder est trop verbeux, on pourrait simplifier avec des boucles
    - Frontend :
        - La page Welcome trop longue à cause des SVG, on pourrait l'alléger en déplaçant des parties dans des composants
        - Pas de feedback utilisateur lors de la création d'une tâche (toast, message de succées etc..)
        - Pas de validation lors de la création de tâche
        - Icone de la sidebar pour la page Tasks identique à celle du Dashboard, ce qui peut prêter à confusion
        - La page Tasks n'utilise pas les composants existants pour les boutons, inputs, labels, ce qui crée une incohérence visuelle et du code dupliqué
- Suggestions d’optimisation :
    - Mettre en place un système de cache pour les requêtes fréquentes
    - Utiliser des Resource pour formater les réponses API
    - Ajouter des tests d’intégration
    - Implémenter des toasts pour les actions utilisateur
    - Utiliser des composants existants (buttons, inputs, labels) pour uniformiser le design et réduire la duplication de code
