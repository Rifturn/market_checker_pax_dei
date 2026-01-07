# Pax Dei Market Checker

Application Symfony pour visualiser et analyser les données du marché de Pax Dei.

## Installation

1. Installer les dépendances PHP :
```bash
composer install
```

2. Installer les extensions PHP nécessaires :
```bash
sudo apt-get install php-sqlite3
```

3. Créer la base de données :
```bash
php bin/console doctrine:migrations:migrate
```

4. Importer les items depuis l'API :
```bash
php bin/console app:import-items
```

## Utilisation

### Démarrer le serveur
```bash
symfony serve
```

Puis accéder à http://localhost:8000/items

### Commandes disponibles

#### Importer/Mettre à jour les items
```bash
# Import initial (ignore les items existants)
php bin/console app:import-items

# Mise à jour forcée (met à jour les items existants)
php bin/console app:import-items --force
```

## Structure de la base de données

### Table `category`
- `id` : ID auto-incrémenté
- `name` : Nom de la catégorie (unique)
- `slug` : Slug URL-friendly

### Table `item`
- `id` : ID auto-incrémenté
- `external_id` : ID de l'item dans l'API Pax Dei (unique)
- `name` : Noms localisés (JSON : De, En, Es, Fr, Pl)
- `icon_path` : URL de l'icône
- `url` : URL vers la page de l'item
- `category_id` : Foreign key vers `category`
- `created_at` : Date de création
- `updated_at` : Date de mise à jour

## Catégories intelligentes

L'application analyse automatiquement le chemin de l'icône (`iconPath`) pour déterminer la catégorie de chaque item.

Plus de 60 patterns sont reconnus, incluant :
- **Consommables** : Potions, Sirops, Pain, Viandes rôties, Ragoûts, etc.
- **Ressources naturelles** : Champignons, Fleurs, Herbes, Céréales, Pierres, etc.
- **Objets de construction** : Éclairage, Meubles, Portes, Enseignes, etc.
- **Matériaux craftés** : Lingots, Composants, Fils, Bois travaillé, etc.
- **Équipement** : Armures (tissu/cuir/métal), Armes, Bijoux, etc.
- **Autres** : Reliques, Outils de métier, Parchemins, etc.

## Modification des catégories

Les catégories sont stockées en base de données et peuvent être modifiées directement :

```php
// Exemple : changer la catégorie d'un item
$item = $itemRepository->findByExternalId('item_id');
$category = $categoryRepository->findOrCreateByName('Nouvelle Catégorie');
$item->setCategory($category);
$entityManager->flush();
```

## Routes disponibles

- `/items` : Liste des items avec filtrage par catégorie et données de marché
- `/categories` : Vue statistique des catégories
- `/items/{itemId}/region/{region}` : Détails des ventes par région

## Technologies utilisées

- **Symfony 6.4** : Framework PHP
- **Doctrine ORM** : Gestion de la base de données
- **SQLite** : Base de données (configurable pour MySQL/PostgreSQL)
- **DataTables** : Interface interactive pour les tableaux
- **Bootstrap 5** : Framework CSS
