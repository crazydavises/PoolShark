<?php
/*
Plugin Name: Pool Shark Registration
Plugin URI: http://lifestrokes.com
Description: Student Registration for LifeStrokes Swim School
Version: 0.1
Author: Amy Davis
Author URI: http://growingliketrees.blogspot.com
*/

// http://codex.wordpress.org/Function_Reference/

$Schedule = new PoolSharkReg;

class PoolSharkReg
{
	var $PoolSharkReg;
	var $CurrentSession = 0;
	var $SkillLevels;
	var $ContactOptions;
	var $SkillLevelInit = 0;
	var $ContactOptionsInit = 0;
	
	function __construct()
	{
		add_action( 'init', array( $this, 'InitDB' ) ); 		
		add_action( 'wp_head', array($this, 'addHeaderCode') );
		add_action( 'lost_password', array($this, 'LostPW') );
		add_action( 'retrieve_password', array($this, 'RetPW') );
		add_filter('lostpassword_redirect', array($this, 'resetPW'));
		add_shortcode( 'RegisterNew', array( $this, 'RegisterNew' ) );
		add_shortcode( 'SetPrefTimeslots', array($this, 'SetPrefTimeslots') );
		add_shortcode( 'LoginOptionsForm', array( $this, 'LoginOptionsForm' ) );
		add_shortcode( 'LoginForm', array( $this, 'LoginForm') );
		add_shortcode( 'MyAccount', array( $this, 'MyAccount') );
		add_shortcode( 'FamilyInfo', array( $this, 'FamilyInfo') );
		add_shortcode( 'MyAvailability', array( $this, 'MyAvailability') );
		add_shortcode( 'NewRegistrationStep1', array( $this, 'NewRegistrationForm_Step1') );
	}

	function InitDB()
	{
		$this->PoolSharkReg = new wpdb('poolsharkreg', 'swimshark', 'poolshark', 'mysql.lifestrokes.com');
	    $this->PoolSharkReg->show_errors();
	    $this->CurrentSession = $this->PoolSharkReg->get_var("SELECT id FROM session WHERE start_date < CURDATE() AND CURDATE() < end_date");
		if ($this->CurrentSession == 0)
			$this->CurrentSession = 20;
		add_filter('query_vars', 'parameter_queryvars' );  

	}

	function addheaderCode()
	{
        echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/poolsharkreg/poolshark_tables.css" />' . "\n";
	}

	function LostPW()
	{
	   echo 'ya lost it, eh?';
	}
	
