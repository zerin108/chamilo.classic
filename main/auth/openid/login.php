<?php
/**
 * OpenID login method
 * 
 * The OpenID login method relies on authentication servers providing a public
 * URL that can confirm the identity of a person, thus avoiding the spread
 * use of password transmissions over non-secure lines (for Dokeos, it is a
 * good way of avoiding password theft)
 */
/**
 * Initialisation
 */
require_once('openid.conf.php');
require_once('openid.lib.php');
/**
 * Logs the user in by using the $openid_url variable
 */
function login()
{
}

function openid_form() 
{
	return '<form name="openid_login"><input type="text" name="openid_url"></input><input type="submit" name="openid_login" value="OK" /></form>';
}

/**
 * The initial step of OpenID authentication responsible for the following:
 *  - Perform discovery on the claimed OpenID.
 *  - If possible, create an association with the Provider's endpoint.
 *  - Create the authentication request.
 *  - Perform the appropriate redirect.
 *
 * @param $claimed_id The OpenID to authenticate
 * @param $return_to The endpoint to return to from the OpenID Provider
 */
function openid_begin($claimed_id, $return_to = '', $form_values = array()) 
{

  $claimed_id = _openid_normalize($claimed_id);

  $services = openid_discovery($claimed_id);
  if (count($services) == 0) {
    echo 'Sorry, that is not a valid OpenID. Please ensure you have spelled your ID correctly.';
    return;
  }

  $op_endpoint = $services[0]['uri'];
  // Store the discovered endpoint in the session (so we don't have to rediscover).
  $_SESSION['openid_op_endpoint'] = $op_endpoint;
  // Store the claimed_id in the session (for handling delegation).
  $_SESSION['openid_claimed_id'] = $claimed_id;
  // Store the login form values so we can pass them to
  // user_exteral_login later.
  $_SESSION['openid_user_login_values'] = $form_values;

  // If bcmath is present, then create an association
  $assoc_handle = '';
  if (function_exists('bcadd')) {
    $assoc_handle = openid_association($op_endpoint);
  }

  // Now that there is an association created, move on
  // to request authentication from the IdP
  $identity = (!empty($services[0]['delegate'])) ? $services[0]['delegate'] : $claimed_id;
  if (isset($services[0]['types']) && is_array($services[0]['types']) && in_array(OPENID_NS_2_0 .'/server', $services[0]['types'])) {
    $identity = 'http://openid.net/identifier_select/2.0';
  }
  $authn_request = openid_authentication_request($claimed_id, $identity, $return_to, $assoc_handle, $services[0]['version']);

  if ($services[0]['version'] == 2) {
    openid_redirect($op_endpoint, $authn_request);
  }
  else {
    openid_redirect_http($op_endpoint, $authn_request);
  }
}

/**
 * Completes OpenID authentication by validating returned data from the OpenID
 * Provider.
 *
 * @param $response Array of returned from the OpenID provider (typically $_REQUEST).
 *
 * @return $response Response values for further processing with
 *   $response['status'] set to one of 'success', 'failed' or 'cancel'.
 */
function openid_complete($response) 
{
  // Default to failed response
  $response['status'] = 'failed';
  if (isset($_SESSION['openid_op_endpoint']) && isset($_SESSION['openid_claimed_id'])) {
    _openid_fix_post($response);
    $op_endpoint = $_SESSION['openid_op_endpoint'];
    $claimed_id = $_SESSION['openid_claimed_id'];
    unset($_SESSION['openid_op_endpoint']);
    unset($_SESSION['openid_claimed_id']);
    if (isset($response['openid.mode'])) {
      if ($response['openid.mode'] == 'cancel') {
        $response['status'] = 'cancel';
      }
      else {
        if (openid_verify_assertion($op_endpoint, $response)) {
          $response['openid.identity'] = $claimed_id;
          $response['status'] = 'success';
        }
      }
    }
  }
  return $response;
}

/**
 * Perform discovery on a claimed ID to determine the OpenID provider endpoint.
 *
 * @param $claimed_id The OpenID URL to perform discovery on.
 *
 * @return Array of services discovered (including OpenID version, endpoint
 * URI, etc).
 */
