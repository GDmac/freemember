<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * FreeMember add-on for ExpressionEngine
 * Copyright (c) 2012 Adrian Macneil
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once(PATH_THIRD.'freemember/config.php');

class Freemember
{
    private static $login_errors;
    private static $registration_errors;
    private static $update_profile_errors;
    private static $forgot_password_errors;
    private static $reset_password_errors;

    protected $tag_vars;

    public function __construct()
    {
        ee()->load->library('freemember_lib');
    }

    /**
     * Login form tag
     */
    public function login()
    {
        // form fields
        $this->tag_vars = array();
        $this->_add_field('email', 'email');
        $this->_add_field('username');
        $this->_add_field('password', 'password');
        $this->_add_field('auto_login', 'checkbox');

        // inline errors
        $this->_add_errors(self::$login_errors);

        return $this->_build_form('act_login');
    }

    /**
     * Login form action
     */
    public function act_login()
    {
        self::$login_errors = ee()->freemember->login();
        $this->_action_complete(self::$login_errors);
    }

    /**
     * Register form tag
     */
    public function register()
    {
        if ($error = ee()->freemember->can_register()) return $error;

        // form fields
        $this->tag_vars = array();
        $this->_add_member_fields();

        // generate captcha
        if (ee()->config->item('use_membership_captcha') == 'y') {
            $this->tag_vars[0]['captcha'] = ee()->functions->create_captcha();
        }

        // inline errors
        $this->_add_errors(self::$registration_errors);

        return $this->_build_form('act_register');
    }

    /**
     * Register form action
     */
    public function act_register()
    {
        self::$registration_errors = ee()->freemember->register();
        $this->_action_complete(self::$registration_errors);
    }

    /**
     * Update form tag
     */
    public function update_profile()
    {
        if ($error = ee()->freemember->can_update()) return $error;

        $member = ee()->freemember->current_member();

        // form fields
        $this->tag_vars = array();
        $this->_add_member_fields($member);

        // inline errors
        $this->_add_errors(self::$update_profile_errors);

        return $this->_build_form('act_update_profile');
    }

    /**
     * Update form action
     */
    public function act_update_profile()
    {
        self::$update_profile_errors = ee()->freemember->update_profile();
        $this->_action_complete(self::$update_profile_errors);
    }

    /**
     * Display member public profiles
     */
    public function members()
    {
        $search = ee()->TMPL->tagparams;
        $members = ee()->freemember_model->find_members($search);
        if ($members) {
            return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $members);
        }

