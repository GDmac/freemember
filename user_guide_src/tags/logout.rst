##########
Logout Tag
##########
::

  {exp:freemember:logout}

.. note::
    This tag is deprecated. You should use the :doc:`logout_url` instead.

The logout tag should be placed on a separate template. As soon as the page is displayed, the
current user will be logged out and redirected to their destination.

.. contents::
  :local:

*********************
Logout Tag Parameters
*********************

return
======
Specify a path to redirect the user to after logging them out. By default the logout tag will
return to the previous page.

******************
Logout Tag Example
******************
::

    {exp:freemember:logout return="/"}
