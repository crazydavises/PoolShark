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
		add_shortcode( 'RegisterNew', array( $this, 'RegisterNew' ) );
		add_shortcode( 'SetPrefTimeslots', array($this, 'SetPrefTimeslots') );
	}

	function InitDB()
	{
		$this->PoolSharkReg = new wpdb('poolsharkreg', 'swimshark', 'poolshark', 'mysql.lifestrokes.com');
	    $this->PoolSharkReg->show_errors();
	    $this->CurrentSession = $this->PoolSharkReg->get_var("SELECT id FROM session WHERE start_date < CURDATE() AND CURDATE() < end_date");
		if ($this->CurrentSession == 0)
			$this->CurrentSession = 16;
		add_filter('query_vars', 'parameter_queryvars' );  

	}

	function addheaderCode()
	{
        echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/poolsharkreg/poolshark_tables.css" />' . "\n";
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
	
		
function RegisterNew()
{
    $email_to = "swim.lifestrokes@gmail.com";
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
                              
			$output .= '<br />Added '.$firstname . ' '. $lastname . '<br /><br />';			
		}
		
		$sessions = $this->PoolSharkReg->get_results( "SELECT name, id 
													FROM session
													WHERE 
													DATEDIFF(end_date, CURDATE()) > 20");
		
		$output .= '<strong>Check which sessions you wish to enroll in.</strong><br />';
		$output .= '<form method="post" action="">';
		$output .= '<input type="hidden" name="guardian_id" value="' . $_POST['guardian_id'] . '">';
		if ($sessions)
		{
			foreach( $sessions as $s)
			{
				$sessionvar = $s->id . '_session';
				$output .= '<input type="checkbox" name="' . $sessionvar  . '" value="yes">' . $s->name . '<br />';
			}
		}

		$parent = $this->PoolSharkReg->get_row("SELECT first_name, last_name
										FROM guardian
										WHERE id = '$_POST[guardian_id]'");
		$output .= '<strong>Check all times that the ' . $parent->first_name . ' ' . $parent->last_name . ' family prefers to come for swim class.<br />';
		$output .='Checking more options is better. <br /></strong>';
			$output .= $this->get_pref_timeslots(0);	
		$output .= '<br /><strong>Please add a comment letting us know which session(s) this availability covers.</strong>';				
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
	$email_text .= "\r\n" . 'Registering for Sessions:' . "\r\n";
	$sessions = $this->PoolSharkReg->get_results( "SELECT name, id 
													FROM session
													WHERE 
													DATEDIFF(end_date, CURDATE()) > 20");
	foreach($sessions as $s)
	{
		$varname = $s->id . '_session';
		if ( isset($_POST[$varname]) AND $_POST[$varname] == "yes")
			$email_text .= $s->name . "\r\n";
	}
	
												
	$email_text .= "\r\n" . 'Available Lesson Times:';		
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

	$output = '';
	$sessions = $this->PoolSharkReg->get_results( "SELECT name, 
													DATE_FORMAT(start_date, '%M %e, %Y') AS start_date,
													DATE_FORMAT(end_date, '%M %e, %Y') AS end_date,
													id 
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
	$output .= 'There are three steps: First your contact information, second the names of the students you wish to register, and third, your availability for lesson times.  Please leave a comment in the box on the third step telling us for which session(s) you wish to register.';
	$output .= '<br /><br />Step 1 (of 3): Contact Information <br />';
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



function get_pref_timeslots( $guardian_id )
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
		$output .='<td>';	
		$varname = $day . $find_time;
		$output .= '<input type="checkbox" name="' . $varname . '" ';
		$output .= ' value="yes" >';
		$output .= '</td>';
	}
	$output .= '</tr>';
}
$output .='</table>';
	
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


function get_student()
{
	global $wp_query;
	if(isset($wp_query->query_vars['student_id']))
	{
		return $wp_query->query_vars['student_id'];
	}
}

?>
