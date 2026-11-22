# kUtRL

Notes for AI assistants working on the Dotclear plugin kUtRL.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Use, create and serve short url on your blog A [user guide in French](https://github.com/JcDenis/kUtRL/blob/master/docs/fr/utilisation.md) is available.

Admin page and configuration page. Public side: widgets, URL handlers.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/ClaudetteMcp/`: tools for the plugin ClaudetteMcp (`list_short_links`, `create_short_link`, never required),
  registered in `src/Frontend.php` with the behavior `ClaudetteMcpAddTool`. Same checks as the admin page `Manage.php`.
* `src/ActivityReportAction.php`: add links actions to the plugin activity report.
* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Combo.php`
* `src/Config.php`: configuration page of the module (plugins manager).
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/FrontendBehaviors.php`: public behaviors.
* `src/FrontendTemplate.php`: template tags.
* `src/FrontendUrl.php`: public URL handler.
* `src/FrontendUtils.php`: frontend utils.
* `src/ImportExportBehaviors.php`: behaviors.
* `src/Install.php`: install and update, database structure (a change needs a migration here).
* `src/LinksListing.php`: links listing.
* `src/Logs.php`: logs class.
* `src/Manage.php`: admin page of the module.
* `src/ManageLinks.php`: admin page (links).
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/Service.php`: service class.
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `src/Utils.php`: helpers.
* `src/Widgets.php`: widgets definition and rendering.
* `src/Wiki.php`: wiki stuff.
* `src/Service/ServiceBilbolinks.php`: bilbolinks service class.
* `src/Service/ServiceBitly.php`: btily service class.
* `src/Service/ServiceCustom.php`: custom service class.
* `src/Service/ServiceDefault.php`: note: "default" ne veut pas dire service par défaut.
* `src/Service/ServiceIsgd.php`: is.gd service class.
* `src/Service/ServiceLocal.php`: local service class.
* `src/Service/ServiceYourls.php`: yourls service class.
* `js/`: `admin.js`, `posts.js`.
* `default-templates/`: public templates.
* Database table `kutrl`.
* URL handlers: `kutrl`.
* Widgets: `shortenkutrl`, `rankkutrl`.
* Behaviors used: `ClaudetteMcpAddTool`, `adminAfterPostUpdate`, `kutrlService`, `adminDashboardFavoritesV2`, `adminColumnsListsV2`, `adminFiltersListsV3`, `adminPostHeaders`, `adminPostFormItems`, `adminAfterPostCreate`, `adminBeforePostDelete`, `adminPostsActions`, `initWidgets`, `exportFullV2`, `exportSingleV2`, `importInitV2`, `importSingleV2`, `importFullV2`, `publicBeforeDocumentV2`, `publicHeadContent`, `publicBeforeContentFilterV2`, `coreInitWikiPost`, `coreInitWikiComment`, `coreInitWikiSimpleComment`.

## Pitfalls

* Table structure changes need a migration in `src/Install.php` (version compare), and must work on MySQL, PostgreSQL and SQLite.

## Testing

* Check the admin page, the configuration page, the widgets (admin and public), the public URLs, the admin help, in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
* The user guide `docs/fr/utilisation.md` describes the features: update it, and its screenshots, when the interface changes.
