# LaPills 2.0

Please note that current module is in the early stages of development and could
be unstable or have serious issues.
Please do not use it in production environment.

## System requirements

* Drupal 8 (8.7.x or newer)
* PHP 7.1 or newer

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
* Manual changes
  - Visit the `Basic site settings` configuration page located at
  `admin/config/system/site-information` and set `Front page` field value to
  `/la_pills/home`. This should replace the default home page with one specific
  to LaPills

The module is automatically loading any packaged session templates into the
database. It is also possible to upload new templates later through the
administration interface. Existing templates could also be removed, along with
any data gathering session entities that have been based on that template.

### Sub-modules

* LA Pills Timer - a module that provides activity logging functionality to
data gathering sessions. A user with sufficient permissions could create
activities to be tracked. The ones that are currently active could be attached
to a newly created or an existing session (copies are made). Later on session
owner could use those for the activity tracking effort.
  - Install the module and configure permissions
  - Currently recommended permissions are (TEACHER is an example role that would
    be creating content)
    - Create new Timer and Timer sessions - TEACHER USER
    - View active Timer and Timer sessions - TEACHER USER
    - View inactive Timer and Timer sessions - TEACHER USER
* LA Pills Quick Feedback - a module that provides quick feedback functionality
to data gathering sessions. A user with sufficient permissions could create
questions and mark them as active. Active ones can be used to construct a Quick
Feedback questionnaire for an existing session, making copies of question data.
This newly added questionnaire has all the questions marked as optional and
behaves in a similar manner to others. Quick Feedback questionnaire replies are
available for download along with the rest of the data. Dashboard would also
show a visualisation for it (similar to others), yet it would only be displayed
to the teacher.
  - Install module and configure permissions
  - Currently recommended permissions are (TEACHER is an example role that would
    be creating content)
    - Create new Question and Questionnaire entities - TEACHER USER
    - View published Question entities - TEACHER USER
  - Make sure that required libraries are installed in
  `DRUPAL_WEB_ROOT/libraries`. Use `la_pills_quick_feedback.libraries.yml` as a
  source for information
    - [Font Awesome](https://fontawesome.com/) in `libraries/fontawesome`, only
    minified versions of JS and CSS + assets are really required

## Themes

[Bootstrap](https://www.drupal.org/project/bootstrap) theme is a must as large
parts of the UI are based on visual elements and capabilities provided by the
Bootstrap component library.

## Optional modules

* [Redirect after login](https://www.drupal.org/project/redirect_after_login) as
a means of redirecting specific user role to a certain URL. Currently it is
suggested to redirect user without permission to create sessions to PIN code
entry page and the rest of the roles to session list page.
  - Authenticated: `/session_entity_code`
  - Teacher: `/admin/structure/session_entity`
  - Admin: `/admin/structure/session_entity`
* [Dropdown Language](https://www.drupal.org/project/dropdown_language) as a
means of adding a language select. It does play well with
[Bootstrap](https://www.drupal.org/project/bootstrap) theme and our own codebase
has a **small fix** to pace it to the right and apply small UI tunes if suitable
theme is active.
  - UI fix only applies if dropdown element is added to the footer of the page