function openid_discovery($claimed_id) {

  $services = array();

  $xrds_url = $claimed_id;
  if (_openid_is_xri($claimed_id)) {
    $xrds_url = 'http://xri.net/'. $claimed_id;
  }
  $url = @parse_url($xrds_url);
  if ($url['scheme'] == 'http' || $url['scheme'] == 'https') {
    // For regular URLs, try Yadis resolution first, then HTML-based discovery
    $headers = array('Accept' => 'application/xrds+xml');
    //TODO
    $result = drupal_http_request($xrds_url, $headers);

    if (!isset($result->error)) {
      if (isset($result->headers['Content-Type']) && preg_match("/application\/xrds\+xml/", $result->headers['Content-Type'])) {
        // Parse XML document to find URL
        $services = xrds_parse($result->data);
      }
      else {
        $xrds_url = NULL;
        if (isset($result->headers['X-XRDS-Location'])) {
          $xrds_url = $result->headers['X-XRDS-Location'];
        }
        else {
          // Look for meta http-equiv link in HTML head
          $xrds_url = _openid_meta_httpequiv('X-XRDS-Location', $result->data);
        }
        if (!empty($xrds_url)) {
          $headers = array('Accept' => 'application/xrds+xml');
          //TODO
          $xrds_result = drupal_http_request($xrds_url, $headers);
          if (!isset($xrds_result->error)) {
            $services = xrds_parse($xrds_result->data);
          }
        }
      }

      // Check for HTML delegation
      if (count($services) == 0) {
        // Look for 2.0 links
        $uri = _openid_link_href('openid2.provider', $result->data);
        $delegate = _openid_link_href('openid2.local_id', $result->data);
        $version = 2;

        // 1.0 links
        if (empty($uri)) {
          $uri = _openid_link_href('openid.server', $result->data);
          $delegate = _openid_link_href('openid.delegate', $result->data);
          $version = 1;
        }
        if (!empty($uri)) {
          $services[] = array('uri' => $uri, 'delegate' => $delegate, 'version' => $version);
        }
      }
    }
  }
  return $services;
}

/**
 * Attempt to create a shared secret with the OpenID Provider.
 *
 * @param $op_endpoint URL of the OpenID Provider endpoint.
 *
 * @return $assoc_handle The association handle.
 */
function openid_association($op_endpoint) {

  // Remove Old Associations:
  //TODO
  api_sql_query("DELETE FROM {openid_association} WHERE created + expires_in < %d", time());

  // Check to see if we have an association for this IdP already
  $assoc_handle = api_sql_query("SELECT assoc_handle FROM {openid_association} WHERE idp_endpoint_uri = '%s'", $op_endpoint));
  if (Database::num_rows($assoc_handle)<=1) {
    $mod = OPENID_DH_DEFAULT_MOD;
    $gen = OPENID_DH_DEFAULT_GEN;
    $r = _openid_dh_rand($mod);
    $private = bcadd($r, 1);
    $public = bcpowmod($gen, $private, $mod);

    // If there is no existing association, then request one
    $assoc_request = openid_association_request($public);
    $assoc_message = _openid_encode_message(_openid_create_message($assoc_request));
    $assoc_headers = array('Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8');
    //TODO
    $assoc_result = drupal_http_request($op_endpoint, $assoc_headers, 'POST', $assoc_message);
    if (isset($assoc_result->error)) {
      return FALSE;
    }

    $assoc_response = _openid_parse_message($assoc_result->data);
    if (isset($assoc_response['mode']) && $assoc_response['mode'] == 'error') {
        return FALSE;
    }

    if ($assoc_response['session_type'] == 'DH-SHA1') {
      $spub = _openid_dh_base64_to_long($assoc_response['dh_server_public']);
      $enc_mac_key = base64_decode($assoc_response['enc_mac_key']);
      $shared = bcpowmod($spub, $private, $mod);
      $assoc_response['mac_key'] = base64_encode(_openid_dh_xorsecret($shared, $enc_mac_key));
    }
    //TODO
    api_sql_query("INSERT INTO {openid_association} (idp_endpoint_uri, session_type, assoc_handle, assoc_type, expires_in, mac_key, created) VALUES('%s', '%s', '%s', '%s', %d, '%s', %d)",
             $op_endpoint, $assoc_response['session_type'], $assoc_response['assoc_handle'], $assoc_response['assoc_type'], $assoc_response['expires_in'], $assoc_response['mac_key'], time());

    $assoc_handle = $assoc_response['assoc_handle'];
  }

  return $assoc_handle;
}

/**
 * Authenticate a user or attempt registration.
 *
 * @param $response Response values from the OpenID Provider.
 */
