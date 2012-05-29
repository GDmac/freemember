###################
Forgot Password Tag
###################
::

  {exp:freemember:forgot_password}

The Forgot Password tag allows users to reset their password. You simply need to submit a valid
email address, and the user will be mailed a link to reset their password.

By default, the email link will simply generate a new password and mail it to them (the standard
EE process). To make the forgot password process more user friendly, use the reset="" parameter
and the Reset Password tag to allow users to choose a new password straight away.

.. contents::
  :local:

******************************
Forgot Password Tag Parameters
******************************

form_id
=======
Set the id attribute on the generated form.

form_name
=========
Set the name attribute on the generated form.

form_class
==========
Set the class attribute on the generated form.

reset
=====
Specify the path to your reset password template. This template will allow the user to choose
a new password after they click the link in their email, instead of sending them a
randomly-generated one.

return
======
The path the user will be redirected to after they enter a valid email address. On this page
you should direct them to check their email for a reset link.

error_handling
==============
Enables inline error-handling.

error_delimiters
================
Specify delimiters which will be wrapped around any inline error messages.

*******************************
Forgot Password Tag Form Fields
*******************************

email
=====
Allow the user to enter their email address, to gain access to their account. Use the field helper
to add it to your form::

    {field:email}

Errors relating to the username are available with the error helper::

    {error:email}

***************************
Forgot Password Tag Example
***************************
::

    {exp:freemember:forgot_password return="account/forgot_sent" error_handling="inline" error_delimiters='<span class="error">|</span>'}

        <p>
            <label for="email">Email</label><br />
            {field:email}<br />
            {error:email}
        <p>

        <p>
            <input type="submit" value="Submit" />
        </p>

    {/exp:freemember:forgot_password}
