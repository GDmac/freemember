##################
Update Profile Tag
##################
::

  {exp:freemember:update_profile}

The Update Profile tag lets you create a form for the current user to update their details,
including changing their email address, password, and any custom fields.

.. contents::
  :local:

*****************************
Update Profile Tag Parameters
*****************************

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
The path to redirect the user to after successfully updating the member details.

error_handling
==============
Enable inline error handling by setting error_handling to "inline".

error_delimiters
================
Specify the code you want to wrap the error messages in, if you are using inline error handling.
For example::

    {exp:freemember:update_profile error_handling="inline" error_delimiters="<span class="error">|</span>"}

****************************
Update Profile Tag Variables
****************************

The following input fields are available in the Update Profile form. Each field should be submitted
as a standard HTML form input element. To speed things up, helper variables are available to
generate the necessary HTML for you.

{email}
=======
The current member's email address.

{confirm_email}
===============
If this field exists in your form, it must match the ``{email}`` field.

{username}
==========
The current member's username.

{error:fieldname}
=================
As per the example, this will display errors for the specified fieldname, such as
`{error:password}` or `{error:email}`

*****************
Login Tag Example
*****************
::

    {exp:freemember:update_profile return="account" require="current_password" error_handling="inline" error_delimiters='<span class="error">|</span>'}

        <p>
            <label for="email">Email</label><br />
            {field:email}<br />
            {error:email}
        </p>

        <p>
            <label for="screen_name">Screen Name</label><br />
            {field:screen_name}<br />
            {error:screen_name}
        </p>

        <p>
            <label for="current_password">Current Password</label><br />
            {field:current_password}<br />
            {error:current_password}
        </p>

        <p>
            <label for="password">New Password</label><br />
            {field:password}<br />
            {error:password}
        </p>

        <p>
            <label for="password_confirm">Confirm New Password</label><br />
            {field:password_confirm}<br />
            {error:password_confirm}
        </p>

        <p>
            {field:auto_login} <label for="auto_login">Remember Me</label>
        </p>

        <p>
            <input type="submit" value="Log in" />
            <a href="{path='account/register'}">Create Account</a>
        </p>

    {/exp:freemember:update_profile}
