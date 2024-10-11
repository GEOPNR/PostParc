#!/bin/sh
set -e

#php bin/console doctrine:migration:migrate --no-interaction
fixuid
exec $@
