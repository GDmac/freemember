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

form_id=""
==========
Required - this needs to be set to something unique

form_name=""
============
Sets the name attribute on the generated form

form_class=""
=============
Sets the class attribute on the generated form

return=""
=========
The path to redirect the user to after login

error_handling="inline"
=======================
Turns on inline error handling

error_delimiters=""
===================
Specify the code you want to wrap the error messages in, if you are using inline error handling.
For example::

    error_delimiters="<span class="error">|</span>"

*******************
Login Tag Variables
*******************

For the login form to work, you need a field with `name="password"`. You then also need a field
with `name="email"` or one with `name="password"`.

You can also turn on auto-login by setting a field (either a hidden one or a checkbox)
with `name="auto_login"` to `value="y"`

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
      <input type="email" name="email" value="{email}" /><br />
      {error:email}
    </p>

    <p>
      <label for="password">Password</label><br />
      <input type="password" name="password" value="" /><br />
      {error:password}
    </p>

    <p>
      <input type="checkbox" name="auto_login" value="y" {auto_login_checked} />
      <label for="auto_login">Remember Me</label>
    </p>

    <p><input type="submit" value="Log in" /></p>

    <p><a href="{path='account/register'}">Create Account</a></p>

  {/exp:freemember:login}
