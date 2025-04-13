=== Ad Unblock ===
Contributors: adunblock
Tags: ad blocker, revenue recovery, monetization, ad recovery
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate your WordPress site with Ad Unblock service to recover ad revenue lost to ad blockers.

== Description ==

Ad Unblock plugin allows you to easily integrate your WordPress site with the [Ad Unblock](https://ad-unblock.com) service, which helps you recover ad revenue lost due to ad blockers.

= Key Features =

* Simple verification code setup
* Control which pages the script runs on (all pages, specific URL patterns, categories, or tags)
* Automatically inserts the required script into your site's header
* Caches API requests for optimal performance

= How It Works =

1. Sign up for an account at [ad-unblock.com](https://ad-unblock.com)
2. Add your website in the Ad Unblock dashboard
3. Get your verification code from the Integration → Verification page
4. Enter the verification code in the plugin settings
5. Configure on which pages the script should run
6. Start recovering your ad revenue!

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ad-unblock` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → Ad Unblock to configure the plugin
4. Enter your verification code from the Ad Unblock dashboard
5. Configure which pages should have the script enabled

== External services ==

This plugin connects to the Ad Unblock service (https://config.adunblocker.com/valid_script_sources.json) to obtain script source URLs. The script source URLs may change hence the server side fetch.

= Data Transmitted =
* The plugin fetches script source URLs from https://config.adunblocker.com/valid_script_sources.json (cached for 5 minutes)
* No personal user data is collected or transmitted by the plugin itself

The external service is provided by Ad Unblock:
* [Terms of Service](https://ad-unblock.com/terms)
* [Privacy Policy](https://ad-unblock.com/privacy/wp-plugin)

== Frequently Asked Questions ==

= Do I need an Ad Unblock account to use this plugin? =

Yes, you need to register at [ad-unblock.com](https://ad-unblock.com) to get a verification code.

= Where do I find my verification code? =

After registering and adding your website on ad-unblock.com, go to Integration → Verification page in your Ad Unblock dashboard.

= Can I control which pages have the script? =

Yes, you can choose to enable the script on all pages, specific URL patterns, categories, or tags.

= Does this plugin slow down my website? =

No, the plugin is designed to be lightweight and efficient. It uses caching to minimize API requests and loads the script asynchronously to avoid affecting page load times.

== Screenshots ==

1. Admin settings page where you can configure your verification code and page rules.

== Changelog ==

= 1.0.0 =
* Initial release
* Added logo and "AdUnblock" text to admin interface
* Improved admin page styling for better user experience
* Added helpful links to support and documentation

== Upgrade Notice ==

= 1.0.0 =
Initial release of the Ad Unblock plugin. 