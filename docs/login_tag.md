# FreeMember Login Tag

    {exp:freemember:login}

As the name suggests, this tag allows you to create a login form with inline error handling,
which bypasses the default EE login notification screen.

Please note that multi-domain login (using MSM) is not supported at this time. Members will need to log in to each domain separately.

## Login Tag Parameters

### form_id
Set the id attribute on the generated form.

### form_name
Set the name attribute on the generated form.

### form_class
Set the class attribute on the generated form.

### return
The path to redirect the user to after login. If you want to return the user to the page they were on before they landed on your login page, you can set this to the constant `PREVIOUS_URL`:

    {exp:freemember:login return="PREVIOUS_URL"}

### group_id_X_return
Specify a custom return path for members of a specific group. For example:

    {exp:freemember:login return="account" group_id_1_return="account/admin"}

### error_handling
Enable inline error handling by setting error_handling to "inline".

### error_delimiters
Specify the code you want to wrap the error messages in, if you are using inline error handling.
For example:

    {exp:freemember:login error_handling="inline" error_delimiters="<span class="error">|</span>"}

## Login Tag Form Fields

The login form must contain at the very least a ``password`` input, and a ``username`` or
``email`` input.

You can also enable auto-login by adding an ``auto_login`` field.

### email
If you want members to log in using their email address, use this field in your form:

    {field:email}

Errors relating to the email address are available with the error helper:

    {error:email}

Alternatively, you can template the field manually:

    <input type="email" name="email" value="{email}" />

### username
If you want members to log in using their username instead of their email address, use this
field in your form instead of the ``email`` field:

    {field:username}

Errors relating to the username are available with the error helper:

    {error:username}

Alternatively, you can template the field manually:

    <input type="text" name="username" value="{username}" />

### password
This field must be submitted. Use the field helper to add it to your form:

    {field:password}

Errors relating to the password are available with the error helper:

    {error:password}

Alternatively, you can template the field manually:

    <input type="password" name="password" value="" />

### auto_login
If this field is submitted, the "Remember Me" feature will be enabled. You can use the field
helper to generate the checkbox:

    {field:auto_login}

Alternatively, you can template the input yourself, and use the ``{auto_login_checked}`` helper
to keep the checkbox checked between requests:

    <input type="checkbox" name="auto_login" value="1" {auto_login_checked} />

## Login Tag Example

    {exp:freemember:login return="account" error_handling="inline" error_delimiters='<span class="error">|</span>'}

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