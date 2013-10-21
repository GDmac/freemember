# FreeMember Release Notes

## FreeMember 2.3.0
*Released October 20, 2013*

* ExpressionEngine 2.6+ is now required.
* Use EE 2.6 `ee()` syntax everywhere
* Use native in EE models to update member data.
* Add `freemember_update_member_start` and `freemember_update_member_custom_start` hooks to
  edit member data before updating database.
* Fix password lockout time language string

## FreeMember 2.2.2
*Released May 4, 2013*

* Avoid calling do_hash() method deprecated in ExpressionEngine 2.6

## FreeMember 2.2.1
*Released March 5, 2013*

* Fix a [bug](http://expressionengine.stackexchange.com/q/6538/150) causing a "not authorized"
  error message after submitting some FreeMember forms.

## FreeMember 2.2.0
*Released February 18, 2013*

* Use CodeIgniter encryption library to encrypt form parameters (upgrading to FreeMember 2.2
  requires setting an encryption key in your `config.php` file). This change makes FreeMember forms
  play nicely with template caching where the same cached form may be used for multiple requests.

## FreeMember 2.1.3
*Released February 17, 2013*

* Fix `return="PREVIOUS_URL"` not working when `PREVIOUS_URL` was the home page

## FreeMember 2.1.2
*Released February 16, 2013*

* Add `secure_action="yes"` and `secure_return="yes"` parameters to all tags

## FreeMember 2.1.1
*Released December 31, 2012*

* Add support for `group_id` parameter in register tag
* Fix birthdate fields not being set in register tag

## FreeMember 2.1.0
*Released September 16, 2012*

* Add support for "select" type custom fields in registration and update tags

## FreeMember 2.0.2
*Released September 7, 2012*

* Allow the return url to be specified using GET variables.

## FreeMember 2.0.1
*Released June 3, 2012*

* Added return="PREVIOUS_URL" parameter to the login tag.
* Fixed a bug where logout was redirecting back one page too many.
* Moved documentation to the GitHub wiki.

## FreeMember 2.0.0
*Released May 29, 2012*

* Complete rewrite with support for custom member fields, member profile editing, and improved error handling.

## FreeMember 1.1.2
*Released February 10, 2011*

* Added `form_id`, `form_name` and `form_class` parameters to all tags.
* Added missing documentation for Register tag.

## FreeMember 1.1.1
*Released February 8, 2011*

* ExpressionEngine 2.4 support.

## FreeMember 1.1.0
*Released August 24, 2011*

* Added Forgot Password support.
* NSM add-on updater support.

## FreeMember 1.0.0
*Released July 23, 2011*

* Initial release.
