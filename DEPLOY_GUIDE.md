# Guide de Déploiement - Market Checker Pax Dei

## 📋 Pré-requis VPS
- **Système**: Debian 12 (Bookworm)
- **RAM**: 2 Go minimum
- **IP**: 137.74.44.207
- **Utilisateur**: debian (avec accès sudo)

## 🚀 Étapes de Déploiement

### 1. Connexion au VPS
```bash
ssh debian@137.74.44.207
```

### 2. Récupération du script de déploiement
```bash
# Télécharger le script depuis GitHub
wget https://raw.githubusercontent.com/Rifturn/market_checker_pax_dei/main/deploy.sh

# Rendre le script exécutable
chmod +x deploy.sh
```

### 3. Exécution du déploiement
```bash
# Lancer le script (attention: prend 10-15 minutes)
./deploy.sh
```

### 4. Sauvegarde des identifiants
Le script affichera :
- **Database Password**: À noter précieusement
- **APP_SECRET**: À noter précieusement

**⚠️ IMPORTANT**: Sauvegardez ces identifiants dans un endroit sûr !

## 📦 Ce qui sera installé/configuré

### Infrastructure
- ✅ PostgreSQL 17
- ✅ PHP 8.4-FPM (avec extensions: pgsql, mbstring, xml, curl, zip, intl, opcache)
- ✅ Nginx
- ✅ Composer 2
- ✅ UFW Firewall (ports 22, 80, 443)

### Application
- ✅ Clone du repository Git
- ✅ Installation des dépendances Composer (production)
- ✅ Création base de données
- ✅ Exécution des migrations (3 nouvelles migrations pour spells/skills/avatars)
- ✅ Import des items depuis l'API Gaming Tools
- ✅ Import des spells (nouveauté)
- ✅ Import des skills (45 compétences - nouveauté)
- ✅ Mise à jour des qualités d'items
- ✅ Analyse des recettes de reliques
- ✅ Création des utilisateurs de test

### Configuration
- ✅ `.env.local` avec connexion DB et `SKILL_MAX_LEVEL=40`
- ✅ Nginx configuré sur port 80
- ✅ Cache Symfony en mode production
- ✅ Permissions correctes pour www-data

## 🧪 Vérification du déploiement

### 1. Test de l'application
```bash
# Ouvrir dans le navigateur
http://137.74.44.207
```

### 2. Vérifier les routes importantes
- **Market**: http://137.74.44.207/market (anciennement /items)
- **Spells**: http://137.74.44.207/spells
- **Avatars**: http://137.74.44.207/avatars
- **Admin**: http://137.74.44.207/admin (login requis)

### 3. Test de connexion admin
```bash
# Utiliser les identifiants créés par app:create-users
# Par défaut: admin / admin (à vérifier dans la commande)
```

### 4. Vérifier les imports
```bash
# Se connecter au VPS
ssh debian@137.74.44.207

# Vérifier les données en base
sudo -u postgres psql -d market_checker_pax_dei -c "SELECT COUNT(*) FROM item;"
sudo -u postgres psql -d market_checker_pax_dei -c "SELECT COUNT(*) FROM spell;"
sudo -u postgres psql -d market_checker_pax_dei -c "SELECT COUNT(*) FROM skill;"

# Devrait afficher:
# - items: ~300-500 items
# - spells: ~100-200 spells
# - skills: 45 skills
```

## 🔧 Commandes utiles post-déploiement

### Redémarrer les services
```bash
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

### Voir les logs
```bash
# Logs Nginx
sudo tail -f /var/log/nginx/market_checker_error.log
sudo tail -f /var/log/nginx/market_checker_access.log

# Logs Symfony
sudo tail -f /var/www/market_checker_pax_dei/var/log/prod.log
```

### Clear cache Symfony
```bash
cd /var/www/market_checker_pax_dei
sudo -u www-data php bin/console cache:clear --env=prod
```

### Re-importer les données
```bash
cd /var/www/market_checker_pax_dei

# Re-import des items
sudo -u www-data php bin/console app:import-items

# Re-import des spells
sudo -u www-data php bin/console app:import-spells

# Re-import des skills
sudo -u www-data php bin/console app:import-skills
```

## 🔐 Installation SSL (optionnel mais recommandé)

### Installer Certbot
```bash
sudo apt install -y certbot python3-certbot-nginx
```

### Obtenir un certificat (nécessite un nom de domaine)
```bash
# Si vous avez un nom de domaine pointant vers 137.74.44.207
sudo certbot --nginx -d votre-domaine.com

# Certbot configurera automatiquement Nginx pour HTTPS
```

## 🆕 Nouveautés de ce déploiement

### Système d'Avatars
- Les utilisateurs peuvent créer **1 avatar par compte**
- **45 compétences** initialisées automatiquement à niveau 0
- Éditeur de compétences avec niveau max configurable (40 par défaut)
- Séparation Combat / Crafting

### Système de Spells
- Import complet des sorts depuis l'API Gaming Tools
- Liaison avec les items qui débloquent les sorts
- Affichage avec icônes et descriptions

### Routes renommées
- `/items` → `/market` (nouvelle URL principale)
- Ancien lien `/items` ne fonctionnera plus

## 📊 Structure de la base de données

### Nouvelles tables
```sql
-- Skills (compétences du jeu)
skill (id, external_id, name, ui_group, skill_level_cap, ...)

-- Avatars des joueurs
avatar (id, user_id, name, created_at, updated_at)

-- Compétences par avatar
avatar_skill (id, avatar_id, skill_id, level)

-- Sorts du jeu
spell (id, external_id, name, icon_path, description, cooldown_duration, ...)

-- Liaison sorts-items
spell_item (id, spell_id, item_id)
```

## ❓ Problèmes courants

### Erreur 502 Bad Gateway
```bash
# Vérifier que PHP-FPM tourne
sudo systemctl status php8.4-fpm

# Redémarrer si nécessaire
sudo systemctl restart php8.4-fpm
```

### Erreur de connexion à la base de données
```bash
# Vérifier PostgreSQL
sudo systemctl status postgresql

# Vérifier les identifiants dans .env.local
sudo cat /var/www/market_checker_pax_dei/.env.local
```

### Page blanche
```bash
# Vérifier les permissions
sudo chown -R www-data:www-data /var/www/market_checker_pax_dei
sudo chmod -R 775 /var/www/market_checker_pax_dei/var

# Clear cache
cd /var/www/market_checker_pax_dei
sudo -u www-data php bin/console cache:clear --env=prod
```

## 📞 Support

En cas de problème, vérifier :
1. Les logs Nginx: `/var/log/nginx/market_checker_error.log`
2. Les logs Symfony: `/var/www/market_checker_pax_dei/var/log/prod.log`
3. Le statut des services: `sudo systemctl status nginx php8.4-fpm postgresql`

---

**Dernière mise à jour**: 8 janvier 2025
**Version**: 2.0.0 (avec avatars & skills)
