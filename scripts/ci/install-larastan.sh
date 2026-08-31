#!/usr/bin/env bash
set -euo pipefail

# Transitional P2 tool bootstrap. It intentionally runs only in CI / a disposable
# working tree because Larastan was not present in the inherited composer.lock.
# Once the team can regenerate and review composer.lock, add larastan/larastan
# to require-dev and delete this script.
composer require --dev larastan/larastan:3.10.0 --no-update --no-interaction
composer update larastan/larastan phpstan/phpstan iamcal/sql-parser \
  --with-dependencies --no-interaction --no-progress --prefer-dist
