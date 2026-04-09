# =============================================================================
# Dockerfile — Minimarket (Laravel 12 + Livewire + Vite)
# =============================================================================
#
# Architecture multi-stage :
#   Stage 1 (node-builder) : compile les assets CSS/JS avec Vite (Node.js)
#   Stage 2 (app)          : image PHP-FPM finale qui sert l'application
#
# Le multi-stage évite d'embarquer Node.js dans l'image de production,
# ce qui réduit considérablement la taille finale de l'image.
# =============================================================================

# ─── Stage 1 : Compilation des assets frontend ────────────────────────────────
FROM node:22-alpine AS node-builder

WORKDIR /app

# On copie d'abord uniquement les fichiers de dépendances pour profiter
# du cache Docker : si package.json n'a pas changé, `npm ci` n'est pas relancé.
COPY package*.json ./
RUN npm ci

# On copie ensuite les fichiers nécessaires à la compilation Vite
COPY vite.config.js ./
COPY tailwind.config.js ./
COPY postcss.config.js ./
COPY resources/ resources/
COPY public/ public/

# Génère public/build/ (JS/CSS minifiés + manifest.json utilisé par Laravel)
RUN npm run build

# ─── Stage 2 : Image PHP-FPM (application Laravel) ────────────────────────────
FROM php:8.3-fpm-alpine AS app

# Dépendances système nécessaires pour compiler les extensions PHP
# - libpng-dev, libjpeg-turbo-dev, freetype-dev : pour GD (images, logos, PDF)
# - libzip-dev : pour ZipArchive (import/export Excel)
# - icu-dev    : pour intl (internationalisation)
# - oniguruma-dev : pour mbstring
# - libxml2-dev   : pour xml (DomPDF, Excel)
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    shadow \
    zip \
    unzip

# Extensions PHP requises par Laravel 12 et ses packages :
# - pdo_mysql  : connexion MySQL
# - mbstring   : traitement des chaînes multibytes
# - bcmath     : calculs financiers précis (prix, totaux)
# - gd         : manipulation d'images (logos, graphiques dans les PDFs)
# - zip        : import/export Excel (Maatwebsite/Excel)
# - intl       : formatage des nombres et dates selon la locale
# - xml        : lecture XML (Excel .xlsx, DomPDF)
# - exif/pcntl : utilisés par Livewire et le worker de queue
# - fileinfo   : détection du type MIME des fichiers uploadés
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        exif \
        pcntl \
        fileinfo

# On récupère Composer depuis son image officielle plutôt que de l'installer
# manuellement — garantit toujours la dernière version stable
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ── Optimisation du cache Docker ──────────────────────────────────────────────
# On copie composer.json/lock en premier et on installe les dépendances PHP
# AVANT de copier le reste du code. Ainsi, si seul le code applicatif change
# (pas les dépendances), cette couche est réutilisée depuis le cache.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copie de l'intégralité du code applicatif
COPY . .

# Injection des assets compilés depuis le Stage 1
# (public/build contient le manifest.json et les fichiers JS/CSS versionnés)
COPY --from=node-builder /app/public/build public/build

# Finalisation de l'autoloader Composer (optimisé pour la production)
# et découverte des packages Laravel (providers, aliases)
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi

# L'utilisateur www-data (PHP-FPM) doit pouvoir écrire dans :
# - storage/     : logs, cache, sessions, fichiers uploadés (logos, exports)
# - bootstrap/cache/ : cache de configuration Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Script exécuté au démarrage du conteneur (migrations, storage:link, etc.)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Port PHP-FPM (utilisé par Nginx pour le reverse proxy)
EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
