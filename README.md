# MGDS — Gestion des services de la mairie

Application Laravel 12 de gestion des tâches et anomalies des services municipaux,
multi-mairies, construite sur la même architecture que PlanEx.

## Fonctionnalités

- **Tableau des anomalies** par mairie : référence automatique `service-numéro` (ex : `12-0`),
  photos avant/après, date butoir, clôture automatique (date + heure) au passage en « Fait ».
- **Visibilité par rôle** : l'employé voit ses tâches, le responsable voit son service,
  le cabinet du maire / la direction générale des services voient tout (avec filtres).
- **Gestion de la Mairie** (responsables & sous-responsables) : 3 onglets —
  gestion des utilisateurs (identifiant auto `prenom.nom`, +1 si doublon),
  fiche contact (annuaire auto + numéros de standard + export PDF),
  avancement des tâches (taux de charge par service, filtre par dates).
- **Connexion par mairie** : sélection de la mairie obligatoire, sauf pour les admins
  (leurs identifiants passent par-dessus la sélection). Blocage si abonnement expiré.
- **Admin** : utilisateurs globaux (colonnes mairie + équipe), gestionnaire des accès mairie
  (nom, email, téléphone, date de fin d'abonnement), observateurs (copie de tous les mails,
  sans limite), logs d'activité, messages (à venir).
- **Notifications e-mail** : création / affectation / clôture de tâche, avec copie
  systématique aux observateurs de la mairie.
- **Langues** : français / anglais.

## Services (équipes) et grades

Les 13 services (Cabinet du maire → Pôle Sécurité / Police Municipale) et les 5 grades
(M./Mme le Maire, Responsable, Sous-Responsable, Secrétaire, Employé) sont définis dans
[app/Support/Referentiel.php](app/Support/Referentiel.php).

Droits : les grades 1 à 4 créent/modifient/suppriment des tâches ; l'employé (5) ne peut
que changer le statut, la description de clôture et la photo « une fois fini » (et ne peut
pas rouvrir une tâche faite).

## Installation locale (XAMPP)

```bash
cd C:\xampp\htdocs\MGDS
composer install
npm install && npm run build
copy .env.example .env        # puis configurer DB_* (MySQL : base "mgds")
php artisan key:generate
php artisan migrate --seed    # crée l'admin + une mairie de démonstration
php artisan storage:link
php artisan serve
```

Comptes créés par le seeder (local) :

| Compte | Identifiant | Mot de passe | Rôle |
|---|---|---|---|
| Admin | `admin` | `Admin@MGDS2026` | Administrateur (pas de sélection de mairie) |
| Démo | `romain.allien` | `password` | Responsable Service Technique |
| Démo | `jean.dupont` | `password` | Employé Service Technique |

⚠️ **Changez le mot de passe admin en production** (le seeder ne crée les comptes démo
qu'en environnement `local`).

## Déploiement (production)

Le déploiement est automatique via **GitHub Actions** à chaque push sur `main`
([.github/workflows/deploy.yml](.github/workflows/deploy.yml)), comme PlanEx :
connexion SSH au serveur via un tunnel **cloudflared**, puis `git reset --hard`,
`composer install --no-dev`, migrations et mise en cache.

### Configuration serveur (une seule fois)

1. **Cloner le dépôt** sur le serveur (⚠️ le dossier doit appartenir à l'utilisateur
   de déploiement `roro`, pas à root — le workflow GitHub Actions y fait des
   `git reset` / `composer install` sans sudo). **npm n'est pas nécessaire en
   production** : le CSS est servi statiquement (`public/css/app.css` + CDN).
   ```bash
   sudo git clone https://github.com/mael992/Projet_Suivi.git /var/www/mgds
   sudo chown -R roro:roro /var/www/mgds
   git config --global --add safe.directory /var/www/mgds
   cd /var/www/mgds
   composer install --no-dev --optimize-autoloader
   cp .env.example .env && php artisan key:generate
   # configurer .env : APP_ENV=production, APP_DEBUG=false, APP_URL=https://m-gds.com,
   #                   DB mysql "mgds", MAIL smtp
   php artisan migrate --force --seed
   php artisan storage:link
   sudo chown -R www-data:www-data storage bootstrap/cache
   ```

2. **Base de données** (avant le `migrate`) :
   ```bash
   sudo mysql -e "CREATE DATABASE mgds CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   # puis donner les droits à l'utilisateur MySQL utilisé dans le .env
   ```

3. **Vhost** nginx — créer le fichier `/etc/nginx/sites-available/mgds`
   avec ce contenu (le site a son propre domaine **m-gds.com**) :
   ```nginx
   server {
       listen 80;
       server_name m-gds.com www.m-gds.com;
       root /var/www/mgds/public;
       index index.php;
       location / { try_files $uri $uri/ /index.php?$query_string; }
       location ~ \.php$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/run/php/php8.2-fpm.sock;
       }
   }
   ```
   puis l'activer :
   ```bash
   sudo ln -s /etc/nginx/sites-available/mgds /etc/nginx/sites-enabled/
   sudo nginx -t && sudo systemctl reload nginx
   ```

4. **Secret GitHub** : dans le dépôt `mael992/Projet_Suivi` →
   *Settings → Secrets and variables → Actions* → créer `SSH_PRIVATE_KEY`
   (la même clé privée de déploiement que planex, l'utilisateur `roro` passe par
   le tunnel cloudflared `ssh.planex26.com`).

5. **DNS / Cloudflare** : faire pointer `m-gds.com` (et `www`) vers le serveur
   (proxy Cloudflare recommandé pour le HTTPS, comme planex).

   Note : le déploiement SSH continue de passer par le tunnel cloudflared
   `ssh.planex26.com` (c'est l'accès au serveur, indépendant du domaine du site).
   Si vous créez un tunnel dédié `ssh.m-gds.com` dans la zone Cloudflare de
   m-gds.com, remplacez simplement les deux occurrences de `ssh.planex26.com`
   dans [deploy.yml](.github/workflows/deploy.yml).

### CI

[tests.yml](.github/workflows/tests.yml) exécute `php artisan test` (PHP 8.2 / 8.3,
SQLite) sur chaque push et pull request.

## Structure

Même squelette que PlanEx : Laravel 12 + Breeze (Blade), Bootstrap 5 via CDN +
`public/css/app.css`, dompdf pour les PDF (courrier d'identifiants, fiche contact),
`ActivityLogger` fichier (rétention 6 mois, conforme CNIL), middleware
`ForcePasswordChange` (mot de passe provisoire 48h).
