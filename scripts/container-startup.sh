#!/bin/sh
set -eu

echo "==> [Raflora Startup] Starting persistent storage initialization..."

# Run the idempotent storage initialization command.
# If directory creation fails or public/storage is an invalid non-symlink path,
# this command exits with a non-zero code, halting startup immediately.
php artisan storage:initialize

echo "==> [Raflora Startup] Storage initialization successful."

# CRITICAL DEPLOYMENT ARCHITECTURE NOTE:
# Automatic database migrations ('php artisan migrate') are deliberately NOT run here.
# 1. Multi-instance / horizontal scaling can cause migration lock contention or race conditions.
# 2. Existing migration '2026_09_21_000002_add_is_bootstrap_and_create_admin_recovery_codes'
#    contains destructive data modifications in its up() method (resets admin verification state).
# Migrations must always be executed as a dedicated, controlled release phase or manual task.

if [ "$#" -gt 0 ]; then
    echo "==> [Raflora Startup] Launching application command: $*"
    exec "$@"
else
    echo "==> [Raflora Startup] No application process specified. Initialization complete."
fi
