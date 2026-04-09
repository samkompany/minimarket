# Minimarket — Système de gestion de caisse et d'inventaire

Application web complète de gestion pour petits commerces et supérettes : ventes, achats, stock, dépenses, rapports financiers et facturation PDF — le tout en temps réel grâce à Laravel + Livewire.

---

## Fonctionnalités

### Point de vente (Caisse)

- Interface de vente rapide avec recherche de produits en temps réel
- Ajout de produits favoris en un clic
- Calcul automatique des totaux, remises et monnaie rendue
- Génération de factures PDF et reçus d'impression
- Historique complet des transactions

### Gestion des stocks

- Suivi des quantités en temps réel
- Alertes de stock minimum et seuils de réapprovisionnement
- Enregistrement des sorties de stock (pertes, casses, ajustements)
- Historique des mouvements de stock

### Achats & Fournisseurs

- Bons de commande avec saisie des prix d'achat
- Réception automatique en stock à la validation
- Gestion du carnet de fournisseurs

### Dépenses & Trésorerie

- Enregistrement des dépenses par catégorie
- Suivi des paiements de dépenses
- Rapport de trésorerie (recettes vs dépenses sur 30/90 jours)

### Tableaux de bord & Rapports (Admin)

- KPIs en temps réel : chiffre d'affaires, marges, ventes du jour
- Produits à marge négative, sans prix, en rupture de stock
- Produits à rotation lente (non vendus depuis 30 jours)
- Rapport de ventes détaillé avec export Excel
- Suivi des ventes impayées et fournisseurs inactifs

### Administration

- Multi-utilisateurs avec 3 niveaux de rôles (admin, vendeur, vendeur simple)
- Suspension de comptes avec motif
- Import de catalogue produits via fichier Excel (matching par SKU/code-barre)
- Configuration dynamique : nom et logo de l'application
- Recherche globale dans toute l'application

---

## Stack technique

| Couche           | Technologie                    |
| ---------------- | ------------------------------ |
| Backend          | Laravel 12 (PHP 8.3)           |
| UI réactive      | Livewire 3 + Volt              |
| Frontend         | Tailwind CSS 3 + Flowbite      |
| Build            | Vite 7                         |
| Base de données  | MySQL (Docker) / SQLite (local)|
| Export Excel     | Maatwebsite Excel 3            |
| PDF / Reçus      | DomPDF                         |
| Queue            | Database-backed (Laravel Queue)|
| Auth             | Laravel Breeze                 |

---

## Installation

### Avec Docker *(recommandé)*

**Prérequis** : Docker Desktop installé et démarré.

```bash
# 1. Cloner le projet
git clone https://github.com/samkompany/minimarket.git
cd minimarket

# 2. Copier la configuration Docker
cp .env.docker .env

# 3. Construire et démarrer les conteneurs
docker compose build
docker compose up -d

# 4. Générer la clé d'application (une seule fois)
docker compose exec app php artisan key:generate

# 5. (Optionnel) Charger les données de démonstration
docker compose exec app php artisan db:seed
```

L'application est accessible sur <http://localhost:8080>

> **Arrêter** : `docker compose down`
> **Logs** : `docker compose logs -f app`

---

### Sans Docker (développement local)

**Prérequis** : PHP 8.3, Composer, Node.js 20+, SQLite ou MySQL.

```bash
git clone https://github.com/samkompany/minimarket.git
cd minimarket

# Installation complète (dépendances + clé + migrations + build)
composer run setup

# Démarrer tous les services (Laravel + Queue + Vite)
composer run dev
```

L'application est accessible sur <http://localhost:8000>

---

## Comptes par défaut

Après `php artisan db:seed` :

| Rôle            | Email                      | Mot de passe |
| --------------- | -------------------------- | ------------ |
| Administrateur  | `admin@minimarket.test`    | `password`   |
| Caissier        | `caisse@minimarket.test`   | `password`   |

> **En production**, définir `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD`, `SEED_CASHIER_EMAIL` et `SEED_CASHIER_PASSWORD` dans le `.env` avant de seeder.

---

## Architecture

```text
minimarket/
├── app/
│   ├── Livewire/          # Composants UI réactifs (une classe = une page)
│   ├── Models/            # Modèles Eloquent (Product, Sale, Stock, User…)
│   └── Http/
│       ├── Controllers/   # Minimal : InvoiceController (PDF) uniquement
│       └── Middleware/    # EnsureUserIsActive (blocage comptes suspendus)
├── resources/views/
│   ├── layouts/           # Layout principal (sidebar dynamique) + guest
│   ├── livewire/          # Templates Blade des composants Livewire
│   └── invoices/          # Templates PDF (factures + reçus)
├── database/
│   └── migrations/        # 30+ migrations — source de vérité du schéma
├── routes/
│   ├── web.php            # Toutes les routes protégées (auth requis)
│   └── auth.php           # Routes d'authentification (Volt)
└── docker/
    └── nginx/default.conf # Config Nginx → PHP-FPM
```

**Pattern clé** : L'application est **Livewire-first** — pas de JavaScript custom ni de controllers classiques pour les pages. Chaque fonctionnalité est un composant `app/Livewire/Feature/Index.php` avec son template `resources/views/livewire/feature/index.blade.php`.

---

## Architecture Docker

```text
Navigateur → http://localhost:8080
                    │
            ┌───────▼────────┐
            │   nginx:80     │  Fichiers statiques + reverse proxy
            └───────┬────────┘
                    │ FastCGI (port 9000)
            ┌───────▼────────┐
            │  app (FPM)     │  Laravel + Livewire
            └───────┬────────┘
                    │
            ┌───────▼────────┐    ┌──────────────┐
            │  mysql:3306    │    │ queue worker  │  Jobs asynchrones
            └────────────────┘    └──────────────┘
```

---

## Commandes utiles

```bash
# Artisan dans Docker
docker compose exec app php artisan <commande>

# Migrations
docker compose exec app php artisan migrate

# Logs applicatifs
docker compose logs -f app

# Accès MySQL
docker compose exec mysql mysql -u minimarket -psecret minimarket

# Tests PHPUnit
docker compose exec app php artisan test
```

---

## Licence

MIT — libre d'utilisation, modification et distribution.
