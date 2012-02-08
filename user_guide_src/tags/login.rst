#########
Login Tag
#########
::

  {exp:freemember:login}

As the name suggests, this tag allows you to create a login form with inline error handling,
which bypasses the default EE login notification screen.

.. contents::
  :local:

****************
Login Parameters
****************

form_id=""
==========
Required - this needs to be set to something unique

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

***************
Login Variables
***************

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

*************
Login Example
*************
::

    {exp:freemember:login form_id="login" return="account" error_handling="inline" error_delimiters='<span class="error">|</span>'}

        <table class="pad">
            <tr>
                <td style="width: 30%"><label for="email">Email</label></td>
                <td>
                    <input type="email" name="email" value="{email}" />
                    {error:email}
                </td>
            </tr>
            <tr>
                <td><label for="password">Password</label></td>
                <td>
                    <input type="password" name="password" value="" />
                    {error:password}
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <label><input type="checkbox" name="auto_login" value="y" {auto_login_checked} /> Remember Me</label>
                </td>
            </tr>
        </table>
        <p class="right">
            <a href="{path='account/register'}">Create Account</a>&nbsp;&nbsp;
            <input type="submit" value="Log in" class="button" />
        </p>

    {/exp:freemember:login}
