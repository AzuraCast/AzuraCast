# Repo Management

This file defines the Wise Path Management Strategy for Rahdiot Tuk-Tuk.

## Upstream Sync

Goal: keep this repository aligned with upstream while isolating project-specific work.

1. Verify remotes:
   - `origin`: `https://github.com/YvesVerela/Rahdiot-Tuk-Tuk.git`
   - `upstream`: `https://github.com/AzuraCast/AzuraCast.git`
2. Update local `main` from upstream:
   - `git checkout main`
   - `git fetch upstream`
   - `git merge --ff-only upstream/main`
3. Publish synchronized `main` to origin:
   - `git push origin main`
4. Rebase/merge active feature branches onto updated `main` before opening PRs.

## Plugin Pattern

Goal: keep custom behavior modular and merge-safe.

1. Build new custom capabilities as plugin-style modules rather than editing core flows when possible.
2. Keep plugin code isolated by responsibility and namespace.
3. Use explicit interfaces or extension points between core and plugin code.
4. Avoid side effects in bootstrap logic; make initialization deterministic and testable.
5. Document plugin contracts and dependencies in module-level README/docs.

## Branch Strategy

Goal: clear branch intent, clean review boundaries, predictable integration.

1. `main` is stable and syncs with upstream.
2. All work starts from a dedicated feature branch:
   - Pattern: `feature/<scope>`
3. Keep branches short-lived and focused on one architecture unit at a time.
4. Rebase/merge from `main` frequently to reduce drift.
5. Merge back to `main` only after review and validation.

