# FreeMember Logout Tag (Deprecated)

    {exp:freemember:logout}

**This tag is deprecated. You should use the [[Logout Url Tag]] instead.**

The logout tag should be placed on a separate template. As soon as the page is displayed, the
current user will be logged out and redirected to their destination.

## Parameters

* `return` - Specify a path to redirect the user to after logging. By default it will return to the previous page.

## Example

    {exp:freemember:logout return="/"}
