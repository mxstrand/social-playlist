# Dev runtime for the Social Playlist API.
# FrankenPHP is the modern official Symfony runtime; it bundles a Mercure hub
# (which we start using from M2 onward) and ships the PHP extensions the
# Codespace's custom PHP build is missing (intl, pdo_pgsql).
FROM dunglas/frankenphp:1-php8.4

# PHP extensions required by Symfony 7 + API Platform + Postgres.
RUN install-php-extensions \
    intl \
    pdo_pgsql \
    opcache \
    zip \
    @composer

# Caddy (autosave) and the Mercure hub (Bolt store) write to /data and /config.
# We run as uid 1000, so make those writable instead of root-only.
RUN mkdir -p /data/caddy /config/caddy && chown -R 1000:1000 /data /config

WORKDIR /app

# Serve the Symfony front controller. FrankenPHP reads its Caddyfile from
# /etc/frankenphp/Caddyfile. Ours is plain HTTP on :8000 (no TLS), so running
# as a non-root uid doesn't hit the root-owned /data TLS cert dir.
COPY Caddyfile /etc/frankenphp/Caddyfile
