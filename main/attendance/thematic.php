<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for thematic control 
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.attendance
*/

// protect a course script
api_protect_course_script(true);


if (api_is_allowed_to_edit(null, true)) {
	$param_gradebook = '';
	if (isset($_SESSION['gradebook'])) {
		$param_gradebook = '&gradebook='.Security::remove_XSS($_SESSION['gradebook']);
	}
	echo '<div class="actions" style="margin-bottom:30px">';
	echo '<a href="index.php?'.api_get_cidreq().$param_gradebook.'&action=thematic_details">'.Display::return_icon('view_table.gif',get_lang('ThematicDetails')).' '.get_lang('ThematicDetails').'</a>';	
	echo '<a href="index.php?'.api_get_cidreq().$param_gradebook.'&action=thematic_list">'.Display::return_icon('view_list.gif',get_lang('ThematicList')).' '.get_lang('ThematicList').'</a>';
	if ($action == 'thematic_list') {
		echo '<a href="index.php?'.api_get_cidreq().$param_gradebook.'&action=thematic_add">'.Display::return_icon('introduction_add.gif',get_lang('NewThematicSection')).' '.get_lang('NewThematicSection').'</a>';
	}
	echo '</div>';
}

$token = md5(uniqid(rand(),TRUE));
$_SESSION['thematic_token'] = $token;


