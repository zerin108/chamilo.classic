<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls 
 */
require_once '../global.inc.php';
$action = $_GET['a'];

switch ($action) {	
	case 'find_users':
	
		if (api_is_anonymous()){
			echo '';
			break;
		}
		$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
		$tbl_my_user		= Database :: get_main_table(TABLE_MAIN_USER);
		$tbl_my_user_friend = Database :: get_main_table(TABLE_MAIN_USER_FRIEND);
		$tbl_user 			= Database :: get_main_table(TABLE_MAIN_USER);
		$search				= Database::escape_string(Security::remove_XSS($_POST['search']));
		$current_date		= date('Y-m-d H:i:s',time());
		
		$user_id = api_get_user_id();
		$is_western_name_order = api_is_western_name_order();
		
		if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') {
			
			//all users 
			if (api_get_setting('display_all_platform_users_in_message_tool') == 'true') {
				$sql = 'SELECT DISTINCT u.user_id as id, '.($is_western_name_order ? 'concat(u.firstname," ",u.lastname," ","( ",u.email," )")' : 'concat(u.lastname," ",u.firstname," ","( ",u.email," )")').' as name
				FROM '.$tbl_user.' u  	
		 		WHERE u.user_id <>'.(int)$user_id.' AND '.($is_western_name_order ? 'concat(u.firstname, " ", u.lastname)' : 'concat(u.lastname, " ", u.firstname)').' like CONCAT("%","'.$search.'","%") LIMIT 15';		
			} else {
				//only my contacts
				$sql = 'SELECT DISTINCT u.user_id as id, '.($is_western_name_order ? 'concat(u.firstname," ",u.lastname," ","( ",u.email," )")' : 'concat(u.lastname," ",u.firstname," ","( ",u.email," )")').' as name
				FROM '.$tbl_my_user_friend.' uf ' .
		 		'INNER JOIN '.$tbl_my_user.' AS u  ON uf.friend_user_id = u.user_id ' .
		 		'WHERE relation_type<>6 AND friend_user_id<>'.(int)$user_id.' AND '.($is_western_name_order ? 'concat(u.firstname, " ", u.lastname)' : 'concat(u.lastname, " ", u.firstname)').' like CONCAT("%","'.$search.'","%") ';
			}
		 	
		} elseif (api_get_setting('allow_social_tool')=='false' && api_get_setting('allow_message_tool')=='true') {
			$valid=api_get_setting('time_limit_whosonline');
			
			$sql='SELECT DISTINCT u.user_id as id, '.($is_western_name_order ? 'concat(u.firstname," ",u.lastname," ","( ",u.email," )")' : 'concat(u.lastname," ",u.firstname," ","( ",u.email," )")').' as name
			 FROM '.$tbl_my_user.' u INNER JOIN '.$track_online_table.' t ON u.user_id=t.login_user_id
			 WHERE DATE_ADD(login_date,INTERVAL "'.$valid.'" MINUTE) >= "'.$current_date.'" AND '.($is_western_name_order ? 'concat(u.firstname, " ", u.lastname)' : 'concat(u.lastname, " ", u.firstname)').' like CONCAT("%","'.$search.'","%") ';
		}		
		$result=Database::query($sql,__FILE__,__LINE__);
		
		if (Database::num_rows($result)>0) {
				while ($row = Database::fetch_array($result,'ASSOC')) {
				$return[] = array('caption'=>$row['name'], 'value'=>$row['id']);
			}
		}
		echo json_encode($return);	
		break;
		
		
	default:
		echo '';
	
}
exit;
?>