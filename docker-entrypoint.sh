#!/bin/sh
# =============================================================================
# docker-entrypoint.sh — Script de démarrage des conteneurs Minimarket
# =============================================================================
#
# Ce script s'exécute automatiquement avant le processus principal (php-fpm
# ou php artisan queue:listen) à chaque démarrage du conteneur.
#
# Il garantit que l'application est dans un état cohérent avant de servir
# des requêtes, même après un `docker compose down` / `up`.
# =============================================================================
set -e

echo "==> Création du lien symbolique public/storage..."
# Crée le lien public/storage → storage/app/public
# Nécessaire pour servir les fichiers uploadés via l'URL /storage/...
# (logos d'application, images produits, etc.)
# --force : recrée le lien s'il existe déjà (sans erreur)
php artisan storage:link --force 2>/dev/null || true

echo "==> Exécution des migrations..."
# Lance les migrations en attente.
# --force : requis hors environnement local (Laravel refuse les migrations
# en production sans ce flag par mesure de sécurité)
php artisan migrate --force

# ── Optimisations production ───────────────────────────────────────────────────
# En production, on met en cache la config, les routes et les vues pour
# améliorer les performances. En développement, ces caches sont désactivés
# car ils empêcheraient de voir les changements immédiatement.
if [ "$APP_ENV" = "production" ]; then
    echo "==> Mode production : mise en cache config/routes/vues..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "==> Démarrage de : $@"
# Lance la commande principale passée en argument
# (php-fpm pour le service app, ou php artisan queue:listen pour le worker)
exec "$@"