        return ee()->TMPL->no_results();
    }

    /**
     * Forgot Password tag
     */
    public function forgot_password()
    {
        $this->tag_vars = array();
        $this->_add_field('email', 'email');

        // inline errors
        $this->_add_errors(self::$forgot_password_errors);

        return $this->_build_form('act_forgot_password');
    }

    /**
     * Forgot password action
     */
    public function act_forgot_password()
    {
        self::$forgot_password_errors = ee()->freemember->forgot_password();
        $this->_action_complete(self::$forgot_password_errors);
    }

    public function reset_password()
    {
        // was reset code specified in params?
        if (($reset_code = ee()->TMPL->fetch_param('reset_code')) === false) {
            // freemember 1.x compabitility
            if (($reset_code = ee()->TMPL->fetch_param('code')) === false) {
                // reset code defaults to last segment
                $reset_code = ee()->uri->segment(ee()->uri->total_segments());
            }
        }

        // verify reset code
        $member = ee()->freemember_model->find_member_by_reset_code($reset_code);
        if (empty($member)) {
            return ee()->TMPL->no_results();
        }

        $this->tag_vars = array();
        $this->_add_field('password', 'password');
        $this->_add_field('password_confirm', 'password');

        // not fields, but available in the template
        $this->tag_vars[0]['email'] = $member->email;
        $this->tag_vars[0]['username'] = $member->username;
        $this->tag_vars[0]['screen_name'] = $member->screen_name;

        // inline errors
        $this->_add_errors(self::$reset_password_errors);

        return $this->_build_form('act_reset_password', array('reset_code' => $reset_code));
    }

    public function act_reset_password()
    {
        self::$reset_password_errors = ee()->freemember->reset_password();
        $this->_action_complete(self::$reset_password_errors);
    }

    /**
     * Logout tag
     *
     * This tag is deprecated and will be removed in future. Please use
     * {exp:freemember:logout_url} instead.
     */
    public function logout()
    {
        $_GET['return_url'] = ee()->TMPL->fetch_param('return');
        $this->act_logout();
    }

    public function logout_url()
    {
        $params = array_filter(array('return_url' => ee()->TMPL->fetch_param('return')));

        $url = ee()->functions->fetch_site_index().QUERY_MARKER.
            'ACT='.ee()->functions->fetch_action_id(__CLASS__, 'act_logout');

        if (!empty($params)) {
            $url .= '&'.http_build_query($params);
        }

        if (ee()->config->item('secure_forms') == 'y') {
            $url .= '&XID={XID_HASH}';
        }

        return $this->escape($url);
    }

    public function act_logout()
    {
        ee()->freemember->logout();
        $this->_action_complete();
    }

    /**
     * Escape HTML entities
     */
    protected function escape($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Add a field helper to tag_vars
     */
    protected function _add_field($name, $type = 'text', $force_value = null)
    {
        if (null !== $force_value || 'password' == $type) {
            $value = $force_value;
        } elseif (isset($_POST[$name])) {
            $value = ee()->input->post($name, true);
        } else {
            // nothing posted, did we already have a template variable set?
            $value = isset($this->tag_vars[0][$name]) ? $this->tag_vars[0][$name] : false;
        }

        // assume email field type
        if ('text' == $type && ('email' == $name || 'email_confirm' == $name)) {
            $type = 'email';
        }

        $this->tag_vars[0][$name] = $value;
        $this->tag_vars[0]['error:'.$name] = false;

        $field = "<input type='$type' name='$name' id='$name'";
        if ($type == 'checkbox') {
            $checked = $value ? ' checked ' : '';
            $field = "<input type='hidden' name='$name' value='' />$field value='1' $checked";
            $this->tag_vars[0][$name.'_checked'] = $checked;
        } else {
            $field .= " value='$value'";
        }

        $this->tag_vars[0]["field:$name"] = $field." />";
    }

    protected function _add_select_field($name, $options)
    {
        if (isset($_POST[$name])) {
            $value = ee()->input->post($name, true);
        } else {
            // nothing posted, did we already have a template variable set?
            $value = isset($this->tag_vars[0][$name]) ? $this->tag_vars[0][$name] : false;
        }

        $this->tag_vars[0][$name] = $value;
        $this->tag_vars[0]['error:'.$name] = false;

        $options_html = '';
        foreach ($options as $option) {
            $options_html .= "<option value='$option'";
            if ($option == $value) $options_html .= " selected";
            $options_html .=">$option</option>";
        }

        $field = "<select name='$name' id='$name'>$options_html</select>";

        $this->tag_vars[0][$name.'_options'] = $options_html;
        $this->tag_vars[0]["field:$name"] = $field;
    }

    protected function _add_member_fields($member = null)
    {
        // standard member fields
        foreach (ee()->freemember_model->member_fields() as $field) {
            if ($member) {
                $this->tag_vars[0][$field] = $member->$field;
            }

            $this->_add_field($field);
        }

        // custom member fields
        foreach (ee()->freemember_model->member_custom_fields() as $field) {
            if ($member) {
                $field_id = 'm_field_id_'.$field->m_field_id;
                $this->tag_vars[0][$field_id] = $member->$field_id;
                $this->tag_vars[0][$field->m_field_name] = $member->$field_id;
            }

            if ('select' == $field->m_field_type) {
                $options = explode("\n", $field->m_field_list_items);
                $this->_add_select_field($field->m_field_name, $options);
            } else {
                $this->_add_field($field->m_field_name);
            }
        }

        // these fields aren't directly mapped to the db
        $this->_add_field('email_confirm');
        $this->_add_field('current_password', 'password');
        $this->_add_field('password', 'password');
        $this->_add_field('password_confirm', 'password');
        $this->_add_field('captcha', 'text', false);
        $this->_add_field('accept_terms', 'checkbox');
    }

    /**
     * Add inline errors to the tag_vars
     */
    protected function _add_errors($errors)
    {
        if (is_array($errors)) {
            foreach ($errors as $key => $value) {
                $this->tag_vars[0]["error:$key"] = ee()->freemember->wrap_error($value);
            }
        }
    }

    /**
     * Output a form based on the current params and tag vars
     */
    protected function _build_form($action, $extra_hidden = array())
    {
        $data = array();
        $data['action'] = ee()->functions->create_url(ee()->uri->uri_string);

        if (ee()->TMPL->fetch_param('secure_action') == 'yes') {
            $data['action'] = str_replace('http://', 'https://', $data['action']);
        }

        $data['id'] = ee()->TMPL->fetch_param('form_id');
        $data['name'] = ee()->TMPL->fetch_param('form_name');
        $data['class'] = ee()->TMPL->fetch_param('form_class');

        $data['hidden_fields'] = $extra_hidden;
        $data['hidden_fields']['ACT'] = ee()->functions->fetch_action_id(__CLASS__, $action);
        $data['hidden_fields']['return_url'] = ee()->TMPL->fetch_param('return');

        if ('PREVIOUS_URL' === $data['hidden_fields']['return_url']) {
            $data['hidden_fields']['return_url'] = $this->history(1);
        }

        // prevents errors in case there are no tag params
        ee()->TMPL->tagparams['encrypted_params'] = 1;

        // encrypt tag parameters
        ee()->load->library('encrypt');
        $data['hidden_fields']['_params'] = ee()->encrypt->encode(json_encode(ee()->TMPL->tagparams));

        return ee()->functions->form_declaration($data).
            ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $this->tag_vars).'</form>';
    }

    /**
     * After form submission, either display the errors or redirect to the return url
     */
    protected function _action_complete($errors = null)
    {
        if (empty($errors)) {
            // redirect to custom url or current page
            $return_url = ee()->input->get_post('return_url') ?: $this->history(0);
            $return_url = ee()->functions->create_url($return_url);

            if (isset($_POST['_params']) && ee()->freemember->form_param('secure_return') == 'yes') {
                $return_url = str_replace('http://', 'https://', $return_url);
            }

            ee()->functions->redirect($return_url);
        } elseif (ee()->freemember->form_param('error_handling') == 'inline') {
            return ee()->core->generate_page();
        }

        return ee()->output->show_user_error(false, $errors);
    }

    protected function history($id)
    {
        $tracker = ee()->session->tracker;

        if (isset($tracker[$id])) {
            if ($tracker[$id] === 'index') {
                return '/';
            }

            return $tracker[$id];
        }
    }
}
