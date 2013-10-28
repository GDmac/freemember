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

class Freemember_lib
{
    protected $_form_params;

    public function __construct()
    {
        ee()->freemember =& $this;

        ee()->load->model('freemember_model');
        ee()->load->helper(array('string', 'security'));
        ee()->lang->loadfile('freemember');
        ee()->lang->loadfile('login');
        ee()->lang->loadfile('member');
        ee()->lang->loadfile('myaccount');
    }

    /**
     * Process login using the current POST data
     *
     * @return an associative array of errors
     */
    public function login()
    {
        // check for fatal errors
        $this->check_banned();

        ee()->load->library('auth');
        $errors = array();

        /* -------------------------------------------
        /* 'member_member_login_start' hook.
        /*  - Take control of member login routine
        /*  - Added EE 1.4.2
        */
            $edata = ee()->extensions->call('member_member_login_start');
            if (ee()->extensions->end_script === true) return;
        /*
        /* -------------------------------------------*/

        if (isset($_POST['email'])) {
            $auth_field = 'email';

            // email was supplied, so is required
            if (empty($_POST['email'])) {
                $errors['email'] = lang('no_email');
            }
        } else {
            $auth_field = 'username';

            // since no email was supplied, username is required
            if (empty($_POST['username'])) {
                $errors['username'] = lang('no_username');
            }
        }

        if (empty($_POST['password'])) {
            $errors['password'] = lang('no_password');
        }

        // oh dear, login failed already...
        if ( ! empty($errors)) {
            return $errors;
        }

        if ( ! ee()->auth->check_require_ip()) {
            return array($auth_field => lang('unauthorized_request'));
        }

        // Check password lockout status
        if (true === ee()->session->check_password_lockout($_POST[$auth_field])) {
            $line = lang('password_lockout_in_effect');
            $line = str_replace("%d", ee()->config->item('password_lockout_interval'), $line);

            return array($auth_field => $line);
        }

        if ($auth_field == 'email') {
            $member = ee()->freemember_model->find_member_by_email($_POST['email']);
            if (empty($member)) {
                return array('email' => lang('invalid_email'));
            }
        } else {
            $member = ee()->freemember_model->find_member_by_username($_POST['username']);
            if (empty($member)) {
                return array('username' => lang('invalid_username'));
            }
        }

        // check for pending members
        if (4 == $member->group_id) {
            return array($auth_field => lang('mbr_account_not_active'));
        }

        $sess = ee()->auth->authenticate_id($member->member_id, $_POST['password']);
        if (! $sess) {
            ee()->session->save_password_lockout($_POST[$auth_field]);

            return array('password' => lang('invalid_password'));
        }

        // Banned
        if ($sess->is_banned()) {
            return array($auth_field => lang('not_authorized'));
        }

        // Allow multiple logins?
        // Do we allow multiple logins on the same account?
        if (ee()->config->item('allow_multi_logins') == 'n' AND $sess->has_other_session()) {
            return array($auth_field => lang('not_authorized'));
        }

        // Start Session
        // "Remember Me" is one year
        if ( ! empty($_POST['auto_login'])) {
            $sess->remember_me(60*60*24*365);
        }

        $sess->start_session();
        ee()->freemember_model->update_online_user_stats();

        // support group_id_X_return params, rewrite return_url based on member group
        if ($return_url = $this->form_param("group_id_{$member->group_id}_return")) {
            $_POST['return_url'] = $return_url;
        }
    }

