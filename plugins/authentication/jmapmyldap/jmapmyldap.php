<?php
/**
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic.Plugin
 * @subpackage  Authentication.JMapMyLDAP
 * 
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.plugin.plugin');
jimport('shmanic.log.ldaphelper');

/**
 * LDAP Authentication Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  Authentication.JMapMyLDAP
 * @since       1.0
 */
class plgAuthenticationJMapMyLDAP extends JPlugin 
{
	
	/**
	 * Constructor
	 *
	 * @param  object  &$subject  The object to observe
	 * @param  array   $config    An array that holds the plugin configuration
	 * 
	 * @since  2.0
	 */
	function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();

	}
	
	/**
	 * This method handles the Ldap authentication and reports 
	 * back to the subject. 
	 * 
	 * There is no custom logging in the authentication.
	 *
	 * @param  array   $credentials  Array holding the user credentials
	 * @param  array   $options      Array of extra options
	 * @param  object  &$response    Authentication response object
	 * 
	 * @return  boolean  Authentication result
	 * @since   1.0
	 */
	public function onUserAuthenticate($credentials, $option, &$response)
	{

		// add the loggers
		JLogLdapHelper::addLoggers();
		
		// If JLDAP2 fails to import then exit
		jimport('shmanic.client.jldap2');
		if(!class_exists('JLDAP2')) { 
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::sprintf('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_MISSING_LIBRARY', 'JLDAP2');
			JLogLdapHelper::addErrorEntry(JText::sprintf('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_MISSING_LIBRARY', 'JLDAP2'), __CLASS__);
			return false;
		}
		
		$response->type = 'LDAP';
		
		// Must have a password to deny anonymous binding
		if(empty($credentials['password'])) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_PASS_BLANK');
			JLogLdapHelper::addInfoEntry(JText::_('JGLOBAL_AUTH_PASS_BLANK'), __CLASS__);
			return false;
		}
		
		$ldap = JLDAP2::getInstance($this->params);
		
		// Start the LDAP connection procedure
		if(!$result = $ldap->connect()) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = 'could not connect';
			return;
		}
		
		/* We will now get the authenticated user's dn.
		 * In this method we are also going to test the 
		 * dn against the password. Therefore, if any dn
		 * is returned, it is a successfully authenticated
		 * user.
		 */
		if(!$dn = $ldap->getUserDN($credentials['username'], $credentials['password'], true)) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_BIND_FAILED');
			$ldap->close();
			return;
		}
		
		/* Let's get the user attributes for this dn. */
		if(!$details = $ldap->getUserDetails($dn)) {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = $this->_reportError(JText::_('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_ATTRIBUTES_FAIL'));
			$ldap->close();
			return false;
		}
		
		/* Set the required Joomla user fields with the Ldap
		 * user attributes.
		 */
		if(isset($details[$ldap->ldap_uid][0])) 
			$response->username 	= $details[$ldap->ldap_uid][0];
			
		if(isset($details[$ldap->ldap_fullname][0])) 
			$response->fullname 	= $details[$ldap->ldap_fullname][0];
			
		if(isset($details[$ldap->ldap_email][0])) 
			$response->email 		= $details[$ldap->ldap_email][0];
			
		$response->set('password_clear', ''); //joomla password should always be blank TODO: review this for password plug-in
		
		/* store for the user plugin so we do not have
		 * to requery everything with the ldap server.
		 * 
		 * NOTE: This uses attributes and is hard coded!
		 * Therefore when querying ldap stuff back, we must
		 * use attributes - TODO: make this a constant!
		*/
		$response->set('attributes', $details);
		
		// Successful authentication, report back and say goodbye!
		JLogLdapHelper::addInfoEntry('User ' . $response->username . ' successfully logged in.', __CLASS__);
		$response->status			= JAuthentication::STATUS_SUCCESS;
		$response->error_message 	= '';
		$ldap->close();
		
		return true;
		
	}
	
	// new sso method ?? i cant see this working!
	public function onUserAuthorisation($response, $options=Array())
	{
		
	}
	
	
	protected function logtesting()
	{
		
		/* SYSTEM TESTING FOR ERRORS */
		 /*JLog::addLogger(			array('logger'=>'messagequeue'),
		JLog::INFO,
		array('ldap'));*/
		
		// Both of these should be file and on-screen
		
		
		
		/* Add file based loggers */
		/*JLog::addLogger(
		array('logger'=>'formattedtext', 'text_file'=>'myext.info.log.php'), JLog::INFO,
		array('myextension'));
		
		JLog::addLogger(
		array('logger'=>'formattedtext', 'text_file'=>'myext.error.log.php'), JLog::ERROR,
		array('myextension'));*/
		
		/* Add on-screen based loggers */
		/*JLog::addLogger(array('logger'=>'messagequeue'), JLog::INFO, array('myextension'));
		JLog::addLogger(array('logger'=>'messagequeue'), JLog::ERROR, array('myextension'));*/
		
		/* These both should print to screen and save to file */
		/*JLog::add('THIS IS INFO2', JLog::INFO, 'myextension');
		JLog::add('THIS IS ERROR2', JLog::ERROR, 'myextension');*/
		
		
		/*JLogLdapHelper::addErrorEntry('THIS IS ERROR', '12345');
		JLogLdapHelper::addErrorEntry('THIS IS ERROR2', '12345');
		 JLogLdapHelper::addInfoEntry('THIS IS INFO', '1234');
		// In file only
		JLogLdapHelper::addDebugEntry('THIS IS DEBUG', '123456');*/
		
		
		
		//die();
		
		//$testing = new JLogEntryLdapEntry('someshit', 'you are shit', JLog::ERROR, 'jldap2');
		//$tmp = array_change_key_case(get_object_vars($testing), CASE_UPPER);
		//print_r($tmp); die();
		//JLog::add($testing);
		
		/*JLog::add('this is info',		JLog::INFO,		'ldap');
		
		JLog::add('oh no an error',		JLog::ERROR,	'ldap');
		
		JLog::add('this is some debug',	JLog::DEBUG,	'ldap');
		
		// this is being printed to my info log file :(
		JLog::add('this is nothing to do with ldap', JLog::INFO);
		
		JLog::add('why are you printing to my ldap log again', JLog::WARNING);
		
		JLog::add('this should be printed out to screen and saved in log', JLog::WARNING, 'ldap');*/
		
		//return;
		
		
		
		// on-screen messages
		/*JFactory::getApplication()->enqueueMessage('This is a message', 	'message ldap');
		JFactory::getApplication()->enqueueMessage('This is a warning', 	'warning ldap');
		JFactory::getApplication()->enqueueMessage('This is a notice', 		'notice ldap');
		JFactory::getApplication()->enqueueMessage('This is a error', 		'error ldap');
		*/
		
	}
	
	/**
	 * This method should handle the SSO Ldap authentication (though
	 * only a username check and no password) and report 
	 * back to the subject. It will also save the ldapUser reference
	 * in the response to be used later in the user plugin (this is
	 * for efficiency).
	 * //TODO: edit parts for the new version
	 * @param  string  $username   String holding the username to check
	 * @param  array   $options    Array of extra options
	 * @param  object  &$response  Authentication response object
	 * 
	 * @return  boolean  Authentication result
	 * @since   1.0
	 */
	public function onSSOAuthenticate($username, $options, &$response) 
	{
		//load up the front end lanuages (used for errors)
		$this->loadLanguage();
		
		jimport('shmanic.jldap2');
		if(!class_exists('JLDAP2')) { //checks for the library
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = $this->_reportError(JText::sprintf('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_MISSING_LIBRARY', 'JLDAP2'));
			return false;
		}
		
		// For JLog & JMapMyLDAP Auth Type Parameter
		$response->type = 'LDAP';

		// load plugin params info
		$ldap 			= new JLDAP2($this->params);
		$userPlugin 	= array();
		
		
		/* Lets start the ldap connection procedure - 
		 * this is slightly different from the user
		 * plugin way as we want to manage the connection
		 * here to authenticate the user with their
		 * credentials - T-1000, advanced prototype
		 */
		$result = $ldap->connect();
		if(JError::isError($result)) {
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = $this->_reportError($result);
			return;
		}
		
		/* We will now get the authenticated user's dn -
		 * in this method we are also going to test dn 
		 * exists. Therefore, if any dn is returned, 
		 * we assume its the SSO user.
		 */
		$dn = $ldap->getUserDN($username, null, false); 
		if(JError::isError($dn)) {
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = $dn;
			return false;
		}

		if(!$dn) { //couldn't find the SSO username
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$ldap->close();
			return false;
		}
		
		return $this->doUserDetails($ldap, $dn, $response);
		
	}
	
	
	public function doUserDetails(&$ldap, $dn, &$response) 
	{

		//we can breath cause we are authenticated
		
		//BOTH THESE CALLING METHODS MUST BE CALLED FROM A LIBRARY!
		
		//CALLING FOR THE REQUIRED ATTRIBUTES
		//...do the work...
		
		//CALLING FOR EXTRA LDAP WORK
		

		
		$details = $ldap->getUserDetails($dn);
		if(JError::isError($details)) {
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = $this->_reportError(JText::_('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_ATTRIBUTES_FAIL'));
			return false;
		}
		
		if(isset($details[$ldap->ldap_uid][0])) 
			$response->username 	= $details[$ldap->ldap_uid][0];
			
		if(isset($details[$ldap->ldap_fullname][0])) 
			$response->fullname 	= $details[$ldap->ldap_fullname][0];
			
		if(isset($details[$ldap->ldap_email][0])) 
			$response->email 		= $details[$ldap->ldap_email][0];
			
		$response->set('password_clear',''); //joomla password should always be blank
			
		$response->status			= JAUTHENTICATE_STATUS_SUCCESS;
		$response->error_message 	= '';
		
		/* we should never require an ldap connection
		 * again - hasta la vista, baby!
		 */
		$ldap->close();
		
		return true;
		
	}
	
	/**
	 * This method should get the LDAP user details.
	 * 
	 * Attempts to get the jmapmyldap user plugin parameters - 
	 * if they don't exist then we assume there is no group
	 * mapping for this installation. Therefore, this would
	 * be treated as a authentication only.
	 * 
	 * The name will be hard coded to the original jmapmyldap
	 * user plugin. Developers wanting to use something else 
	 * will have to create a new authentication plugin. This is
	 * because most of the actions here are based on the fields
	 * named in jmapmyldap - and its not that hard to create a 
	 * new one due to the extensive use of the jldap2 library.
	 * http://ls/joomla1/administrator/index.php?option=com_plugins&view=plugin&layout=edit&extension_id=10003
	 * @param  jldap2  &$ldap      Connect JLDAP2 object 
	 * @param  string  $dn         String containing the user's dn
	 * @param  object  &$response  Authentication response object
	 * 
	 * @return  boolean  True on get detail success
	 * @since   1.0
	 */
	public function OLDdoUserDetails(&$ldap, $dn, &$response)
	{

		
		$ldapUser	 	= Array();
		
		jimport('shmanic.jmapmyldap');
		if(class_exists('JMapMyLDAP')) { //checks for the library
			$userParams 	= new JRegistry;
			$userPlugin 	= JPluginHelper::getPlugin('user','jmapmyldap');
		}

		if(count($userPlugin)) {
			/* we can use the parameters from the user plugin
			 * to determine if we should get the user groups.
			 */
			
			$userParams->loadJSON($userPlugin->params);
			
			$attributes = JMapMyLDAP::getAttributes($userParams); //get the attributes we need to process in regards to the group map
			
			$details = $ldap->getUserDetails($dn, $attributes);
			if(JError::isError($details)) {
				$response->status = JAUTHENTICATE_STATUS_FAILURE;
				$response->error_message = $this->_reportError(JText::_('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_ATTRIBUTES_FAIL'));
				return false;
			}
			
			//we must convert this to a jmapmyentry
			if(isset($details['dn']) && $details['dn']!="") {
				
				$ldapUser = isset($details[$attributes['lookupKey']]) ?
					$ldapUser = new JMapMyEntry($details['dn'], $details[$attributes['lookupKey']]) :
					$ldapUser = new JMapMyEntry($details['dn'], array());
				
			} else {
				$response->status = JAUTHENTICATE_STATUS_FAILURE;
				$response->error_message = $this->_reportError(JText::_('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_USER_DETAIL_FAIL'));
				return false;
			}
			
			foreach($details as $key=>$val) { //loop round each attribute
				if($key!="" && $key!="dn" && $key!=$attributes['lookupKey']) { //we want to override some though
					//overrides for username, name and email so we can get them back later
					if		($key==$ldap->ldap_fullname) 	$ldapUser->set('fullname', $val);
					elseif	($key==$ldap->ldap_email) 		$ldapUser->set('email', $val);
					elseif	($key==$ldap->ldap_uid) 		$ldapUser->set('username', $val);
					else 	$ldapUser->set($key, $val);
				}
			}

			/* store for the user plugin so we do not have
			 * to requery everything with the ldap server.
			 */
			$response->set('jmapmyentry', $ldapUser);

		} else {

			/* what we are going to do is assume this is a
			 * authentication plugin installation only.
			 * Therefore, we are only getting the user details
			 * for the authentication process only.
			 */
			$details = $ldap->getUserDetails($dn);

		}
		
		if(JError::isError($details)) {
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = $this->_reportError(JText::_('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_ATTRIBUTES_FAIL'));
			return false;
		}
		
		if(isset($details[$ldap->ldap_uid][0])) 
			$response->username 	= $details[$ldap->ldap_uid][0];
			
		if(isset($details[$ldap->ldap_fullname][0])) 
			$response->fullname 	= $details[$ldap->ldap_fullname][0];
			
		if(isset($details[$ldap->ldap_email][0])) 
			$response->email 		= $details[$ldap->ldap_email][0];
			
		$response->set('password_clear',''); //joomla password should always be blank
			
		$response->status			= JAUTHENTICATE_STATUS_SUCCESS;
		$response->error_message 	= '';
		
		/* we should never require an ldap connection
		 * again - hasta la vista, baby!
		 */
		$ldap->close();
		
		return true;
	}
	
	/**
	 * Reports an error to the screen if debug mode is enabled.
	 * As the reponse caller in onUserAuthenticate() manages 
	 * the JLog, its excluded from this method.
	 *
	 * @param  mixed  $exception  The authentication error can either be
	 *                              a string or a JException.
	 * 
	 * @return  string  Exception comment string
	 * @since   1.0
	 */
	protected function _reportError($exception = null) 
	{
		$comment = is_null($exception) ? JText::_('PLG_AUTHENTICATION_JMAPMYLDAP_ERROR_UNKNOWN') : $exception;
			
		if(JDEBUG) {
			JError::raiseWarning('SOME_ERROR_CODE', $comment); 
		}

		return $comment;
	}
	
}