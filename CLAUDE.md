# CLAUDE.md

## Project Overview

This is a Provisioner plugin for COmanage Registry version 4.x used to
provision users and groups to the Dataverse web application from Harvard.
This plugin is specifically designed for the CADRE project in Australia and
assumes a specific naming convention for the names of groups in Registry.

As with COmanage Registry, this plugin uses a Model View Controller (MVC)
framework built on CakePHP version 2.x.

See [README.md](README.md) for the design, the Dataverse server configuration
it requires, and the mapping between Registry and Dataverse objects.

## Coding Style

Match the existing COmanage Registry style: two-space indentation, opening
braces on the same line, `array()` syntax, and the COmanage license header and
docblocks (`@since`, `@param`, `@return`). Do not run an autoformatter
(php-cs-fixer, Pint, etc.) on this repository.

## Testing

The account ownership rules in `Lib/DataverseOwnership.php` have PHPUnit tests
under `Test/Case/Lib` that run outside Registry: `composer install`, then
`vendor/bin/phpunit`. Run them after changing anything in `Lib/` or the model.
Everything else needs a Registry deployment with a Dataverse server, such as
CADRE TEST; there is no test harness for the model or controllers.

## Pushing

This repository is set up for the machine account `skoranda-agent`; the global
"Machine account" rules apply.

- `bot` -> `https://github.com/skoranda-agent/DataverseProvisioner.git` (Claude
  pushes feature branches here).
- `upstream` -> `https://github.com/cilogon/DataverseProvisioner.git`
  (canonical; pull requests target it).
- `origin` -> `https://github.com/skoranda/DataverseProvisioner.git` (the
  developer's personal fork; Claude does not push here).

Shipping flow: push the feature branch to `bot`, then open a ready-for-review
pull request with
`gh pr create --repo cilogon/DataverseProvisioner --base main --head skoranda-agent:<branch>`.
Before any push, confirm `gh api user --jq .login` prints `skoranda-agent` and
confirm the `bot` URL with `git remote -v`.

Never push to `upstream` or `origin`, never push `main` anywhere, and never
approve or merge a pull request.
