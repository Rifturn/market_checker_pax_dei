#!/bin/bash
# Script de déploiement pour VPS OVH
# À exécuter sur le serveur VPS avec l'utilisateur debian (sudo requis)

set -e  # Arrêter en cas d'erreur

echo "=== Déploiement Market Checker Pax Dei ==="

# Variables à configurer
DOMAIN="137.74.44.207"  # IP du VPS
DB_NAME="market_checker_pax_dei"
DB_USER="paxdei_user"
DB_PASSWORD="PaxDei2026_$(openssl rand -base64 8)"
APP_SECRET="$(openssl rand -hex 32)"
GIT_REPO="https://github.com/Rifturn/market_checker_pax_dei.git"

echo "Mot de passe DB généré : $DB_PASSWORD"
echo "APP_SECRET généré : $APP_SECRET"
echo "SAUVEGARDEZ CES VALEURS !"
read -p "Appuyez sur Entrée pour continuer..."

# DÉJÀ EXÉCUTÉ - Décommentez si besoin
# 1. Mise à jour du système
#echo "=== Mise à jour du système ==="
#sudo apt update && sudo apt upgrade -y

# DÉJÀ EXÉCUTÉ - Décommentez si besoin
# 2. Installation des dépendances
#echo "=== Installation des dépendances ==="
#sudo apt install -y curl git unzip wget gnupg2 ca-certificates lsb-release apt-transport-https

# DÉJÀ EXÉCUTÉ - Décommentez si besoin
# 3. Installation de PHP 8.4
#echo "=== Installation de PHP 8.4 ==="
#sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
#echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
#sudo apt update
#sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring \
#    php8.4-xml php8.4-curl php8.4-zip php8.4-intl php8.4-opcache

# DÉJÀ EXÉCUTÉ - Décommentez si besoin
# 4. Installation de PostgreSQL 17
#echo "=== Installation de PostgreSQL 17 ==="
#sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
#wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo gpg --dearmor -o /etc/apt/trusted.gpg.d/postgresql.gpg
#sudo apt update
#sudo apt install -y postgresql-17 postgresql-contrib-17

# DÉJÀ EXÉCUTÉ - Décommentez si besoin
# 5. Configuration de PostgreSQL
#echo "=== Configuration de PostgreSQL ==="
#sudo -u postgres psql <<EOF
#CREATE DATABASE $DB_NAME;
#CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
#GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
#ALTER DATABASE $DB_NAME OWNER TO $DB_USER;
#\q
#EOF

# DÉJÀ EXÉCUTÉ - Décommentez si besoin
# 6. Installation de Composer
#echo "=== Installation de Composer ==="
#curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer 2>/dev/null || \
#sudo curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# DÉJÀ EXÉCUTÉ - Décommentez si besoin
# 7. Installation de Nginx
#echo "=== Installation de Nginx ==="
#sudo apt install -y nginx

# 8. Création du répertoire du projet
echo "=== Clonage du projet ==="
sudo mkdir -p /var/www
cd /var/www
sudo rm -rf market_checker_pax_dei 2>/dev/null || true
sudo git clone $GIT_REPO market_checker_pax_dei
cd market_checker_pax_dei

# 9. Installation des dépendances Composer
echo "=== Installation des dépendances Composer ==="
export APP_ENV=prod
sudo -E composer install --no-dev --optimize-autoloader --no-scripts

# 10. Configuration de l'environnement
echo "=== Configuration de l'environnement ==="
sudo tee .env.local > /dev/null <<EOF_ENV
APP_ENV=prod
APP_SECRET=$APP_SECRET
DATABASE_URL="postgresql://$DB_USER:$DB_PASSWORD@127.0.0.1:5432/$DB_NAME?serverVersion=17&charset=utf8"
SKILL_MAX_LEVEL=40
EOF_ENV

# Regénérer l'autoloader après avoir créé .env.local
sudo -E composer dump-autoload --optimize --no-dev

# 11. Permissions
echo "=== Configuration des permissions ==="
sudo mkdir -p /var/www/market_checker_pax_dei/var/cache /var/www/market_checker_pax_dei/var/log
sudo chown -R www-data:www-data /var/www/market_checker_pax_dei
sudo chmod -R 755 /var/www/market_checker_pax_dei
sudo chmod -R 775 /var/www/market_checker_pax_dei/var

# 12. Migrations et data
echo "=== Exécution des migrations ==="
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

echo "=== Import des items ==="
sudo -u www-data php bin/console app:import-items

echo "=== Import des spells ==="
sudo -u www-data php bin/console app:import-spells

echo "=== Import des skills ==="
sudo -u www-data php bin/console app:import-skills

echo "=== Mise à jour des qualités ==="
sudo -u www-data php bin/console app:update-item-quality

echo "=== Analyse des recettes ==="
sudo -u www-data php bin/console app:analyze-relic-recipes

echo "=== Création des utilisateurs ==="
sudo -u www-data php bin/console app:create-users

# 13. Clear cache
echo "=== Clear cache production ==="
sudo -u www-data php bin/console cache:clear --env=prod

# 14. Configuration Nginx
echo "=== Configuration Nginx ==="
sudo tee /etc/nginx/sites-available/market_checker > /dev/null <<'EOF_NGINX'
server {
    listen 80;
    server_name DOMAIN_PLACEHOLDER;
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
EOF_NGINX

sudo sed -i "s/DOMAIN_PLACEHOLDER/$DOMAIN/g" /etc/nginx/sites-available/market_checker

sudo ln -sf /etc/nginx/sites-available/market_checker /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test configuration Nginx
sudo nginx -t

# 15. Redémarrage des services
echo "=== Redémarrage des services ==="
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
sudo systemctl enable nginx
sudo systemctl enable postgresql

# 16. Configuration du firewall
echo "=== Configuration du firewall ==="
sudo apt install -y ufw
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
echo "y" | sudo ufw enable || true

echo ""
echo "==================================="
echo "✅ DÉPLOIEMENT TERMINÉ !"
echo "==================================="
echo ""
echo "Base de données:"
echo "  - Nom: $DB_NAME"
echo "  - User: $DB_USER"
echo "  - Password: $DB_PASSWORD"
echo ""
echo "Application:"
echo "  - APP_SECRET: $APP_SECRET"
echo ""
echo "Accédez à votre application via:"
echo "  http://$DOMAIN"
echo ""
echo "Pour installer SSL (Let's Encrypt):"
echo "  apt install certbot python3-certbot-nginx"
echo "  certbot --nginx -d $DOMAIN"
echo ""
echo "SAUVEGARDEZ LES IDENTIFIANTS CI-DESSUS !"
echo "==================================="
