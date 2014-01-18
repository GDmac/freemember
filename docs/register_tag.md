# FreeMember Register Tag

    {exp:freemember:register}

This tag allows you to template a simple member registration form with inline error handling
and no annoying default EE notification page after registration.

Make sure to check the member registration settings in your control panel to make sure the
process behaves the way you wish.

## Register Tag Parameters

### form_id
Set the id attribute on the generated form.

### form_name
Set the name attribute on the generated form.

### form_class
Set the class attribute on the generated form.

### return
The path to redirect the user to after registration

### require
Specify multiple pipe-separated required fields for your form. For example:

    require="first_name|last_name"

### rules:field_name
Specify SafeCracker-style validation rules for your form. For example:

    rules:first_name="required|min_length[5]"

### error_handling
Turns on inline error handling

### error_delimiters
Specify the code you want to wrap the error messages in, if you are using inline error handling.
For example:

    error_delimiters="<span class="error">|</span>"

### group_id
Specify the group ID of the new member. If this parameter is not set, the ExpressionEngine default
member group will apply. If you wish to let the member choose their member group, you can whitelist
valid groups, then submit a matching `group_id` form field:

    {exp:freemember:register group_id="5|6|7"}
        <input type="text" name="group_id" value="7" />
    {/exp:freemember:register}

## Register Tag Form Fields

For the registration form to work, you you must submit the ``email``, ``password``, and
``password_confirm`` fields. The rest of the fields are optional. If the ``username`` is not
submitted, it defaults to the email address. Likewise, if the ``screen_name`` is not submitted,
it defaults to the ``username``.

In addition, you may submit any of the other member fields (and custom member fields) as
available in the [Update Profile Tag](update_profile_tag.md).

Note: If you have set any member fields as "required" in the ExpressionEngine control panel,
you should either make sure that you have also specified those fields in the `require=""` parameter,
or you should not mark them as required in the control panel.

### email
Allow the user to specify their email address. This must be unique across all members in your
site. Use the field helper to add this to your form::

    {field:email}

Errors relating to the email address are available with the error helper::

    {error:email}

### email_confirm
If this field is submitted, it must match the {email} field.

### username
Allow the user to choose a username. This must be unique across all members in your site.
Use the field helper to add this to your form::

    {field:username}

Errors relating to the username are available with the error helper::

    {error:username}

### screen_name
Allow the user to choose a screen name. This must be unique across all members in your site.
Use the field helper to add this to your form:

    {field:screen_name}

Errors relating to the screen name are available with the error helper::

    {error:screen_name}

### group_id
If you have whitelisted valid group IDs in the register tag parameters, you can submit a `group_id`
field as part of your form data (see `group_id` parameter above). There are no helpers available
for this field, as in most cases it is not needed.

    <select name="group_id">
        <option value="5">Author</option>
        <option value="6">Editor</option>
    </select>

### accept_terms
If you have enabled it in the EE config, this field must be submitted along with your form. You
can either submit it as a hidden field, or use the field helper to add a checkbox to your form::

    {field:accept_terms} <label for="accept_terms">I accept the terms & conditions</label><br />

Errors relating to the accept terms checkbox are available with the error helper::

    {error:accept_terms}

### captcha
If you have captchas enabled, you must display the captcha and add a field to your registration
form. Use the following code as a template::

    {if captcha}
        Please enter the following characters into the box below:<br />
        {captcha}<br />
        {field:captcha}<br />
        {error:captcha}
    {/if}

### custom member fields
Any custom member fields can be added using standard field helpers:

    {field:member_field_name}

You can also template your own HTML form input:

    <input type="text" name="member_field_name" value="{member_field_name}" />

Errors can be displayed using the standard error helper:

    {error:member_field_name}

Select fields will automatically be displayed using HTML `<select>` tags. If you wish to add your own HTML (for example, an empty value at the top of the select menu), you can template it manually:

    <select name="member_field_name">
        <option value="">Select one...</option>
        {member_field_name_options}
    </select>

The `{field_name_options}` helper will generate html `<option>` tags, and automatically pre-select the correct value during form POSTs etc.

## Example

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
