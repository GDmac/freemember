##########
Logout Tag
##########
::

  {exp:freemember:logout}

Sick of that annoying notification page when you log out? Just stick this tag by itself
on a template, then link to this template, to log the current member out. You can optionally
set a return parameter to redirect the user after logout.

.. contents::
  :local:

*****************
Logout Parameters
*****************

return=""
=========
Specify a path to redirect the user to after logging them out. By default the logout tag will
return to the previous page.

**************
Logout Example
**************
::

    {exp:freemember:logout return="/"}