function openid_authentication($response) {

  $identity = $response['openid.identity'];

  $account = user_external_load($identity);
  if (isset($account->uid)) {
    if (!variable_get('user_email_verification', TRUE) || $account->login) {
      user_external_login($account, $_SESSION['openid_user_login_values']);
    }
    else {
      //TODO
      //drupal_set_message(t('You must validate your email address for this account before logging in via OpenID'));
    }
  }
  elseif (variable_get('user_register', 1)) {
    // Register new user
    $form_state['redirect'] = NULL;
    $form_state['values']['name'] = (empty($response['openid.sreg.nickname'])) ? $identity : $response['openid.sreg.nickname'];
    $form_state['values']['mail'] = (empty($response['openid.sreg.email'])) ? '' : $response['openid.sreg.email'];
    $form_state['values']['pass']  = user_password();
    $form_state['values']['status'] = variable_get('user_register', 1) == 1;
    $form_state['values']['response'] = $response;
    $form_state['values']['auth_openid'] = $identity;
    //TODO
    $form = drupal_retrieve_form('user_register', $form_state);
    drupal_prepare_form('user_register', $form, $form_state);
    drupal_validate_form('user_register', $form, $form_state);
    if (form_get_errors()) {
      // We were unable to register a valid new user, redirect to standard
      // user/register and prefill with the values we received.
      drupal_set_message(t('OpenID registration failed for the reasons listed. You may register now, or if you already have an account you can <a href="@login">log in</a> now and add your OpenID under "My Account"', array('@login' => url('user/login'))), 'error');
      $_SESSION['openid'] = $form_state['values'];
      // We'll want to redirect back to the same place.
      $destination = drupal_get_destination();
      unset($_REQUEST['destination']);
      drupal_goto('user/register', $destination);
    }
    else {
      unset($form_state['values']['response']);
      $account = user_save('', $form_state['values']);
      user_external_login($account);
    }
    drupal_redirect_form($form, $form_state['redirect']);
  }
  else {
    drupal_set_message(t('Only site administrators can create new user accounts.'), 'error');
  }
  drupal_goto();
}

function openid_association_request($public) {
  include_once drupal_get_path('module', 'openid') .'/openid.inc';

  $request = array(
    'openid.ns' => OPENID_NS_2_0,
    'openid.mode' => 'associate',
    'openid.session_type' => 'DH-SHA1',
    'openid.assoc_type' => 'HMAC-SHA1'
  );

  if ($request['openid.session_type'] == 'DH-SHA1' || $request['openid.session_type'] == 'DH-SHA256') {
    $cpub = _openid_dh_long_to_base64($public);
    $request['openid.dh_consumer_public'] = $cpub;
  }

  return $request;
}

function openid_authentication_request($claimed_id, $identity, $return_to = '', $assoc_handle = '', $version = 2) {

  $realm = ($return_to) ? $return_to : url('', array('absolute' => TRUE));

  $ns = ($version == 2) ? OPENID_NS_2_0 : OPENID_NS_1_0;
  $request =  array(
    'openid.ns' => $ns,
    'openid.mode' => 'checkid_setup',
    'openid.identity' => $identity,
    'openid.claimed_id' => $claimed_id,
    'openid.assoc_handle' => $assoc_handle,
    'openid.return_to' => $return_to,
  );

  if ($version == 2) {
    $request['openid.realm'] = $realm;
  }
  else {
    $request['openid.trust_root'] = $realm;
  }

  // Simple Registration
  $request['openid.sreg.required'] = 'nickname,email';
  $request['openid.ns.sreg'] = "http://openid.net/extensions/sreg/1.1";

  $request = array_merge($request, module_invoke_all('openid', 'request', $request));

  return $request;
}

/**
 * Attempt to verify the response received from the OpenID Provider.
 *
 * @param $op_endpoint The OpenID Provider URL.
 * @param $response Array of repsonse values from the provider.
 *
 * @return boolean
 */
function openid_verify_assertion($op_endpoint, $response) {

  $valid = FALSE;

	//TODO
  $association = db_fetch_object(db_query("SELECT * FROM {openid_association} WHERE assoc_handle = '%s'", $response['openid.assoc_handle']));
  if ($association && isset($association->session_type)) {
    $keys_to_sign = explode(',', $response['openid.signed']);
    $self_sig = _openid_signature($association, $response, $keys_to_sign);
    if ($self_sig == $response['openid.sig']) {
      $valid = TRUE;
    }
    else {
      $valid = FALSE;
    }
  }
  else {
    $request = $response;
    $request['openid.mode'] = 'check_authentication';
    $message = _openid_create_message($request);
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8');
    $result = drupal_http_request($op_endpoint, $headers, 'POST', _openid_encode_message($message));
    if (!isset($result->error)) {
      $response = _openid_parse_message($result->data);
      if (strtolower(trim($response['is_valid'])) == 'true') {
        $valid = TRUE;
      }
      else {
        $valid = FALSE;
      }
    }
  }

  return $valid;
}