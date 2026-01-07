# Déploiement sur VPS OVH

## Prérequis

1. **VPS OVH** avec Ubuntu 22.04/24.04 (Debian 11/12 fonctionne aussi)
2. **Accès SSH root** ou utilisateur avec sudo
3. **Repository GitHub** configuré
4. **(Optionnel) Nom de domaine** pointant vers l'IP du VPS

## Étapes de déploiement

### 1. Préparer le script

Sur votre machine locale, éditez `deploy.sh` :

```bash
nano deploy.sh
```

Modifiez ces lignes :
```bash
DOMAIN="votre-domaine.com"  # Votre domaine ou l'IP du VPS
GIT_REPO="https://github.com/VOTRE_USERNAME/market_checker_pax_dei.git"
```

### 2. Connectez-vous au VPS

```bash
ssh root@VOTRE_IP_VPS
```

### 3. Transférez et exécutez le script

**Option A - Copier le script manuellement:**
```bash
# Sur votre machine locale
scp deploy.sh root@VOTRE_IP_VPS:/root/

# Sur le VPS
chmod +x /root/deploy.sh
/root/deploy.sh
```

**Option B - Déploiement manuel étape par étape:**

Suivez les étapes ci-dessous si vous préférez un contrôle total.

---

## Déploiement manuel détaillé

### 1. Mise à jour du système

```bash
apt update && apt upgrade -y
```

### 2. Installation de PHP 8.4

```bash
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-intl php8.4-opcache
```

### 3. Installation de PostgreSQL 17

```bash
sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
apt update
apt install -y postgresql-17 postgresql-contrib-17
```

### 4. Créer la base de données

```bash
sudo -u postgres psql

CREATE DATABASE market_checker_pax_dei;
CREATE USER paxdei_user WITH PASSWORD 'VOTRE_MOT_DE_PASSE_SECURISE';
GRANT ALL PRIVILEGES ON DATABASE market_checker_pax_dei TO paxdei_user;
ALTER DATABASE market_checker_pax_dei OWNER TO paxdei_user;
\q
```

### 5. Installation de Composer

```bash
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

### 6. Installation de Nginx

```bash
apt install -y nginx git
```

### 7. Cloner le projet

```bash
cd /var/www
git clone https://github.com/VOTRE_USERNAME/market_checker_pax_dei.git
cd market_checker_pax_dei
```

Si le repository est privé, configurez un token GitHub :
```bash
git clone https://VOTRE_TOKEN@github.com/VOTRE_USERNAME/market_checker_pax_dei.git
```

### 8. Installer les dépendances

```bash
composer install --no-dev --optimize-autoloader
```

### 9. Configurer l'environnement

Créez `.env.local` :

```bash
nano .env.local
```

Contenu :
```env
APP_ENV=prod
APP_SECRET=GÉNÉREZ_UNE_CLÉ_ALÉATOIRE_ICI
DATABASE_URL="postgresql://paxdei_user:VOTRE_MOT_DE_PASSE@127.0.0.1:5432/market_checker_pax_dei?serverVersion=17&charset=utf8"
```

Pour générer APP_SECRET :
```bash
openssl rand -hex 32
```

### 10. Permissions

```bash
chown -R www-data:www-data /var/www/market_checker_pax_dei
chmod -R 755 /var/www/market_checker_pax_dei
chmod -R 775 /var/www/market_checker_pax_dei/var
```

### 11. Exécuter les migrations et importer les données

```bash
cd /var/www/market_checker_pax_dei

# Migrations
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

# Import des items
sudo -u www-data php bin/console app:import-items

# Mise à jour des qualités
sudo -u www-data php bin/console app:update-item-quality

# Analyse des recettes
sudo -u www-data php bin/console app:analyze-relic-recipes

# Création des utilisateurs (admin/admin et user/user)
sudo -u www-data php bin/console app:create-users

# Clear cache
sudo -u www-data php bin/console cache:clear --env=prod
```

### 12. Configuration Nginx

Créez le virtual host :

```bash
nano /etc/nginx/sites-available/market_checker
```

Contenu :
```nginx
server {
    listen 80;
    server_name votre-domaine.com;  # ou votre IP
    root /var/www/market_checker_pax_dei/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/market_checker_error.log;
    access_log /var/log/nginx/market_checker_access.log;
}
```

Activez le site :
```bash
ln -s /etc/nginx/sites-available/market_checker /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default  # Supprimer le site par défaut
nginx -t  # Tester la configuration
systemctl restart nginx
systemctl restart php8.4-fpm
```

### 13. Configuration du firewall

```bash
apt install -y ufw
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw enable
```

### 14. (Optionnel) Installer SSL avec Let's Encrypt

Si vous avez un nom de domaine :

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d votre-domaine.com
```

---

## Mise à jour de l'application

Pour mettre à jour l'application après un push sur GitHub :

```bash
cd /var/www/market_checker_pax_dei
git pull origin main  # ou master selon votre branche
composer install --no-dev --optimize-autoloader
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction
sudo -u www-data php bin/console cache:clear --env=prod
```

---

## Vérifications

1. **Tester l'accès web** : `http://VOTRE_IP` ou `http://votre-domaine.com`
2. **Vérifier les logs Nginx** : `tail -f /var/log/nginx/market_checker_error.log`
3. **Vérifier les logs Symfony** : `tail -f /var/www/market_checker_pax_dei/var/log/prod.log`
4. **Tester PostgreSQL** : `sudo -u postgres psql -d market_checker_pax_dei -c "SELECT COUNT(*) FROM item;"`

---

## Troubleshooting

### Erreur 500

```bash
# Vérifier les logs
tail -50 /var/log/nginx/market_checker_error.log
tail -50 /var/www/market_checker_pax_dei/var/log/prod.log

# Vérifier les permissions
chown -R www-data:www-data /var/www/market_checker_pax_dei
chmod -R 775 /var/www/market_checker_pax_dei/var
```

### Connexion base de données impossible

```bash
# Vérifier que PostgreSQL écoute
sudo -u postgres psql -c "SHOW listen_addresses;"

# Tester la connexion
psql -h 127.0.0.1 -U paxdei_user -d market_checker_pax_dei
```

### Nginx ne démarre pas

```bash
# Tester la config
nginx -t

# Vérifier les logs
tail -50 /var/log/nginx/error.log
```

---

## Maintenance

### Sauvegarde de la base de données

```bash
pg_dump -U paxdei_user market_checker_pax_dei > backup_$(date +%Y%m%d).sql
```

### Restauration

```bash
psql -U paxdei_user market_checker_pax_dei < backup_20260107.sql
```

### Surveiller les ressources

```bash
htop  # Installer avec: apt install htop
df -h  # Espace disque
free -h  # Mémoire
```
