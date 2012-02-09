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

form_id=""
==========
Required - this needs to be set to something unique

return=""
=========
The path to redirect the user to after registration

error_handling="inline"
=======================
Turns on inline error handling

error_delimiters=""
===================
Specify the code you want to wrap the error messages in, if you are using inline error handling.
For example::

    error_delimiters="<span class="error">|</span>"

**********************
Register Tag Variables
**********************



For the registration form to work, you just need to submit fields with ``name="password"``,
``name="password_confirm"``, and ``name="email"``. You can optionally template fields with
``name="username"`` and ``name="screenname"``. If username and screen name aren't submitted,
they will be set to the email address. If the username but not the screenname is submitted,
the screenname will be set to the username

You also need to set a field with ``name="accept_terms"`` to any value - this can either be
done with a hidden field as in our example, or as a checkbox that the user has to tick.
If the setting in your config is set to require terms of service, then as long as this
field is submitted it will validate (ie, it can have any value, just has to have
``name="accept_terms"``. You can template errors using ``{error:accept_terms}``

Custom field processing and 'edit' functionality coming soon!

{captcha}
=========
Displays a captcha image. You can also use {if captcha} to conditionally display it
(if it has been turned on in the EE settings), and a text input with ``name="captcha"``
to submit the code.

{error:fieldname}
=================
Displays any errors for a field, such as ``{error:captcha}`` or ``{error:email}``

********************
Register Tag Example
********************
::

  {exp:freemember:register form_id="register" return="account" error_handling="inline" error_delimiters='<span class="error">|</span>'}

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
      <label for="password_confirm">Confirm Password</label><br />
      <input type="password" name="password_confirm" value="" /><br />
      {error:password_confirm}
    </p>

    {if captcha}
      <p>
        <label for="captcha">Captcha</label><br />
        {captcha}<br />
        <input type="text" name="captcha" value="" /><br />
        {error:captcha}
      </p>
    {/if}

    <p>
      <input type="checkbox" name="accept_terms" value="y" />
      <label for="accept_terms">Accept Terms</label>
    </p>

    <p>
      <input type="submit" value="Submit" />
    </p>

  {/exp:freemember:register}