    /**
     * Process registration form using the current POST data
     *
     * @return an associative array of errors
     */
    public function register()
    {
        // check for fatal errors
        $this->check_banned();

        if ($error = $this->can_register()) {
            ee()->output->show_user_error('general', array($error));
        }

        if ($errors = $this->_validate_register()) {
            return $errors;
        }

        // let EE take over
        $this->mock_output();
        $this->load_member_class('member_register')->register_member();
        $this->unmock_output();

        // get new member id
        $member_id = ee()->db->select('member_id')
            ->where('email', $_POST['email'])->get('members')->row('member_id');

        // update standard and custom member fields
        // EE's register_member() method doesn't add all profile fields, such as date of birth
        $member_data = $_POST;
        unset($member_data['username']);
        unset($member_data['screen_name']);
        unset($member_data['email']);
        unset($member_data['password']);
        unset($member_data['group_id']);

        // check member group form param
        if (false !== $this->form_param('group_id')) {
            // is user-submitted group_id allowed?
            $group_ids = array_filter(explode('|', $this->form_param('group_id')));

            if (isset($_POST['group_id']) and in_array($_POST['group_id'], $group_ids)) {
                $member_data['group_id'] = (int)$_POST['group_id'];
            } elseif (count($group_ids) > 0) {
                $member_data['group_id'] = reset($group_ids);
            }
        }

        ee()->freemember_model->update_member($member_id, $member_data);
        ee()->freemember_model->update_member_custom($member_id, $member_data);
    }

    public function update_profile()
    {
        // check for fatal errors
        $this->check_banned();

        if ($error = $this->can_update()) {
            ee()->output->show_user_error('general', array($error));
        }

        if ($errors = $this->_validate_update()) {
            return $errors;
        }

        // update member_data
        $member_id = ee()->session->userdata('member_id');
        unset($_POST['group_id']);

        ee()->freemember_model->update_member($member_id, $_POST);
        ee()->freemember_model->update_member_custom($member_id, $_POST);
    }

    /**
     * Process forgot password form. We have our own code for this so that we can generate
     * a custom reset url in the email.
     */
    public function forgot_password()
    {
        // check for fatal errors
        $this->check_banned();

        // form validation
        ee()->load->library('fm_form_validation');
        ee()->form_validation->add_rules('email', 'lang:email', 'required|valid_email');

        if (ee()->form_validation->run() === false) {
            return ee()->form_validation->error_array();
        }

        // check member exists
        $member = ee()->db->where('email', ee()->input->post('email'))->get('members')->row();
        if (empty($member)) {
            return array('email' => lang('no_email_found'));
        }

        // clean old password reset codes
        ee()->freemember_model->clean_password_reset_codes($member->member_id);

        // create new reset code
        $reset_code = strtolower(ee()->functions->random('alnum', 12));
        ee()->db->insert('reset_password', array(
            'member_id' => $member->member_id,
            'resetcode' => $reset_code,
            'date' => ee()->localize->now,
        ));

        if ($reset_url = $this->form_param('reset')) {
            $reset_url = ee()->functions->create_url($reset_url.'/'.$reset_code);
        } else {
            $reset_url = ee()->functions->fetch_site_index().QUERY_MARKER.
                'ACT='.ee()->functions->fetch_action_id('Member', 'process_reset_password').'&id='.$reset_code;
        }

        // send reset instructions email
        ee()->load->library(array('email', 'template'));

        $template = ee()->functions->fetch_email_template('forgot_password_instructions');

        $email_vars = array();
        $email_vars[0]['name'] = $member->username;
        $email_vars[0]['reset_url'] = $reset_url;
        $email_vars[0]['site_name'] = ee()->config->item('site_name');
        $email_vars[0]['site_url'] = ee()->config->item('site_url');

        ee()->email->wordwrap = true;
        ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
        ee()->email->to($member->email);
        ee()->email->subject(ee()->template->parse_variables($template['title'], $email_vars));
        ee()->email->message(ee()->template->parse_variables($template['data'], $email_vars));

        if ( ! ee()->email->send()) {
            ee()->output->show_user_error('submission', array(lang('error_sending_email')));
        }
    }

    public function reset_password()
    {
        // verify reset code
        $member = ee()->freemember_model->find_member_by_reset_code(ee()->input->post('reset_code'));
        if (empty($member)) {
            return ee()->output->show_user_error('submission', array(lang('mbr_id_not_found')));
        }

        // allow valid_password validator to make sure password doesn't match username
        $_POST['username'] = $member->username;

        ee()->load->library('fm_form_validation');

        ee()->form_validation->add_rules('password', 'lang:password', 'required|valid_password');
        ee()->form_validation->add_rules('password_confirm', 'lang:password_confirm', 'required|matches[password]');

        // run form validation
        if (ee()->form_validation->run() === false) {
            return ee()->form_validation->error_array();
        }

        // update member password
        ee()->freemember_model->update_member($member->member_id,
            array('password' => $_POST['password']));

        // expire reset code
        ee()->freemember_model->clean_password_reset_codes($member->member_id);
    }

