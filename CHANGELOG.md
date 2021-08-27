todo
- Added public page of the list of know urls and in/visible status
- Added passworded links
- clean PSR-2 codding style and short array
- fix php 7.3+ and php 8.0.x compatibility
- fix compatibility with Dotclear 2.19
- fix widgets rendering
- move setting to config file
- fix dashboard rendering
- fix deprecated external service
- create readme file

2021.08.27
- add dashboard icon

2011.04.01
 * Changed version numbering
 * Added service su.pr (stumbleUpon)
 * Fixed wiki settings (thanks @ploum )

1.0 20110213
 * Added generic class to easlily access services
 * Changed generic service class (and class extend it)
 * Fixed config of default service
 * Fixed display of admin fake section
 * Added checkbox helpers on admin

0.6.1 20110130
 * Cleaned up script

0.6 20110118 'prepare the soCial révolution'
 * Added default defined service (for all blogs of a multiblog)
 * Added configurable external service
 * Added goog.gl client service (first step)
 * Added default settings for third part plugins
 * Added behaviors after short link creation
 * Added attribute to disable URL shortining on template tag with 'active mode' (fixed bug on URL of POST form)
 * Remove all messenger functions: this is to another plugin to do that
 * Remove priority in plugin definition

0.5 20100909
 * Removed old Twitter functions
 * Added StatusNet small functions (Identica)
 * Required plugin Tac for Twitter ability
 * Added YOURLS client service

0.4.2 20100809
 * Fixed bug on dcTwitter shorten service
 * Fixed bug on custom local link
 * Added category URL to active mode
 * Added priority to plugin definition

0.4.1 20100701
 * Fixed multiple bugs

0.4 20100628
 * Switched to DC 2.2
 * Fixed no short urls on preview mode
 * Fixed lock hash of deleted urls
 * Fixed hide new short url widget on kutrl pages
 * Fixed typo
 * Added active mode that shorten urls on default template values
 * Added special tweeter message for post (can include post title)
 * Added kutrl special 404 page

0.3.3 20100528
 * Fixed settings in tweeter class
 * Renamed tweeter class

0.3.2 20100525
 * FIxed minor bugs
 * Fixed DC 2.1.7

0.3 20100414
 * Added DC 2.2 compatibility (new settings)
 * Added semi-custom hash on kUtRL service
 * Added status update for Twitter/Identi.ca on new short link
 * Added services error management (first step)
 * Added options to widgets
 * Upgraded bitly service to v3
 * Changed admin design

0.2 20091223
 * Fixed public redirection with suffix
 * Added short.to service

0.1.2 20091212
 * Added option to short url of new entry by default
 * Fixed typo

0.1.1 20091212
 * Added option to display long url when unactive
 * Fixed support of kutrl in feeds

0.1 20091209
 * First lab release