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
    protected $EE;
    protected $_form_params;

    public function __construct()
    {
        $this->EE =& get_instance();
        $this->EE->freemember =& $this;

        $this->EE->load->model('freemember_model');
        $this->EE->load->helper(array('string', 'security'));
        $this->EE->lang->loadfile('freemember');
        $this->EE->lang->loadfile('login');
        $this->EE->lang->loadfile('member');
        $this->EE->lang->loadfile('myaccount');
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

        $this->EE->load->library('auth');
        $errors = array();

        /* -------------------------------------------
        /* 'member_member_login_start' hook.
        /*  - Take control of member login routine
        /*  - Added EE 1.4.2
        */
            $edata = $this->EE->extensions->call('member_member_login_start');
            if ($this->EE->extensions->end_script === true) return;
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

        if ( ! $this->EE->auth->check_require_ip()) {
            return array($auth_field => lang('unauthorized_request'));
        }

        // Check password lockout status
        if (true === $this->EE->session->check_password_lockout($_POST[$auth_field])) {
            $line = lang('password_lockout_in_effect');
            $line = str_replace("%x", $this->EE->config->item('password_lockout_interval'), $line);

            return array($auth_field => $line);
        }

        if ($auth_field == 'email') {
            $member = $this->EE->freemember_model->find_member_by_email($_POST['email']);
            if (empty($member)) {
                return array('email' => lang('invalid_email'));
            }
        } else {
            $member = $this->EE->freemember_model->find_member_by_username($_POST['username']);
            if (empty($member)) {
                return array('username' => lang('invalid_username'));
            }
        }

        // check for pending members
        if (4 == $member->group_id) {
            return array($auth_field => lang('mbr_account_not_active'));
        }

        $sess = $this->EE->auth->authenticate_id($member->member_id, $_POST['password']);
        if (! $sess) {
            $this->EE->session->save_password_lockout($_POST[$auth_field]);

            return array('password' => lang('invalid_password'));
        }

        // Banned
        if ($sess->is_banned()) {
            return array($auth_field => lang('not_authorized'));
        }

        // Allow multiple logins?
        // Do we allow multiple logins on the same account?
        if ($this->EE->config->item('allow_multi_logins') == 'n' AND $sess->has_other_session()) {
            return array($auth_field => lang('not_authorized'));
        }

        // Start Session
        // "Remember Me" is one year
        if ( ! empty($_POST['auto_login'])) {
            $sess->remember_me(60*60*24*365);
        }

        $sess->start_session();
        $this->EE->freemember_model->update_online_user_stats();

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
            $this->EE->output->show_user_error('general', array($error));
        }

        if ($errors = $this->_validate_register()) {
            return $errors;
        }

        // let EE take over
        $this->mock_output();
        $this->load_member_class('member_register')->register_member();
        $this->unmock_output();

        // get new member id
        $member_id = $this->EE->db->select('member_id')
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

        $this->EE->freemember_model->update_member($member_id, $member_data);
        $this->EE->freemember_model->update_member_custom($member_id, $member_data);
    }

    public function update_profile()
    {
        // check for fatal errors
        $this->check_banned();

        if ($error = $this->can_update()) {
            $this->EE->output->show_user_error('general', array($error));
        }

        if ($errors = $this->_validate_update()) {
            return $errors;
        }

        // update member_data
        $member_id = $this->EE->session->userdata('member_id');
        unset($_POST['group_id']);

        $this->EE->freemember_model->update_member($member_id, $_POST);
        $this->EE->freemember_model->update_member_custom($member_id, $_POST);
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
        $this->EE->load->library('fm_form_validation');
        $this->EE->form_validation->add_rules('email', 'lang:email', 'required|valid_email');

        if ($this->EE->form_validation->run() === false) {
            return $this->EE->form_validation->error_array();
        }

        // check member exists
        $member = $this->EE->db->where('email', $this->EE->input->post('email'))->get('members')->row();
        if (empty($member)) {
            return array('email' => lang('no_email_found'));
        }

        // clean old password reset codes
        $this->EE->freemember_model->clean_password_reset_codes($member->member_id);

        // create new reset code
        $reset_code = strtolower($this->EE->functions->random('alnum', 12));
        $this->EE->db->insert('reset_password', array(
            'member_id' => $member->member_id,
            'resetcode' => $reset_code,
            'date' => $this->EE->localize->now,
        ));

        if ($reset_url = $this->form_param('reset')) {
            $reset_url = $this->EE->functions->create_url($reset_url.'/'.$reset_code);
        } else {
            $reset_url = $this->EE->functions->fetch_site_index().QUERY_MARKER.
                'ACT='.$this->EE->functions->fetch_action_id('Member', 'reset_password').'&id='.$reset_code;
        }

        // send reset instructions email
        $this->EE->load->library(array('email', 'template'));

        $template = $this->EE->functions->fetch_email_template('forgot_password_instructions');

        $email_vars = array();
        $email_vars[0]['name'] = $member->username;
        $email_vars[0]['reset_url'] = $reset_url;
        $email_vars[0]['site_name'] = $this->EE->config->item('site_name');
        $email_vars[0]['site_url'] = $this->EE->config->item('site_url');

        $this->EE->email->wordwrap = true;
        $this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
        $this->EE->email->to($member->email);
        $this->EE->email->subject($this->EE->template->parse_variables($template['title'], $email_vars));
        $this->EE->email->message($this->EE->template->parse_variables($template['data'], $email_vars));

        if ( ! $this->EE->email->send()) {
            $this->EE->output->show_user_error('submission', array(lang('error_sending_email')));
        }
    }

    public function reset_password()
    {
        // verify reset code
        $member = $this->EE->freemember_model->find_member_by_reset_code($this->EE->input->post('reset_code'));
        if (empty($member)) {
            return $this->EE->output->show_user_error('submission', array(lang('mbr_id_not_found')));
        }

        // allow valid_password validator to make sure password doesn't match username
        $_POST['username'] = $member->username;

        $this->EE->load->library('fm_form_validation');

        $this->EE->form_validation->add_rules('password', 'lang:password', 'required|valid_password');
        $this->EE->form_validation->add_rules('password_confirm', 'lang:password_confirm', 'required|matches[password]');

        // run form validation
        if ($this->EE->form_validation->run() === false) {
            return $this->EE->form_validation->error_array();
        }

        // update member password
        $this->EE->freemember_model->update_member($member->member_id,
            array('password' => $_POST['password']));

        // expire reset code
        $this->EE->freemember_model->clean_password_reset_codes($member->member_id);
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
        if ($this->EE->session->userdata('is_banned') === true) {
            return $this->EE->output->show_user_error('general', array(lang('not_authorized')));
        }

        // blacklist/whitelist Check
        if ($this->EE->blacklist->blacklisted == 'y' && $this->EE->blacklist->whitelisted == 'n') {
            return $this->EE->output->show_user_error('general', array(lang('not_authorized')));
        }
    }

    public function can_register()
    {
        // do we allow new member registrations?
        if ($this->EE->config->item('allow_member_registration') == 'n') {
            return lang('mbr_registration_not_allowed');
        }

        // is user already logged in?
        if ($this->EE->session->userdata('member_id') != 0) {
            return lang('mbr_you_are_registered');
        }
    }

    public function can_update()
    {
        // is user logged in?
        if ($this->EE->session->userdata('member_id') == 0) {
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

        $this->EE->load->library('fm_form_validation');
        $this->_add_member_validation_rules();

        // rules specific to registration form
        $this->EE->form_validation->add_rules('password', 'lang:password', 'required');
        $this->EE->form_validation->add_rules('password_confirm', 'lang:password_confirm', 'required');

        if ($this->EE->config->item('use_membership_captcha') == 'y') {
            $this->EE->form_validation->add_rules('captcha', 'lang:captcha', 'required|fm_valid_captcha');
        }

        if ($this->EE->config->item('require_terms_of_service') == 'y') {
            $this->EE->form_validation->add_rules('accept_terms', 'lang:accept_terms', 'required');
        }

        /**
         * freemember_register_validation hook
         * Add any extra form validation rules
         * @since 2.0
         */
        $this->EE->extensions->call('freemember_register_validation');
        if ($this->EE->extensions->end_script === true) return;

        // run form validation
        if ($this->EE->form_validation->run() === false) {
            return $this->EE->form_validation->error_array();
        }
    }

    /**
     * Validate the update profile form before submitting it
     */
    protected function _validate_update()
    {
        $this->EE->load->library('fm_form_validation');
        $this->_add_member_validation_rules();

        // set existing data
        $this->EE->form_validation->set_old_value('username', $this->EE->session->userdata('username'));
        $this->EE->form_validation->set_old_value('email', $this->EE->session->userdata('email'));
        $this->EE->form_validation->set_old_value('screen_name', $this->EE->session->userdata('screen_name'));

        // if new password is submitted, then current_password and password_confirm are required
        if ( ! empty($_POST['password'])) {
            $this->EE->form_validation->add_rules('current_password', 'lang:current_password', 'required');
            $this->EE->form_validation->add_rules('password_confirm', 'lang:password_confirm', 'required');
        }

        /**
         * freemember_update_validation hook
         * Add any extra form validation rules
         * @since 2.0
         */
        $this->EE->extensions->call('freemember_update_validation');
        if ($this->EE->extensions->end_script === true) return;

        // run form validation
        if ($this->EE->form_validation->run() === false) {
            return $this->EE->form_validation->error_array();
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
            $this->EE->form_validation->add_rules($field, "lang:$field", 'required');
        }

        // check for rules:field="" params
        foreach ($this->_form_params as $key => $rules) {
            if (0 === strpos($key, 'rules:')) {
                $field = substr($key, 6);
                $this->EE->form_validation->add_rules($field, "lang:$field", $rules);
            }
        }

        // required if submitted (can't be set to empty string)
        foreach (array('email', 'email_confirm', 'username', 'screen_name') as $field) {
            if (isset($_POST[$field])) {
                $this->EE->form_validation->add_rules($field, "lang:$field", 'required');
            }
        }

        // standard rules
        $this->EE->form_validation->add_rules('email', 'lang:email', 'valid_user_email');
        $this->EE->form_validation->add_rules('email_confirm', 'lang:email_confirm', 'matches[email]');
        $this->EE->form_validation->add_rules('username', 'lang:username', 'valid_username');
        $this->EE->form_validation->add_rules('screen_name', 'lang:screen_name', 'valid_screen_name');
        $this->EE->form_validation->add_rules('password', 'lang:password', 'valid_password');
        $this->EE->form_validation->add_rules('password_confirm', 'lang:password', 'matches[password]');
        $this->EE->form_validation->add_rules('current_password', 'lang:current_password', 'fm_current_password');

        // trigger unique checks
        $this->EE->form_validation->set_old_value('username', ' ');
        $this->EE->form_validation->set_old_value('email', ' ');
        $this->EE->form_validation->set_old_value('screen_name', ' ');

        // custom field rules
        foreach ($this->EE->freemember_model->member_custom_fields() as $field) {
            $field_rules = '';
            if ($field->m_field_required == 'y') {
                $field_rules .= '|required';
            }

            // ensure select fields match a valid option
            if ($field->m_field_type == 'select') {
                $options = explode("\n", $field->m_field_list_items);
                if ( ! in_array($this->EE->input->post($field->m_field_name), $options)) {
                    $field_rules .= '|fm_invalid_selection';
                }
            }

            // do this whether or not we have any rules, so it updates the field label
            $this->EE->form_validation->add_rules($field->m_field_name, $field->m_field_label, $field_rules);
        }
    }

    /**
     * Fetch a param from the encrypted form_params
     */
    public function form_param($key)
    {
        if (null === $this->_form_params) {
            $this->EE->load->library('encrypt');
            $this->_form_params = json_decode($this->EE->encrypt->decode($this->EE->input->post('_params')), true);

            if (empty($this->_form_params)) {
                return $this->EE->output->show_user_error('general', array(lang('not_authorized')));
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
            if ($this->EE->session->userdata('member_id') == 0) {
                $current_member = false;
            } else {
                $current_member = $this->EE->db->from('members m')
                    ->join('member_data md', 'md.member_id = m.member_id', 'left')
                    ->where('m.member_id', $this->EE->session->userdata('member_id'))
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
        $this->EE->load->library('fm_mock_output');
        $this->EE->old_output =& $this->EE->output;
        $this->EE->output =& $this->EE->fm_mock_output;
    }

    /**
     * Be a tidy kiwi.. restore the standard EE output library.
     */
    public function unmock_output()
    {
        $this->EE->output =& $this->EE->old_output;
        unset($this->EE->old_output);
    }
}
