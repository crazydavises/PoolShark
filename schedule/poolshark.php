<?php
/*
Plugin Name: Pool Shark
Plugin URI: http://lifestrokes.com
Description: Database Functions for LifeStrokes Swim School
Version: 0.1
Author: Amy Davis
Author URI: http://growingliketrees.blogspot.com
*/

// http://codex.wordpress.org/Function_Reference/

$Schedule = new PoolShark;

class PoolShark
{
	var $PoolShark;
	var $SkillLevels;
	var $ContactOptions;
	var $SkillLevelInit = 0;
	var $ContactOptionsInit = 0;
	var $group_discount_percent = .2;
	
	function __construct()
	{
		add_action( 'init', array( $this, 'InitDB' ) ); 		
		add_action( 'wp_head', array($this, 'addHeaderCode') );
		add_action( 'admin_menu', array( $this, 'AdminMenu' ) );
		add_shortcode( 'EditSchedule', array( $this, 'EditSchedule' ) );
		add_shortcode( 'PrintSchedule', array( $this, 'PrintSchedule' ) );
		add_shortcode( 'ClassCancel', array( $this, 'ClassCancel' ) );
		add_shortcode( 'DefineSession', array( $this, 'DefineSession' ) );
		add_shortcode( 'CreateSession', array( $this, 'CreateSession') );
		add_shortcode( 'RemoveSession', array( $this, 'RemoveSession') );
		add_shortcode( 'UpdateFamily', array($this, 'UpdateFamily') );
		add_shortcode( 'RegisterExistingStudents', array($this, 'RegisterExistingStudents') );
		add_shortcode( 'RegisterNew', array($this, 'RegisterNew') );
		add_shortcode( 'ShowRegistered', array( $this, 'ShowRegistered') );
		add_shortcode( 'TeachingSchedule', array( $this, 'TeachingSchedule') );
		add_shortcode( 'ScheduleClasses', array($this, 'ScheduleClasses') );
		add_shortcode( 'ApprovePending', array($this, 'ApprovePending') );
		add_shortcode( 'ShowPending', array($this, 'ShowPending') );
		add_shortcode( 'DBSearch', array($this, 'DBSearch') );
		add_shortcode( 'LessonData', array($this, 'LessonData') );
		add_shortcode( 'SetClassLevels', array($this, 'SetClassLevels') );
	}
	function InitDB()
	{
		$this->PoolShark = new wpdb('lifestrokesadmin', 'swim_@lot!', 'poolshark', 'mysql.lifestrokes.com');
	    $this->PoolShark->show_errors();
		add_filter('query_vars', 'parameter_queryvars' );  
	}
	function addheaderCode()
	{
        echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/poolshark/poolshark_tables.css" />' . "\n";
	}

	function GetFutureSessionsOption( $sess )
	{
		$output = '';
		$sessions = $this->PoolShark->get_results( "SELECT id, name
														FROM session");
//														WHERE end_date > CURDATE()");
		if ($sessions)
		{
			$output .= '<option>select session</option>';
			foreach( $sessions as $s)
			{
				$output .= '<option value=' . $s->id;
				if ($s->id == $sess)
					$output .= ' selected ';
				$output .= '>' . $s->name . '</option>';
			}
		}
		
		return $output;		
	}

	function GetAllSessionsOption( $sess)
	{
		$output = '';
		$sessions = $this->PoolShark->get_results( "SELECT id, name
														FROM session ORDER BY end_date DESC ");
		if ($sessions)
		{
			$output .= '<option>select session</option>';
			foreach( $sessions as $s)
			{
				$output .= '<option value=' . $s->id;
				if ($s->id == $sess)
					$output .= ' selected ';
				$output .= '>' . $s->name . '</option>';
			}
		}
		
		return $output;		
	}	

	function GetAllClassesOption( $session_id, $current_class)
	{
	$output = '';
	if ($current_class == 0)
		$output = '<option>select class</option>';
	else
		$output = '<option value="0">remove from classes</option>';
		
	$classes = $this->PoolShark->get_results("SELECT class.id, 
													 skill.shortname AS skill,
													 teacher.short_name AS teacher, 
													 time_slot.short_day AS class_day, 
													 TIME_FORMAT(time_slot.start_time, '%l:%i') AS ez_start_time 
													 FROM class, skill, teacher, time_slot, class_timeslot
													 WHERE session_id = '$session_id'
													 AND class.skill_id = skill.level
													 AND class.teacher_id = teacher.id
													 AND class_timeslot.class_id = class.id
													 AND class_timeslot.timeslot_id = time_slot.id
													 ORDER by time_slot.weekday, time_slot.start_time, teacher.id");
		
	if ($classes)
	{
		foreach($classes as $class)
		{
			$output .= '<option value=' . $class->id;
	       	if ($class->id == $current_class)
	       		{
					$found_current = true;
					$output .= ' selected ';
				}
	     	$output .='> ' . $class->class_day.', '. $class->ez_start_time .' '.$class->teacher .' ('. $class->skill ;
	   		$output .= ')</option>';
		}

	}
	else
	$output .= '<option>' . $enroll . '</option>';
	return $output;
	}


	function GetAllOpenClassesOption ( $session_id, $current_class, $level_of_class )
	{
	$output = '';
	if ($current_class == 0)
		$output = '<option>select class</option>';
	else
		$output = '<option value="0">remove from classes</option>';


	if ($level_of_class != 0)
	{
		$classes = $this->PoolShark->get_results("SELECT class.id, 
													 skill.size_limit AS size_limit, 
													 skill.name AS skill,
													 teacher.name AS teacher, 
													 time_slot.weekday AS class_day, 
												 TIME_FORMAT(time_slot.start_time, '%l:%i %p') AS ez_start_time 
													 FROM class, skill, teacher, time_slot, class_timeslot
													 WHERE session_id = '$session_id'
													 AND class.skill_id = skill.level
													 AND class.teacher_id = teacher.id
													 AND class_timeslot.class_id = class.id
													 AND class_timeslot.timeslot_id = time_slot.id
													 AND (skill.level = '$level_of_class' OR skill.level = 0)
													 ORDER by time_slot.weekday, time_slot.start_time");
	}
	else
	{
		$classes = $this->PoolShark->get_results("SELECT class.id, 
													 skill.size_limit AS size_limit, 
													 skill.name AS skill,
													 teacher.name AS teacher, 
													 time_slot.weekday AS class_day, 
													 TIME_FORMAT(time_slot.start_time, '%l:%i %p') AS ez_start_time 
													 FROM class, skill, teacher, time_slot, class_timeslot
													 WHERE session_id = '$session_id'
													 AND class.skill_id = skill.level
													 AND class.teacher_id = teacher.id
													 AND class_timeslot.class_id = class.id
													 AND class_timeslot.timeslot_id = time_slot.id
													 ORDER by time_slot.weekday, time_slot.start_time");		
		
	}
	$found_current = false;
	if ($classes)
	{
		foreach($classes as $class)
		{		
			$num_students = $this->PoolShark->get_var("SELECT COUNT(*)
                                                FROM class_student
                                                WHERE class_id = '$class->id'");
			
			if ($class->size_limit > $num_students)
			{		
				$age_range = $this->PoolShark->get_row("SELECT MAX(DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birthdate, '%Y') -
          	(DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birthdate, '00-%m-%d')) ) as max_age, 
														MIN(DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birthdate, '%Y') -
          	(DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birthdate, '00-%m-%d')) ) as min_age
														FROM student, class_student
														WHERE class_id = '$class->id'
														AND student_id = student.id");
	       		$output .= '<option value=' . $class->id;
	       		if ($class->id == $current_class)
	       		{
					$found_current = true;
					$output .= ' selected ';
				}
	     		$output .='> ' . $class->id . ' ' . $class->class_day.', '. $class->ez_start_time .' with '.$class->teacher .' ('. $class->skill ;
	     		if ($age_range)
					$output .= ','.$age_range->min_age . '-' . $age_range->max_age;
				$output .= ')</option>';
	        }
		}
	}
	if (!$found_current && $current_class != 0)
	{
		$cur_cl = $this->PoolShark->get_row("SELECT class.id, 
			skill.name AS skill, 
			teacher.name AS teacher,
			time_slot.weekday AS class_day,
			MAX(DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birthdate, '%Y') -
          	(DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birthdate, '00-%m-%d')) ) as max_age, 
			MIN(DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birthdate, '%Y') -
          	(DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birthdate, '00-%m-%d')) ) as min_age,
			TIME_FORMAT(time_slot.start_time, '%l:%i %p') AS ez_start_time 
			FROM class, skill, teacher, time_slot, class_timeslot, student, class_student
			WHERE session_id = '$session_id'
			AND class.skill_id = skill.level
			AND class.id = '$current_class' 
			AND class_student.class_id = '$current_class'
			AND class_student.student_id = student.id
			AND class.teacher_id = teacher.id
			AND class_timeslot.class_id = class.id
			AND class_timeslot.timeslot_id = time_slot.id");
	      
	       	$output .= '<option value=' . $cur_cl->id;
			$output .= ' selected ';
			$output .='> ' . $cur_cl->class_day.', '. $cur_cl->ez_start_time .' with '.$cur_cl->teacher .' ('. $cur_cl->skill ;
	     	$output .= ','.$cur_cl->min_age . '-' . $cur_cl->max_age;
			$output .= ')</option>';		
		}
		
	return $output;
	}
	
