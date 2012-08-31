<?php  
/* 
Template Name: Custom WordPress Login 
*/  
global $user_ID;  
  
if (!$user_ID) {  
  
if($_POST){  
//We shall SQL escape all inputs  
$username = $wpdb->escape($_REQUEST['username']);  
$password = $wpdb->escape($_REQUEST['password']);  
$remember = $wpdb->escape($_REQUEST['rememberme']);  
  
if($remember) $remember = "true";  
else $remember = "false";  
$login_data = array();  
$login_data['user_login'] = $username;  
$login_data['user_password'] = $password;  
$login_data['remember'] = $remember;  
$user_verify = wp_signon( $login_data, true );   
  
if ( is_wp_error($user_verify) )  
{  
   echo "<span class='error'>Invalid username or password. Please try again!</span>";  
   exit();  
 } else  
 {  
   echo "<script type='text/javascript'>window.location='". get_bloginfo('url') ."'</script>";  
   exit();  
 }  
} else {   
  
get_header();  
?>
<script src="http://code.jquery.com/jquery-1.4.4.js"></script>  
 

<div id="container">  
<div id="content">  
<h1></h1>Log In to LifeStrokes <br /></h1>
<div id="result"></div>  
  
 <!-- To hold validation results -->  
<form id="wp_login_form" action="" method="post">  
  
<label>Username</label>  
<input name="username" class="text" value="" type="text"><br />
<label>Password</label>  
<input name="password" class="text" value="" type="password"><br />  
<label>  
<input name="rememberme" value="forever" type="checkbox">Remember me</label><br />  
<input id="submitbtn" name="submit" value="Login" type="submit"><br />  
</form>  
  
<script type="text/javascript">                           
$("#submitbtn").click(function() {  
  
$('#result').html('<img src="<?php bloginfo('template_url'); ?>/images/loader.gif" class="loader" />').fadeIn();  
var input_data = $('#wp_login_form').serialize();  
$.ajax({  
type: "POST",  
url:  "<?php echo "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>",  
data: input_data,  
success: function(msg){  
$('.loader').remove();  
$(' 
<div>').html(msg).appendTo('div#result').hide().fadeIn('slow');  
}  
});  
return false;  
  
});  
</script>  
</div>  
</div>  
  
<?php  
  
get_footer();  
    }  
}  
else {  
	get_header(); 
	echo "hello, you are logged in already.";
    echo "<script type='text/javascript'-->window.location='". get_bloginfo('url') ."'";  
    get_footer();
}  
?>  