if ($action == 'thematic_list') {

	/*
	if (api_is_allowed_to_edit(null, true)) {
		$param_gradebook = '';
		if (isset($_SESSION['gradebook'])) {
			$param_gradebook = '&gradebook='.Security::remove_XSS($_SESSION['gradebook']);
		}
		echo '<div class="actions" style="margin-bottom:30px">';
		echo '<a href="index.php?'.api_get_cidreq().$param_gradebook.'&action=thematic_add">'.Display::return_icon('introduction_add.gif',get_lang('NewThematicSection')).' '.get_lang('NewThematicSection').'</a>';		
		echo '</div>';
	}
	*/
	
	echo '<div><strong>'.get_lang('ThematicList').'</strong></div>';
	
	$table = new SortableTable('thematic_list', array('Thematic', 'get_number_of_thematics'), array('Thematic', 'get_thematic_data'));
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false, array('style'=>'width:20px;'));
	$table->set_header(1, get_lang('Title'), false );
	
	if (api_is_allowed_to_edit(null, true)) {
		$table->set_header(2, get_lang('Actions'), false,array('style'=>'text-align:center;width:40%;'));
		$table->set_form_actions(array ('thematic_delete_select' => get_lang('DeleteAllThematics')));	
	}
	
	$table->display();
	
} else if ($action == 'thematic_details') {

	if (!empty($thematic_id)) {
		echo '<div><strong>'.$thematic_data[$thematic_id]['title'].': '.get_lang('Details').'</strong></div><br />';
		echo '<div>'.get_lang('Progress').': '.$total_average_of_advances.'%</div><br />';					
	} else {
		echo '<div><strong>'.get_lang('ThematicDetails').'</strong></div><br />';
		echo '<div>'.get_lang('Progress').': <span id="div_result">'.$total_average_of_advances.'</span>%</div><br />';		
	}
	
	echo '<table width="100%" class="data_table">';	
	echo '<tr><th width="35%">'.get_lang('Thematic').'</th><th width="30%">'.get_lang('ThematicPlan').'</th><th width="25%">'.get_lang('ThematicAdvance').'</th></tr>';

		foreach ($thematic_data as $thematic) {			
			echo '<tr>';
			
			// display thematic data		
			echo '<td><div><strong>'.$thematic['title'].'</strong></div><div>'.$thematic['content'].'</div></td>';
			
			// display thematic plan data
			echo '<td>';
				//echo '<div style="text-align:right"><a href="index.php?'.api_get_cidreq().'&origin=thematic_details&action=thematic_plan_list&thematic_id='.$thematic['id'].$param_gradebook.'">'.Display::return_icon('info.gif',get_lang('ThematicPlan'),array('style'=>'vertical-align:middle')).' '.get_lang('EditThematicPlan').'</a></div><br />';
				if (api_is_allowed_to_edit(null, true)) {
					echo '<div style="text-align:right"><a href="index.php?'.api_get_cidreq().'&origin=thematic_details&action=thematic_plan_list&thematic_id='.$thematic['id'].$param_gradebook.'">'.Display::return_icon('edit.gif',get_lang('EditThematicPlan'),array('style'=>'vertical-align:middle')).'</a></div><br />';
				}
				if (!empty($thematic_plan_data[$thematic['id']])) {
					foreach ($thematic_plan_data[$thematic['id']] as $thematic_plan) {
						echo '<div><strong>'.$thematic_plan['title'].'</strong></div><div>'.$thematic_plan['description'].'</div>'; 
					}
				} else {
					echo '<div><em>'.get_lang('StillDoNotHaveAThematicPlan').'</em></div>';
				}				
			echo '</td>';
			
			// display thematic advance data
			echo '<td>';
				//echo '<div style="text-align:right"><a href="index.php?'.api_get_cidreq().'&origin=thematic_details&action=thematic_advance_list&thematic_id='.$thematic['id'].$param_gradebook.'">'.Display::return_icon('porcent.png',get_lang('ThematicAdvance'),array('style'=>'vertical-align:middle')).' '.get_lang('EditThematicAdvance').'</a></div><br />';
				if (api_is_allowed_to_edit(null, true)) {
					echo '<div style="text-align:right"><a href="index.php?'.api_get_cidreq().'&origin=thematic_details&action=thematic_advance_list&thematic_id='.$thematic['id'].$param_gradebook.'">'.Display::return_icon('edit.gif',get_lang('EditThematicAdvance'),array('style'=>'vertical-align:middle')).'</a></div><br />';
				}
				if (!empty($thematic_advance_data[$thematic['id']])) {
					echo '<table width="100%">';					
						foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
							echo '<tr>';
							echo '<td width="90%">';
								echo '<div><strong>'.api_get_local_time($thematic_advance['start_date']).'</strong></div>';
								echo '<div>'.$thematic_advance['content'].'</div>';
								echo '<div>'.get_lang('Hours').' : '.$thematic_advance['duration'].'</div>';
							echo '</td>';
							if (empty($thematic_id) && api_is_allowed_to_edit(null, true)) {
								$checked = '';
								if ($last_done_thematic_advance == $thematic_advance['id']) {
									$checked = 'checked';
								}								
								echo '<td><center><input type="radio" name="thematic_done" value="'.$thematic_advance['id'].'" '.$checked.' onclick="update_done_thematic_advance(this.value)"></center></td>';
							} else {
								if ($thematic_advance['done_advance'] == 1) {
									echo '<td><center>'.get_lang('Done').'</center></td>';	
								} else {
									echo '<td><center>-</center></td>';
								}
								
							}
							echo '</tr>';							 
						}
					echo '</table>';
				} else {
					echo '<div><em>'.get_lang('StillDoNotHaveAThematicAdvance').'</em></div>';
				}				
			echo '</td>';
			
			echo '</tr>';
			
		}

	echo '</table>';
	
} else if ($action == 'thematic_add' || $action == 'thematic_edit') {

	$header_form = get_lang('NewThematicSection');
	if ($action == 'thematic_edit') {
		$header_form = get_lang('EditThematicSection');	
	}
	
	// display form
	$form = new FormValidator('thematic_add','POST','index.php?action='.$action.'&'.api_get_cidreq().$param_gradebook,'','style="width: 100%;"');
	$form->addElement('header', '', $header_form);	
	$form->addElement('hidden', 'thematic_token',$token);
	
	if (!empty($thematic_id)) {
		$form->addElement('hidden', 'thematic_id',$thematic_id);
	}
		
	$form->add_textfield('title', get_lang('Title'), true, array('size'=>'50'));
	$form->add_html_editor('content', get_lang('Content'), false, false, array('ToolbarSet' => 'TrainingDescription', 'Width' => '100%', 'Height' => '250'));	
	$form->addElement('html','<div class="clear" style="margin-top:50px;"></div>');
	$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
	
	if (!empty($thematic_data)) {
		// set default values
		$default['title'] = $thematic_data['title'];
		$default['content'] = $thematic_data['content'];	
		$form->setDefaults($default);
	}
	
	$form->display();
		
} 

?>