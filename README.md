# MGDS — Gestion des services de la mairie

Application Laravel 12 de gestion des tâches et anomalies des services municipaux,
multi-mairies, construite sur la même architecture que PlanEx.

## Fonctionnalités

- **Suivi des tâches** par mairie : référence automatique `service-numéro` (ex : `12-0`),
  photos avant/après, date butoir, clôture automatique (date + heure) au passage en « Fait ».
- **Visibilité par rôle** : l'employé voit ses tâches, le responsable voit son service,
  le cabinet du maire / la direction générale des services voient tout (avec filtres).
- **Gestion de la Mairie** (responsables & sous-responsables) : 3 onglets —
  gestion des utilisateurs (identifiant auto `prenom.nom`, +1 si doublon),
  fiche contact (annuaire auto + numéros de standard + export PDF),
  avancement des tâches (taux de charge par service, filtre par dates).
- **Connexion par mairie** : sélection de la mairie obligatoire, sauf pour les admins.
  Blocage si l'abonnement de la mairie a expiré.
- **Administration** : logs d'activité (rétention 6 mois, conformité CNIL), utilisateurs
  globaux, gestionnaire des accès mairie avec observateurs (copie de tous les mails).
- **Notifications e-mail** : création / affectation / clôture de tâche.
- **Langues** : français / anglais. Charte graphique bleu marine & or.

## Services (équipes) et grades

Les 13 services et les 5 grades (M./Mme le Maire, Responsable, Sous-Responsable,
Secrétaire, Employé) sont définis dans [app/Support/Referentiel.php](app/Support/Referentiel.php).

Droits : les grades 1 à 4 créent/modifient/suppriment des tâches ; l'employé (5) ne peut
que changer le statut, la description de clôture et la photo « une fois fini » (et ne peut
pas rouvrir une tâche faite).

## Installation locale

```bash
composer install
npm install && npm run build        # optionnel : le CSS est servi statiquement
copy .env.example .env              # configurer DB_* et, si besoin, ADMIN_*
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Le seeder crée le compte administrateur à partir des variables d'environnement
`ADMIN_USERNAME` / `ADMIN_PASSWORD` / `ADMIN_EMAIL` (si `ADMIN_PASSWORD` est absent,
un mot de passe aléatoire est généré et affiché une seule fois dans la console).
En environnement `local` uniquement, une mairie de démonstration et deux comptes
de test sont également créés.

## Déploiement

Le déploiement est automatique via **GitHub Actions** à chaque push sur `main`
([.github/workflows/deploy.yml](.github/workflows/deploy.yml)) : connexion SSH au
serveur via un tunnel **cloudflared**, puis `git reset --hard`,
`composer install --no-dev`, migrations et mise en cache.

Toutes les informations serveur passent par des **secrets GitHub**
(*Settings → Secrets and variables → Actions*) :

| Secret | Contenu |
|---|---|
| `SSH_PRIVATE_KEY` | Clé privée SSH de déploiement (la clé publique est dans `authorized_keys` du serveur) |
| `SSH_HOST` | Nom d'hôte du tunnel cloudflared SSH |
| `SSH_USER` | Utilisateur SSH de déploiement |
| `DEPLOY_PATH` | Chemin de l'application sur le serveur |

### Préparation du serveur (une seule fois)

```bash
sudo git clone <ce dépôt> <DEPLOY_PATH>
sudo chown -R <SSH_USER>:<SSH_USER> <DEPLOY_PATH>
git config --global --add safe.directory <DEPLOY_PATH>
cd <DEPLOY_PATH>
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# .env : APP_ENV=production, APP_DEBUG=false, APP_URL, DB_*, MAIL_*, ADMIN_*
php artisan migrate --force --seed
php artisan storage:link

# droits : git déploie en tant que <SSH_USER>, PHP-FPM écrit en tant que www-data
sudo chown -R <SSH_USER>:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Vhost Apache2 (`/etc/apache2/sites-available/mgds.conf`) :

```apache
<VirtualHost *:80>
    ServerName votre-domaine.tld
    ServerAlias www.votre-domaine.tld
    DocumentRoot <DEPLOY_PATH>/public

    <Directory <DEPLOY_PATH>/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
sudo a2ensite mgds && sudo a2enmod rewrite
sudo apachectl configtest && sudo systemctl reload apache2
```

### CI

[tests.yml](.github/workflows/tests.yml) exécute `php artisan test` (PHP 8.2 / 8.3,
SQLite) sur chaque push et pull request.

## Structure

Même squelette que PlanEx : Laravel 12 + Breeze (Blade), Bootstrap 5 via CDN +
`public/css/app.css`, dompdf pour les PDF (courrier d'identifiants, fiche contact),
`ActivityLogger` fichier, middleware `ForcePasswordChange` (mot de passe provisoire 48h).