	function resetPW()
	{
      echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/poolsharkreg/poolshark_tables.css" />' . "\n";
	   echo '<?php get_header() ?>';
		
	}
	function RetPW()
	{
		echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/poolsharkreg/poolshark_tables.css" />' . "\n";
		echo 'retrieve it';
	}
	
	
	function GetContactMethodOptions ( $current_method )
	{
	if ($this->ContactOptionsInit == 0)
	{
	$this->ContactOptions = $this->PoolSharkReg->get_results("SELECT contact_method.name AS method,
													 contact_method.id AS id
													 FROM contact_method");
	$this->ContactOptionsInit = 1;
	}
	$methods = $this->ContactOptions;
	$output = '<option';
	if ($current_method == 0)
		$output .= ' selected';
	$output .= '>Choose Method</option>';
	if ($methods)
	{
		foreach($methods as $m)
		{		
			$output .= '<option value=' . $m->id;
			if ($current_method == $m->id)
				$output .= ' selected';
			$output .= '>' . $m->method . '</option>';
		}
	}
	return $output;
	}
		
function enroll_session()
{
		$enroll = $this->PoolSharkReg->get_var( "SELECT MIN(id)
													FROM session
													WHERE 
													DATEDIFF(end_date, CURDATE()) > 20");
		return $enroll;
}
		
function current_session()
{
	$session = $this->PoolSharkReg->get_var( "SELECT MAX(id) FROM session
											WHERE DATEDIFF(CURDATE(), start_date) >= 0");
	return $session;
}


function GetSkillLevelOptions ( $current_skill )
{
	if ($this->SkillLevelInit == 0)
	{
	$this->SkillLevels = $this->PoolSharkReg->get_results("SELECT skill.name AS skill,
													 skill.level AS id
													 FROM skill");
	$this->SkillLevelInit = 1;
	}
	$skills = $this->SkillLevels;
	
	$output = '<option';
	if ($current_skill == 0)
		$output .= ' selected';
	$output .= '>Skill Level</option>';
	if ($skills)
	{
		foreach($skills as $s)
		{		
			$output .= '<option value=' . $s->id;
			if ($current_skill == $s->id)
				$output .= ' selected';
			$output .= '>' . $s->skill . '</option>';
		}
	}
return $output;
}


function SavePrefTimeslots( $guardian_id)
{
	$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday');
	$class_slots = time_range('8:00am', '9:00pm', '30 mins');

	foreach($days as $day)
	{
		foreach ($class_slots as $slot)
		{
			$find_time = date('H:i:s', $slot);
			$ez_time = date('g:ia', $slot); 
			$varname = $day . $find_time;
			$slot = $this->PoolSharkReg->get_var("SELECT time_slot.id AS id 
												FROM time_slot, guardian_pref_timeslot
												WHERE guardian_pref_timeslot.guardian_id = '$guardian_id'
												AND guardian_pref_timeslot.timeslot_id = time_slot.id
												AND time_slot.weekday = '$day'
												AND time_slot.start_time = CAST('$find_time' AS time)");
																							

			if (!$slot && isset($_POST[$varname]) AND $_POST[$varname] == "yes")
			{
				$timeslot_id = 0;
				$timeslot_id = $this->PoolSharkReg->get_var("SELECT MAX(id) FROM time_slot
															WHERE weekday = '$day'
															AND start_time = '$find_time'");
				
				if ($timeslot_id == 0)
				{	$this->PoolShark->insert( 'time_slot', array ('weekday' => $day,
															  'start_time' => $find_time),
													   array ( '%s', '%s' ));
					$timeslot_id = $this->PoolSharkReg->get_var("SELECT MAX(id) FROM time_slot
															WHERE weekday = '$day'
															AND start_time = '$find_time'");
				}										
				
				$this->PoolSharkReg->insert( 'guardian_pref_timeslot', array('guardian_id' => $guardian_id,
																  'timeslot_id' => $timeslot_id),
																  array('%d', '%d'));
			}
			else if ($slot > 0 && (!isset($_POST[$varname]) || $_POST[$varname] == "no"))
			{
				// remove from guardian_pref_timeslot
				$this->PoolSharkReg->query("DELETE FROM guardian_pref_timeslot 
										WHERE guardian_id = $guardian_id 
										AND timeslot_id = $slot");			
			}
		}
	}	
}
	
	
	
function GetPrefTimeslots( $guardian_id )
{

$output = '';	
$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday');
$hour_slots = time_range( '8:00am', '9:00pm', '60 mins');
$class_slots = time_range('8:00am', '9:00pm', '30 mins');

$output .= '<table class="manage">';
foreach($days as $day)
{
	$output .= '<tr><th colspan=27>' . $day . '</th></tr>';
	foreach ($hour_slots as $slot)
	{
		$output .='<td>' . date('g',$slot) . '</td><td></td>';
	}
	$output .= '</tr><tr>';
	
	foreach ($class_slots as $slot)
	{			
		$find_time = date('H:i:s', $slot);
		$class = NULL;
		$class = $this->PoolSharkReg->get_row("SELECT time_slot.id AS id 
												FROM time_slot, guardian_pref_timeslot
												WHERE guardian_pref_timeslot.guardian_id = '$guardian_id'
												AND guardian_pref_timeslot.timeslot_id = time_slot.id
												AND time_slot.weekday = '$day'
												AND time_slot.start_time = CAST('$find_time' AS time)");
			
		$output .='<td>';
	
		$varname = $day . $find_time;
//		$output .= $varname;
		$output .= '<input type="checkbox" name="' . $varname . '" ';
		if ($class)
		     $output .= ' checked ';
		$output .= ' value="yes" >';
		$output .= '</td>';
	}
	$output .= '</tr>';
}
$output .='</table>';
	
return $output;
}	
	
function MyAvailability()
{
	$output = '';
	$guardian_id = get_contact();
	$enroll = $this->enroll_session();
	$current = $this->current_session();
	$sessions = $this->PoolSharkReg->get_results( "SELECT name, 
													DATE_FORMAT(start_date, '%M %e, %Y') AS start_date,
													DATE_FORMAT(end_date, '%M %e, %Y') AS end_date
													FROM session
													WHERE 
													id = $enroll");
	$kids = $this->PoolSharkReg->get_results("SELECT first_name, last_name, id AS stid
												FROM student
												WHERE guardian_id = '$guardian_id'");
												
	if (!isset($_POST['update_availability']))
	{
	
		if ($sessions)
		{
			$output .= '<strong>Now registering for: <br />';
			foreach( $sessions as $s)
			{
				$output .= $s->name . ', from ' . $s->start_date . ' to ' . $s->end_date . '.<br />';
			}
			$output .= '</strong><br />';
		}	
		$output .= '<form method="post" action="">';
		$output .= '<input type="hidden" name="guardian_id" value="' . $guardian_id . '">';
		$output .= '<input type="hidden" name="session_id" value="'. $enroll . '">';
		if ($kids)
		{
			$output .= 'Check which swimmers to register. <br />';
			$output .= '<table>';
			foreach( $kids as $k)
			{
				$output .= '<tr><td>';
				$cur_class = $this->PoolSharkReg->get_row( "SELECT class_id, class_student.id AS cs_id 
									FROM class_student, class 
									WHERE class.session_id = '$current'
									AND class_student.class_id = class.id
									AND student_id = '$k->stid'");
				$already_reg = $this->PoolSharkReg->get_row( "SELECT class_id, class_student.id AS cs_id
									FROM class_student, class 
									WHERE class.session_id = '$enroll'
									AND class_student.class_id = class.id
									AND student_id = '$k->stid'");
									
									
				$varname = 'reg' . $k->stid;
				$output .= '<input type="checkbox" name="' . $varname . '" value="yes">';
				$output .= $k->first_name . ' ' . $k->last_name . '</td>';
				$output .= '<td>';
				if ($already_reg)
				{
					$classtime = $this->PoolSharkReg->get_row("SELECT 
													 time_slot.short_day AS class_day, 
													 TIME_FORMAT(time_slot.start_time, '%l:%i') AS ez_start_time 
													 FROM class, time_slot, class_timeslot
													 WHERE session_id = '$session_id'
													 AND class_timeslot.class_id = class.id
													 AND class_timeslot.timeslot_id = time_slot.id
													 AND class.id = '$already_reg->class_id'");

					$output .= 'already registered at ' .$classtime->ez_start_time . ' on ' . $classtime->class_day;

				}
				else if ($cur_class)
				{
					$classtime = $this->PoolSharkReg->get_row("SELECT 
													 time_slot.short_day AS class_day, 
													 TIME_FORMAT(time_slot.start_time, '%l:%i') AS ez_start_time 
													 FROM class, time_slot, class_timeslot
													 WHERE session_id = '$session_id'
													 AND class_timeslot.class_id = class.id
													 AND class_timeslot.timeslot_id = time_slot.id
													 AND class.id = '$cur_class->class_id'");
					
					$varname='keep'.$k->stid;
					$output .= '<input type="checkbox" name="' . $varname . '" value="yes">';
					$output .= 'Prefer to keep current class time (' . $classtime->ez_start_time . ' on ' . $classtime->class_day . ')';
						
				}
				$output .= '</td></tr>';
				
			}
			$output .= '</table>';
		}
		
		$output .= 'Please update your availability for next session.<br />';
		$output .= $this->GetPrefTimeslots($guardian_id);					
		$output .= '<input type="submit" name="update_availability" value="Update" />';
		$output .= '</form><br />';
		return $output;
	}
	else
	{
		$this->SavePrefTimeslots($_POST['guardian_id']);
		if ($sessions)
		{
			$output .= 'Current availability for: <br />';
			foreach( $sessions as $s)
			{
				$output .= $s->name . ', from ' . $s->start_date . ' to ' . $s->end_date . '.<br />';
			}
		}	
	
		$output .= '<form method="post" action="">';
		$output .= '<input type="hidden" name="guardian_id" value="' . $guardian_id . '">';
		$output .= '<input type="hidden" name="session_id" value="'. $enroll . '">';
		$output .= $this->GetPrefTimeslots($guardian_id);					
		$output .= '<input type="submit" name="update_availability" value="Change" />';
		$output .= '</form><br />';
		return $output;
		
	}
}


function GetMyFamilyInfo( $parent_id)
{
	$output = '';
	$enroll = $this->enroll_session();
	$current = $this->current_session();
	
	$SessionName = $this->PoolSharkReg->get_var("SELECT name FROM session WHERE id='$enroll'");
	
	$contact_info = $this->PoolSharkReg->get_row("SELECT guardian.id AS parent_id, 
												 guardian.first_name AS guardian_fname, 
												 guardian.last_name AS guardian_lname, 
												 provisional, registration,
												 home_phone, cell_phone, email, contact_method.id AS pref_contact 
												 FROM guardian, contact_method
												 WHERE guardian.id = '$parent_id'
												 AND contact_method.id = guardian.pref_contact");
	$num_students = $this->PoolSharkReg->get_var("SELECT COUNT(*) FROM student WHERE guardian_id = '$parent_id'");

	$student_info = $this->PoolSharkReg->get_results("SELECT student.id, student.first_name AS kid_fname,
												student.last_name AS kid_lname,
												student.skill_id AS skill,
												student.active,
												DATE_FORMAT(birthdate, '%Y') AS birthyear,  
												DATE_FORMAT(birthdate, '%m') AS birthmonth, 
												DATE_FORMAT(birthdate, '%d') AS birthday
												FROM student
												WHERE student.guardian_id = '$parent_id'");

	$balance = $this->PoolSharkReg->get_var("SELECT SUM(payment_due)
											FROM class_student, student, class
											WHERE student.guardian_id = '$parent_id'
											AND class_student.student_id = student.id
											AND class_student.class_id = class.id
											AND class.session_id = '$enroll'");
	$overdue = $this->PoolSharkReg->get_var("SELECT SUM(payment_due)
											FROM class_student, student, class
											WHERE student.guardian_id = '$parent_id'
											AND class_student.student_id = student.id
											AND class_student.class_id = class.id
											AND class.session_id = '$current'");
											
	$total_bal = $overdue;
	if ($enroll != $current)
		$total_bal += $balance;
	
	
	//$output .= 'Currently Enrolling for '  . $SessionName . '<br />';	
	//$output .='(current is ' . $current . ')<br />';				
	if ($contact_info)
	{
		$output .= '<table>';
		$output .= '<tr><td><strong>Contact First Name</strong></td>';
		$output .= '<td><input maxlength="48" name="new_firstname" size="20" type="text"';
		$output .= ' value="' . $contact_info->guardian_fname . '" /></td></tr>';
		$output .= '<tr><td><strong>Contact Last Name</strong></td>';
		$output .= '<td><input maxlength="48" name="new_lastname" size="20" type="text"';
		$output .= ' value="' . $contact_info->guardian_lname . '" /></td></tr>';
		$output .= '<tr><td><strong>Balance';
		if ($overdue > 0)
			$output .= ' (Overdue)';
		$output .= '</strong></td>';
		$output .= '<td><input readonly maxlength="48" name="new_balance" size="20" type="text"';
		$output .= ' value="' . $total_bal . '" /></td></tr>';

		$output .= '<tr><td><strong>Phone</strong></td>';
		$output .= '<td><input maxlength="48" name="new_homephone" size="20" type="text"';
		$output .= ' value="' . $contact_info->home_phone . '" /></td></tr>';
		$output .= '<tr><td><strong>Alternate Phone</strong></td>';
		$output .= '<td><input maxlength="48" name="new_cellphone" size="20" type="text"';
		$output .= ' value="' . $contact_info->cell_phone . '" /></td></tr>';
		$output .= '<tr><td><strong>Email</strong></td>';
		$output .= '<td><input maxlength="48" name="new_email" size="20" type="text"';
		$output .= ' value="' . $contact_info->email . '" /></td></tr>';
		$output .= '<tr><td><strong>Preferred Contact Method</strong></td>';
		$output .= '<td><select name=new_contact>'. $this->GetContactMethodOptions($contact_info->pref_contact) . '</select></td></tr>';
		$output .= '</table>';
	}
	if ($num_students > 0)
	{
	$output .= $num_students . ' students. <br />';
	$class_info = NULL;

	if ($student_info)
	{
		$i = 0;
		foreach($student_info as $child)
		{
			$tag = 'c' . $i;
			$i++;
			$output .= '<input type="hidden" name="' . $tag  .'_id" value="' . $child->id . '">';
			$output .= '<table>';
			$output .= '<tr><td>Student ' . $child->id . ' First Name:</td>';
			$output .= '<td><input maxlength="48" name="' . $tag . '_firstname" size="20" type="text"';
			$output .= ' value="' .  $child->kid_fname . '" /></td></tr>';
			$output .= '<tr><td>Student Last Name:</td>';
			$output .= '<td><input maxlength="48" name="' . $tag . '_lastname" size="30" type="text"';
			$output .= ' value="'. $child->kid_lname . '"  /></td></tr>';
			$output .= '<tr><td>Student Birthdate:</td>';
			$output .= '<td>' . birthdate_combo($tag, $child->birthmonth, $child->birthday, $child->birthyear) . '</td></tr>'; 
			$levels = $this->GetSkillLevelOptions($child->skill);
			$output .= '<tr><td>Current Skill Level:</td><td><select name="' . $tag . '_level">' . $levels . '</select></td></tr>';
			$output .= '<tr><td><input type="Checkbox" name="' . $tag . '_active" value="1"';
			if ($child->active == 1)
				$output .= ' checked';
			$output .='>Currently taking lessons</td><td></td></tr>';

			$output .= '<tr><td>History</td><td></td></tr>';
			$class_info = $this->PoolSharkReg->get_results("SELECT skill.name AS skill, 
					session.name AS session, session.id AS session_id,
					class.id AS class_id
					FROM class_student, class, skill, session
					WHERE class_student.student_id = '$child->id'
					AND class_student.class_id = class.id
					AND class.skill_id = skill.level
					AND class.session_id = session.id");
				
			$already_enrolled = 0;	
			foreach($class_info as $cl)
			{
				if ($cl->session_id == $enroll)
				{
					$already_enrolled = $cl->class_id;
					continue;
				}
				$output .= '<tr><td>' . $cl->session . '</td><td>' . $cl->skill . '</td></tr>';
			}
				
//				$openclasses = $this->GetAllOpenClassesOption($enroll, $already_enrolled, $child->skill);
//				$output .= '<tr><td>Enroll in ' . $SessionName . '</td><td> ';
//				$output .= '<select name="' . $tag . '_class">';	
//			    $output .= $openclasses;
//				$output .= '</select><br />';			
//				$output .= '</td></tr>';
			
			$output .= '</table>';
		}
	} 	
	} // end if num_students > 0
	$output .= ' <input type = "radio" name="add_child" value="add_child"> Add a Child <br /><br />';
	
	$output .= '<input type="submit" name="update_family" value="Update My Information" /><br /><br />';
return $output;		
}


function FamilyInfo()
{
$output = '';
$enroll = $this->enroll_session();
$current = $this->current_session();
$num_students = 0;
$parent_id = get_contact();

$output .= 'got a contact' . $parent_id . '<br />';

if ($parent_id != 0 && !isset($_POST['update_family']))
{
	$output .= 'parent_id = '. $parent_id . '<br />';
	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="update_contactid" value="' . $parent_id . '"><br />';
	$output .= $this->GetMyFamilyInfo( $parent_id );
	$output .= '</form>';
	return $output;
}
else if (isset($_POST['update_family']) AND $_POST['update_family'] == "Update My Information")
{
												
	$parent_info = $this->PoolSharkReg->get_row("SELECT guardian.id AS parent_id, 
												 guardian.first_name AS guardian_fname, 
												 guardian.last_name AS guardian_lname,
												 registration, 
												 home_phone, cell_phone, work_phone, email, contact_method.id AS pref_contact 
												 FROM guardian, contact_method
												 WHERE contact_method.id = guardian.pref_contact
												 AND guardian.id = '$_POST[update_contactid]'");
	if ($parent_info)
	{
	// find out if new data is different from old.	
	if (($_POST['new_firstname'] != $parent_info->guardian_fname) ||
		($_POST['new_lastname']  != $parent_info->guardian_lname) ||
		($_POST['new_registration']  != $parent_info->registration) ||		
		($_POST['new_homephone']  != $parent_info->home_phone) ||
		($_POST['new_cellphone']  != $parent_info->cell_phone) ||
		($_POST['new_email']  != $parent_info->email) ||
		($_POST['new_contact']  != $parent_info->pref_contact))
		{
			$this->PoolSharkReg->update('guardian', array( 'first_name' => $_POST['new_firstname'], 
													'last_name' => $_POST['new_lastname'],
													'registration' => $_POST['new_registration'],
													'home_phone' => $_POST['new_homephone'],
													'cell_phone' => $_POST['new_cellphone'], 
													'email' => $_POST['new_email'], 
													'pref_contact' => $_POST['new_contact']), 
											array( 'id' => $parent_info->parent_id ), 
											array( '%s', '%s', '%d', '%d', '%s',  '%s', '%s', '%s', '%d'), 
											array( '%d' ));
			$output .= 'Updated Contact Info for  ' . $_POST['new_firstname'] . ' ' . $_POST['new_lastname']; 
		}
	
		$num_students = $this->PoolSharkReg->get_var("SELECT COUNT(*) FROM student WHERE guardian_id = '$_POST[update_contactid]'");	
	}

	if ($num_students > 0)
	{
		$student_info = $this->PoolSharkReg->get_results("SELECT student.id, student.first_name AS kid_fname,
												student.last_name AS kid_lname,
												student.skill_id AS skill,
												DATE_FORMAT(birthdate, '%Y') AS birthyear,  
												DATE_FORMAT(birthdate, '%m') AS birthmonth, 
												DATE_FORMAT(birthdate, '%d') AS birthday
												FROM student
												WHERE student.guardian_id = '$parent_id'");
	if ($student_info)
	{
		$i = 0;
		foreach($student_info as $child)
		{
			$tag = 'c' . $i;
			$i++;

			// find out if new data is different from old.	
			$firstname = $tag . '_firstname';
			$lastname = $tag . '_lastname';
			$birthyear = $tag . 'DateOfBirth_Year';
			$birthmonth = $tag . 'DateOfBirth_Month';
			$birthday= $tag . 'DateOfBirth_Day';
			$skill = $tag . '_level';
			$active = $tag . '_active';
			$class = $tag . '_class';
			
			
			if (!isset($_POST[$firstname]))
			{
				$output .= 'could not find ' . $firstname . '<br />';
				continue;
			}			
	
			if (($_POST[$firstname] != $child->kid_fname) ||
			($_POST[$lastname]  != $child->kid_lname) ||
			($_POST[$birthyear] != $child->birthyear) ||
			($_POST[$birthmonth] != $child->birthmonth) ||
			($_POST[$birthday] != $child->birthday) || 
			($_POST[$skill] != $child->skill) ||
			($_POST[$active] != $child->active))
			{
				$new_birthdate =  $_POST[$birthyear] . '-'; 
				$new_birthdate .=	$_POST[$birthmonth] . '-';
				$new_birthdate .= $_POST[$birthday];
 
				$this->PoolSharkReg->update('student', array( 'first_name' => $_POST[$firstname], 
													'last_name' => $_POST[$lastname],
													 'birthdate' => $new_birthdate,
													 'skill_id' => $_POST[$skill],
													  'active' => $_POST[$active]), 
													array( 'id' => $child->id ), 
													array( '%s', '%s', '%s', '%d', '%d' ), 
													array( '%d' ));
									
		//		$output .= 'Updated Student ' . $_POST[$firstname] . ' ' . $_POST[$lastname] . '.<br />'; 
			}

			$class_id = $_POST[ $class ];
			$this->PoolSharkReg->update('guardian', array('registration' => 1),
											     array('id' =>$parent_id),
												 array('%d'),
												 array('%d'));// don't re-enroll student in class
			//$already_enrolled = 0;
			$class_price = $this->PoolSharkReg->get_var("SELECT price FROM class WHERE id = '$class_id'");
			$already_enrolled = $this->PoolSharkReg->get_row( "SELECT class_id, class_student.id AS cs_id, price 
				FROM class_student, class 
				WHERE class.session_id = '$enroll'
				AND class_student.class_id = class.id
				AND student_id = '$child->id'");

			if (!$already_enrolled || !isset($already_enrolled))
			{	
				$this->PoolSharkReg->insert( 'class_student', array( 'student_id' => $child->id,
                                                           'class_id'   => $class_id, 
                                                           'payment_due' => $class_price),
                                                           array( '%s', '%s', '%d') );
                
		//	$output .= 'Added student to class. <br />';
			}
			if (isset($already_enrolled)  && $already_enrolled->class_id != $class_id)
			{
				$output .= 'Moved student to new class. <br />';
				$this->PoolSharkReg->update('class_student', array('class_id'=> $class_id, 
																'payment_due'=>$class_price),
													array('id' => $already_enrolled->cs_id),
													array('%d', '%d'),
													array('%d'));
			}
            $cur_level = $this->PoolSharkReg->get_var("SELECT skill_id FROM class WHERE class.id = '$class_id'");
			if ($cur_level == 0)
				$this->PoolSharkReg->update('class', array( 'skill_id' => $_POST[$skill]), 
										array( 'id' => $class_id ), 
											array( '%d' ), 
											array( '%d' ));
											
 		} // for each child
	}	// if student_info	
	
	if ($_POST['add_child'] == "add_child")
	{
		$this->PoolSharkReg->insert('student', 
						array(  'guardian_id' => $parent_id,
								'active' => "1"),
						array( '%d', '%d'));
	}
	}	// if num_students > 0
} // if update information
	
$output .= '<form method="post" action="">';
$output .= '<input type="hidden" name="update_contactid" value="' . $parent_id . '"><br />';
$output .= $this->GetMyFamilyInfo($parent_id);
$output .='</form>';
	
return $output;	
}




function create_password_form( $email )
{
	$output = '';
	
	$output .= '<form name="" method="POST" action="http://lifestrokes.com/test_site/lifestrokes-login2.php?action=register">			
				<input type="hidden" name="redirect_to" value="http://www.lifestrokes.com/test_site/registration-successful/" />
			<p class="login-username">
				<label for="user_login">Email:</label>
				<input type="text" name="user_email" class="input" value="';
	if ($email != "")
		$output .= $email . '" readonly="readonly';	
	$output .= '" size="20" tabindex="10"/>
			</p>
			<p class="login-password">
				<label for="user_pass">Choose Password:</label>
				<input type="password" name="pass1" class="input" value="" size="20" tabindex="20" />
			</p>
			<p class="login-password">
				<label for="user_pass">Type it Again:</label>
				<input type="password" name="pass2" class="input" value="" size="20" tabindex="20" />
			</p>
			
			<p class="login-remember"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> Remember Me</label></p>

			<p class="login-submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="Set Password" tabindex="100" />
			</p>
			
		</form>';
		
	return $output;
}

function check_user_exists( $email )
{
	$user_id = 0;
	$user_id = $this->PoolSharkReg->get_var("SELECT id FROM guardian WHERE email = '$email'");
	if (!isset($user_id) || $user_id == 0)
		return 0;
	else
		return $user_id;
}

function check_user_exists_wordpress( $email )
{
	global $wpdb;
	$user_id = 0;
	$user_id = $this->PoolSharkReg->get_var("SELECT ID FROM wp_zwfpq6_users WHERE user_email = '$email'");
	if (!isset($user_id) || $user_id == 0)
		return 0;
	else
		return $user_id;
}

function check_username_exists( $firstname, $lastname)
{
	$user_id = 0;
	$user_id = $this->PoolSharkReg->get_var("SELECT id FROM guardian WHERE first_name= '$firstname' AND last_name='$lastname'");
	if (!isset($user_id) || $user_id == 0)
		return 0;
	else
		return $user_id;
}

function LoginForm()
{
	$output = '';

	global $wpdb;
	if (is_user_logged_in())
	{
		$current_user = wp_get_current_user();
		$user_info = $this->PoolSharkReg->get_row("SELECT id, first_name, last_name FROM guardian WHERE email='$current_user->user_email'");
		if ($user_info)
		{
			$output .= 'Welcome <strong>' . ' ' .$user_info->first_name . ' ' . $user_info->last_name . '</strong>. <br />';
			//TODO make this use the parent id in the url
			// write "MyAccount" page that checks if logged in, if current user is one requested and displays account if so
			$output .= '<a href=./familyinfo/?contact_id=' . $user_info->id . '>My Family Information</a><br /><br />';
			$output .= '<a href=./availability/?contact_id=' . $user_info->id . '>Availability/Registration</a><br /><br />';
			//if ($session_name)
//			{	
//				//TODO write registration page, duh.  Have it accept url parent id too.
//				$output .= '<a href=http://lifestrokes.com/test_site/register?contact_id=' . $user_info->id . '>Register for' . 
//				$output .= $s->name . '.</a><br />';
//			}	

		}
		else
			$output .= 'Could not find this email address in the LifeStrokes database.<br />';

		$output .= '<br /><a href="' . wp_logout_url( home_url() ) . '" title="Logout">Logout</a>';
	
		return $output;
			
	}	
	else
	{ 
		$have_pw_args = array(
        'echo' => false,
        'redirect_to' => 'http://lifestrokes.com/test_site/login', 
        'form_id' => 'loginform',
        'label_username' => __( 'Email' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'label_log_in' => __( 'Log In' ),
        'id_username' => 'user_login',
        'id_password' => 'user_pass',
        'id_remember' => 'rememberme',
        'id_submit' => 'wp-submit',
        'remember' => true,
        'value_username' => NULL,
        'value_remember' => false );
		$output .= wp_login_form( $have_pw_args );
		$output .= '<a href="' . wp_lostpassword_url() . '" title="Lost Password">Lost Password</a>';
	}
	return $output;
}

function email_admin( $user_fname, $user_lname, $kid1, $kid2, $email)
{
    $email_to = "swim.lifestrokes@gmail.com";
    $email_subject = "LifeStrokes Website Registration Problems";
	$headers = 'From: LifeStrokesRegistration@lifestrokes.com'."\r\n". 
				'Reply-To: no-reply@lifestrokes.com' . "\r\n" . 
				'X-Mailer: PHP/' . phpversion();
	$email_text = 'Here is a problem case, and everything I know about it.';
	$email_text .= "\r\n\r\n";
	$email_text .= "Name: " . $user_fname . " " . $user_lname . "\r\n";
	$email_text .= "Children: " . $kid1 . " and " . $kid2 . "\r\n";
	$email_text .= "Email: " . $email . "\r\n\r\n"; 
	$email_text .= "Email them when you get their account found and set up\r\n";
	@mail($email_to, $email_subject, $email_text, $headers);  	
	
}

function get_new_userid()
{
	$tempname = 'tmp' . time();
	$this->PoolSharkReg->insert( 'guardian', array( 'first_name'  => $tempname,
                                               'last_name'  => $tempname,
                                               'provisional' => 1),
                                               array( '%s', '%s', '%d' ) );
    $temp_id = $this->PoolSharkReg->get_var("SELECT id FROM guardian WHERE first_name='$tempname' AND last_name='$tempname'");
	return $temp_id;
}
function add_child( $num_kids )
{
	$output = '';
	for ($i = 0; $i < $num_kids; $i++)
	{
		$tag = 'c' . $i;
		
		$output .= '<table>';
		$output .= '<tr><td>Student First Name:</td>';
		$output .= '<td><input maxlength="48" name="' . $tag . 'firstname" size="20" type="text" /></td></tr>';
		$output .= '<tr><td>Student Last Name:</td>';
		$output .= '<td><input maxlength="48" name="' . $tag . 'lastname" size="30" type="text" /></td></tr>';
		$output .= '<tr><td>Student Birthdate:</td>';
		$output .= '<td>' . birthdate_combo($tag, 0,0,0) . '</td></tr>'; 
		$output .= '<input type="hidden" name="' . $tag . '_skill" value="0">';
		$output .=  '</table>';
	}
	
	return $output;	
}

function NewRegistrationForm_Step1()
{
	$num_kids = 1;
	if (isset($_POST["num_kids"]) )
		$num_kids = $_POST["num_kids"];
	
	$guardian_id = 0;
	if (isset($_POST["guardian_id"]) )
		$guardian_id = $_POST["guardian_id"];
	else
		$guardian_id = $this->get_new_userid();

	if (!isset($_POST["AddAnother"]) && !isset($_POST["GoToStep2"]))
	{
		$output = 'Step 1: Add each swimmer, then go to Step 2.<br />';
		$output .= '<form method="post" action="">';
		$output .= '<input type="hidden" name="num_kids" value=' . $num_kids . '>';
		$output .= '<input type="hidden" name="guardian_id" value=' . $guardian_id . '>';
		$output .= $this->add_child($num_kids);
		$output .= '<input type="submit" name="AddAnother" value="Add Another Swimmer" >';
		$output .= '<input type="submit" name="GoToStep2" value="Done, Go to Step 2" >';
		$output .= '</form>';
		return $output;
	}
	
	if (isset($_POST["AddAnother"]) )
	{
		$num_kids += 1;
		$output = 'Step 1: Add each swimmer, then go to Step 2.<br />';
		$output .= '<form method="post" action="">';
		$output .= '<input type="hidden" name="num_kids" value=' . $num_kids . '>';
		$output .= '<input type="hidden" name="guardian_id" value=' . $guardian_id . '>';
		for ($i = 0; $i < $num_kids; $i++)
		{
			
			$tag = 'c' . $i;
			$firstvar = $tag . 'firstname';
			$lastvar = $tag . 'lastname';
			$yearvar = $tag . 'DateOfBirth_Year';
			$monthvar = $tag . 'DateOfBirth_Month';
			$dayvar = $tag . 'DateOfBirth_Day';

			$cur_firstname = '';
			$cur_lastname = '';
			$cur_yearvar = 0;
			$cur_monthvar = 0;
			$cur_dayvar = 0;
			
			if ($i != $num_kids - 1)
			{
				$cur_firstname = $_POST[$firstvar];
				$cur_lastname = $_POST[$lastvar];
				$cur_yearvar = $_POST[$yearvar];
				$cur_monthvar = $_POST[$monthvar];
				$cur_dayvar = $_POST[$dayvar];
			}
				
			$output .= '<table>';
			$output .= '<tr><td>Student First Name:</td>';
			$output .= '<td><input maxlength="48" name="' . $tag . 'firstname" size="20" type="text" value="' . $cur_firstname . '" ></td></tr>';
			$output .= '<tr><td>Student Last Name:</td>';
			$output .= '<td><input maxlength="48" name="' . $tag . 'lastname" size="30" type="text" value="' . $cur_lastname . '" ></td></tr>';
			$output .= '<tr><td>Student Birthdate:</td>';
			$output .= '<td>' . birthdate_combo($tag, $cur_monthvar,$cur_dayvar,$cur_yearvar) . '</td></tr>'; 
			$output .= '<input type="hidden" name="' . $tag . '_skill" value="0">';
			$output .=  '</table>';
		}		
		$output .= '<input type="submit" name="AddAnother" value="Add Another Swimmer" >';
		$output .= '<input type="submit" name="GoToStep2" value="Done, Go to Step 2" >';
		$output .= '</form>';
		return $output;		
		
	}
	
	if (isset($_POST["GoToStep2"]))
	{
		$num_students = $_POST['num_kids'];
		$name_str = "";
		for ($i = 0; $i < $num_students; $i++)
		{
			$tag = 'c' . $i;
			$firstvar = $tag . 'firstname';
			$lastvar = $tag . 'lastname';
			$firstname = '';
			$lastname = '';
			$firstname = $_POST[$firstvar];
			$lastname = $_POST[$lastvar];
			$id = 0;
			$yearvar = $tag . 'DateOfBirth_Year';
			$monthvar = $tag . 'DateOfBirth_Month';
			$dayvar = $tag . 'DateOfBirth_Day';
				
			$birthdate =  $_POST[$yearvar] . '-'; 
			$birthdate .=	$_POST[$monthvar] . '-';
			$birthdate .=	$_POST[$dayvar];
				
			$this->PoolSharkReg->insert( 'student', array(  'first_name' =>$firstname,
										 'last_name'  =>$lastname,
                                         'guardian_id' =>$_POST['guardian_id'],
                                         'birthdate' =>$birthdate) ,  
                                  array( '%s', '%s', '%d', '%s') );
                              
            if ($i > 0)
            {
				$name_str .= ', ';
				if ($i == $num_students - 1)
					$name_str .= 'and ';
			}
			$name_str .= $firstname . ' '. $lastname;			
		}
		
		return $this->NewRegistrationForm_Step2($_POST["guardian_id"], $num_kids, $name_str);
	}
}

function NewRegistrationForm_Step2($guardian_id, $num_kids, $names)
{
	
	$output = 'Step 2: Select times that you are available for lessons,<br />';
	$output .='(choosing more available times is better, we will try to schedule your kids together).<br /><br />';
	$output .= 'Register ' . $num_kids . ' kids with guardian ' . $guardian_id . '<br />';
	$output .= $names . '<br />';
	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="guardian_id" value="' . $guardian_id . '">';
	$output .= $this->GetPrefTimeslots($guardian_id);					
	$output .= '<input type="submit" name="update_availability" value="Go to Step 3" />';
	$output .= '</form><br />';
	return $output;
}
function LoginOptionsForm()
{
	$output = '';

		$have_pw_args = array(
        'echo' => false,
        'redirect' => 'http://lifestrokes.com/test_site/login', 
        'form_id' => 'loginform',
        'label_username' => __( 'Email' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'label_log_in' => __( 'Go' ),
        'id_username' => 'user_login',
        'id_password' => 'user_pass',
        'id_remember' => 'rememberme',
        'id_submit' => 'wp-submit',
        'remember' => true,
        'value_username' => NULL,
        'value_remember' => false );

	global $wpdb;
//	$query = "SELECT user_login FROM wp_users";
	$session = $this->enroll_session();
	$session_name = $this->PoolSharkReg->get_var( "SELECT name
													FROM session
													WHERE 
													id = '$session'");

	if (is_user_logged_in())
	{
		$current_user = wp_get_current_user();
		$user_info = $this->PoolSharkReg->get_row("SELECT id, first_name, last_name FROM guardian WHERE email='$current_user->user_email'");
		if ($user_info)
		{
			$output .= 'Welcome <strong>' . ' ' .$user_info->first_name . ' ' . $user_info->last_name . '</strong>. <br />';
			//TODO make this use the parent id in the url
			// write "MyAccount" page that checks if logged in, if current user is one requested and displays account if so
			$output .= '<a href=./familyinfo/?contact_id=' . $user_info->id . '>My Family Information</a><br /><br />';
			$output .= '<a href=./availability/?contact_id=' . $user_info->id . '>Registration/Availability</a><br /><br />';
			if ($session_name)
			{	
				//TODO write registration page, duh.  Have it accept url parent id too.
				$output .= '<a href=http://lifestrokes.com/test_site/register?contact_id=' . $user_info->id . '>Register for';
				$output .= $session_name . '.</a><br />';
			}	
		}
		else
			$output .= 'Could not find this email address in the LifeStrokes database.<br />';

		return $output;
			
	}
/*
 * 		$guardian_id = $this->PoolSharkReg->get_var("SELECT id FROM guardian WHERE email = '$current_user->user_email'");
		$output .= 'User ' . $guardian_id . ' <br />';
		if ($guardian_id)
		{	
			$output .= $this->MyAccount($guardian_id);
		}
		else
		{
			$output .= 'Sorry, I could not find your account from this email address.  Perhaps you gave us a different one?  Please fill out the form below, and we will email you when we have your account linked up properly.<br />';	
		}
	}	
	*/
	else if (isset($_POST["Go"]))
	{
		$output = '';
		switch($_POST["status"])
		{
		case "need_pw":
			if ($this->check_user_exists(sanitize_user($_POST["email"])) != 0)
			{
				if ( email_exists($_POST["email"]) )
				{
					$output .= 'You already have this email registered, and have a password. Please log in.';
					$output .= wp_login_form( $have_pw_args );
					$output .= '<p class="login-username"><a href="' .  wp_lostpassword_url() . '" title="Lost Password">Lost Password</a></p>';
				} 
				else				
					$output .= $this->create_password_form($_POST["email"]);
			}
			else
				$output .= 'Could not find that email registered in LifeStrokes. Please choose \"I have a Lifestrokes student, but no account\".<br />';
			break;
		case "have_student":
			if ($this->check_user_exists(sanitize_user($_POST["email"])) != 0)
			{
				if ( email_exists($_POST["email"]) )
				{
					$output .= 'You already have this email registered, and have a password. Please log in.';
					$output .= wp_login_form( $have_pw_args );
					$output .= '<p class="login-username"><a href="' .  wp_lostpassword_url() . '" title="Lost Password">Lost Password</a></p>';
				} 
				else
				{
					$output .= 'You already have this email registered, and just need to create a password. <br />';
					$output .= $this->create_password_form($_POST["email"]);
				}
			}
			else 
			{
				$user_id = $this->check_username_exists( $_POST["contact_fname"], $_POST["contact_lname"]);
				if ($user_id != 0)
				{
				$output .= '<form method="POST" action="">';
				$output .= '<input type="hidden" name="user_id" value=' . $user_id . '>';
				$output .= '<input type="hidden" name="email" value='. $_POST["email"] . '>';
				$output .= 'I think we found you.  Please verify the first names of up to two of your kids taking lessons. If you have two or more children in lessons, fill out both names.<br />';
				$output .= '<p class="login-username">Child First Name:<input type="text" name="child1_fname">  </p>';
				$output .= '<p class="login-username">Child First Name:<input type="text" name="child2_fname">  </p>';
				$output .= '<p class="login-submit"><input type="submit" name="Child-Verify" value="Verify Children" /></p>';	
				$output .= '</form>';
				}
				else
				{
					$output .= 'I could not find a \"' . $_POST["contact_fname"] . ' ' . $_POST["contact_lname"] . ' in the database.  Would you like to ';
					$output .= '<br />  <form method="POST" action="">';
					$output .= '<input type="radio" name="create_account" value="have_student">Try again?<br />';
					$output .= '<input type="hidden" name="status" value="have_student">';
					$output .= '<p class="login-username">First Name:<input type="text" name="contact_fname"  Last Name: <input type="text" name="contact_lname"></p>';
					$output .= '<p class="login-username">Email:<input type="text" name="email"></p>';	
					$output .= '<p class="login-submit"><input type="submit" name="Go" value="Go" /></p>';	
					$output .= '</form><br />';
					$output .= '<form method="POST" action="">';
					$output .= '<input type="radio" name="create_account" value="send_email">Send email to Lifestrokes so that they\'ll look at it and correct your account?<br />';
					$output .= '<input type="hidden" name="status" value="send_email">';
					$output .= '<input type="hidden" name="contact_fname" value="' . $_POST["contact_fname"] . '">';
					$output .= '<input type="hidden" name="contact_lname" value="' . $_POST["contact_lname"] . '">';
					$output .= '<input type="hidden" name="email" value="' . $_POST["email"] . '">';
					$output .= '<p class="login-submit"><input type="submit" name="Go" value="Go" /></p>';
					$output .= '</form><br />';
				}
			}
			break;
		case "send_email":
			email_admin( $_POST["contact_fname"], $_POST["contact_lname"], "", "", $_POST["email"]);
			$output .= 'Thank you for you patience.  We will email you as soon as we have your email assigned to your LifeStrokes information.<br />';
			break;
		case "new_registration":
			$output .= $this->NewRegistrationForm_Step1();
			break;
		}
		return $output;
	}
	else if (isset($_POST["Child-Verify"]))
	{
		$user_id = $_POST["user_id"];
		$kid1_ok = true;
		$kid2_ok = true;
		
		if (isset($_POST["child1_fname"]) )
		{
			
			$kid1_id = $this->PoolSharkReg->get_var("SELECT id FROM student 
													WHERE guardian_id = '$user_id' 
													AND first_name LIKE '$_POST[child1_fname]'");
			if (!isset($kid1_id) || $kid1_id == 0)
			{
				$kid1_ok = false;
				$output .= 'First child\'s name could not be found in database.<br />';
			}
		}
		if (isset($_POST["child2_fname"]) )
		{
			if ($_POST["child2_fname"] == $_POST["child1_fname"])
			{
				$output .= 'Your children must have different names.<br />';
				$kid2_ok = false;
			}
			$kid2_id = $this->PoolSharkReg->get_var("SELECT id FROM student 
													WHERE guardian_id = '$user_id' 
													AND first_name LIKE '$_POST[child2_fname]'");
			if (!isset($kid2_id) || $kid2_id == 0)
			{
				$output .= 'Second child\'s name could not be found in database.<br />';
				$kid2_ok = false;
			}
		}
		$num_kids = $this->PoolSharkReg->get_var( "SELECT COUNT(id) FROM student
													WHERE guardian_id = '$user_id'");
		
		if ($num_kids >= 2 && (!isset($_POST["child1_fname"]) || !isset($_POST["child2_fname"])) )
		{
			$kid1_ok = false;
			$output .= 'You have more than one child in the database.  Please enter two names.<br />';
		}
		if ($num_kids == 1 && !isset($_POST["child1_fname"]) && !isset($_POST["child2_fname"]) )
		{
			$kid1_ok = false;
			$output .= 'Please enter a child\'s name <br />';
		}
		if ($num_kids == 0)
		{
			$output .= 'Could not find your children in database.< br/>';
			$kid2_ok = false;
		}
		if ($kid1_ok && $kid2_ok)
		{
			$this->PoolSharkReg->update('guardian', array( 'email' => $_POST["email"]), 
										array( 'id' => $user_id ), 
											array( '%s' ), 
											array( '%d' ));
			$output .= 'Thanks. We found you and have added this email address to your account.<br />';
			$output .= 'Please choose a password.';
			$output .= $this->create_password_form($_POST["email"]);
		}
		
		else
		{
			$output .= 'I\'m sorry, we could not verify your account yet.  We will look at your information as soon as possible and update your account, then email when you can create a password.  Thanks for your patience.<br />';		
		}
		return $output;
	}
	else
	{ 

		$need_pw_args = array(
        'echo' => false,
        'redirect' => 'http://lifestrokes.com/test_site/login', 
        'form_id' => 'loginform',
        'label_username' => __( 'Email' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'label_log_in' => __( 'Go' ),
        'id_username' => 'user_login',
        'id_password' => 'user_pass',
        'id_remember' => 'rememberme',
        'id_submit' => 'wp-submit',
        'remember' => true,
        'value_username' => NULL,
        'value_remember' => false );



		$output .= 'In an attempt to serve students and their families better, we would like to create
		a password on your LifeStrokes account.  This will eventually allow you to register for lessons
		online and pay online.<p><br /><br />';
		$output .= 'Please select one of the following options. <br />';
		
		$output .= '<form method="POST" action="http://lifestrokes.com/test_site/register-new-student/">';
		$output .= '<input type="hidden" name="status" value="new_registration">';
		$output .= '<input type="radio" name="create_account" value="new_registration">I would like to register a New Student for Lessons. <br />';
		$output .= '<table class="login"><tr><td><input type="submit" name="Go" value="Go" /></td></tr></table>';
		$output .= '</form>';

		$output .= '<form method="POST" action="">';
		$output .= '<input type="hidden" name="status" value="need_pw">';
		$output .='<input type="radio" name="create_account" value="need_pw">I have a LifeStrokes Account, but I need a password.<br />';			
		$output .= '<table class="login"><tr><td><p class="login-username">Email:<input type="text" name="email"></p>';
		$output .= '<p class="login-submit"><input type="submit" name="Go" value="Go" /></p></td></tr></table>';
		$output .= '</form><br />';
		
		$output .= '<input type="radio" name="create_account" value="have_pw">I have a LifeStrokes Account and a Password.<br />';
		$output .= '<table class="login"><tr><td>' .  wp_login_form( $have_pw_args ) ;
		$output .= '<p class="login-username"><a href="' . wp_lostpassword_url() . '" title="Lost Password">Lost Password</a></p></td></tr></table>';
	
		$output .= '<form method="POST" action="">';
		$output .= '<input type="hidden" name="status" value="have_student">';
		$output .= '<input type="radio" name="create_account" value="current_student">I have LifeStrokes Students, but no account, or do not know what email address is on my account. <br />';
		$output .= '<table class="login"><tr><td><p class="login-username">First Name:<input type="text" name="contact_fname">  Last Name: <input type="text" name="contact_lname"></p>';
		$output .= '<p class="login-username">Email:<input type="text" name="email"></p>';	
		$output .= '<p class="login-submit"><input type="submit" name="Go" value="Go" /></p></td></tr></table>';
		$output .= '</form><br />';
		
		return $output;
	}
	
}	
	

function create_login()
{
	if ($_POST[create_account] == "need_pw")
	{
		$output .= 'You need to choose a password. <br />';
	}
	else if ($_POST[create_account] == "have_pw")
	{		
		
	}
	else if ($_POST[create_account] == "current_student")
	{
		$output .= 'Please enter First and Last name of Primary Contact (usually a parent) for your students. <br />';
		$output .= 'Please enter an email address to use on this account <br />';
		$output .= 'Please choose a password.  <br />';
		$output .= 'You will receive an email when your account is activated. <br />';
	}
	else if ($_POST[create_account] == "new_registration")
		return RegisterNew();
	
	return $output;
		 
	
		/*$output .= '<form name="loginform" id="loginform" action="http://lifestrokes.com/test_site/wp-login.php" method="post">';

		$output .= '<label for="log">Email</label> <input type="text" name="log" id="user_login" class="input" value="" size="20" tabindex="10" /></p>';
		$output .= '<label for="pwd">Password</label> <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" tabindex="20" /></p>';
 
		$output .= '<input type="submit" name="wp-submit" id="wp-submit" value="LOG IN" />';
		$output .= '<input type="button" value="SIGNUP" onClick="parent.location=';
		$output .= bloginfo('url') . '/wp-login.php?action="register" />';
 
		$output .= '<a href="' . bloginfo('url');
		$output .= '/wp-login.php?action=lostpassword">Lost your password?</a>';
		

		$output .= '<input type="hidden" name="redirect_to" value="';
		$output .= bloginfo('url');
		$output .= '/members" />';
		$output .= '<input type="hidden" name="testcookie" value="1" />';
		$output .= '</form>'; */
} 
		



function RegisterNew()
{
    $email_to = "swim@lifestrokes.com";
    $email_subject = "New Registration from LifeStrokes Website";
	$headers = 'From: LifeStrokesRegistration@lifestrokes.com'."\r\n". 
				'Reply-To: no-reply@lifestrokes.com' . "\r\n" . 
				'X-Mailer: PHP/' . phpversion();	
	
	$parent_id = 0;	
	$output = '';
	if (isset($_POST['RegisterNew']) && $_POST['RegisterNew'] == "Next") // step 2
	{

		$output  = "";
		$this->PoolSharkReg->insert( 'guardian', array( 'first_name'  => $_POST['pfirstname'],
                                                'last_name'  => $_POST['plastname'],
                                               'home_phone' => $_POST['homephone'],
                                               'cell_phone' => $_POST['cellphone'],
                                               'work_phone' => $_POST['workphone'],
                                               'email'      => $_POST['email'],
                                               'pref_contact' => $_POST['contact_select'],
                                               'provisional' => 1),
                                               array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ) );
       	$parent_id = $this->PoolSharkReg->get_var("SELECT MAX(id) FROM guardian
                                                     WHERE first_name = '$_POST[pfirstname]'
                                                     AND last_name = '$_POST[plastname]'");
       	$output .= 'Thank you for registering  '.$_POST['pfirstname'].' '. $_POST['plastname'] . '<br />';
       	
       	
		$output .= '<strong>Step 2 (of 3) Enter Students</strong><br />';

		if (is_numeric($_POST['num_students']) && $_POST['num_students'] > 0 && $_POST['num_students'] < 15)
		{
			$output .= '<form method="post" action="">';
			$output .= '<input type="hidden" name="guardian_id" value="' . $parent_id . '">';
			$output .= '<input type="hidden" name="num_students" value="' . $_POST['num_students'] . '">';
			
			$levels = $this->GetSkillLevelOptions(0);			
			for ($i = 0; $i < $_POST['num_students']; $i++)
			{
				$tag = 'c' . $i;
				$output .= '<table>';
				$output .= '<tr><td>Student First Name:</td>';
				$output .= '<td><input maxlength="48" name="' . $tag . 'firstname" size="20" type="text" /></td></tr>';
				$output .= '<tr><td>Student Last Name:</td>';
				$output .= '<td><input maxlength="48" name="' . $tag . 'lastname" size="30" type="text" /></td></tr>';
				$output .= '<tr><td>Student Birthdate:</td>';
				$output .= '<td>' . birthdate_combo($tag, 0,0,0) . '</td></tr>'; 
				$output .= '<input type="hidden" name="' . $tag . '_skill" value="0">';
		//		$output .= '<tr><td>Skill:</td>';
		//		$output .= '<td><select name="' . $tag . '_skill">';
		//		$output .= $levels;
		//		$output .= '</select></td></tr>';
				$output .=  '</table>';
			}
			$output .= '<br /><br />';
			$output .= '<input type="submit" name="RegisterNew" value="Finish" />';
			$output .= '</form>';			
		}
		else
		{
			$output .= 'This site can only enter 1 to 15 students at a time.  <br />';
		}
       	
	}
	else if (isset($_POST['RegisterNew']) && $_POST['RegisterNew'] == "Finish") // step 3
	{
		$num_students = $_POST['num_students'];
		for ($i = 0; $i < $num_students; $i++)
		{
			$tag = 'c' . $i;
			$firstvar = $tag . 'firstname';
			$lastvar = $tag . 'lastname';
			$firstname = '';
			$lastname = '';
			$firstname = $_POST[$firstvar];
			$lastname = $_POST[$lastvar];
			$id = 0;
			$yearvar = $tag . 'DateOfBirth_Year';
			$monthvar = $tag . 'DateOfBirth_Month';
			$dayvar = $tag . 'DateOfBirth_Day';
				
			$birthdate =  $_POST[$yearvar] . '-'; 
			$birthdate .=	$_POST[$monthvar] . '-';
			$birthdate .=	$_POST[$dayvar];

			$skillvar = $tag . '_skill';
				
			$this->PoolSharkReg->insert( 'student', array(  'first_name' =>$firstname,
										 'last_name'  =>$lastname,
                                         'guardian_id' =>$_POST['guardian_id'],
                                         'birthdate' =>$birthdate, 
                                         'skill_id' => $_POST[$skillvar]),
                                  array( '%s', '%s', '%d', '%s', '%d') );
                              
			$output .= '<br />Registered '.$firstname . ' '. $lastname . '<br />';			
		}

		$parent = $this->PoolSharkReg->get_row("SELECT first_name, last_name
										FROM guardian
										WHERE id = '$_POST[guardian_id]'");
		$output .= '<br /><br /><strong>Check all times that the ' . $parent->first_name . ' ' . $parent->last_name . ' family prefers to come for swim class.<br />';
		$output .='Checking more options is better. <br /></strong>';
		$output .= '<form method="post" action="">';
		$output .= '<input type="hidden" name="guardian_id" value="' . $_POST['guardian_id'] . '">';
		$output .= $this->GetPrefTimeslots(0);					
		$output .= '<br />Additional Comments:<br />';
		$output .=  '<textarea  name="comments" maxlength="1000" cols="25" rows="6"></textarea>';
		$output .= '<input type="submit" name="RegisterNew" value="Done!" /><br /><br />';
		$output .= '</form><br />';
		$output .= '<br /><br />';
	}	
	else if (isset($_POST['RegisterNew']) && $_POST['RegisterNew'] == "Done!")
	{	
	$email_text = 'New Registration from LifeStrokes website' . "\r\n";
	$contact = $this->PoolSharkReg->get_row( "SELECT 
						guardian.first_name, guardian.last_name, 
						home_phone, cell_phone, work_phone, email, contact_method.name AS pref_contact
						FROM guardian, contact_method 
						WHERE guardian.id = '$_POST[guardian_id]'
						AND contact_method.id = pref_contact");

	if ($contact)
	{
		$email_text .= $contact->first_name . ' ' . $contact->last_name . "\r\n";
		$email_text .= 'Home: ' . $contact->home_phone . "\r\n";
		$email_text .= 'Cell: ' . $contact->cell_phone . "\r\n";
		$email_text .= 'Work: ' . $contact->work_phone . "\r\n";
		$email_text .= 'Email: ' . $contact->email . "\r\n";
		$email_text .= 'Contact by ' . $contact->pref_contact . " preferred.\r\n";
	}	
	
	$students = $this->PoolSharkReg->get_results("SELECT student.first_name, student.last_name, 
												DATE_FORMAT(student.birthdate, '%M %e, %Y') AS birthdate, 
												skill.name AS skill
												FROM student, skill
												WHERE student.guardian_id = '$_POST[guardian_id]'
												AND student.skill_id = skill.level");
	if ($students)
	{
		$email_text .= "\r\nStudents: \r\n";
		foreach($students as $s)
		{
			$email_text .= $s->first_name . ' ' . $s->last_name . "\r\n";
			$email_text .= 'Birthdate: ' . $s->birthdate . "\r\n";
			$email_text .= 'Registered Skill: ' . $s->skill . "\r\n\r\n";
		}
	}
												
	$email_text .= 'Available Lesson Times:' . "\r\n";	
	
	
	$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday');
	$class_slots = time_range('8:00am', '9:00pm', '30 mins');

	foreach($days as $day)
	{
		$email_text .= "\r\n" . $day . ':  ';
		foreach ($class_slots as $slot)
		{
			$find_time = date('H:i:s', $slot);
			$ez_time = date('g:ia', $slot); 
			$varname = $day . $find_time;
			$slot = $this->PoolSharkReg->get_var("SELECT time_slot.id AS id 
												FROM time_slot, guardian_pref_timeslot
												WHERE guardian_pref_timeslot.guardian_id = '$_POST[guardian_id]'
												AND guardian_pref_timeslot.timeslot_id = time_slot.id
												AND time_slot.weekday = '$day'
												AND time_slot.start_time = CAST('$find_time' AS time)");
																							

			if (!$slot && isset($_POST[$varname]) AND $_POST[$varname] == "yes")
			{
				$timeslot_id = 0;
				$timeslot_id = $this->PoolSharkReg->get_var("SELECT MAX(id) FROM time_slot
															WHERE weekday = '$day'
															AND start_time = '$find_time'");
				
				if ($timeslot_id == 0)
				{	$this->PoolSharkReg->insert( 'time_slot', array ('weekday' => $day,
															  'start_time' => $find_time),
													   array ( '%s', '%s' ));
					$timeslot_id = $this->PoolSharkReg->get_var("SELECT MAX(id) FROM time_slot
															WHERE weekday = '$day'
															AND start_time = '$find_time'");
				}										
				
				$email_text .= $ez_time . ' ';
				$this->PoolSharkReg->insert( 'guardian_pref_timeslot', array('guardian_id' => $_POST['guardian_id'],
																  'timeslot_id' => $timeslot_id),
																  array('%d', '%d'));
			}
		}
	}

	
	$email_text .= "\r\n\r\nComments:\r\n " . $_POST['comments'] . "\r\n";
	@mail($email_to, $email_subject, $email_text, $headers);  	
	$output .= 'Thank you!  You have successfully registered, and your preferred class times saved.<br />';
	$output .= 'We will contact you soon to schedule your classes. <br /><br />';
	
	return $output;
	}
	else  /* the initial page */
	{

	
	$sessions = $this->PoolSharkReg->get_results( "SELECT name, 
													DATE_FORMAT(start_date, '%M %e, %Y') AS start_date,
													DATE_FORMAT(end_date, '%M %e, %Y') AS end_date
													FROM session
													WHERE 
													DATEDIFF(end_date, CURDATE()) > 20");

	
	if ($sessions)
	{
		foreach( $sessions as $s)
		{
			$output .= $s->name . ' is from ' . $s->start_date . ' to ' . $s->end_date . '.<br />';
		}
	}
	
	$output .=  '<br /><h2>Register student(s) for Swimming Lessions.  <br /> </h2>';	
	$output .= 'Step 1 (of 3): Contact Information <br />';
	$output .= '<form method="post" action=""><table>';
	$output .= '<tr><td>Parent First Name:</td>';
	$output .= '<td><input maxlength="48" name="pfirstname" size="20" type="text" /></td></tr>';
	$output .= '<tr><td>Parent Last Name:</td>';
	$output .= '<td><input maxlength="48" name="plastname" size="30" type="text" /></td></tr>';
	$output .= '<tr><td>Home Phone:</td>';
	$output .= '<td><input maxlength="12" name="homephone" size="12" type="text" /></td></tr>';
	$output .= '<td>Cell Phone:</td>';
	$output .= '<td><input maxlength="12" name="cellphone" size="12" type="text" /></td></tr>';
	$output .= '<tr><td>Work Phone:</td>';
	$output .= '<td><input maxlength="12" name="workphone" size="12" type="text" /></td></tr>';
	$output .= '<tr><td>Email:</td>';
	$output .= '<td><input maxlength="60&quot;" name="email" size="40" type="text" /></td></tr>';
	$output .= '<tr><td>Best way to contact you:</td>';
	$output .= '<td><select name="contact_select">';
	$output .= $this->GetContactMethodOptions(0);
	$output .= '</select></td></tr>';
	$output .= '<tr><td>Number of students:</td>';
	$output .= '<td><input maxlength=2 size=4 type="text" name="num_students"></td></tr></table>';
	$output .= '<br /><br />';
	$output .= '<input type="submit" name="RegisterNew" value="Next" />';
	$output .= '</form>';	
		
	}

	return $output;
}


	
} // end class


function birthdate_combo( $prefix, $selbirthmonth, $selbirthday, $selbirthyear )
{
    
    if (!isset( $selbirthmonth )) {
        $selbirthmonth = $selbirthday = $selbirthyear = 0;
    }


    $output = '<select name="' . $prefix .'DateOfBirth_Month">';
    $monthname = array("Month", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

    for( $i = 0; $i <= 12; $i++)
    {
        $output .= '<option value="';
        if ($i < 10)
            $output .='0';
        $output .= $i;
        if ($selbirthmonth == $i)
            $output .= '" selected>';
        else
            $output .= '">';
        $output .= $monthname[$i] . '</option>';
    }
    
    $output .= '</select><select name="' . $prefix . 'DateOfBirth_Day">';
    
    for( $i = 0; $i <= 31; $i++)
    {
        $output .= '<option value="';
        if ($i < 10) {
            $output .='0';
        }
        $output .= $i;
        if ($selbirthday == $i)
        {
            $output .= '" selected>';
        }
        else
            $output .= '">';
        if ($i == 0) {
            $output .= 'Day';
        } else {
            $output .= $i;
        }
        $output .= '</option>';
    }

    $output .='</select>';
    $output .= '<select name="' . $prefix . 'DateOfBirth_Year">';
    $output .='<option value="0000">Year</option>';
    
    for( $i = date("Y"); $i >= 1980; $i--)
    {
        $output .= '<option value="' . $i;
        if ($selbirthyear == $i)
            $output .= '" selected>';
        else
            $output .= '">';
        $output .= $i;
        $output .= '</option>';
    }
    
    $output .='</select>';
    
    return $output;
}

function time_range( $start, $end, $by='30 mins')
{

$start_time = strtotime($start);
$end_time = strtotime($end);

$times = array();
for ( ;$start_time < $end_time; ) {
$times[] = $start_time;
$start_time = strtotime('+'.$by, $start_time);
}
$times[] = $start_time;
return $times;
}

function parameter_queryvars( $qvars )
{
$qvars[] = 'student_id';
$qvars[] = 'session_id';
$qvars[] = 'teacher_id';
$qvars[] = 'contact_id';
return $qvars;
}

function get_session()
{
global $wp_query;
if (isset($wp_query->query_vars['session_id']))
{
return $wp_query->query_vars['session_id'];
}
}

function get_teacher()
{
global $wp_query;
if (isset($wp_query->query_vars['teacher_id']))
{
return $wp_query->query_vars['teacher_id'];
}
}

function get_contact()
{
global $wp_query;
if (isset($wp_query->query_vars['contact_id']))
{
return $wp_query->query_vars['contact_id'];
}
}

function get_student()
{
	global $wp_query;
	if(isset($wp_query->query_vars['student_id']))
	{
		return $wp_query->query_vars['student_id'];
	}
}

?>
