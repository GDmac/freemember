##############
Logout Url Tag
##############
::

  {exp:freemember:logout_url}

Sick of that annoying notification page when you log out? This tag creates a link to the
FreeMember logout action, which will log the user out and instantly redirect them on to their
destination.

.. contents::
  :local:

*********************
Logout Tag Parameters
*********************

return
======
If this parameter is present, the user will be redirected to the specified path after clicking
the logout link. If this parameter is omitted, the user will be redirected to the current page.

******************
Logout Tag Example
******************
::

    <a href="{exp:freemember:logout_url}">Log out</a>
