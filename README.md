# DAMo - Distribution

Drupal 8 distribution to kickstart Digital Assets Management projects.

## Important note

Heavily under development, not yet usable.

## Installation

Using the [DAMo composer project](https://github.com/brainsum/damo-project) is recommended.

## User roles

* **Media API** user is for the API users, e.g. for an interconnected Drupal system where the https://www.drupal.org/project/filefield_sources_jsonapi module is installed.
* **Agency** is for external users like graphic designers/agencies, photographers. They can just upload media assets for approval, which means a Manager will need to approve (publish) them first before they appear in the DAM library.
* **Authenticated user** is for simple "read-only" users, they can browse, search, view, download assets.
* **Manager** is the highest level regarding the DAM functionality. They can fully manage the content of the DAM.
* **Administrator** is like a superuser who can manage users, but also the full settings of the site. Give it only to people who know Drupal.

## Drupal 9 upgrade info

`0.27.x` versions pave the path for full Drupal 9 readiness and contain breaking changes.
Sites created with `0.26.2` or prior need to do the following manual steps before upgrading to Drupal 9:

- `better_formats` is a dead module with no D9 compatibility. If you need features from it:
    - Back up your config and prepare to migrate to `allowed_formats`
    - Update to `0.27.0` (this uninstalls the `better_formats` module)
    - Add `allowed_formats` and configure it
    - See: <https://www.drupal.org/project/allowed_formats>
- `brainsum/jquery_ui_datepicker` was built on the core datepicker library but that has been removed in D9, meaning the module has been removed.
    - If it's still needed, some compatibility waw achieved in the new 3.0 version, but the CSS is broken (although the widget is still usable).
        - Users should not upgrade to this but use a different module instead. As of now no replacement module is available. 
    - In line with this module getting removed the `field_expiration_date` field has been also removed from the default config. 

Note, although uninstalled from Drupal, the composer.json still contains them. They are deprecated, and are going to be removed in `0.28.0`.
