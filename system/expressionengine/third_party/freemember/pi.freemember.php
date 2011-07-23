<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * FreeMember module for ExpressionEngine
 *
 * We can't claim copyright on this code, most of the algorithms are more or
 * less copied from ExpressionEngine. That said, if ExpressionEngine's code base
 * was a bit more modular, we could have simply called their API, and this module
 * would only need to be about 200 lines long.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

$plugin_info = array(
	'pi_name'			=> 'FreeMember',
	'pi_version'		=> '1.0',
	'pi_author'			=> 'Exp:resso',
	'pi_author_url'		=> 'http://exp-resso.com/',
	'pi_description'	=> 'Free your member templates!',
	'pi_usage'			=> 'Documentation coming soon...',
);

class Freemember
{
	public $return_data;

	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->helper('string');

		$this->EE->lang->loadfile('freemember');
		$this->EE->lang->loadfile('login');
		$this->EE->lang->loadfile('member');
		$this->EE->lang->loadfile('myaccount');
	}

	public function register()
	{
		$form_id = $this->EE->TMPL->fetch_param('form_id');
		if (empty($form_id))
		{
			return 'Missing form_id parameter.';
		}

		if ($this->EE->config->item('allow_member_registration') == 'n')
		{
			return 'Member registration is disabled.';
		}

		$tag_vars[0] = array(
			'username' => FALSE,
			'password' => FALSE,
			'password_confirm' => FALSE,
			'email' => FALSE,
			'email_confirm' => FALSE,
			'screen_name' => FALSE,
			'url' => FALSE,
			'location' => FALSE,
			'captcha' => FALSE,
		);

		foreach ($tag_vars[0] as $field_name => $value)
		{
			$tag_vars[0]['error:'.$field_name] = FALSE;
		}

		if ($this->EE->input->post('register_form') == $form_id)
		{
			if ( ! isset($_POST['username']))
			{
				$_POST['username'] = $this->EE->input->post('email');
			}

			foreach ($tag_vars[0] as $field_name => $value)
			{
				$tag_vars[0][$field_name] = $this->EE->input->post($field_name);
			}

			// password can't be pre-filled with POST data
			$tag_vars[0]['password'] = FALSE;
			$tag_vars[0]['password_confirm'] = FALSE;

			// handle form submission
			$errors = $this->_member_validate();

			if (empty($errors))
			{
				$this->_member_register();
				$return_url = $this->EE->functions->create_url($this->EE->input->post('return_url'));
				$this->EE->functions->redirect($return_url);
			}
			else
			{
				if ( ! is_array($errors))
				{
					// fatal error, display error message
					return $this->EE->output->show_user_error(FALSE, array($errors));
				}
				elseif ($this->EE->TMPL->fetch_param('error_handling') == 'inline')
				{
					// template inline errors
					$delim = explode('|', $this->EE->TMPL->fetch_param('error_delimiters'));
					if (count($delim) != 2)
					{
						$delim = array('', '');
					}

					foreach ($errors as $field_name => $message)
					{
						$tag_vars[0]['error:'.$field_name] = $delim[0].$message.$delim[1];
					}
				}
				else
				{
					// display errors
					return $this->EE->output->show_user_error(FALSE, $errors);
				}
			}
		}

		// do we need a captcha?
		if ($this->EE->config->item('use_membership_captcha') == 'y')
		{
			$tag_vars[0]['captcha'] = $this->EE->functions->create_captcha();

			if (empty($tag_vars[0]['captcha']))
			{
				$tag_vars[0]['captcha'] = FALSE;
			}
		}

		// start our form output
		$out = $this->EE->functions->form_declaration(array(
			'id' => $form_id,
			'action' => $this->EE->functions->create_url($this->EE->uri->uri_string),
			'hidden_fields' => array(
				'register_form' => $form_id,
				'return_url' => $this->EE->TMPL->fetch_param('return'),
			),
		));

		// parse tagdata variables
		$out .= $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $tag_vars);

		// end form output and return
		return $out.'</form>';
	}

	public function login()
	{
		$form_id = $this->EE->TMPL->fetch_param('form_id');
		if (empty($form_id))
		{
			return 'Missing form_id parameter.';
		}

		$tag_vars[0] = array(
			'email' => FALSE,
			'username' => FALSE,
			'password' => FALSE,
			'auto_login' => FALSE,
		);

		foreach ($tag_vars[0] as $field_name => $value)
		{
			$tag_vars[0]['error:'.$field_name] = FALSE;
		}

		if ($this->EE->input->post('login_form') == $form_id)
		{
			$tag_vars[0]['email'] = $this->EE->input->post('email');
			$tag_vars[0]['username'] = $this->EE->input->post('username');
			$tag_vars[0]['auto_login'] = (bool)$this->EE->input->post('auto_login');
			$tag_vars[0]['password'] = FALSE; // don't pre-load password field

			// handle form submission
			$errors = $this->_member_login();

			if (empty($errors))
			{
				$return_url = $this->EE->functions->create_url($this->EE->input->post('return_url'));
				$this->EE->functions->redirect($return_url);
			}
			else
			{
				if ($this->EE->TMPL->fetch_param('error_handling') == 'inline')
				{
					// template inline errors
					$delim = explode('|', $this->EE->TMPL->fetch_param('error_delimiters'));
					if (count($delim) != 2)
					{
						$delim = array('', '');
					}

					foreach ($errors as $field_name => $message)
					{
						$tag_vars[0]['error:'.$field_name] = $delim[0].$message.$delim[1];
					}
				}
				else
				{
					// display errors
					return $this->EE->output->show_user_error(FALSE, $errors);
				}
			}
		}

		// auto_login_checked helper tag
		$tag_vars[0]['auto_login_checked'] = $tag_vars[0]['auto_login'] ? ' checked="checked" ' : FALSE;

		// start our form output
		$out = $this->EE->functions->form_declaration(array(
			'id' => $form_id,
			'action' => $this->EE->functions->create_url($this->EE->uri->uri_string),
			'hidden_fields' => array(
				'login_form' => $form_id,
				'return_url' => $this->EE->TMPL->fetch_param('return'),
			),
		));

		// parse tagdata variables
		$out .= $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $tag_vars);

		// end form output and return
		return $out.'</form>';
	}

	/**
	 * Logout
	 *
	 * Avoid stupid "You are now logged out" messages, by putting this on a template, and linking to it
	 */
	public function logout()
	{
		// Kill the session and cookies
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('ip_address', $this->EE->input->ip_address());
		$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
		$this->EE->db->delete('online_users');

		$this->EE->session->destroy();

		$this->EE->functions->set_cookie('read_topics');

		/* -------------------------------------------
		/* 'member_member_logout' hook.
		/*  - Perform additional actions after logout
		/*  - Added EE 1.6.1
		*/
			$edata = $this->EE->extensions->call('member_member_logout');
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		if (($return = $this->EE->TMPL->fetch_param('return')) !== FALSE)
		{
			$this->EE->functions->redirect($this->EE->functions->create_url($return));
		}
		else
		{
			// return to most recent page
			$this->EE->functions->redirect($this->EE->functions->form_backtrack(1));
		}
	}

	/**
	 * Member Validate
	 *
	 * Most of this code is poached from mod.member_register.php.
	 * Dear EL, please stop writing 500 line functions
	 * so that we can re-use your functions :) It would be nice if validate and register
	 * were split into separate functions in mod.member_register.php
	 *
	 * Modified to return an array of errors, instead of displaying stupid ugly grey error page
	 */
	private function _member_validate()
	{
		$errors = array();

		// Do we allow new member registrations?
		if ($this->EE->config->item('allow_member_registration') == 'n')
		{
			return lang('not_authorized');
		}

		// Is user banned?
		if ($this->EE->session->userdata('is_banned') === TRUE)
		{
			return lang('not_authorized');
		}

		// Blacklist/Whitelist Check
		if ($this->EE->blacklist->blacklisted == 'y' &&
			$this->EE->blacklist->whitelisted == 'n')
		{
			return lang('not_authorized');
		}

		$this->EE->load->helper('url');

		/* -------------------------------------------
		/* 'member_member_register_start' hook.
		/*  - Take control of member registration routine
		/*  - Added EE 1.4.2
		*/
			$edata = $this->EE->extensions->call('member_member_register_start');
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		// Set the default globals
		$default = array(
			'username', 'password', 'password_confirm', 'email',
			'screen_name', 'url', 'location'
		);

		foreach ($default as $val)
		{
			if ( ! isset($_POST[$val])) $_POST[$val] = '';
		}

		if ($_POST['screen_name'] == '')
		{
			$_POST['screen_name'] = $_POST['username'];
		}

		// Instantiate validation class
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate.php';
		}

		$VAL = new EE_Validate(array(
			'member_id'			=> '',
			'val_type'			=> 'new', // new or update
			'fetch_lang' 		=> TRUE,
			'require_cpw' 		=> FALSE,
		 	'enable_log'		=> FALSE,
			'username'			=> $_POST['username'],
			'cur_username'		=> '',
			'screen_name'		=> $_POST['screen_name'],
			'cur_screen_name'	=> '',
			'password'			=> $_POST['password'],
		 	'password_confirm'	=> $_POST['password'],
		 	'cur_password'		=> '',
		 	'email'				=> $_POST['email'],
		 	'cur_email'			=> ''
		 ));

		/* MODIFIED TO TAKE NOTE OF WHICH FIELDS ARE CAUSING ERRORS */
		$VAL->validate_username();
		if ( ! empty($VAL->errors))
		{
			$errors['username'] = reset($VAL->errors);
			$VAL->errors = array();
		}

		$VAL->validate_screen_name();
		if ( ! empty($VAL->errors))
		{
			$errors['screen_name'] = reset($VAL->errors);
			$VAL->errors = array();
		}

		$VAL->validate_password();
		if ( ! empty($VAL->errors))
		{
			$errors['password'] = reset($VAL->errors);
			$VAL->errors = array();
		}

		$VAL->validate_email();
		if ( ! empty($VAL->errors))
		{
			$errors['email'] = reset($VAL->errors);
			$VAL->errors = array();
		}

		// do our own check for password_confirm errors
		if ($_POST['password'] && $_POST['password'] != $_POST['password_confirm'])
		{
			$errors['password_confirm'] = lang('missmatched_passwords');
		}

		// Do we have any custom fields?
		$query = $this->EE->db->select('m_field_id, m_field_name, m_field_label, m_field_required')
							  ->where('m_field_reg', 'y')
							  ->get('member_fields');

		$cust_errors = array();
		$cust_fields = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				if ($row['m_field_required'] == 'y' &&
					( ! isset($_POST['m_field_id_'.$row['m_field_id']]) OR
						$_POST['m_field_id_'.$row['m_field_id']] == ''))
				{
					$cust_errors['m_field_id_'.$row['m_field_id']] = lang('mbr_field_required').'&nbsp;'.$row['m_field_label'];
				}
				elseif (isset($_POST['m_field_id_'.$row['m_field_id']]))
				{
					$cust_fields['m_field_id_'.$row['m_field_id']] = $this->EE->security->xss_clean($_POST['m_field_id_'.$row['m_field_id']]);
				}
			}
		}

		if (isset($_POST['email_confirm']) && $_POST['email'] != $_POST['email_confirm'])
		{
			$cust_errors['email_confirm'] = lang('mbr_emails_not_match');
		}

		if ($this->EE->config->item('use_membership_captcha') == 'y')
		{
			if ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
			{
				$cust_errors['captcha'] = lang('captcha_required');
			}
		}

		if ($this->EE->config->item('require_terms_of_service') == 'y')
		{
			if ( ! isset($_POST['accept_terms']))
			{
				$cust_errors['accept_terms'] = lang('mbr_terms_of_service_required');
			}
		}

		$errors = array_merge($errors, $cust_errors);

		// Display error is there are any
		if (count($errors) > 0)
		{
			return $errors;
		}

		// Do we require captcha?
		if ($this->EE->config->item('use_membership_captcha') == 'y')
		{
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_captcha WHERE word='".$this->EE->db->escape_str($_POST['captcha'])."' AND ip_address = '".$this->EE->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");

			if ($query->row('count')  == 0)
			{
				return array('captcha' => lang('captcha_incorrect'));
			}

			$this->EE->db->query("DELETE FROM exp_captcha WHERE (word='".$this->EE->db->escape_str($_POST['captcha'])."' AND ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
		}

		// Secure Mode Forms?
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes WHERE hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."' AND ip_address = '".$this->EE->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");

			if ($query->row('count')  == 0)
			{
				return lang('not_authorized');
			}

			$this->EE->db->query("DELETE FROM exp_security_hashes WHERE (hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
		}

		return FALSE;
	}


	/**
	 * Member Register
	 *
	 * Dear EL. Please please please put this code in a model somewhere.
	 * I copy-pasted 280 lines of code, just to remove the last line
	 * which displays your ugly grey error message.
	 */
	private function _member_register()
	{
		// Assign the base query data
		$data = array(
			'username'		=> $this->EE->input->post('username'),
			'password'		=> $this->EE->functions->hash($_POST['password']),
			'ip_address'	=> $this->EE->input->ip_address(),
			'unique_id'		=> $this->EE->functions->random('encrypt'),
			'join_date'		=> $this->EE->localize->now,
			'email'			=> $this->EE->input->post('email'),
			'screen_name'	=> $this->EE->input->post('screen_name'),
			'url'			=> prep_url($this->EE->input->post('url')),
			'location'		=> $this->EE->input->post('location'),

			// overridden below if used as optional fields
			'language'		=> ($this->EE->config->item('deft_lang')) ?
									$this->EE->config->item('deft_lang') : 'english',
			'time_format'	=> ($this->EE->config->item('time_format')) ?
									$this->EE->config->item('time_format') : 'us',
			'timezone'		=> ($this->EE->config->item('default_site_timezone') &&
								$this->EE->config->item('default_site_timezone') != '') ?
									$this->EE->config->item('default_site_timezone') : $this->EE->config->item('server_timezone'),
			'daylight_savings' => ($this->EE->config->item('default_site_dst') &&
									$this->EE->config->item('default_site_dst') != '') ?
										$this->EE->config->item('default_site_dst') : $this->EE->config->item('daylight_savings')
		);

		// Set member group

		if ($this->EE->config->item('req_mbr_activation') == 'manual' OR
			$this->EE->config->item('req_mbr_activation') == 'email')
		{
			$data['group_id'] = 4;  // Pending
		}
		else
		{
			if ($this->EE->config->item('default_member_group') == '')
			{
				$data['group_id'] = 4;  // Pending
			}
			else
			{
				$data['group_id'] = $this->EE->config->item('default_member_group');
			}
		}

		// Optional Fields

		$optional = array(
			'bio'			=> 'bio',
			'language'		=> 'deft_lang',
			'timezone'		=> 'server_timezone',
			'time_format'	=> 'time_format'
		);

		foreach($optional as $key => $value)
		{
			if (isset($_POST[$value]))
			{
				$data[$key] = $_POST[$value];
			}
		}

		if ($this->EE->input->post('daylight_savings') == 'y')
		{
			$data['daylight_savings'] = 'y';
		}
		elseif ($this->EE->input->post('daylight_savings') == 'n')
		{
			$data['daylight_savings'] = 'n';
		}

		// We generate an authorization code if the member needs to self-activate
		if ($this->EE->config->item('req_mbr_activation') == 'email')
		{
			$data['authcode'] = $this->EE->functions->random('alnum', 10);
		}

		// Insert basic member data
		$this->EE->db->query($this->EE->db->insert_string('exp_members', $data));

		$member_id = $this->EE->db->insert_id();

		// Insert custom fields
		$cust_fields['member_id'] = $member_id;

		$this->EE->db->query($this->EE->db->insert_string('exp_member_data', $cust_fields));


		// Create a record in the member homepage table
		// This is only necessary if the user gains CP access,
		// but we'll add the record anyway.

		$this->EE->db->query($this->EE->db->insert_string('exp_member_homepage',
								array('member_id' => $member_id)));

		// Mailinglist Subscribe
		$mailinglist_subscribe = FALSE;

		if (isset($_POST['mailinglist_subscribe']) && is_numeric($_POST['mailinglist_subscribe']))
		{
			// Kill duplicate emails from authorizatin queue.
			$this->EE->db->where('email', $_POST['email'])
						 ->delete('mailing_list_queue');

			// Validate Mailing List ID
			$query = $this->EE->db->select('COUNT(*) as count')
								  ->where('list_id', $_POST['mailinglist_subscribe'])
								  ->get('mailing_lists');

			// Email Not Already in Mailing List
			$results = $this->EE->db->select('COUNT(*) as count')
									->where('email', $_POST['email'])
									->where('list_id', $_POST['mailinglist_subscribe'])
									->get('mailing_list');

			// INSERT Email
			if ($query->row('count')  > 0 && $results->row('count')  == 0)
			{
				$mailinglist_subscribe = TRUE;

				$code = $this->EE->functions->random('alnum', 10);

				if ($this->EE->config->item('req_mbr_activation') == 'email')
				{
					// Activated When Membership Activated
					$this->EE->db->query("INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date)
								VALUES ('".$this->EE->db->escape_str($_POST['email'])."', '".$this->EE->db->escape_str($_POST['mailinglist_subscribe'])."', '".$code."', '".time()."')");
				}
				elseif ($this->EE->config->item('req_mbr_activation') == 'manual')
				{
					// Mailing List Subscribe Email
					$this->EE->db->query("INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date)
								VALUES ('".$this->EE->db->escape_str($_POST['email'])."', '".$this->EE->db->escape_str($_POST['mailinglist_subscribe'])."', '".$code."', '".time()."')");

					$this->EE->lang->loadfile('mailinglist');
					$action_id  = $this->EE->functions->fetch_action_id('Mailinglist', 'authorize_email');

					$swap = array(
									'activation_url'	=> $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$code,
									'site_name'			=> stripslashes($this->EE->config->item('site_name')),
									'site_url'			=> $this->EE->config->item('site_url')
								 );

					$template = $this->EE->functions->fetch_email_template('mailinglist_activation_instructions');
					$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
					$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

					// Send email
					$this->EE->load->library('email');
					$this->EE->email->wordwrap = true;
					$this->EE->email->mailtype = 'plain';
					$this->EE->email->priority = '3';

					$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
					$this->EE->email->to($_POST['email']);
					$this->EE->email->subject($email_tit);
					$this->EE->email->message($email_msg);
					$this->EE->email->send();
				}
				else
				{
					// Automatically Accepted
					$this->EE->db->query("INSERT INTO exp_mailing_list (list_id, authcode, email, ip_address)
										  VALUES ('".$this->EE->db->escape_str($_POST['mailinglist_subscribe'])."', '".$code."', '".$this->EE->db->escape_str($_POST['email'])."', '".$this->EE->db->escape_str($this->EE->input->ip_address())."')");
				}
			}
		}

		// Update
		if ($this->EE->config->item('req_mbr_activation') == 'none')
		{
			$this->EE->stats->update_member_stats();
		}

		// Send admin notifications
		if ($this->EE->config->item('new_member_notification') == 'y' &&
			$this->EE->config->item('mbr_notification_emails') != '')
		{
			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			$swap = array(
							'name'					=> $name,
							'site_name'				=> stripslashes($this->EE->config->item('site_name')),
							'control_panel_url'		=> $this->EE->config->item('cp_url'),
							'username'				=> $data['username'],
							'email'					=> $data['email']
						 );

			$template = $this->EE->functions->fetch_email_template('admin_notify_reg');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);

			$this->EE->load->helper('string');

			// Remove multiple commas
			$notify_address = reduce_multiples($this->EE->config->item('mbr_notification_emails'), ',', TRUE);

			// Send email
			$this->EE->load->helper('text');

			$this->EE->load->library('email');
			$this->EE->email->wordwrap = true;
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
			$this->EE->email->to($notify_address);
			$this->EE->email->subject($email_tit);
			$this->EE->email->message(entities_to_ascii($email_msg));
			$this->EE->email->Send();
		}

		// -------------------------------------------
		// 'member_member_register' hook.
		//  - Additional processing when a member is created through the User Side
		//  - $member_id added in 2.0.1
		//
			$edata = $this->EE->extensions->call('member_member_register', $data, $member_id);
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		// Send user notifications
		if ($this->EE->config->item('req_mbr_activation') == 'email')
		{
			$action_id  = $this->EE->functions->fetch_action_id('Member', 'activate_member');

			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			$board_id = ($this->EE->input->get_post('board_id') !== FALSE && is_numeric($this->EE->input->get_post('board_id'))) ? $this->EE->input->get_post('board_id') : 1;

			$forum_id = ($this->EE->input->get_post('FROM') == 'forum') ? '&r=f&board_id='.$board_id : '';

			$add = ($mailinglist_subscribe !== TRUE) ? '' : '&mailinglist='.$_POST['mailinglist_subscribe'];

			$swap = array(
				'name'				=> $name,
				'activation_url'	=> $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$data['authcode'].$forum_id.$add,
				'site_name'			=> stripslashes($this->EE->config->item('site_name')),
				'site_url'			=> $this->EE->config->item('site_url'),
				'username'			=> $data['username'],
				'email'				=> $data['email']
			 );

			$template = $this->EE->functions->fetch_email_template('mbr_activation_instructions');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);

			// Send email
			$this->EE->load->helper('text');

			$this->EE->load->library('email');
			$this->EE->email->wordwrap = true;
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
			$this->EE->email->to($data['email']);
			$this->EE->email->subject($email_tit);
			$this->EE->email->message(entities_to_ascii($email_msg));
			$this->EE->email->Send();

			$message = lang('mbr_membership_instructions_email');
		}
		elseif ($this->EE->config->item('req_mbr_activation') == 'manual')
		{
			$message = lang('mbr_admin_will_activate');
		}
		else
		{
			// Log user in (the extra query is a little annoying)
			$this->EE->load->library('auth');
			$member_data_q = $this->EE->db->get_where('members', array('member_id' => $member_id));

			$incoming = new Auth_result($member_data_q->row());
			$incoming->remember_me(60*60*24*182);
			$incoming->start_session();

			$message = lang('mbr_your_are_logged_in');
		}
	}

	/**
	 * Most pointless function ever, found in mod.member.php, should be in a helper somewhere
	 */
	private function _var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return FALSE;
		}

		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}

		return $str;
	}

	/**
	 * Don't know why there isn't a library to handle logins in EE
	 * Wrote most of this myself, based on mod.member_auth.php code
	 * Added in stuff for email auth
	 */
	private function _member_login()
	{
		$this->EE->load->library('auth');
		$errors = array();

		/* -------------------------------------------
		/* 'member_member_login_start' hook.
		/*  - Take control of member login routine
		/*  - Added EE 1.4.2
		*/
			$edata = $this->EE->extensions->call('member_member_login_start');
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		$email = $this->EE->input->post('email');
		$username = $this->EE->input->post('username');
		$password = $this->EE->input->post('password');

		if (isset($_POST['email']))
		{
			if (empty($email))
			{
				$errors['email'] = lang('no_email');
			}
		}
		else // no email submitted, so use username
		{
			if (empty($username))
			{
				$errors['username'] = lang('no_username');
			}
		}

		if (empty($password))
		{
			$errors['password'] = lang('no_password');
		}

		// oh dear, login failed already...
		if ( ! empty($errors))
		{
			return $errors;
		}

		// This should go in the auth lib.
		if ( ! $this->EE->auth->check_require_ip())
		{
			return lang('unauthorized_request');
		}

		// Check password lockout status
		if (TRUE === $this->EE->session->check_password_lockout($username))
		{
			$line = lang('password_lockout_in_effect');
			$line = str_replace("%x", $this->EE->config->item('password_lockout_interval'), $line);

			return array('password' => $line);
		}

		// do our own valid username/email check, since EE doesn't report
		// differences between invalid username and invalid password
		if ($email)
		{
			$member = $this->EE->db->get_where('members', array('email' => $email))->row_array();
			if (empty($member))
			{
				return array('email' => lang('invalid_email'));
			}

			$sess = $this->EE->auth->authenticate_email($email, $password);
		}
		else
		{
			$member = $this->EE->db->get_where('members', array('username' => $username))->row_array();
			if (empty($member))
			{
				return array('username' => lang('invalid_username'));
			}

			$sess = $this->EE->auth->authenticate_username($username, $password);
		}

		if ( ! $sess)
		{
			$this->EE->session->save_password_lockout($member['username']);
			return array('password' => lang('invalid_password'));
		}

		// Banned
		if ($sess->is_banned())
		{
			return array('username' => lang('not_authorized'));
		}

		// Allow multiple logins?
		// Do we allow multiple logins on the same account?
		if ($this->EE->config->item('allow_multi_logins') == 'n')
		{
			if ($sess->has_other_session())
			{
				return array('username' => lang('not_authorized'));
			}
		}

		/**
		 * At this point EE usually checks the username/password length is ok, and if not
		 * redirects to a member template to ask user to update. since we don't have a
		 * update form, will just have to leave it out for now
		 */

		// Start Session
		// "Remember Me" is one year
		if (isset($_POST['auto_login']))
		{
			$sess->remember_me(60*60*24*365);
		}

		$sess->start_session();
		$this->_update_online_user_stats();
	}

	/**
	 * Luckily this is a private function in mod.member_auth.php, so instead of having
	 * the burden of simply calling it, we get to copy/paste it here
	 */
	private function _update_online_user_stats()
	{
		if ($this->EE->config->item('enable_online_user_tracking') == 'n' OR
			$this->EE->config->item('disable_all_tracking') == 'y')
		{
			return;
		}

		// Update stats
		$cutoff = $this->EE->localize->now - (15 * 60);
		$anon = ($this->EE->input->post('anon') == 1) ? 'n' : 'y';

		$in_forum = ($this->EE->input->get_post('FROM') == 'forum') ? 'y' : 'n';

		$escaped_ip = $this->EE->db->escape_str($this->EE->input->ip_address());

		$this->EE->db->where('site_id', $this->EE->config->item('site_id'))
					 ->where("(ip_address = '".$escaped_ip."' AND member_id = '0')", '', FALSE)
					 ->or_where('date < ', $cutoff)
					 ->delete('online_users');

		$data = array(
						'member_id'		=> $this->EE->session->userdata('member_id'),
						'name'			=> ($this->EE->session->userdata('screen_name') == '') ? $this->EE->session->userdata('username') : $this->EE->session->userdata('screen_name'),
						'ip_address'	=> $this->EE->input->ip_address(),
						'in_forum'		=> $in_forum,
						'date'			=> $this->EE->localize->now,
						'anon'			=> $anon,
						'site_id'		=> $this->EE->config->item('site_id')
					);

		$this->EE->db->where('ip_address', $this->EE->input->ip_address())
					 ->where('member_id', $data['member_id'])
					 ->update('online_users', $data);
	}
}

/* End of file pi.freemember.php */