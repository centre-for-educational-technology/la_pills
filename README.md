# LaPills 2.0

Please note that current module is in the early stages of development and could
be unstable or have serious issues.
Please do not use it in production environment.

## System requirements

* Drupal 8 (8.6.x or newer)
* PHP 7.0 or newer

## Usage and running in development

* Setup Drupal 8 instance
* Go to `DRUPAL_ROOT/modules` and run
`git clone git@github.com:centre-for-educational-technology/la_pills.git`
* Make sure that required libraries are installed in
`DRUPAL_WEB_ROOT/libraries`. Use `la_pills.libraries.yml` as a source for
information
  - [d3.js](https://d3js.org/) in `libraries/d3` with only `d3.min.js` being
  used
  - [c3.js](https://c3js.org/) in `libraries/c3` with only `c3.min.js` and
  `c3.min.css` being used
* Go into administration and activate the module
* Visit `structure/session_entity` for the list of available sessions and their
management
* It would be needed to configure the permissions in order for certain user
roles to have access to pages
  - These are the currently recommended default permissions (TEACHER is an
    example role that would be creating content)
    - Create new LA Pills Session entities - TEACHER USER
    - Edit own LA Pills Session entities - TEACHER USER
    - View published LA Pills Session entities - ANONYMOUS USER
    - View unpublished LA Pills Session entities - TEACHER USER
* **NB!** Uninstall the **Internal Page Cache (page_cache)** module to resolve
cache issues for Anonymous users

The module is automatically loading any packaged session templates into the
database. Please note that it currently only doing that once and it would be
needed to **deactivate**, **uninstall** and **reinstall** the module to start
afresh in case of any changes.
That is the currently suggested course of action after any significant code
updates that tamper with database (it might be safe to always do that).

## Themes

Module should be able to work well enough with any themes, though currently
using [Bootstrap](https://www.drupal.org/project/bootstrap) might provide the
best results as visuals are being fine-tuned for that theme specifically.
