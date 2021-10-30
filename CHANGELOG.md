xxx.xx.xx
- [ ] Fix bit.ly multi domain for short links
- [ ] Add bit.ly custom domain
- [ ] Add public page of the list of know urls and in/visible status
- [ ] Add passworded links
- Never fix breaking comments or complexe public url (add warning)
- fix permissions
- fix js load
- add user pref on table cols
- add user pref on filters (dc 2.20)

2021.09.16
- remove deprecated external service
- update php header to phpdoc style
- fix post page options display
- hack plugin permission to load plugin and shorten url on the fly

2021.08.28 - pre release
- add dashboard icon
- clean PSR-2 codding style and short array
- fix php 7.3+ and php 8.0.x compatibility
- fix compatibility with Dotclear 2.19
- create readme file
- merge index file sub parts
- update admin pager for links list
- remove custom js
- fix widgets rendering
- fix public template now based on dotty
- fix translation

2011.04.01
- Changed version numbering
- Added service su.pr (stumbleUpon)
- Fixed wiki settings (thanks @ploum )

1.0 - 2011.02.13
- Added generic class to easlily access services
- Changed generic service class (and class extend it)
- Fixed config of default service
- Fixed display of admin fake section
- Added checkbox helpers on admin

0.6.1 - 2011.01.30
- Cleaned up script

0.6 - 2011.01.18
- Added default defined service (for all blogs of a multiblog)
- Added configurable external service
- Added goog.gl client service (first step)
- Added default settings for third part plugins
- Added behaviors after short link creation
- Added attribute to disable URL shortining on template tag with 'active mode' (fixed bug on URL of POST form)
- Remove all messenger functions: this is to another plugin to do that
- Remove priority in plugin definition

0.5 - 2010.09.09
- Removed old Twitter functions
- Added StatusNet small functions (Identica)
- Required plugin Tac for Twitter ability
- Added YOURLS client service

0.4.2 - 2010.08.09
- Fixed bug on dcTwitter shorten service
- Fixed bug on custom local link
- Added category URL to active mode
- Added priority to plugin definition

0.4.1 - 2010.07.01
- Fixed multiple bugs

0.4 - 2010.06.28
- Switched to DC 2.2
- Fixed no short urls on preview mode
- Fixed lock hash of deleted urls
- Fixed hide new short url widget on kutrl pages
- Fixed typo
- Added active mode that shorten urls on default template values
- Added special tweeter message for post (can include post title)
- Added kutrl special 404 page

0.3.3 - 2010.05.28
- Fixed settings in tweeter class
- Renamed tweeter class

0.3.2 - 2010.05.25
- FIxed minor bugs
- Fixed DC 2.1.7

0.3 2010.04.14
- Added DC 2.2 compatibility (new settings)
- Added semi-custom hash on kUtRL service
- Added status update for Twitter/Identi.ca on new short link
- Added services error management (first step)
- Added options to widgets
- Upgraded bitly service to v3
- Changed admin design

0.2 - 2009.12.23
- Fixed public redirection with suffix
- Added short.to service

0.1.2 - 2009.12.12
- Added option to short url of new entry by default
- Fixed typo

0.1.1 - 2009.12.12
- Added option to display long url when unactive
- Fixed support of kutrl in feeds

0.1 - 2009.12.09
- First lab release