	function GetContactMethodOptions ( $current_method )
	{
	if ($this->ContactOptionsInit == 0)
	{
	$this->ContactOptions = $this->PoolShark->get_results("SELECT contact_method.name AS method,
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
		
	function GetRegistrationOptions( $current_registration )
	{
		$output = '<option value=0';
		if ($current_registration == 0)
			$output .= ' selected';
		$output .= '>Register Now</option>';
		$output .= '<option value=1';
		if ($current_registration == 1)
			$output .= ' selected';
		$output .= '>Yes, registered</option>';
		$output .= '<option value=-1';
		if ($current_registration == -1)
			$output .= ' selected';
		$output .= '>No, not registering</option>';
			
		return $output;
	}
	
	
	
function GetSkillLevelOptions ( $current_skill )
{
	if ($this->SkillLevelInit == 0)
	{
	$this->SkillLevels = $this->PoolShark->get_results("SELECT skill.name AS skill,
													 skill.level AS id
													 FROM skill");
	$this->SkillLevelInit = 1;
	}
	$skills = $this->SkillLevels;
	$output = '<option value=0';
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
	
function TdClass( $balance, $registration, $in_session)
{
$class = "clear";
if (!$in_session)
	return $class;
	
if ($balance == 0)
{
	if ($registration == 1)
		$class = "registered";
	else if ($registration == -1)
		$class = "not_registering";
	else
		$class = "pd_up";
}
else 
{
	if ($balance > 0)
		$class = "owe";
	else if ($balance < 0)
		$class = "credit";
}
return $class;
}

function ClassLevelsTable($session_id)
{
	$output = '';
	$SessionName = $this->PoolShark->get_var("SELECT name FROM session WHERE id='$session_id'");
	if ($SessionName)
	{
		$output .= 'Schedule for '. $SessionName . '<br /> ';
	}	
	

	$classtimes = $this->PoolShark->get_results("SELECT time_slot.weekday AS class_day, 
						teacher.name AS teacher, class.skill_id, start_time,
						TIME_FORMAT(time_slot.start_time, '%l:%i %p') AS ez_start_time, 
						class.id AS class_id
						FROM class, teacher, class_timeslot, time_slot, skill
						WHERE session_id = '$session_id'
						AND class_timeslot.class_id = class.id
						AND class_timeslot.timeslot_id = time_slot.id
						AND class.teacher_id = teacher.id
						AND class.skill_id = skill.level
						ORDER BY class_day, start_time");	
						
	if ($classtimes)
	{
		$previous_day == '';
		$first_day = true;
		foreach($classtimes as $class_info)
		{	
			$current_day = $class_info->class_day;
			if ($current_day != $previous_day)
			{
				if (!$first_day)
				{
					$output .= '</table><br />';
					$output .= '<input type="submit" name="set_skills" value="Update Class Levels" /><br /><br />';
				}
				$output .= '<strong >' . $class_info->class_day . '<br /></strong>';
				$output .= '<table class="schedule"><tr>';
				// table headers
				$output .= '<th>Time</th><th>Teacher</th><th>Skill</th></tr>';
				$previous_day = $current_day;
				$first_day = false;
			}
			
			$output .= '<tr><td>' . $class_info->ez_start_time . '</td>';
			$output .= '<td>' . $class_info->teacher . ' ' . $class_info->class_id . '</td>';
			$level = $this->GetSkillLevelOptions($class_info->skill_id);
			$output .= '<td class="clear"><select name="' . $class_info->class_id . '_level">' . $level . '</select> </td>';	
			$output .= '</tr>';
		}
		$output .= '</table>';			
		$output .= '<input type="submit" name="set_skills" value="Update Class Levels" /><br /><br />';
	}
		
	return $output;
}

function SetClassLevels()
{
	$session_id = 0;
	$session_id = get_session();
	$output = '';
	if (isset($_POST['set_skills']) && $_POST['set_skills'] != "Set Class Levels")
	{
		
		$session_id = $_POST['session_id'];
		// update the skills
		$classtimes = $this->PoolShark->get_results("SELECT time_slot.weekday AS class_day, 
						class.skill_id, start_time,
						class.id AS class_id
						FROM class, class_timeslot, time_slot
						WHERE session_id = '$session_id'
						AND class_timeslot.class_id = class.id
						AND class_timeslot.timeslot_id = time_slot.id
						ORDER BY class_day, start_time");	
						
		if ($classtimes)
		{
			foreach($classtimes as $class_info)
			{	
				$classtag = $class_info->class_id . '_level';		
				if (isset($_POST[$classtag])  && $_POST[$classtag] != $class_info->skill_id)
				{
				
					$this->PoolShark->update('class', array( 'skill_id' => $_POST[$classtag]),
												array('id' => $class_info->class_id),
												array('%d'),
												array('%d'));
												
				}
			}		
		}	
	}	
	else if (($session_id == 0) AND !isset($_POST['session_id']))
	{
		$output = 'Set Class Levels for what session? '.  $session_id . ' <br >';
		$FutureSessions = $this->GetAllSessionsOption(0);
		$output .= '<form method="post" action="">';
		$output .= '<select name="session_id">';
		$output .= $FutureSessions;
		$output .= '</select>';
		$output .= '<br /><br />';
		$output .= '<input type="submit" name="set_skills" value="Set Class Levels" />';
		$output .= '</form>';
		return $output;
	}	
	else if (isset($_POST['session_id']))
	{
		$session_id = $_POST['session_id'];
	}
	
	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="session_id" value=' . $session_id . '>';
	$output .= $this->ClassLevelsTable($session_id);
	$output .= '</form>';
	
		
	return $output;	
}	
	


function ShowScheduleTable($session_id, $editable)
{
	$output = '';
	$SessionName = $this->PoolShark->get_var("SELECT name FROM session WHERE id='$session_id'");
	$show_parent = false;
	$show_phone = false;
	$show_balance = false;
	$enroll = $this->enroll_session();
	if(isset($_POST["show_parent"]) && $_POST["show_parent"] == "yes")
		$show_parent = true;
	if(isset($_POST["show_phone"]) && $_POST["show_phone"] == "yes")
		$show_phone = true;
	if(isset($_POST["show_balance"]) && $_POST["show_balance"] == "yes")
		$show_balance = true;
	if(isset($_POST["schedule_classes"]) && $_POST["schedule_classes"] == "yes")
		$schedule_classes = true;
	
		
	$current_session = $this->PoolShark->get_var( "SELECT id 
													FROM session
													WHERE start_date < CURDATE()
													AND CURDATE() < end_date");
	if (!$current_session)
		$current_session = 21;
		
	$has_begun = false;
	$output .= 'current session is ' . $current_session . ' session_id is ' . $session_id . '<br />';
	if ($current_session >= $session_id)
		$has_begun = true;
	
	if ($SessionName)
	{
		$output .= '<h1>Schedule for '. $SessionName . '</h1><br /> ';
	}



	$days = $this->PoolShark->get_results("SELECT DISTINCT time_slot.weekday AS class_day, teacher.name AS teacher, teacher_id
						FROM class, teacher, class_timeslot, time_slot
						WHERE session_id = '$session_id'
						AND class_timeslot.class_id = class.id
						AND class_timeslot.timeslot_id = time_slot.id
						AND class.teacher_id = teacher.id
						ORDER BY class_day, teacher_id");
	if ($days)
	{
		foreach($days as $day)
		{
			$output .= '<strong> ' . $day->class_day . ' with Miss ' . $day->teacher . '<br /></strong>';
			$num_kids = $this->PoolShark->get_var("SELECT MAX(skill.size_limit) FROM class, skill, time_slot
                                         WHERE session_id = '$session_id'
                                         AND time_slot.weekday = '$day->class_day'
                                         AND teacher_id = '$day->teacher_id'
                                         AND skill.level = class.skill_id");
			$output .= '<table class="schedule"><tr>';
			// table headers
			$output .= '<th>Level</th><th>Time</th><th colspan='.$num_kids.'>Students</th></tr>';

			$classes = $this->PoolShark->get_results("SELECT class.id, skill.name AS skill, skill_id, skill.size_limit AS size_limit,
                                          TIME_FORMAT(time_slot.start_time, '%l:%i %p') AS ez_start_time, skill.level
                                          FROM class, skill, class_timeslot, time_slot
                                          WHERE class.session_id = '$session_id'
                                          AND class.teacher_id = '$day->teacher_id'
                                          AND time_slot.weekday = '$day->class_day'
                                          AND skill.level = class.skill_id
                                          AND class_timeslot.class_id = class.id
                                          AND class_timeslot.timeslot_id = time_slot.id
                                          ORDER BY time_slot.start_time");
			if ($classes)
			{
			foreach($classes as $class)
			{
			$num_students = $this->PoolShark->get_var("SELECT COUNT(*) 
						FROM class_student 
						WHERE class_id = '$class->id'");

			$ToFill = $class->size_limit - $num_students;
			
			$space = "open";
			if ($ToFill <= 0)
				$space = "full";
				
			$output .= '<tr>';							
			if ($editable)	
			{
				$level = $this->GetSkillLevelOptions($class->level);
				$output .= '<td class="clear"><select name="' . $class->id . '_level">' . $level . '</select> </td>';
			}
			else			
				$output .= '<td class=' . $space . '> ' . $class->skill_id . '</td>';
			$output .=  '<td class=' . $space . '> ' .$class->ez_start_time . '</td>';			
			
			$students = $this->PoolShark->get_results("SELECT student.id, guardian_id, payment_due AS balance, 
			registration, student.first_name, student.last_name, home_phone, cell_phone,
			guardian.first_name as parent_fname, guardian.last_name as parent_lname,
			DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birthdate, '%Y') -
          	(DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birthdate, '00-%m-%d')) AS age
			FROM class_student, student, guardian
			WHERE class_student.class_id = '$class->id' 
			AND class_student.student_id = student.id
			AND student.guardian_id = guardian.id");

			if ($num_students > 0)
			{
			$cnt = 1;
			if (!$students)
				$output .= '<td colspan=' . $num_kids . '> No Students Found </td>';
			else foreach($students as $student)
			{
				$varname = 's' + $student->id;
				if ($cnt == $num_students && $ToFill == 0)
				{ 
					// last column in this row				
					$cols = 1 + $ToFill + $num_kids - $num_students;
					$output .= '<td colspan=' . $cols . ' '; 
				}
				else
					$output .= '<td ';	
				
				$class = $this->TdClass($student->balance, $student->registration, $has_begun);
				$output .= 'class="' . $class . '">';
				if ($editable)
				{
					$output .= '<input type="checkbox" name="' . $varname . '" ';
					$output .= ' value="yes" >';
				}		
				$output .= '<a href=../edit-family/?contact_id=' . $student->guardian_id . ' class="schedule">';
				$output .=  $student->first_name . ' ' . $student->last_name .'</a>';
				$output .=  ', ' .  $student->age; 
				if ($show_balance)
					$output .=  '<br /><h6>$' . $student->balance;
				if ($show_parent)
					$output .= '<br /><h6>' . $student->parent_fname . ' ' . $student->parent_lname;
				if ($show_phone)
					$output .= '<br /><h6>'. $student->home_phone . ' ' . $student->cell_phone ;
				if ($editable && $schedule_classes)
				{
					$already_enrolled = $this->PoolShark->get_var( "SELECT class_id  
								FROM class_student, class 
								WHERE class.session_id = '$enroll'
								AND class_student.class_id = class.id
								AND student_id = '$student->id'");	
					$classes = $this->GetAllClassesOption($enroll, $already_enrolled);
					$output .= '<br /><select name="' . $student->id . '_class">';	
					$output .= $classes;
					$output .= '</select>';				
				}
				$output .='</td>';
				$cnt++;
			}  // foreach student
			if ($cnt == $num_students+1)
			{
				if ($ToFill > 0)
				{
					$output .= '<td colspan='. $ToFill .' class="open">' . $ToFill. ' OPEN </td>';
				}
			}
			} // if num_students
			else
			{
				$output .=  '<td colspan='.$num_kids.'></td>';
			}	
			} // foreach class
			$output .= '</tr>';
			} // if classes
		$output .= '</table>';
	
		if ($editable)
		{
			$output .= ' <input type = "radio" name = "update_students" value="pd_up"> Paid Up. <br />';
			$output .= ' <input type = "radio" name = "update_students" value="credit"> Credit One Lesson. <br />';
			$output .= ' <input type = "radio" name = "update_students" value="reg"> Registering for next session. <br />';
			$output .= ' <input type = "radio" name = "update_students" value="not_reg"> NOT registering for next session. <br />';
			$output .= ' <input type = "radio" name = "update_students" value="schedule_same"> Schedule at same time next session. <br />';	
			$output .= ' <input type = "radio" name = "update_students" value="selected_schedule"> Schedule into selected class next session. <br />';	
			$output .= ' <input type = "submit" name = "apply_student_change" value = "Apply" />    ';
			$output .= ' <input type = "submit" name = "update_schedule" value = "Update Levels" /><br/><br />';

		}

		} // foreach day
	} // if days
	
	
	$output .= '<input type = "checkbox" name = "show_balance" value = "yes"';
	if ($show_balance)
		$output .= ' checked';
	$output .= '> Show Balance<br />';
	$output .= '<input type = "checkbox" name = "show_parent" value = "yes"';
	if ($show_parent)
		$output .= ' checked';
	$output .= '> Show Contact Name<br />';
	$output .= '<input type = "checkbox" name = "show_phone" value = "yes"';
	if ($show_phone)
		$output .= ' checked';
	$output .= '> Show Contact Phone<br />';
	$output .= '<input type = "checkbox" name = "schedule_classes" value = "yes"';
	if ($schedule_classes)
		$output .= ' checked';
	$output .= '> Show Class Scheduling Info<br />';


	$output .= ' <input type = "submit" name = "view_schedule" value = "Apply" /><br/><br />';
	return $output;
	
	
}

function PrintSchedule()
{
	$session_id = 0;
	$session_id = get_session();
	$output = '';
	if (($session_id == 0) AND !isset($_POST['session_id']))
	{
		$output = 'Show Schedule for What Session? '.  $session_id . ' <br >';
		$FutureSessions = $this->GetAllSessionsOption(0);
		$output .= '<form method="post" action="">';
		$output .= '<select name="session_id">';
		$output .= $FutureSessions;
		$output .= '</select>';
		$output .= '<br /><br />';
		$output .= '<input type="submit" name="show_schedule" value="Show Session Schedule" />';
		$output .= '</form>';
		return $output;
	}	
	else if (isset($_POST['session_id']))
	{
		$session_id = $_POST['session_id'];
	}
	
	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="session_id" value="' . $session_id . '"><br />';
	$output .= $this->ShowScheduleTable($session_id, false);
	$output .= '</form>';	
		
	return $output;	
}

function EditSchedule()
{
	$session_id = 0;
	$session_id = get_session();
	$output = '';
	$debug = 'WARNING, AMY IS WORKING ON THIS PART OF THE SITE RIGHT NOW <br />';
	if (($session_id == 0) AND !isset($_POST['session_id']))
	{
		$output = 'Edit Schedule for What Session? '.  $session_id . ' <br >';
		$FutureSessions = $this->GetAllSessionsOption(0);
		$output .= '<form method="post" action="">';
		$output .= '<select name="session_id">';
		$output .= $FutureSessions;
		$output .= '</select>';
		$output .= '<br /><br />';
		$output .= '<input type="submit" name="show_schedule" value="Edit Session Schedule" />';
		$output .= '</form>';
		return $output;
	}
	else if (isset($_POST['update_schedule']) AND $_POST['update_schedule'] == "Update Levels")
	{
		$classes = $this->PoolShark->get_results("SELECT class.id, 
									skill.name AS skill, 
									skill.size_limit AS size_limit,
									skill.level
                                    FROM class, skill
                                    WHERE class.session_id = '$session_id'
                                    AND skill.level = class.skill_id");
		if ($classes)
		{
			foreach($classes as $class)
			{
			$classtag = $class->id.'_level';
			if ($_POST[$classtag] != $class->level)
			{
			//	$output .= $class->id . ' tag ' . $classtag . ' level from ' . $class->level . ' to ' . $_POST[$classtag] . '<br />';
			$this->PoolShark->update('class', array( 'skill_id' => $_POST[$classtag]), 
										array( 'id' => $class->id ), 
											array( '%d' ), 
											array( '%d' ));

			}
			}
		}
		
		$output .= '<form method="post" action="">';
		$output .= '<input type="hidden" name="session_id" value="' . $session_id . '"><br />';
		$output .= $this->ShowScheduleTable($session_id, true);
		$output .= ' <input type="submit" name="update_schedule" value="Update Levels" /> </td></tr>';
		$output .= '</form>';	
		return $output;
	}
	else if (isset($_POST['apply_student_change']))
	{
		
		$students = $this->PoolShark->get_results("SELECT student.id, guardian_id
			FROM student");
	//	$output .= 'Update these checked students <br />';
		if ($students)
		{
			foreach($students as $student)
			{
				$varname = 's' + $student->id;
				if (isset($_POST[$varname]) AND $_POST[$varname] == "yes")
				{
	//				$output .= 'student ' . $student->id . ' with guardian ' . $student->guardian_id . '<br />';
					
					if ($_POST['update_students'] == "pd_up")
					{
						$cs_id = $this->PoolShark->get_var("SELECT class_student.id 
														FROM class_student, class 
														WHERE class_student.student_id = '$student->id'
														AND class.id = class_student.class_id
														AND class.session_id = '$session_id'");
						$this->PoolShark->update('class_student', 
								array('payment_due' => 0.00),
								array('id' => $cs_id),
								array('%d'),
								array('%d'));
								
						
	//					$this->PoolShark->update('guardian', array('balance' => 0.00),
	//										     array('id' =>$student->guardian_id),
	//											 array('%d'),
	//											 array('%d'));
					}
					if ($_POST['update_students'] == "credit")
					{
						$debug .= 'for student ' . $student->id . ' and session ' . $session_id . '<br />';
						$cs_info = $this->PoolShark->get_row("SELECT payment_due, class_student.id, skill.cost 
														FROM class_student, class, skill 
														WHERE class_student.student_id = '$student->id'
														AND class.id = class_student.class_id
														AND class.skill_id = skill.level
														AND class.session_id = '$session_id'");
														
						
						$new_balance = $cs_info->payment_due - $cs_info->cost;
						$debug .= 'Balance set from ' . $cs_info->payment_due . ' to ' . $new_balance . ' for record ' . $cs_info->id . '<br />';
						$this->PoolShark->update('class_student', array('payment_due' => $new_balance),
													array('id' => $cs_info->id),
													array('%d'),
													array('%d'));
						
					}
					if ($_POST['update_students'] == "reg")
					{
						
						$this->PoolShark->update('guardian', array('registration' => 1),
											     array('id' =>$student->guardian_id),
												 array('%d'),
												 array('%d'));
					
					}
					if ($_POST['update_students'] == "not_reg")
					{
						
						$this->PoolShark->update('guardian', array('registration' => -1),
											     array('id' =>$student->guardian_id),
												 array('%d'),
												 array('%d'));
					
					}
					if ($_POST['update_students'] == "schedule_same" || $_POST['update_students'] == "selected_schedule")
					{
						$debug .= ' update student schedule for student' . $student->id . '<br />';
						$this->PoolShark->update('guardian', array('registration' => 1),
											     array('id' =>$student->guardian_id),
												 array('%d'),
												 array('%d'));
						$num_active = $this->PoolShark->get_var("SELECT COUNT DISTINCT(first_name) FROM student 
																WHERE guardian_id = '$student->guardian_id'
																AND active = 1");
						$next_class_info = NULL;
						$enroll = $this->enroll_session();
						if ($_POST['update_students'] == "schedule_same")
						{// find current class
							$class_info = $this->PoolShark->get_row("SELECT timeslot_id, teacher_id, class.id, payment_due
									FROM class_student, class_timeslot, class
									WHERE class_student.student_id = '$student->id'
									AND class_student.class_id = class.id
									AND class_timeslot.class_id = class.id
									AND class.session_id = '$session_id'");
							if ($class_info)
							{
								//$output .= 'Current timeslot for student ' . $student->id . ' is ' . $class_info->timeslot_id . ' class ' . $class_info->id . '<br />';
						
								// find class in next session with same timeslot and teacher
						
								
								//$output .= 'next session is ' . $enroll . '<br />';
							
								$next_class_info = $this->PoolShark->get_row("SELECT class.id, class.price 
								FROM class, class_timeslot
								WHERE class.id = class_timeslot.class_id
								AND class_timeslot.timeslot_id = '$class_info->timeslot_id'
								AND class.teacher_id = '$class_info->teacher_id'
								AND class.session_id = '$enroll'");
								
								$next_class_price = $next_class_info->price;
								if ($num_active >= 3)
									$next_class_price = $next_class_price - ($next_class_price * $this->group_discount_percent);
								$next_session_price = $class_info->payment_due + $next_class_price;
							}
						}
						
						if (!isset($next_class_info) || $next_class_info == NULL)
						{
							// get the value of the dropdown
							$tag = $student->id . "_class";
							$debug .= 'tag = ' . $tag . '<br />';
							$class_id = $_POST[$tag];
							$debug .= 'class_id = ' . $class_id . '<br />';
							if ($class_id != 0)
							{
								$next_class_info = $this->PoolShark->get_row("SELECT class.id, class.price
								FROM class
								WHERE class.id = '$class_id'");
							}
							else 
							{
								$next_class_info = array( "id" => 0, "price" => $next_session_price);
							}
						}
						if (isset($next_class_info))
						{
							$debug .= 'Enroll in class ' . $next_class_info->id . ' session ' . $enroll . ' price ' . $next_class_info->price . ' <br />';
							$already_enrolled = $this->PoolShark->get_row( "SELECT payment_due, class_id, class_student.id AS cs_id 
								FROM class_student, class 
								WHERE class.session_id = '$enroll'
								AND class_student.class_id = class.id
								AND student_id = '$student->id'");	
								
							$debug .= 'Already enrolled in ' . $already_enrolled->class_id . '<br />';	
						// check to see if student is already enrolled for next session
						}
						if ($next_class_info && !$already_enrolled && $next_class_info->id != 0)
						{
							$debug .= 'Adding enrollment <br />';
							$this->PoolShark->insert('class_student', 
								array('class_id' => $next_class_info->id, 
											'student_id' => $student->id,
											'payment_due' => $next_session_price),
								array ('%d', '%d', '%d'));
						}
						else if ($already_enrolled && $next_class_info && 
						($next_class_info->id != $already_enrolled->class_id))
						{
								// move from one class to the other
									$output .= 'moving student to class ' . $next_class_info->id . '<br />';
									$this->PoolShark->update('class_student', 
																array('class_id' => $next_class_info->id, 
																'payment_due' => $next_session_price),
													array('id' => $already_enrolled->cs_id),
													array('%d', '%d'),
													array('%d'));
								
								 // if two ids are equal, do nothing.
						}
						else if (!isset($next_class_info))
						{
							$output .= 'need to create class for timeslot ' . $class_info->timeslot_id . '? <br />';
						}			
					} // if schedule
				}
			}
		}
		$output .= '<form method="post" action="">';
		$output .= '<input type="hidden" name="session_id" value="' . $session_id . '"><br />';
		$output .= $this->ShowScheduleTable($session_id, true);
		$output .= '</form>';
		
		$debug .= $output;
		//return $debug;
		return $output;
	}
	else if (isset($_POST['session_id']))
	{
		$session_id = $_POST['session_id'];
	}
	
	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="session_id" value="' . $session_id . '"><br />';
	$output .= $this->ShowScheduleTable($session_id, true);
	$output .= '</form>';
	
	return $output;
}

function enroll_session()
{
		$enroll = $this->PoolShark->get_var( "SELECT MIN(id)
													FROM session
													WHERE 
													DATEDIFF(end_date, CURDATE()) > 20");
		return $enroll;
}

function next_session ()
{
	$next_session = $this->PoolShark->get_var("SELECT id FROM session WHERE session.start_date > CURDATE()");
	return $next_session;
}

// returns 0, 1 or -1.  0 = don't know 1 = taking next session, -1 = not taking next session
function taking_next_session( $family_id )
{	
	
	$registered = 0;
	$registered = $this->PoolShark->get_var("SELECT registration FROM guardian 
											WHERE guardian.id = $family_id");
	if ($registered == 0)
	{
		// find students in this family
		$registered = $this->PoolShark->get_var("SELECT id from students, students_to_schedule 
												WHERE student.guardian_id = $family_id
												AND student_to_schedule.student_id = students.student_id");
		if ($registered == 0)
		{ // are they already scheduled?
			$session = $this->PoolShark->next_session();
			$registered = $this->PoolShark->get_var( "SELECT class.id FROM class_student, class, guardian, student
									WHERE class.session = '$session'
									AND class.id = class_student.class_id
									AND class_student.student_id = student.id
									AND student.guardian_id = '$family_id'");
		}
		if ($registered != 0)
		{

			$this->PoolShark->update('guardian', array('registration' =>$registered),
											     array('id' =>$family_id),
												 array('%d'),
												 array('%d'));
		}										 
	}
	return $registered;
}




function ApprovePending( $attributes )
{
$output = '';

$provisional_students = $this->PoolShark->get_results("SELECT guardian.id, 
													guardian.first_name as guardian_fname, 
													guardian.last_name as guardian_lname 
													FROM guardian 
													WHERE provisional = 1
													ORDER BY guardian_lname");
if ($provisional_students)
{
	$output .= 'Showing all students who have registered on webpage and not yet been approved or removed. <br >';
	$output .= 'To Approve this Registration, enroll these students in a session, or set their Skill level, or view and update any ';
	$output .= 'of their information, click on their name.<br /><br />';

	$output .= '<table>';
	foreach ($provisional_students as $prov)
	{
		$num_students = $this->PoolShark->get_var("SELECT COUNT(*) FROM student WHERE student.guardian_id = '$prov->id'");
		
		$output .= '<tr><td>';
		$output .= '<a href=../edit-family/?contact_id=' . $prov->id . '>';
		$output .= $prov->guardian_fname . ' ' . $prov->guardian_lname . ', ' . $num_students . ' students </a></td></tr>';
	}
	$output .= '</table>';
}
else
{
	$output .= 'No pending students to approve. <br />';
}
return $output;
}


function current_session()
{
	$session = $this->PoolShark->get_var( "SELECT MAX(id) FROM session
											WHERE DATEDIFF(CURDATE(), start_date) >= 0");
	return $session;
}

function GetUpdateFamilyTable( $parent_id)
{
	$output = '';
	$provisional = 0;
	$FutureSessions = $this->GetFutureSessionsOption(0);
	$enroll = $this->enroll_session();
	$current = $this->current_session();
	$debug = '<strong></strong>Hey, Amy is working on this page right now.  Give her a few minutes, and try again, or
	something funny may happen.</strong><br />';
	$SessionName = $this->PoolShark->get_var("SELECT name FROM session WHERE id='$enroll'");
	
	$contact_info = $this->PoolShark->get_row("SELECT guardian.id AS parent_id, 
												 guardian.first_name AS guardian_fname, 
												 guardian.last_name AS guardian_lname, 
												 provisional, registration, notes,
												 home_phone, cell_phone, email, contact_method.id AS pref_contact 
												 FROM guardian, contact_method
												 WHERE guardian.id = '$parent_id'
												 AND contact_method.id = guardian.pref_contact");
	$num_students = $this->PoolShark->get_var("SELECT COUNT(*) FROM student WHERE guardian_id = '$parent_id'");

	$student_info = $this->PoolShark->get_results("SELECT student.id, student.first_name AS kid_fname,
												student.last_name AS kid_lname,
												student.skill_id AS skill,
												student.active,
												DATE_FORMAT(birthdate, '%Y') AS birthyear,  
												DATE_FORMAT(birthdate, '%m') AS birthmonth, 
												DATE_FORMAT(birthdate, '%d') AS birthday
												FROM student
												WHERE student.guardian_id = '$parent_id'");
	$balance = 0;
	$overdue = 0;
	
	$balance = $this->PoolShark->get_var("SELECT SUM(payment_due)
											FROM class_student, student, class
											WHERE student.guardian_id = '$parent_id'
											AND class_student.student_id = student.id
											AND class_student.class_id = class.id
											AND class.session_id = '$enroll'");
	
	$overdue = $this->PoolShark->get_var("SELECT SUM(payment_due)
											FROM class_student, student, class
											WHERE student.guardian_id = '$parent_id'
											AND class_student.student_id = student.id
											AND class_student.class_id = class.id
											AND class.session_id = '$current'");
	$total_bal = $overdue;
	if ($enroll != $current)
		$total_bal += $balance;
	
	$debug .= 'balance is ' . $balance . ' overdue is ' . $overdue . ' sum is ' . $total_bal . '<br />';
					
	$output .= 'Currently Enrolling for '  . $SessionName . '<br />';	
	//$output .='(current is ' . $current . ')<br />';				
	if ($contact_info)
	{
		if ($contact_info->provisional == 1)
			$provisional = 1;
			
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
		$output .= '<td><input maxlength="48" name="new_balance" size="20" type="text"';
		$output .= ' value="' . $total_bal . '" /></td></tr>';
//		if ( !$provisional)
//		{
//			$output .= '<tr><td><strong>Registration Status</strong></td>';
//			$output .= '<td><select name=new_registration>'. $this->GetRegistrationOptions($contact_info->registration) . '</select></td></tr>';
//		}
		$output .= '<tr><td><strong>Notes</strong></td>';
		$output .= '<td><input maxlength="240" name="new_notes" size="60" type="text"';
		$output .= ' value="' . $contact_info->notes . '" /></td></tr>';
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
//		$output .= '<tr><td colspan=2><input type="submit" name="update_family" value="Update Contact Info" /></td></tr>';	
		$output .= '</table>';
	}
	if ($num_students > 0)
	{
	$output .= $num_students . ' students. <br />';
	
//	if ($provisional)
//	{
//		$output .= 'Enroll in Session: ';
//		$output .= '<select name="session_select">';
//		$output .=  $FutureSessions;
//		$output .= '</select>';
//	}
	

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
			if (!$provisional)
			{
				$output .= '<tr><td>History</td><td></td></tr>';
				$class_info = $this->PoolShark->get_results("SELECT skill.name AS skill, 
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
				$debug .= 'enroll option has already_enrolled = ' . $already_enrolled . '<br />';
				$openclasses = $this->GetAllClassesOption($enroll, $already_enrolled, 0);
				$output .= '<tr><td>Enroll in ' . $SessionName . '</td><td> ';
				$output .= '<select name="' . $tag . '_class">';	
			    $output .= $openclasses;
				$output .= '</select><br />';			
				$output .= '</td></tr>';
			}
			$output .= '</table>';
		}
	//	$output .= '<input type="submit" name="update_family" value="Update Student Info" /><br /><br />';
	} //student_info
	

	} // end if num_students > 0
	$output .= ' <input type = "radio" name="add_child" value="add_child"> Add a Child <br /><br />';
	


	// now print out their chosen times
	$output .= $this->GetPrefTimeslots($parent_id);	

	if ($provisional == 1)
	{
		// will this be duplicate information?????
		$already = $this->PoolShark->get_results("SELECT id, first_name, last_name FROM guardian 
									WHERE first_name LIKE '$contact_info->guardian_fname'
									AND last_name LIKE '$contact_info->guardian_lname'
									AND provisional = 0");		
		if ($already)
		{
			$output .= 'DATABASE CONTAINS THESE PEOPLE ALREADY.  If this person is among the list, then select that one to replace their information with the New Registration.  If none of these are the right person, proceed with "Approve Registration" <br /><br />'; 
			foreach($already as $in)
			{
				$varname = 'rep' . $in->id;
				$output .= '<input type="checkbox" name="' . $varname . '" ';
				$output .= ' value="yes" >';
				$output .= $in->first_name . ' ' . $in->last_name . ' <br />'; 
			}
		}
				
		$output .= '<table>';
		$output .= '<tr><td><input type="submit" name="update_family" value="Approve Registration" />';
		if ($already)
			$output .= ' <input type="submit" name="update_family" value="Replace Info" /> ';
		$output .= ' <input type="submit" name="update_family" value="Remove This Family" /> </td></tr>';
		$output .= '</table>';		
	}
	else
		$output .= '<input type="submit" name="update_family" value="Update Information" /><br /><br />';

	$debug .= $output;
//	return $debug;
return $output;		
}


function UpdateFamily()
{

$parent_id = 0;
$parent_id = get_contact();
$student_id = 0;
$student_id = get_student();
$output = '';
$enroll = $this->enroll_session();
$current = $this->current_session();
$num_students = 0;
$just_enrolled_in_class = 0;
$warnings = '';

if ($parent_id != 0 AND !isset($_POST['update_family']))
{
	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="update_contactid" value="' . $parent_id . '"><br />';
	$output .= $this->GetUpdateFamilyTable( $parent_id );
	$output .= '</form>';
}
else if (isset($_POST['update_family']) AND $_POST['update_family'] == "Update Information")
{
												
	$num_students = $this->PoolShark->get_var("SELECT COUNT(*) FROM student WHERE guardian_id = '$_POST[update_contactid]'");	
	$transaction_cost = 0;
	if ($num_students > 0)
	{
		$student_info = $this->PoolShark->get_results("SELECT student.first_name AS kid_fname,
												student.last_name AS kid_lname, student.id,
												student.skill_id AS skill, student.active,
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
			$debug .= 'child ' . $tag . ' is active? ' . $child->active . '<br />';
			// find out if new data is different from old.	
			$firstname = $tag . '_firstname';
			$lastname = $tag . '_lastname';
			$birthyear = $tag . 'DateOfBirth_Year';
			$birthmonth = $tag . 'DateOfBirth_Month';
			$birthday= $tag . 'DateOfBirth_Day';
			$skill = $tag . '_level';
			$active = $tag . '_active';
			$class = $tag . '_class';
			
			if (!isset($_POST[$active]))
			{
				$debug .= 'Post active was not set <br />';
				$_POST[$active] = 0;
			}
				
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
				$debug .= ' new active is ' . $_POST[$active] . '<br />';
				$this->PoolShark->update('student', array( 'first_name' => $_POST[$firstname], 
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
			$debug .= 'class_id is ' . $class_id . '<br />';
			
			if ($class_id != 0)
			{
				$this->PoolShark->update('guardian', array('registration' => 1),
											     array('id' =>$parent_id),
												 array('%d'),
												 array('%d'));// don't re-enroll student in class
				//$already_enrolled = 0;
				$this->PoolShark->update('student', array('active'=>1),
												array('id'=>$chld->id),
												array('%d'),
												array('%d'));
			
				$class_price = $this->PoolShark->get_var("SELECT price FROM class WHERE id = '$class_id'");
			}
			else
				$class_price = $this->PoolShark->get_var("SELECT price FROM session WHERE id = '$enroll'");
				
			$debug .= 'class_price is ' . $class_price . '<br />';	

			
			$already_enrolled = $this->PoolShark->get_row( "SELECT class_id, class_student.id AS cs_id, price 
									FROM class_student, class 
									WHERE class.session_id = '$enroll'
									AND class_student.class_id = class.id
									AND student_id = '$child->id'");
			$debug .= 'already_enrolled in class ' . $already_enrolled->class_id . '<br />';
			$remove_from = $this->PoolShark->get_var("SELECT class_student.id AS cs_id
													FROM class_student
													WHERE class_student.class_id = '0'
													AND student_id = '$child->id'");
			$debug .= 'remove from = ' . $remove_from . '<br />';
			
			if ((!$already_enrolled || !isset($already_enrolled)) && $class_id != 0 && (!$remove_from || !isset($remove_from)))
			{	
				
				$debug .= 'inserting <br />';
				$transaction_cost += $class_price;
				$this->PoolShark->insert( 'class_student', array( 'student_id' => $child->id,
                                                           'class_id'   => $class_id, 
                                                           'payment_due' => $class_price),
                                                           array( '%s', '%s', '%d') );
                
		//	$output .= 'Added student to class. <br />';
			}
			else if ((isset($already_enrolled) && $already_enrolled->class_id != $class_id) ||
				(isset($remove_from) && $class_id != 0))
			{
				$output .= 'Moved student to new class. <br />';
				if ($already_enrolled->class_id)
					$csid = $already_enrolled->cs_id;
				else
					$csid = $remove_from;
				
				$debug .= 'csid = ' . $csid . '<br />';
				$this->PoolShark->update('class_student', array('class_id'=> $class_id, 
																'payment_due'=>$class_price),
													array('id' => $csid),
													array('%d', '%d'),
													array('%d'));
			}
			if ($class_id != 0)
			{
					$cur_level = $this->PoolShark->get_var("SELECT skill_id FROM class WHERE class.id = '$class_id'");
				if ($cur_level == 0)
					$this->PoolShark->update('class', array( 'skill_id' => $_POST[$skill]), 
										array( 'id' => $class_id ), 
											array( '%d' ), 
											array( '%d' ));
			}				
 		} // for each child
	}	// if student_info	
	
	if ($_POST['add_child'] == "add_child")
	{
		$this->PoolShark->insert('student', 
						array(  'guardian_id' => $_POST['update_contactid'],
								'active' => "1"),
						array( '%d', '%d'));
	}
	}	// if num_students > 0
	
	
	
	$parent_info = $this->PoolShark->get_row("SELECT guardian.id AS parent_id, 
												 guardian.first_name AS guardian_fname, 
												 guardian.last_name AS guardian_lname,
												 registration, notes,
												 home_phone, cell_phone, email, contact_method.id AS pref_contact 
												 FROM guardian, contact_method
												 WHERE contact_method.id = guardian.pref_contact
												 AND guardian.id = '$_POST[update_contactid]'");
	if ($parent_info)
	{
		$debug .= 'home_phone is ' . $parent_info->home_phone . ' newhomephone = ' . $_POST['new_homephone'] . '<br />';
	// find out if new data is different from old.	
	if (($_POST['new_firstname'] != $parent_info->guardian_fname) ||
		($_POST['new_lastname']  != $parent_info->guardian_lname) ||
		($_POST['new_registration']  != $parent_info->registration) ||
		($_POST['new_notes']  != $parent_info->notes) ||				
		($_POST['new_homephone']  != $parent_info->home_phone) ||
		($_POST['new_cellphone']  != $parent_info->cell_phone) ||
		($_POST['new_email']  != $parent_info->email) ||
		($_POST['new_contact']  != $parent_info->pref_contact))
		{
			$this->PoolShark->update('guardian', array( 'first_name' => $_POST['new_firstname'], 
													'last_name' => $_POST['new_lastname'],
													'registration' => $_POST['new_registration'],
													'notes' => $_POST['new_notes'],
													'home_phone' => $_POST['new_homephone'],
													'cell_phone' => $_POST['new_cellphone'], 
													'email' => $_POST['new_email'], 
													'pref_contact' => $_POST['new_contact']), 
											array( 'id' => $parent_info->parent_id ), 
											array( '%s', '%s', '%s', '%s', '%s', '%s',  '%s', '%d'), 
											array( '%d' ));
			$debug .= 'Updated Contact Info for  ' . $_POST['new_firstname'] . ' ' . $_POST['new_lastname']; 
		}
	

		$overdue_balance = 0;									
		$overdue_balance = $this->PoolShark->get_var("SELECT SUM(payment_due) 
											FROM class_student, student, class
											WHERE student.guardian_id = '$_POST[update_contactid]'
											AND class_student.student_id = student.id
											AND class_student.class_id = class.id
											AND class.session_id = '$current'");
		$balance_info = $this->PoolShark->get_row("SELECT SUM(payment_due) AS total_due,
											COUNT(student.id) AS num_active
											FROM class_student, student, class
											WHERE student.guardian_id = '$_POST[update_contactid]'
											AND class_student.student_id = student.id
											AND class_student.class_id = class.id
											AND class.session_id = '$enroll'");
		
		
		$debug .= 'new_balance in post is ' . $_POST['new_balance'] . '<br />';
		$debug .= 'num_active = ' . $balance_info->num_active . '<br />';
		$total_old_bal = $overdue_balance_info->total_due;
		$new_bal = $balance_info->total_due;
		
		if ($enroll != $current)
			$total_old_bal += $balance_info->total_due;
/*		if ($balance_info->num_active >= 3 && $just_enrolled_in_class)
		{
			$debug .= 'group discount <br />';
			$new_bal = $new_bal - ($new_bal * $this->group_discount_percent);
			$debug .= 'new added balance is ' . $new_bal . '<br />';
		}
	*/
		if ($_POST['new_balance'] != $total_old_bal)
		{
			if ($transaction_cost != 0)
				$warnings .= 'Warning: $'. $transaction_cost . ' that would have been added to balance was not added, because balance was edited manually. <br />';
		
			if ($balance_info->num_active == 0)
				$warnings .= 'Warning: there are no active students in this family, therefore their balance cannot be applied to current students, and may show zero. <br />';
			else
			{
			
				// divide the new balance up among the students in this family
				// who are enrolled in current or next session
			
				$div_bal = $_POST['new_balance']/ $balance_info->num_active;
				$new_bal_per_kid = round( $div_bal, 2);	
				$debug .= 'Updated balance to $' . $new_bal_per_kid . ' for each child enrolled. <br />';
			}
		}
		else if ($transaction_cost != 0)
		{
			if ($balance_info->num_active >= 3)
			{
				$debug .= 'group discount <br />';
				$debug .= 'transaction cost was ' . $transaction_cost . '<br />';
				$transaction_cost = $transaction_cost - ($transaction_cost * $this->group_discount_percent);
				$debug .= 'new trans cost is ' . $transaction_cost . '<br />';	
			}
			if ($balance_info->num_active == 0)
				$warnings .= 'Warning: there are no active students in this family, therefore their balance cannot be applied to current students, and may show zero. <br />';
			else
			{
			
				// divide the new balance up among the students in this family
				// who are enrolled in current or next session
			
				$div_bal = $transaction_cost/ $balance_info->num_active;
				$total_bal = $total_old_bal + $div_bal;
				$new_bal_per_kid = round( $total_bal, 2);	
				$debug .= 'Updated balance to $' . $new_bal_per_kid . ' for each child enrolled. <br />';
			}
		}
	}
	
	if ($num_students > 0)
	{
		$student_info = $this->PoolShark->get_results("SELECT student.id
												FROM student
												WHERE student.guardian_id = '$parent_id'");
		
	if ($student_info)
	{
		$i = 0;
		foreach($student_info as $child)	
		{
			if (isset($new_bal_per_kid))
			{
				$cs_to_update = $this->PoolShark->get_var("SELECT class_student.id 
														FROM class_student, class 
														WHERE class_student.student_id = '$child->id'
														AND class.id = class_student.class_id
														AND class.session_id = '$enroll'");
				
				if ($cs_to_update)
				{
						$this->PoolShark->update('class_student', 
												array( 'payment_due' => $new_bal_per_kid),
												array( 'id' => $cs_to_update),
												array('%s'),
												array('%d'));
				}
			}
		}
	}
}

	$this->SavePrefTimeslots($_POST['update_contactid']);
	
	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="update_contactid" value="' . $_POST['update_contactid'] . '"><br />';
	$output .= $this->GetUpdateFamilyTable($_POST['update_contactid']);
	$output .='</form>';
	

}
else if (isset($_POST['update_family']) AND $_POST['update_family'] == "Approve Registration")
{
	$session_name = $this->PoolShark->get_var("SELECT name FROM session WHERE id = '$_POST[session_select]'");
	$this->PoolShark->update('guardian', array( 'provisional' => 0),
										array( 'id' => $_POST['update_contactid']),
												 array( '%d'),
												 array ('%d'));
	$output .= 'Approved ' . $_POST['new_firstname'] . ' ' . $_POST['new_lastname'] . ' their registration is no longer pending.<br />'; 
	
	$new_students = $this->PoolShark->get_results("SELECT id, first_name,
												last_name, birthdate
												FROM student
												WHERE student.guardian_id = '$_POST[update_contactid]'");
	if ($new_students)
	{
		foreach($new_students as $news)
		{
			$this->PoolShark->insert('students_to_schedule', 
					array(  'session_id' => $_POST['session_select'], 
							'student_id' => $news->id),
					array( '%d', '%d'));
			$output .= 'Enrolled Student ' .$news->first_name . ' ' . $news->last_name . ' in ' . $session_name . ' <br />';
		}
	}	

	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="update_contactid" value="' . $_POST['update_contactid'] . '"><br />';
	$output .= $this->GetUpdateFamilyTable($_POST['update_contactid']);
	$output .='</form>';
}
else if (isset($_POST['update_family']) AND $_POST['update_family'] == "Replace Info")
{				
	$session_name = $this->PoolShark->get_var("SELECT name FROM session WHERE id = '$_POST[session_select]'");
	// figure out who we're replacing.
	$already = $this->PoolShark->get_results("SELECT id, first_name, last_name FROM guardian 
									WHERE first_name LIKE '$_POST[new_firstname]'
									AND last_name LIKE '$_POST[new_lastname]'
									AND provisional = 0");		
	$updated_id = 0;
	if ($already)
	{
		foreach($already as $in)
		{
			
			$varname = 'rep' . $in->id;
			if (isset($_POST[$varname]) && $_POST[$varname] == "yes")
			{
				$updated_id = $in->id;
				// replace guardian info
				$this->PoolShark->update('guardian', array( 'first_name' => $_POST['new_firstname'], 
													'last_name' => $_POST['new_lastname'],
													'balance' =>  $_POST['new_balance'],
													'home_phone' => $_POST['new_homephone'],
													'cell_phone' => $_POST['new_cellphone'],
													'email' => $_POST['new_email'], 
													'pref_contact' => $_POST['new_contact'],
													'provisional' => 0),
											array( 'id' => $in->id ), 
											array( '%s', '%s', '%s', '%s', '%s',  '%s', '%d', '%d'), 
											array( '%d' ));		


				// remove the provisional one
				$this->PoolShark->query("DELETE FROM guardian WHERE id = '$_POST[update_contactid]'");			
				$this->PoolShark->query("DELETE FROM guardian_pref_timeslot WHERE guardian_id = '$_POST[update_contactid]'");
				
				// find old students
				$old_students = $this->PoolShark->get_results("SELECT id, first_name, last_name, birthdate, guardian_id
														FROM student 
														WHERE guardian_id = '$in->id'");
															
				$new_students = $this->PoolShark->get_results("SELECT id, first_name,
												last_name, birthdate
												FROM student
												WHERE student.guardian_id = '$_POST[update_contactid]'");

				if ($old_students)
				{
					foreach($old_students as $olds)
					{
						foreach($new_students as $news)
						{
							if( $olds->first_name == $news->first_name && $olds->guardian_id == $in->id)
							{
								$this->PoolShark->update('student', array( 'first_name' => $news->first_name,
																		   'last_name' => $news->last_name,									
																		   'birthdate' => $news->birthdate,
																		   'active' => 1), 
														array( 'id' => $olds->id ), 
													array( '%s', '%s', '%s', '%d', '%d' ), 
													array( '%d' ));
								$this->PoolShark->insert('students_to_schedule', 
											array(  'session_id' => $_POST['session_select'], 
													'student_id' => $olds->id),
											array( '%d', '%d'));
								$output .= 'Enrolled Student ' .$olds->first_name . ' ' . $olds->last_name . ' in ' . $session_name . ' <br />';

								$this->PoolShark->query("DELETE FROM student WHERE id = '$news->id'");
								
							}
						}
					}
				}
				// now, are there any new students left?
				$new_students = $this->PoolShark->get_results("SELECT id, first_name,
												last_name, birthdate
												FROM student
												WHERE student.guardian_id = '$_POST[update_contactid]'");
				// if so, update their guardian_id to the old parent
				if ($new_students)
				{
					foreach($new_students as $news)
					{
					$this->PoolShark->update('student', array ('guardian_id' => $in->id),
												array('id' => $news->id),
												array('%d'),
												array('%d'));
					
					$this->PoolShark->insert('students_to_schedule', 
								array(  'session_id' => $_POST['session_select'], 
										'student_id' => $news->id),
								array( '%d', '%d'));
					$output .= 'Enrolled Student ' .$news->first_name . ' ' . $news->last_name . ' in ' . $session_name . ' <br />';

					}
				}
			}
		}		
	}
	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="update_contactid" value="' . $updated_id . '"><br />';
	$output .= $this->GetUpdateFamilyTable($updated_id);
	$output .='</form>';		 
}
else if (isset($_POST['update_family']) AND $_POST['update_family'] == "Remove This Family")
{
		$this->PoolShark->query("DELETE FROM guardian WHERE id = '$_POST[update_contactid]'");
		$this->PoolShark->query("DELETE FROM student WHERE guardian_id = '$_POST[update_contactid]'");
		$this->PoolShark->query("DELETE FROM guardian_pref_timeslot WHERE guardian_id = '$_POST[update_contactid]'");
		$output .= 'Completely removed ' . $_POST['new_firstname'] . ' ' . $_POST['new_lastname'] . ' and everything about them. You will not be able to undo this.<br />'; 
}
$warnings_out = $warnings;
$warnings_out .= $output;
$debug .= $output;
//return $debug;
//return $output;	
return $warnings_out; 
}


function SavePrefTimeslots( $guardian_id)
{
	$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday');
	$class_slots = time_range('8:00am', '9:00pm', '30 mins');

	foreach($days as $day)
	{
		foreach ($class_slots as $slot)
		{
			$find_time = date('H:i:s', $slot);
			$ez_time = date('g:ia', $slot); 
			$varname = $day . $find_time;
			$slot = $this->PoolShark->get_var("SELECT time_slot.id AS id 
												FROM time_slot, guardian_pref_timeslot
												WHERE guardian_pref_timeslot.guardian_id = '$guardian_id'
												AND guardian_pref_timeslot.timeslot_id = time_slot.id
												AND time_slot.weekday = '$day'
												AND time_slot.start_time = CAST('$find_time' AS time)");
																							

			if (!$slot && isset($_POST[$varname]) AND $_POST[$varname] == "yes")
			{
				$timeslot_id = 0;
				$timeslot_id = $this->PoolShark->get_var("SELECT MAX(id) FROM time_slot
															WHERE weekday = '$day'
															AND start_time = '$find_time'");
				
				if ($timeslot_id == 0)
				{	$this->PoolShark->insert( 'time_slot', array ('weekday' => $day,
															  'start_time' => $find_time),
													   array ( '%s', '%s' ));
					$timeslot_id = $this->PoolShark->get_var("SELECT MAX(id) FROM time_slot
															WHERE weekday = '$day'
															AND start_time = '$find_time'");
				}										
				
				$this->PoolShark->insert( 'guardian_pref_timeslot', array('guardian_id' => $guardian_id,
																  'timeslot_id' => $timeslot_id),
																  array('%d', '%d'));
			}
			else if ($slot > 0 && (!isset($_POST[$varname]) || $_POST[$varname] == "no"))
			{
				// remove from guardian_pref_timeslot
				$this->PoolShark->query("DELETE FROM guardian_pref_timeslot 
										WHERE guardian_id = $guardian_id 
										AND timeslot_id = $slot");			
			}
		}
	}	
}
	
	
function GetPrefTimeslots( $guardian_id )
{
$output = '';	
$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday');
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
		$class = $this->PoolShark->get_row("SELECT time_slot.id AS id 
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
	


function get_teaching_schedule( $session_id, $editable )
{
$output = '';	
$teachers = $this->PoolShark->get_results("SELECT name, id FROM teacher");
$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday');
$hour_slots = time_range( '8:00am', '9:00pm', '60 mins');
$class_slots = time_range('8:00am', '9:00pm', '30 mins');

$output .= '<table class="manage">';
foreach($days as $day)
{
	$output .= '<tr><th colspan=28>' . $day . '</th></tr>';
	$output .= '<tr><td>Teacher</td>';
	foreach ($hour_slots as $slot)
	{
		$output .='<td>' . date('g',$slot) . '</td><td></td>';
	}
	$output .= '</tr>';
	
	if ($teachers){
	foreach($teachers as $teacher)
	{
		$output .= '<tr><td>' . $teacher->name . '</td>';
		foreach ($class_slots as $slot)
		{			
			$find_time = date('H:i:s', $slot);
			$class = NULL;
			$class = $this->PoolShark->get_row("SELECT class.id AS id 
													FROM class, time_slot, class_timeslot, teacher
													WHERE class.session_id = '$session_id'
													AND class.teacher_id = teacher.id
													AND teacher.name = '$teacher->name'
													AND class_timeslot.class_id = class.id
													AND class_timeslot.timeslot_id = time_slot.id
													AND time_slot.weekday = '$day'
													AND time_slot.start_time = CAST('$find_time' AS time)");
			
			$output .='<td>';
			if ($editable)
			{ 	
				$varname = $teacher->name . $day . $find_time;
//				$output .= $varname;
				$output .= '<input type="checkbox" name="' . $varname . '" ';
				if ($class)
				     $output .= ' checked ';
				$output .= ' value="yes" >';
			}
//			else if ($class)
//				$output .= '<a href="../class_info?class_id='. $class->id . '">' . $class->shortname . '" </a>';
//			else
//				$output .= ' ';
			$output .= '</td>';
		}
		$output .= '</tr>';
	}
	}
}
$output .='</table>';
	
return $output;
}

function RemoveSession()
{
	$output = '';
	$sessions = $this->PoolShark->get_results( "SELECT id, name, start_date, end_date
												FROM session ORDER BY end_date DESC ");
	if (!isset($_POST["RemoveSession"]) )
	{
		if ($sessions)
		{
			$output .= 'Remove a Session.<br />';
			$output .= '<form method="post" action="">';
			$output .= '<table class="schedule">';
			$output .= '<tr><th></th><th>Session Name</th><th>Start Date</th><th>End Date</th></tr>';
			foreach( $sessions as $session)
			{
				$tag = 'session' . $session->id;
				$output .= '<tr><td><input type="checkbox" name="' . $tag . '" value="1"></td>';
				$output .= '<td>' . $session->name . '</td>';
				$output .= '<td>' . $session->start_date . '</td>';
				$output .= '<td>' . $session->end_date . '</td></tr>';
			}
			$output .= '</table><br /><input type="submit" name="RemoveSession" value="Remove Session" />';
			$output .= '</form>';	
		}
		return $output;
	}
	
	if ($sessions)
	foreach( $sessions as $s)
	{
		$tag = 'session' . $s->id;
		if (isset($_POST[$tag]) && $_POST[$tag] == 1)
		{
			$this->PoolShark->query("DELETE FROM session WHERE id = '$s->id'");
			$output .= 'Removed ' . $s->name . '.<br />';
		}
	}
	return $output;
}

function DefineSession()
{
	$output = '';
	$output .= 'Add a new Session.<br />';
	$output .= '<form method="post" action="../session-created/">';
	$output .= '<table class="schedule">';
	$output .= '<tr><td>Session Name:</td>';
	$output .= '<td><input maxlength="80" name="session_name" size="60" type="text" /></td></tr>';
	$output .= '<tr><td>Starts On:</td>';
	$output .= '<td>' . birthdate_combo( 'sd_', 0, 0, date("Y")) . '</td></tr>';
	$output .= '<tr><td>Ends On:</td>';
	$output .= '<td>' . birthdate_combo( 'ed_', 0, 0, date("Y")) . '</td></tr>';	
	$output .= '<tr><td>Cost: </td>';
	$output .= '<td><input maxlength="15" name="session_cost" size="10" type="text" /><br /></td></tr>';
	$output .= '</table><br /><input type="submit" name="create_session" value="Create Session" />';
	$output .= '</form>';	

	return $output;
}

function CreateSession()
{
	$output = '';
	if (isset($_POST['create_session']))
	{
	$session_start_date =  $_POST['sd_DateOfBirth_Year'] . '-'; 
	$session_start_date .=	$_POST['sd_DateOfBirth_Month'] . '-';
	$session_start_date .=	$_POST['sd_DateOfBirth_Day'];

	$session_end_date =  $_POST['ed_DateOfBirth_Year'] . '-'; 
	$session_end_date .=	$_POST['ed_DateOfBirth_Month'] . '-';
	$session_end_date .=	$_POST['ed_DateOfBirth_Day'];

	$this->PoolShark->insert( 'session', array( 'name' => $_POST['session_name'], 
												'start_date' => $session_start_date,
												'end_date' => $session_end_date,
												'price' => $_POST['session_cost']), 
										 array( '%s', '%s', '%s', '%d') );

	$session_id = $this->PoolShark->get_var("SELECT id FROM session WHERE name = '$_POST[session_name]'");	
	$output .= 'New Session ' . $_POST['session_name'] . ' ' . $session_id . ' created successfully. <br />' ;
	$output .= '<a href=../add-client/> Add New students. </a><br />';
	$output .= '<a href=../register-students/?session_id=' . $session_id . '> Register students for this session. </a><br />';
	$output .= '<a href=../teaching-schedule/?session_id=' . $session_id . '> Select teaching times for this session </a><br />';
	}
	else
	{
		$output .= '<a href = "../add-new-session/"> Add a new session.</a>';
	}
	return $output;	
}



function ShowRegistered()
{
$session_id = 0;
$session_id = get_session();
$output = '';
if ($session_id == 0 AND !isset($_POST['session_id']))
{
$output = 'Show Students Registered for What Session?<br >';
$FutureSessions = $this->GetAllSessionsOption(0);
$output .= '<form method="post" action="">';
$output .= '<select name="session_id">';
$output .= $FutureSessions;
$output .= '</select>';
$output .= '<br /><br />';
$output .= '<input type="submit" value="Show Students in Session" />';
$output .= '</form>';
return $output;
}
else if (isset($_POST['register']))
{
$session_id = $_POST['session_id'];

$session_name = $this->PoolShark->get_var("SELECT name FROM session WHERE id = '$_POST[session_id]'");

$students = $this->PoolShark->get_results("SELECT student.id AS stid FROM student");
$enrolled = $this->PoolShark->get_results("SELECT student_id FROM students_to_schedule WHERE session_id = '$_POST[session_id]'");


if ($students)
{		
	foreach($students AS $s)
	{
		$varname = 'reg' . $s->stid;
		if (isset($_POST[$varname]) && $_POST[$varname] == "yes")
		{		
			$already_enrolled = 0;
			if ($enrolled)
			{
				foreach($enrolled AS $e)
				{
					if ( $e->student_id == $s->stid)
						$already_enrolled = 1;
				}	
			}
			if ($already_enrolled == 0)
			{
				$this->PoolShark->insert('students_to_schedule', 
										array( 'session_id' => $_POST['session_id'], 
												'student_id' => $s->stid),
										array( '%d', '%d'));
			}
		}
	}
}
}

$session_name = $this->PoolShark->get_var("SELECT name FROM session WHERE id = '$_POST[session_id]'");

$registered_students = $this->PoolShark->get_results( "SELECT student.id AS stid, 
						student.first_name as kid_fname, student.last_name as kid_lname
						FROM students_to_schedule, student
						WHERE students_to_schedule.student_id = student.id
						AND students_to_schedule.session_id = '$_POST[session_id]' 
						ORDER BY kid_lname");	
if ($registered_students)
{
	$output .= 'Registered students for ' .  $session_name . '. <br />';
	foreach($registered_students as $student)
	{
		$output .= $student->kid_fname . ' ' . $student->kid_lname . ' <br />';
	}

	$output .= '<a href=../teaching-schedule/?session_id=' . $session_id . '> Select teaching times for this session </a><br />';
}
else
{
	$output .= 'No Students registered for ' . $session_name . '. <br />';
}

return $output;
}

function RegisterNew()
{
 	$parent_id = 0;	
	$output = '';
	if (isset($_POST['RegisterNew']) && $_POST['RegisterNew'] == "Next") // step 2
	{
		$current_session = 0;
		$this->PoolShark->insert( 'guardian', array( 'first_name'  => $_POST['pfirstname'],
                                                'last_name'  => $_POST['plastname'],
                                               'home_phone' => $_POST['homephone'],
                                               'cell_phone' => $_POST['cellphone'],
                                               'work_phone' => $_POST['workphone'],
                                               'email'      => $_POST['email'],
                                               'pref_contact' => $_POST['contact_select']),
                                               array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ) );
        $relative_start = $this->PoolShark->get_var("SELECT DATEDIFF(start_date, CURDATE()) AS relative_start FROM session WHERE id = '$_POST[session_id]'");
        if ($relative_start < 0)
			$current_session = 1;
        
       	$parent_id = $this->PoolShark->get_var("SELECT MAX(id) FROM guardian
                                                     WHERE first_name = '$_POST[pfirstname]'
                                                     AND last_name = '$_POST[plastname]'");
       	$output .= 'Registered  '.$_POST['pfirstname'].' '. $_POST['plastname'] . '<br />';     	
		$output .= '<strong>Step 2 (of 3) Enter Students</strong><br />';
		if (is_numeric($_POST['num_students']) && $_POST['num_students'] > 0 && $_POST['num_students'] < 15)
		{
			$output .= '<form method="post" action="">';
			$output .= '<input type="hidden" name="guardian_id" value="' . $parent_id . '">';
			$output .= '<input type="hidden" name="num_students" value="' . $_POST['num_students'] . '">';
			$output .= '<input type="hidden" name="session_id" value="'. $_POST['session_id'] . '">';
			$output .= '<input type="hidden" name="current_session" value="' . $current_session . '">';
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
				$output .= '<tr><td>Skill:</td>';
				$output .= '<td><select name="' . $tag . '_skill">';
				$output .= $levels;
				$output .= '</select></td></tr>';
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
		$schedule_list = '';
		$num_students = $_POST['num_students'];
		$openclasses = $this->GetAllOpenClassesOption($_POST['session_id'], 0, 0);
		
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
			$student_id = 0;	
			$this->PoolShark->insert( 'student', array(  'first_name' =>$firstname,
										 'last_name'  =>$lastname,
                                         'guardian_id' =>$_POST['guardian_id'],
                                         'birthdate' =>$birthdate, 
                                         'skill_id' => $_POST[$skillvar]),
                                  array( '%s', '%s', '%d', '%s', '%d') );
			$student_id = $this->PoolShark->get_var("SELECT id FROM student 
												WHERE first_name = '$firstname' 
												AND last_name = '$lastname'
												AND guardian_id = '$_POST[guardian_id]'
												AND birthdate='$birthdate'");
				
			if($_POST['current_session'] == 0)
			{
				$this->PoolShark->insert( 'students_to_schedule', 
											   array( 'student_id' => $student_id, 
												     'session_id' => $_POST['session_id']),
	 										   array( '%d', '%d'));
			}
			else
			{
				$schedule_list .= 'Enroll ' .$firstname . ' ' .$lastname . ' in ';
				$schedule_list .= '<input type="hidden" name="' . $tag . '_id" value='. $student_id .'">';
				$schedule_list .= '<input type="hidden" name="' .$tag .'_skill" value=' . $_POST[$skillvar] .'">';
				$schedule_list .= '<select name="' . $tag . '_class">';	
			    $schedule_list .= $openclasses;
				$schedule_list .= '</select><br />';			
			}
			$output .= '<br />Registered '.$firstname . ' '. $lastname . '<br />';		
		}
		if ($_POST['current_session'] == 0)
		{
			$parent = $this->PoolShark->get_row("SELECT first_name, last_name
											FROM guardian
											WHERE id = '$_POST[guardian_id]'");
			$output .= '<br /><br /><strong>Check all times that the ' . $parent->first_name . ' ' . $parent->last_name . ' family prefers to come for swim class.<br />';
			$output .='Checking more options is better. <br /></strong>';
			$output .= '<form method="post" action="">';
			$output .= '<input type="hidden" name="guardian_id" value="' . $_POST['guardian_id'] . '">';
			$output .= '<input type="hidden" name="session_id" value="'. $_POST['session_id'] . '">';
			$output .= $this->GetPrefTimeslots(0);					
			$output .= '<input type="submit" name="RegisterNew" value="Done!" /><br /><br />';
			$output .= '</form><br />';
			$output .= '<br /><br />';
		}
		else
		{
			$output .= '<br /><form method="post" action="">';
			$output .= '<input type="hidden" name="guardian_id" value="' . $_POST['guardian_id'] . '">';
			$output .= '<input type="hidden" name="session_id" value="'. $_POST['session_id'] . '">';
			$output .= '<input type="hidden" name="current_session" value="'. $_POST['current_session'] .'">';
			$output .= '<input type="hidden" name="num_students" value="'. $num_students . '">';
			$output .= $schedule_list;
			$output .= '<br /><input type="submit" name="RegisterNew" value="Done!" /><br /><br />';
			$output .= '</form><br />';	
		}
	}	
	else if (isset($_POST['RegisterNew']) && $_POST['RegisterNew'] == "Done!")
	{	
	if ($_POST['current_session'] == 0)
	{
		$this->SavePrefTimeslots($_POST['guardian_id']);
		$output .= 'Registration was successful, and preferred class times saved.<br />';	

	}
	else
	{	
		for ($i = 0; $i < $_POST['num_students']; $i++)
		{
			$tag = 'c' . $i;
			$student_id = $_POST[ $tag.'_id' ];
			$class_id = $_POST[ $tag.'_class' ];
			$skill_id = $_POST[ $tag.'_skill' ];
			$class_price = $this->PoolShark->get_var("SELECT price FROM class WHERE id = '$class_id'");
			// don't re-enroll student in class
			$already_enrolled = 0;
			$already_enrolled = $this->PoolShark->get_var( "SELECT student_id FROM class_student 
				WHERE class_id = '$class_id'
				AND student_id = '$student_id'");
				
			if ($already_enrolled == 0 || !isset($already_enrolled))
			{	
				$this->PoolShark->insert( 'class_student', array( 'student_id' => $student_id,
                                                           'class_id'   => $class_id,
                                                           'payment_due' => $class_price),
                                                           array( '%s', '%s') );
			}
            $cur_level = $this->PoolShark->get_var("SELECT skill_id FROM class WHERE class.id = '$class_id'");
			if ($cur_level == 0)
				$this->PoolShark->update('class', array( 'skill_id' => $skill_id), 
										array( 'id' => $class_id ), 
											array( '%d' ), 
											array( '%d' ));
			$stname = $this->PoolShark->get_row("SELECT first_name, last_name FROM student WHERE id = '$student_id'");
 			$output .= 'Added ' . $stname->first_name . ' '. $stname->last_name . ' to class. <br />';
		}
	}
	
	return $output;
	}
	else  /* the initial page */
	{
	$output = '';
	$output = 'Register for What Session?<br >';
	$FutureSessions = $this->GetFutureSessionsOption(0);
	$output .= '<form method="post" action="">';
	$output .= '<select name="session_id">';
	$output .= $FutureSessions;
	$output .= '</select>';
	$output .= 'hello? <br />';
	$output .=  '<br /><h2>Register new student(s) for Swimming Lessions.  <br /> </h2>';	
	$output .= 'Step 1 (of 3) <br />';
	$output .= '<table>';
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
	$output .= '<br />';
	$output .= '<input type="submit" name="RegisterNew" value="Next" />';
	$output .= '</form>';	
		
	}

	return $output;
}


function RegisterExistingStudents()
{

$session_id = 0;
$session_id = get_session();

if ($session_id == 0 AND !isset($_POST['session_select']))
{
$output = 'Register Students for What Session?<br >';
$FutureSessions = $this->GetFutureSessionsOption(0);
$output .= '<form method="post" action="">';
$output .= '<select name="session_select">';
$output .= $FutureSessions;
$output .= '</select>';
$output .= '<br /><br />';
$output .= '<input type="submit" value="Register for Session" />';
$output .= '</form>';
return $output;
}
else
{
$output = 'session_select = ' . $_POST['session_select'];
$session_name = $this->PoolShark->get_var("SELECT name FROM session WHERE id = '$_POST[session_select]'");
$output = 'Register for ' . $session_name . '<br />';

$session_id = $_POST['session_select'];

$clients = $this->PoolShark->get_results( "SELECT student.id AS stid, 
						student.first_name as kid_fname, student.last_name as kid_lname, 
						birthdate, 
						DATE_FORMAT(NOW(), '%Y') - 
						DATE_FORMAT(birthdate, '%Y') -  
						(DATE_FORMAT(NOW(), '00-%m-%d') < 
						DATE_FORMAT(birthdate, '00-%m-%d')) AS age,
						skill.name AS level,
						guardian.first_name as guardian_fname, guardian.last_name as guardian_lname 
						FROM student, guardian, skill 
						WHERE student.guardian_id = guardian.id
						AND student.id > 0
						AND student.skill_id = skill.level
						ORDER BY kid_lname");

if ($clients)
{
	$output .= '<form method="post" action="../students-registered/" ><table class="manage">';
	$output .= '<input type="hidden" name="session_id" value=' . $session_id . '>';
	$output .= '<tr><th rowspan=2>Enroll?</th><th colspan=2>Student</th><th rowspan=2>Age</th><th rowspan=2>Level</th><th rowspan=2>Contact</th></tr>';
	$output .= '<tr><th>Last Name</th><th>First Name</th></tr>';
	foreach( $clients as $student)
	{
		$varname = 'reg' . $student->stid;
		$enrolled = $this->PoolShark->get_var( "SELECT student_id FROM students_to_schedule 
												WHERE session_id = '$session_id'
												AND student_id = '$student->stid'");
												  
		$output .= '<tr><td><input type="checkbox" name="' . $varname .  '" ';
		if ($enrolled)
		     $output .= ' checked ';
		$output .= ' value="yes" ></td><td>';
		$output .= $student->kid_lname . ' </td><td> ' . $student->kid_fname . ' </td><td> ';
		$output .= $student->age . '</td><td> ' . $student->level . ' </td><td> ';
		$output .= $student->guardian_fname;
		if ($student->guardian_lname != $student->kid_lname)
		{
			$output .= ' ' . $student->guardian_lname;
		}
		$output .= ' </td></tr>';
		
	}
	$output .= '</table>';	
	$output .= '<input type="submit" name="register" value="Register for Session" />';
	$output .= '</form>';
}
else
{
	$output = 'No students to add';
}
	
}
return $output;
}

/* Lessoncount
 * @brief find the number of lessons remaining in this session
 * 
 * @param session
 * @param start_date
 * @param day
 * 
 * @return number of lessons
 */ 
function LessonCount( $session, $start_date, $day)
{
	$num = 0;
	$dayval = 0;
	
	switch($day)
	{
	case "Monday":
		$dayval = 1;
		break;
	case "Tuesday":
		$dayval = 2;
		break;
	case "Wednesday":
		$dayval = 3;
		break;
    case "Thursday":
		$dayval = 4;
		break;
	case "Friday": 
		$dayval = 5;
		break;
	case "Saturday":
		$dayval = 6;
		break;
	}
	$session_data = $this->PoolShark->get_row("SELECT UNIX_TIMESTAMP(start_date) AS start_date, UNIX_TIMESTAMP(end_date) AS end_date FROM session WHERE id='$session'");
	if ($start_date < $session_data->start_date || $start_date > $session_data->end_date)
		$start_date = $session_data->start_date;
	$end_date = $session_data->end_date;
	
	$days_in_session = ($end_date - $start_date) /60 /60 /24;

	$start_dow = date ('w', $start_date);
	$end_dow = date ('w', $end_date);
	
	$partialWeek = false;
	//get partial week day count
    if ($start_dow < $end_dow) 
    {   
		if (($dayval >= $start_dow) && ($dayval <= $end_dow))         
			$partialWeek = true;
    }
    else if ($start_dow == $end_dow)
    {
		if ($dayval == $start_dow)
			$partialWeek = true;
    }
    else 
    {
		if (( $dayval >= $start_dow) || ($dayval <= $end_dow))
			$partialWeek = true;
    }


	$partialWeekCount = 0;
	if ($partialWeek)
		$partialWeekCount = 1;
	
	$numLessons = floor($days_in_session / 7 ) + $partialWeekCount;

    //first count the number of complete weeks, then add 1 if $day falls in a partial week.
    return $numLessons;
}

function LessonCountDebug( $session, $start_date, $day)
{
	$num = 0;
	$dayval = 0;
$debug = '';
	
	switch($day)
	{
	case "Monday":
		$dayval = 1;
		break;
	case "Tuesday":
		$dayval = 2;
		break;
	case "Wednesday":
		$dayval = 3;
		break;
    case "Thursday":
		$dayval = 4;
		break;
	case "Friday": 
		$dayval = 5;
		break;
	case "Saturday":
		$dayval = 6;
		break;
	}
	$debug .= 'dayval: ' .$dayval . '<br />';
	
	$session_data = $this->PoolShark->get_row("SELECT UNIX_TIMESTAMP(start_date) AS start_date, UNIX_TIMESTAMP(end_date) AS end_date FROM session WHERE id='$session'");
	if ($start_date < $session_data->start_date || $start_date > $session_data->end_date)
		$start_date = $session_data->start_date;
	$end_date = $session_data->end_date;
	
	$debug .= 'start_date = ' . $start_date . ' end_date= ' . $end_date . '<br />';
	$days_in_session = ($end_date - $start_date) /60 /60 /24;
	
	$debug .= 'this session is ' . $days_in_session . 'days <br />';
	$debug .= 'start_date is on date ' . date('w', $start_date) . ' and end date is on date ' . date('w', $end_date) . '<br />';

	$start_dow = date ('w', $start_date);
	$end_dow = date ('w', $end_date);
	
	$partialWeek = false;
	//get partial week day count
    if ($start_dow < $end_dow) 
    {   
		if (($dayval >= $start_dow) && ($dayval <= $end_dow))         
			$partialWeek = true;
    }
    else if ($start_dow == $end_dow)
    {
		if ($dayval == $start_dow)
			$partialWeek = true;
    }
    else 
    {
		if (( $dayval >= $start_dow) || ($dayval <= $end_dow))
			$partialWeek = true;
    }

	$partialWeekCount = 0;
	if ($partialWeek)
		$partialWeekCount = 1;
	
	$numLessons = floor($days_in_session / 7 ) + $partialWeekCount;
	$debug .= 'PartialWeekCount = ' . $partialWeekCount . '<br />';
	$debug .= 'numLessons = ' . $numLessons . '<br />';
    //first count the number of complete weeks, then add 1 if $day falls in a partial week.
    return $debug;
}


function TeachingSchedule()
{
	
$session_id = 0;
$session_id = get_session();
$debug = '';
$session_name = $this->PoolShark->get_var("SELECT name FROM session WHERE id = '$session_id'");
$session_price = $this->PoolShark->get_var("SELECT price FROM session WHERE id = '$session_id'");
$lesson_price = $this->PoolShark->get_var("SELECT cost FROM skill WHERE level = 0");

$debug .= 'session_price = ' . $session_price . '<br />';
$debug .= 'lesson_price = ' . $lesson_price . '<br />';

if ($session_id == 0 AND !isset($_POST['session_select']))
{
$output = 'Set Teaching Times for What Session?<br >';
$FutureSessions = $this->GetFutureSessionsOption(0);
$output .= '<form method="post" action="">';
$output .= '<select name="session_select">';
$output .= $FutureSessions;
$output .= '</select>';
$output .= '<br /><br />';
$output .= '<input type="submit" value="Set Schedule" />';
$output .= '</form>';
return $output;
}
else
{
if (isset($_POST['session_select']))
{
	$session_id = $_POST['session_select'];
}		
if (!isset($_POST['teaching_schedule']) )
{

$output = 'Teaching Schedule for ' . $session_name . '<br />';

$output .= '<form method="post" action="">';
$output .= '<input type="hidden" name="session_select" value=' . $session_id . '>';
$output .= 'Check box to teach during each timeslot. Uncheck to cancel class.<br />';

$output .= $this->get_teaching_schedule($session_id, true);
$output .= '<input type="submit" name="teaching_schedule" value="Update Teaching Times" />';
$output .= '</form>';		
}
else
{	
$output = '';
$teachers = $this->PoolShark->get_results("SELECT name, id FROM teacher");
$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday');
$class_slots = time_range('8:00am', '9:00pm', '30 mins');

foreach($days as $day)
{
	if ($teachers){
	foreach($teachers as $teacher)
	{
		foreach ($class_slots as $slot)
		{
			$find_time = date('H:i:s', $slot);
			$varname = $teacher->name .  $day . $find_time;
			$class = $this->PoolShark->get_var("SELECT class.id AS id 
												FROM class, time_slot, class_timeslot, teacher
												WHERE class.session_id = '$session_id'
												AND class.teacher_id = teacher.id
												AND teacher.name = '$teacher->name'
												AND class_timeslot.class_id = class.id
												AND class_timeslot.timeslot_id = time_slot.id
												AND time_slot.weekday = '$day'
												AND time_slot.start_time = CAST('$find_time' AS time)");
			if ($class AND (!isset($_POST[$varname])))
			{
				// remove from class and time_slot
				$this->PoolShark->query("DELETE FROM class WHERE id = '$class'");
				$this->PoolShark->query("DELETE FROM class_timeslot WHERE class_id = '$class'");
			}
			else if (!$class && isset($_POST[$varname]) AND $_POST[$varname] == "yes")
			{
				// create this class
				$num_lessons = 0;
				if ($day == "MonWed")
				{
					$num_lessons = $this->LessonCount($session_id, 0, "Monday") + $this->LessonCount($session_id, 0, "Wednesday");
				}
				else if ($day == "TueThurs")
				{
					$num_lessons = $this->LessonCount($session_id, 0, "Tuesday") + $this->LessonCount($session_id, 0, "Thursday");				
				}
				else
					$num_lessons = $this->LessonCount($session_id, 0, $day);
				
				$debug .= $this->LessonCountDebug($session_id, 0, $day);
				$debug .= 'called lessoncount with ' . $session_id . ', ' . 0 . ', ' . $day . '<br />';
				if ($num_lessons != 0)
					$session_price = $num_lessons * $lesson_price;
					
				$debug .= 'creating class on ' . $day . ' session ' . $session_id . ' price ' . $session_price . ' for ' . $num_lessons . ' lessons <br />';
				$this->PoolShark->insert( 'class', array( 'session_id' => $session_id,
														  'teacher_id' => $teacher->id,
														  'price' => $session_price ),
												  array( '%d', '%d', '%d' ));
				$class_id = $this->PoolShark->get_var("SELECT MAX(id) FROM class 
													   WHERE class.session_id = '$session_id'
													   AND class.teacher_id = $teacher->id");
//				$output .= 'New class_id is ' . $class_id . '<br />';

				$timeslot_id = 0;
				$timeslot_id = $this->PoolShark->get_var("SELECT MAX(id) FROM time_slot
															WHERE weekday = '$day'
															AND start_time = '$find_time'");
				if ($timeslot_id == 0)
				{	
					$this->PoolShark->insert( 'time_slot', array ('weekday' => $day,
															  'start_time' => $find_time),
													   array ( '%s', '%s' ));
					$timeslot_id = $this->PoolShark->get_var("SELECT MAX(id) FROM time_slot
															WHERE weekday = '$day'
															AND start_time = '$find_time'");
				}  
				$this->PoolShark->insert( 'class_timeslot', array('class_id' => $class_id,
																  'timeslot_id' => $timeslot_id),
																  array('%d', '%d'));
			}
		}
	}
	}
}
//$output = $debug;
$output .= '<h1>Confirm Teaching Schedule</h1> (Edit if necessary, then click "Update")<br />';
$output .= '<form method="post" action="">';
$output .= '<input type="hidden" name="session_select" value=' . $session_id . '>';
$output .= 'Check box to teach during each timeslot.<br />';

$output .= $this->get_teaching_schedule($session_id, true);
$output .= '<input type="submit" name="teaching_schedule" value="Update Teaching Times" /><br /><br />';
$output .= '</form><br />';
$output .= '<a href="../schedule/?session_id=' . $session_id . '">Work on Class Schedule</a><br />';
} // else
} // else
return $output;
}

function build_scheduling_form_table( $session_id, $day)
{

	$output = '<strong>Classes on ' . $day->class_day . ' </strong><br />';
	$output .= '<table class="schedule">';
	$output .= '<tr><th>Students to Schedule</th><th>Enroll in class</th><th>Currently Enrolled</th></tr>';
		
	$num_classes = $this->PoolShark->get_var("SELECT COUNT(class.id) 
													FROM class, class_timeslot, time_slot
													WHERE class.session_id = '$session_id'
													AND class.id = class_timeslot.class_id
													AND class_timeslot.timeslot_id = time_slot.id
													AND time_slot.weekday = '$day->class_day'");
	$num_lines = $num_classes;//+ 14;												
	$classes = $this->PoolShark->get_results("SELECT class.id, teacher.name AS teacher,
                                          TIME_FORMAT(time_slot.start_time, '%l:%i %p') AS ez_start_time
                                          FROM class, class_timeslot, time_slot, teacher
                                          WHERE class.session_id = '$session_id'
                                          AND time_slot.weekday = '$day->class_day'
                                          AND class_timeslot.class_id = class.id
                                          AND class_timeslot.timeslot_id = time_slot.id
                                          AND teacher.id = class.teacher_id
                                          ORDER BY time_slot.start_time");
        
   $levels = $this->PoolShark->get_results("SELECT skill.level, skill.shortname, skill.name FROM skill");
	if ($levels)
	{
		$output .= '<tr><td rowspan=' . $num_lines . '>';			
		foreach($levels as $level)
		{
		$students_to_schedule = $this->PoolShark->get_results("SELECT student.id AS id, 
									student.first_name, student.last_name,   
									DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birthdate, '%Y') -
									(DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birthdate, '00-%m-%d')) AS age
									FROM student, students_to_schedule, skill
									WHERE student.id = students_to_schedule.student_id
									AND students_to_schedule.session_id = '$session_id'
									AND student.skill_id = skill.level
									AND skill.level = '$level->level'
									ORDER BY first_name");
									
	
								
		if ($students_to_schedule)
		{
			$output .= '<div style="height: 6em; width: 12em; overflow: auto;">'; 
			$output .= '<strong>' . $level->name . '</strong><br />'; 

			foreach($students_to_schedule as $student)
			{
				$varname = 'reg' . $student->id;
				$output .= '<input id=' . $student->id . ' type="checkbox" name="' . $varname . '" value="yes" />';
				$output .= '<label for=' . $student->id . '>'. $student->first_name . ' '  . $student->last_name;
				$output .= ', ' . $student->age  . '</label><br />'; 
//					$output .= '<option value="' . $student->id . '">' . $student->first_name . ' ' . $student->last_name;
//					$output .= ', ' . $student->age  . '</option>';
			}
			$output .= '</div><br /><br />';
		}
		}
	$output .= '</td>';
	}
	

	if ($classes)
	{
	foreach($classes as $class)
	{
		$num_in_class = $this->PoolShark->get_var("SELECT COUNT(*) 
														FROM class_student 
														WHERE class_id = '$class->id'");
		$size_limit = 8;
		if ($num_in_class > 0)
		{
		$class_info = $this->PoolShark->get_row("SELECT size_limit, name from skill, class 
												WHERE class.id = $class->id
												AND class.skill_id = skill.level");
		if ($class_info)
			$size_limit = $class_info->size_limit;										
		}
		$output .= '<td>';
		$output .= '<strong>' . $class->ez_start_time . ' with ' . $class->teacher . '</strong><br />';
		if ($num_in_class < $size_limit)
		{
			$varname = '>> Enroll in class (' . $class->id . ')';
			$output .= '<input type="submit" id=' . $class->id . ' name="enroll" value="';
			$output .=  $varname . '" /> <br />';
		}
		if ($num_in_class > 0)
		{
			$varname = 'Remove from class << (' . $class->id . ')';
			$output .= '<input type="submit" id=' . $class->id . ' name="remove" value="';
			$output .=  $varname . '" /> <br />';
		}
		$output .= '</td>';
		if ($num_in_class < $size_limit)
			$output .= '<td class="open">';
		else
			$output .= '<td class="full">';
			
		$output .= '<strong> ' . $class->ez_start_time . ' with ' . $class->teacher . '</strong><br />';

		$scheduled_students = $this->PoolShark->get_results("SELECT student.id AS id,
									student.first_name, student.last_name,
									DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birthdate, '%Y') -
									(DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birthdate, '00-%m-%d')) AS age									FROM student, class, class_student
									WHERE student.id = class_student.student_id
									AND class.session_id = '$session_id'
									AND class_student.class_id = class.id
									AND class.id = '$class->id'
									ORDER BY last_name");
		if ($scheduled_students)
		{
			$output .= $class_info->name . '<br />';
			foreach($scheduled_students as $s)
			{
				$varname = 'rem' . $s->id;
				$output .= '<input id=' . $s->id . ' type="checkbox" name="' . $varname . '" value="yes" />';
				$output .= '<label for=' . $s->id . '>'. $s->first_name . ' '  . $s->last_name;
				$output .= ', ' . $s->age  . '</label><br />'; 
			}

		}
		$output .= '</td></tr>';
	}		
	}

$output .='</table>';	
return $output;
}	


function ScheduleClasses()
{
$session_id = 0;
$session_id = get_session();
$output = '';
if ($session_id == 0 AND !isset($_POST['session_select']))
{
$output = 'Schedule Classes in What Session?<br >';
$FutureSessions = $this->GetFutureSessionsOption(0);
$output .= '<form method="post" action="">';
$output .= '<select name="session_select">';
$output .= $FutureSessions;
$output .= '</select>';
$output .= '<br /><br />';
$output .= '<input type="submit" value="Schedule Classes" />';
$output .= '</form>';
return $output;
}
else
{
if (isset($_POST['enroll'])  || isset($_POST['remove']))
{
	$session_id = $_POST['session_select'];
	// find out what class they clicked enroll on
	$classes = $this->PoolShark->get_results("SELECT class.id, teacher.name AS teacher, class.skill_id AS skill,
                                          TIME_FORMAT(time_slot.start_time, '%l:%i %p') AS ez_start_time
                                          FROM class, class_timeslot, time_slot, teacher
                                          WHERE class.session_id = '$session_id'
                                          AND class_timeslot.class_id = class.id
                                          AND class_timeslot.timeslot_id = time_slot.id
                                          AND teacher.id = class.teacher_id
                                          ORDER BY time_slot.start_time");	
    $class_to_enroll = 0;
    $class_to_remove = 0;
    $class_skill = 0;
	if ($classes)
	{
		foreach($classes as $class)
		{
			$addvarname = '>> Enroll in class (' . $class->id . ')';
			$removevarname = 'Remove from class << (' . $class->id . ')';
			if (isset($_POST['enroll']) && $_POST['enroll'] == $addvarname)
			{
				//$output .= 'Student(s) Added to class '. $class->id . '<br />';
				$class_to_enroll = $class->id;
				$class_skill = $class->skill;
			}
			if (isset($_POST['remove']) && $_POST['remove'] == $removevarname)
			{	
				//$output .= 'Student(s) Removed from class ' . $class->id . '<br />';
				$class_to_remove = $class->id;
			}
		}		
	}
	// find out what students were checked.
	$students_to_schedule = $this->PoolShark->get_results("SELECT student_id AS id, 
									student.skill_id AS level
									FROM students_to_schedule, student
									WHERE student.id = students_to_schedule.student_id");
									
	$scheduled_students = $this->PoolShark->get_results("SELECT student_id AS id, class.skill_id AS level
									FROM class_student, class
									WHERE class_student.class_id = '$class_to_remove'
									AND class.id = class_student.class_id");
	$level_of_class = 0;
	$price_of_class = $this->PoolShark->get_var("SELECT price FROM session WHERE id = '$session_id'");
	if ($students_to_schedule)
	{
		foreach($students_to_schedule as $stu)
		{
			$addvarname = 'reg' . $stu->id;
	
			if(isset($_POST[$addvarname]) && $_POST[$addvarname] == "yes")
			{
				if (($stu->level < 8) && ($stu->level > $level_of_class))
					$level_of_class = $stu->level;
				$already = 0;
				$already = $this->PoolShark->get_var("SELECT class_id FROM class_student
														WHERE student_id = '$stu->id' 
														AND class_id = '$class_to_enroll'");
				if ($already == 0)
				{	$this->PoolShark->insert('class_student', array( 'class_id' => $class_to_enroll,
													 'student_id' => $stu->id,
													 'payment_due' => $price_of_class ),
											  array( '%d', '%d', '%d') );
				}
				$this->PoolShark->query("DELETE FROM students_to_schedule WHERE student_id = '$stu->id'");				
			}
		}
	}
	if ($scheduled_students)
	{
		foreach($scheduled_students as $stu)
		{
			$remvarname = 'rem' . $stu->id;
		
			if(isset($_POST[$remvarname]) && $_POST[$remvarname] == "yes")
			{
				$this->PoolShark->insert('students_to_schedule', array( 'student_id' => $stu->id, 
																		'session_id' => $session_id),
																 array( '%d', '%d'));
				$this->PoolShark->query("DELETE FROM class_student WHERE student_id = '$stu->id'");
			}
				
				
		}
	
	}
	
	if(isset($_POST['enroll']))
	{
	// set the level of the class
	if ($level_of_class > $class_skill)
		$this->PoolShark->update('class', array( 'skill_id' => $level_of_class), 
										array( 'id' => $class_to_enroll ), 
											array( '%d' ), 
											array( '%d' ));
	else
		$level_of_class = $class_skill;
		
	// update the levels of the students in the class
	if ($students_to_schedule)
	{
		foreach($students_to_schedule as $stu)
		{
			$addvarname = 'reg' . $stu->id;
			$remvarname = 'rem' . $stu->id;
			if(isset($_POST[$addvarname]) && $_POST[$addvarname] == "yes")
			{				
				$this->PoolShark->update('student', array( 'skill_id' => $level_of_class),
													array( 'id' => $stu->id),
													array( '%d'),
													array( '%d') );
			}
		}
	}
	}
}

if (isset($_POST['session_select']))
{
	$session_id = $_POST['session_select'];
}
$session_name = $this->PoolShark->get_var("SELECT name FROM session WHERE id = '$session_id'");
$output .= 'Schedule Students and Classes in session ' . $session_name;
$output .= '<br />Select students, then click on class to enroll them.<br />';

$days = $this->PoolShark->get_results("SELECT DISTINCT time_slot.weekday AS class_day
						FROM class, class_timeslot, time_slot
						WHERE session_id = '$session_id'
						AND class_timeslot.class_id = class.id
						AND class_timeslot.timeslot_id = time_slot.id
						ORDER BY class_day");
if ($days)
{
	//$output = '';
	$output .= '<form method="post" action="">';
	$output .= '<input type="hidden" name="session_select" value=' . $session_id . '>';

	foreach($days as $day)
	{
		$output .= $this->build_scheduling_form_table( $session_id, $day);
	}
}
// on right, have all the classes scheduled for this session
$output .= '</form>';	
}	

return $output;
}


function LessonDataTable()
{
	$output = '';
	$output .= '<form action="" method="POST">';
	$output .= '<table class="schedule">';
	$output .= '<tr><th colspan = 2>Each Lesson Costs</th><th>Class Size</th></tr>';
		
	$skill_set = $this->PoolShark->get_results("SELECT name, cost, level, size_limit FROM skill ORDER BY level");
	if ($skill_set)
	{
		foreach($skill_set as $class_type)
		{	
			$pricetag = $class_type->level . 'cost';
			$sizetag = $class_type->level . 'size';
			$output .= '<tr><td>' . $class_type->name . '</td><td>';
			$output .= '$<input maxlength="10" name="' . $pricetag . '" size="10" type="text"';
			$output .= 'value="' . $class_type->cost . '"/> </td>';
			$output .= '<td><input maxlength="10" name="' . $sizetag . '" size="10" type="text"';
			$output .= 'value="' . $class_type->size_limit . '"/></td></tr>';
		}
	}
	$output .= '</table>';
	$output .= '<input type="submit" name="UpdateLesson" value="Update Lessons" /><br /><br />';
	$output .= '</form>';
	return $output;
}

function LessonData()
{
	if (!isset($_POST['UpdateLesson']))
	{
		$output = $this->LessonDataTable();
		return $output;
	}
	else
	{ // update database
		$skill_set = $this->PoolShark->get_results("SELECT name, cost, level, size_limit FROM skill ORDER BY level");
		
		if ($skill_set)
		{
			foreach($skill_set as $class_type)
			{	
				$debug .= 'skill ' . $class_type->level . '<br />';
				$pricetag = $class_type->level . 'cost';
				$sizetag = $class_type->level . 'size';
				$debug .= 'cost: ' . $pricetag . ' ' . $_POST[$pricetag] . '<br />';
				$debug .= 'size: ' . $sizetag . ' ' . $_POST[$sizetag] . ' <br />';

//				if ($_POST[$pricetag] != $class_type->cost)
//				{
						$this->PoolShark->update('skill', array( 'cost' => $_POST[$pricetag],
																 'size_limit' => $_POST[$sizetag]), 
										array( 'level' => $class_type->level ), 
											array( '%s', '%d' ), 
											array( '%d' ));
//				}
			}
		}
	//$output = $debug;
	$output = $this->LessonDataTable();	
	return $output;
	}
}


function DBSearch()
{
	$output = '';
	if (!isset($_POST['Search']))
	{
	$output .= 'Fill in as many or few of the fields as you like.  Entering just a letter of a name is fine.<br /><br />';
	$output .= '<form action="" method="POST">';
	$output .= '<table>';
	$output .= '<tr><th colspan=2>Search by Name</th></tr>';
	$output .= '<tr><td>Contact First Name</td>';
	$output .= '<td><input maxlength="48" name="find_firstname" size="20" type="text" /></td></tr>';
	$output .= '<tr><td>Contact Last Name</td>';
	$output .= '<td><input maxlength="48" name="find_lastname" size="20" type="text" /></td></tr>';
	$output .= '<tr><td>Student First Name:</td>';
	$output .= '<td><input maxlength="48" name="student_firstname" size="20" type="text" /></td></tr>';
	$output .= '<tr><td>Student Last Name:</td>';
	$output .= '<td><input maxlength="48" name="student_lastname" size="20" type="text" /></td></tr>';
	$output .= '</table><br />';
	
	$levels = $this->GetSkillLevelOptions(0);
	$AllSessions = $this->GetAllSessionsOption(0);
	$output .= '<table>';
	$output .= '<tr><th colspan=2>Find Students</th></tr>';
	$output .= '<tr><td>With Birthdays between:</td>';
	$output .= '<td>' . birthdate_combo('from', 0, 0, -1);
	$output .= ' and<br />' . birthdate_combo('to', 0, 0, -1) . ' </td></tr>'; 
	$output .= '<tr><td>With Skill Level:</td><td><select name="find_level">' . $levels . '</select></td></tr>';
//	$output .= '<tr><td>Enrolled in Session: </td><td><select name="find_session">';
//	$output .=  $AllSessions;	
//	$output .= '</select></td></tr>';
	$output .= '</table><br />';
	
	$output .= '<table>';
	$output .= '<tr><th colspan=2> Find by Contact info </th></tr>';
	$output .= '<tr><td>Phone Number:</td>';
	$output .= '<td><input maxlength="48" name="find_phone" size="20" type="text" /></td></tr>';
	$output .= '<tr><td>Email:</td>';
	$output .= '<td><input maxlength="48" name="find_email" size="20" type="text" /></td></tr>';
	$output .= '<tr><td>Contact Method:</td>';
	$output .= '<td><select name=find_contact>'. $this->GetContactMethodOptions(0) . '</select></td></tr>';
	$output .= '</table><br />';
	$output .= '<input type="submit" name="Search" value="Search" /><br /><br />';
	$output .= '</form>';
	}
	else
	{
	// select from guardian
	if ($_POST['find_firstname'] != '' || 
		$_POST['find_lastname'] != '' ||
		$_POST['find_phone'] != '' ||
		$_POST['find_email'] != '' )
	{
		$need_and = 0;
		$query = "SELECT id, first_name, last_name FROM guardian WHERE ";
		$orderby = '';
		if ($_POST['find_firstname'] != '')
		{
			if ($need_and)
				$query .= "AND ";
			$query .= "first_name LIKE '" . $_POST['find_firstname'] . "%' ";
			$need_and = 1;
		}
		if ($_POST['find_lastname'] != '')
		{
			if ($need_and)
				$query .= "AND ";
			$query .= "last_name LIKE '" . $_POST['find_lastname'] . "%' ";
			$need_and = 1;
		}
		if ($_POST['find_email'] != '')
		{
			if ($need_and)
				$query .= "AND ";
			$query .= "email LIKE '%" . $_POST['find_email'] . "%' ";
			$need_and = 1;
		}
		if ($_POST['find_phone'] != '')
		{
			if ($need_and)
				$query .= "AND ";
			$query .= "home_phone LIKE '%". $_POST['find_phone'] . "%' OR ";
			$query .= "cell_phone LIKE '%". $_POST['find_phone'] . "%' OR ";
			$query .= "work_phone LIKE '%". $_POST['find_phone'] . "%'";
		}
		$query .= " ORDER BY first_name";
		
//		$output .= 'Guardian query: ' . $query . '<br /><br />';	
		$names = $this->PoolShark->get_results($query);
		if ($names)
		{
		foreach($names as $client)
		{
			if ($guardian = 1)
			{
				$output .= '<a href=./edit-family/?contact_id=' . $client->id . '> ' . $client->first_name . ' ' . $client->last_name . '</a><br />';				
			}
		}
		} 
	}
	else if ($_POST['student_firstname'] != '' || 
		$_POST['student_lastname'] != '' || 
		$_POST['find_level'] != 0 ||
		$_POST['fromDateOfBirth_Month'] != 0)
	{
		$need_and = 0;
		$query = "SELECT id, guardian_id, first_name, last_name FROM student WHERE ";
		if ($_POST['student_firstname'] != '')
		{
			if ($need_and)
				$query .= "AND ";
			$query .= "first_name LIKE '" . $_POST['student_firstname'] . "%' ";
			$need_and = 1;
		}
		if ($_POST['student_lastname'] != '')
		{
			if ($need_and)
				$query .= "AND ";
			$query .= "last_name LIKE '" . $_POST['student_lastname'] . "%' ";
			$need_and = 1;
		}		
		if ($_POST['find_level'] != 0)
		{
			if ($need_and)
				$query .= "AND ";
			$query .= "skill_id =" . $_POST['find_level'] . " ";
			$need_and = 1;
		}				
		if ($_POST['fromDateOfBirth_Month'] != 0 && 
			$_POST['fromDateOfBirth_Day'] != 0 &&
			$_POST['toDateOfBirth_Month'] != 0 &&
			$_POST['toDateOfBirth_Day'] != 0 )
		{
			$from_date = '2011-' . $_POST['fromDateOfBirth_Month'] . '-' . $_POST['fromDateOfBirth_Day'];
			$to_date = '2011-' . $_POST['toDateOfBirth_Month'] . '-' . $_POST['toDateOfBirth_Day'];
			
			if ($need_and)
				$query .= "AND ";
			$query .= "date_format(  birthdate, '%m-%d' )
						BETWEEN date_format( '$from_date', '%m-%d' )
						AND date_format( '$to_date', '%m-%d' ) ";
			$need_and = 1;
		}			
		$query .= " ORDER BY first_name";
//		$output .= 'Student query: ' . $query . '<br /><br />';	
		$names = $this->PoolShark->get_results($query);
		if ($names)
		{
		foreach($names as $client)
		{
			if ($guardian = 1)
			{
				$output .= '<a href=./edit-family/?contact_id=' . $client->guardian_id . '> ' . $client->first_name . ' ' . $client->last_name . '</a><br />';				
			}
		}
		} 		
		
		
	}
	else
	{
		$output .= 'Found no matches.  Please try again. <br />';	
	}
	}
return $output;
}

	function AdminMenu()
	{
		add_menu_page('Registration', 'Registration', 'read', 'phf_reg_options', array( $this, 'RegOptions' ) );
	}
	function RegOptions()
	{
		return 'Hey! this is RegOptions';
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
    if ($selbirthyear >=0)
	{
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
	}
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
