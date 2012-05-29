############
Register Tag
############
::

  {exp:freemember:register}

This tag allows you to template a simple member registration form with inline error handling
and no annoying default EE notification page after registration.

Make sure to check the member registration settings in your control panel to make sure the
process behaves the way you wish.

.. contents::
  :local:

***********************
Register Tag Parameters
***********************

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
======
The path to redirect the user to after registration

error_handling
==============
Turns on inline error handling

error_delimiters
================
Specify the code you want to wrap the error messages in, if you are using inline error handling.
For example::

    error_delimiters="<span class="error">|</span>"

************************
Register Tag Form Fields
************************

For the registration form to work, you you must submit the ``email``, ``password``, and
``password_confirm`` fields. The rest of the fields are optional. If the ``username`` is not
submitted, it defaults to the email address. Likewise, if the ``screen_name`` is not submitted,
it defaults to the ``username``.

In addition, you may submit any of the other member fields (and custom member fields) as
available in the :doc:`update_profile`.

email
=====
Allow the user to specify their email address. This must be unique across all members in your
site. Use the field helper to add this to your form::

    {field:email}

Errors relating to the email address are available with the error helper::

    {error:email}

email_confirm
=============
If this field is submitted, it must match the {email} field.

username
========
Allow the user to choose a username. This must be unique across all members in your site.
Use the field helper to add this to your form::

    {field:username}

Errors relating to the username are available with the error helper::

    {error:username}

screen_name
===========
Allow the user to choose a screen name. This must be unique across all members in your site.
Use the field helper to add this to your form::

    {field:screen_name}

Errors relating to the screen name are available with the error helper::

    {error:screen_name}

accept_terms
============
If you have enabled it in the EE config, this field must be submitted along with your form. You
can either submit it as a hidden field, or use the field helper to add a checkbox to your form::

    {field:accept_terms} <label for="accept_terms">I accept the terms & conditions</label><br />

Errors relating to the accept terms checkbox are available with the error helper::

    {error:accept_terms}

captcha
=======
If you have captchas enabled, you must display the captcha and add a field to your registration
form. Use the following code as a template::

    {if captcha}
        Please enter the following characters into the box below:<br />
        {captcha}<br />
        {field:captcha}<br />
        {error:captcha}
    {/if}

********************
Register Tag Example
********************
::

    {exp:freemember:register return="account" error_handling="inline" error_delimiters='<span class="error">|</span>'}

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
            <label for="password_confirm">Confirm Password</label><br />
            {field:password_confirm}<br />
            {error:password_confirm}
        </p>

        <p>
            {field:accept_terms} <label for="accept_terms">Accept Terms</label><br />
            {error:accept_terms}
        </p>

        <p>
            <input type="submit" value="Submit" />
        </p>

    {/exp:freemember:register}
