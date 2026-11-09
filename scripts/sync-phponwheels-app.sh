#!/usr/bin/env bash
# Pushes phponwheels' current branch to origin, then updates the phponwheels/phponwheels
# dependency in the sibling phponwheels-app checkout so it tracks main's tip.
#
# Installed as this repo's post-commit hook by scripts/install-hooks.sh, so it
# normally runs automatically after every commit. Skip once with:
#   SKIP_PHPONWHEELS_SYNC=1 git commit ...
set -euo pipefail

phponwheels_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
app_dir="$phponwheels_dir/../pow-app"

if [ ! -d "$app_dir" ]; then
    echo "[sync-phponwheels-app] $app_dir not found, skipping" >&2
    exit 0
fi
app_dir="$(cd "$app_dir" && pwd)"

branch="$(git -C "$phponwheels_dir" rev-parse --abbrev-ref HEAD)"
if [ "$branch" = "HEAD" ]; then
    echo "[sync-phponwheels-app] detached HEAD, not pushing" >&2
    exit 1
fi

echo "[sync-phponwheels-app] pushing $branch to origin..."
git -C "$phponwheels_dir" push origin "HEAD:$branch"

echo "[sync-phponwheels-app] updating phponwheels/phponwheels in phponwheels-app (composer resolves dev-main against Packagist, which re-indexes shortly after the push above)..."
( cd "$app_dir" && composer update phponwheels/phponwheels --with-all-dependencies )

echo "[sync-phponwheels-app] done: phponwheels-app/vendor/phponwheels/phponwheels now tracks $(git -C "$phponwheels_dir" rev-parse --short HEAD)."
echo "[sync-phponwheels-app] phponwheels-app/composer.lock changed locally and is NOT committed/pushed automatically - review and commit it there when ready."