    /**
     * Log out the current user
     */
    public function logout()
    {
        $this->mock_output();
        $this->load_member_class('member_auth')->member_logout();
        $this->unmock_output();
    }

    /**
     * For some reason this stuff isn't built into EE
     */
    public function check_banned()
    {
        // is user banned?
        if (ee()->session->userdata('is_banned') === true) {
            return ee()->output->show_user_error('general', array(lang('not_authorized')));
        }

        // blacklist/whitelist Check
        if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n') {
            return ee()->output->show_user_error('general', array(lang('not_authorized')));
        }
    }

    public function can_register()
    {
        // do we allow new member registrations?
        if (ee()->config->item('allow_member_registration') == 'n') {
            return lang('mbr_registration_not_allowed');
        }

        // is user already logged in?
        if (ee()->session->userdata('member_id') != 0) {
            return lang('mbr_you_are_registered');
        }
    }

    public function can_update()
    {
        // is user logged in?
        if (ee()->session->userdata('member_id') == 0) {
            return lang('must_be_logged_in');
        }
    }

    /**
     * Do our own validation before handing things over to the Member_register class.
     * This allows us to catch errors and display them inline.
     */
    protected function _validate_register()
    {
        // automatically set screen_name and username if not submitted
        // also makes sure these fields are requried by initializing to empty string
        if ( ! isset($_POST['email'])) $_POST['email'] = '';
        if ( ! isset($_POST['username'])) $_POST['username'] = $_POST['email'];
        if ( ! isset($_POST['screen_name'])) $_POST['screen_name'] = $_POST['username'];

        ee()->load->library('fm_form_validation');
        $this->_add_member_validation_rules();

        // rules specific to registration form
        ee()->form_validation->add_rules('password', 'lang:password', 'required');
        ee()->form_validation->add_rules('password_confirm', 'lang:password_confirm', 'required');

        if (ee()->config->item('use_membership_captcha') == 'y') {
            ee()->form_validation->add_rules('captcha', 'lang:captcha', 'required|fm_valid_captcha');
        }

        if (ee()->config->item('require_terms_of_service') == 'y') {
            ee()->form_validation->add_rules('accept_terms', 'lang:accept_terms', 'required');
        }

        /**
         * freemember_register_validation hook
         * Add any extra form validation rules
         * @since 2.0
         */
        ee()->extensions->call('freemember_register_validation');
        if (ee()->extensions->end_script === true) return;

        // run form validation
        if (ee()->form_validation->run() === false) {
            return ee()->form_validation->error_array();
        }
    }

    /**
     * Validate the update profile form before submitting it
     */
    protected function _validate_update()
    {
        ee()->load->library('fm_form_validation');
        $this->_add_member_validation_rules();

        // set existing data
        ee()->form_validation->set_old_value('username', ee()->session->userdata('username'));
        ee()->form_validation->set_old_value('email', ee()->session->userdata('email'));
        ee()->form_validation->set_old_value('screen_name', ee()->session->userdata('screen_name'));

        // if new password is submitted, then current_password and password_confirm are required
        if ( ! empty($_POST['password'])) {
            ee()->form_validation->add_rules('current_password', 'lang:current_password', 'required');
            ee()->form_validation->add_rules('password_confirm', 'lang:password_confirm', 'required');
        }

        /**
         * freemember_update_validation hook
         * Add any extra form validation rules
         * @since 2.0
         */
        ee()->extensions->call('freemember_update_validation');
        if (ee()->extensions->end_script === true) return;

        // run form validation
        if (ee()->form_validation->run() === false) {
            return ee()->form_validation->error_array();
        }
    }

    /**
     * Add basic rules for validating a member profile (register/update).
     * Automatically merges require="" and rules:field="" params.
     */
    public function _add_member_validation_rules()
    {
        // check for require="" param
        $require_fields = explode('|', $this->form_param('require'));
        foreach ($require_fields as $field) {
            ee()->form_validation->add_rules($field, "lang:$field", 'required');
        }

        // check for rules:field="" params
        foreach ($this->_form_params as $key => $rules) {
            if (0 === strpos($key, 'rules:')) {
                $field = substr($key, 6);
                ee()->form_validation->add_rules($field, "lang:$field", $rules);
            }
        }

        // required if submitted (can't be set to empty string)
        foreach (array('email', 'email_confirm', 'username', 'screen_name') as $field) {
            if (isset($_POST[$field])) {
                ee()->form_validation->add_rules($field, "lang:$field", 'required');
            }
        }

        // standard rules
        ee()->form_validation->add_rules('email', 'lang:email', 'valid_user_email');
        ee()->form_validation->add_rules('email_confirm', 'lang:email_confirm', 'matches[email]');
        ee()->form_validation->add_rules('username', 'lang:username', 'valid_username');
        ee()->form_validation->add_rules('screen_name', 'lang:screen_name', 'valid_screen_name');
        ee()->form_validation->add_rules('password', 'lang:password', 'valid_password');
        ee()->form_validation->add_rules('password_confirm', 'lang:password', 'matches[password]');
        ee()->form_validation->add_rules('current_password', 'lang:current_password', 'fm_current_password');

        // trigger unique checks
        ee()->form_validation->set_old_value('username', ' ');
        ee()->form_validation->set_old_value('email', ' ');
        ee()->form_validation->set_old_value('screen_name', ' ');

        // custom field rules
        foreach (ee()->freemember_model->member_custom_fields() as $field) {
            $field_rules = '';
            if ($field->m_field_required == 'y') {
                $field_rules .= '|required';
            }

            // ensure select fields match a valid option
            if ($field->m_field_type == 'select') {
                $options = explode("\n", $field->m_field_list_items);
                if ( ! in_array(ee()->input->post($field->m_field_name), $options)) {
                    $field_rules .= '|fm_invalid_selection';
                }
            }

            // do this whether or not we have any rules, so it updates the field label
            ee()->form_validation->add_rules($field->m_field_name, $field->m_field_label, $field_rules);
        }
    }

    /**
     * Fetch a param from the encrypted form_params
     */
    public function form_param($key)
    {
        if (null === $this->_form_params) {
            ee()->load->library('encrypt');
            $this->_form_params = json_decode(ee()->encrypt->decode(ee()->input->post('_params')), true);

            if (empty($this->_form_params)) {
                return ee()->output->show_user_error('general', array(lang('not_authorized')));
            }
        }

        return isset($this->_form_params[$key]) ? $this->_form_params[$key] : false;
    }

    /**
     * Wrap an error using the current error delimiters
     */
    public function wrap_error($message)
    {
        $delimiters = explode('|', $this->form_param('error_delimiters'), 2);
        if (2 == count($delimiters)) {
            return $delimiters[0].$message.$delimiters[1];
        }

        return $message;
    }

    public function current_member()
    {
        static $current_member = null;
        if (null === $current_member) {
            if (ee()->session->userdata('member_id') == 0) {
                $current_member = false;
            } else {
                $current_member = ee()->db->from('members m')
                    ->join('member_data md', 'md.member_id = m.member_id', 'left')
                    ->where('m.member_id', ee()->session->userdata('member_id'))
                    ->get()->row();
            }
        }

        return $current_member;
    }

    /**
     * Create a new instance of an EE member module class.
     */
    public function load_member_class($class_name)
    {
        if ( ! class_exists('Member')) {
            require PATH_MOD.'member/mod.member.php';
        }

        $class_name = ucfirst($class_name);
        if ( ! class_exists($class_name)) {
            require PATH_MOD.'member/mod.'.strtolower($class_name).'.php';
        }

        return new $class_name();
    }

    /**
     * Replace the EE output library with our mock class.
     * This prevents calls to show_message() from halting the script.
     */
    public function mock_output()
    {
        ee()->load->library('fm_mock_output');
        ee()->old_output =& ee()->output;
        ee()->output =& ee()->fm_mock_output;
    }

    /**
     * Be a tidy kiwi.. restore the standard EE output library.
     */
    public function unmock_output()
    {
        ee()->output =& ee()->old_output;
        unset(ee()->old_output);
    }
}
