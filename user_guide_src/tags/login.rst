#########
Login Tag
#########
::

  {exp:freemember:login}

As the name suggests, this tag allows you to create a login form with inline error handling,
which bypasses the default EE login notification screen.

.. contents::
  :local:

********************
Login Tag Parameters
********************

form_id
=======
Set the id attribute on the generated form.

form_name
=========
Set the name attribute on the generated form.

form_class
==========
Set the class attribute on the generated form.

return
=========
The path to redirect the user to after login.

group_id_X_return
=================
Specify a custom return path for members of a specific group. For example::

    {exp:freemember:login return="account" group_id_1_return="account/admin"}

error_handling
==============
Enable inline error handling by setting error_handling to "inline".

error_delimiters
================
Specify the code you want to wrap the error messages in, if you are using inline error handling.
For example::

    {exp:freemember:login error_handling="inline" error_delimiters="<span class="error">|</span>"}

*******************
Login Tag Variables
*******************

The login form must contain at the very least a ``password`` input, and a ``username`` or
``email`` input.

You can also enable auto-login by setting a field (either a hidden one or a checkbox)
with `name="auto_login"` to `value="1"`.

{email}
=======
If your form contains errors and the page is reloaded, set the email input field's value
to this to remember the previously entered address.

{username}
==========
If your members log in with a username instead of an email address, this will pre-fill the
username field if the form validation fails.

{auto_login_checked}
====================
As above, this keeps the auto login checkbox checked if your form validation fails.

{error:fieldname}
=================
As per the example, this will display errors for the specified fieldname, such as
`{error:password}` or `{error:email}`

*****************
Login Tag Example
*****************
::

    {exp:freemember:login form_id="login" return="account" error_handling="inline" error_delimiters='<span class="error">|</span>'}

        <p>
            <label for="email">Email</label><br />
            {field:email}<br />
            {error:email}
        </p>

        <p>
            <label for="password">Password</label><br />
            {field:password}<br />
            {error:password}
        </p>

        <p>
            {field:auto_login} <label for="auto_login">Remember Me</label>
        </p>

        <p>
            <input type="submit" value="Log in" />
            <a href="{path='account/register'}">Create Account</a>
        </p>

    {/exp:freemember:login}
