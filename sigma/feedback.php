<?php
if(isset($_POST['status']) && trim($_POST['status']) == "true")
{
	if(isset($_POST['feedback']) && trim($_POST['feedback']) != "" && isset($_POST['mailto']) && trim($_POST['mailto']) != "")
	{
		$feedback=trim($_POST['feedback']);
		$mailfrom=isset($_POST['email'])? $_POST['email']:"";
		$urlat=isset($_POST['url'])? $_POST['url']:"";
		$mailto=trim($_POST['mailto']);

		// subject
		$subject = 'You got a feedback';

		// message
		$message = '
		<html>
		<head>
		  <title>Feedback</title>
		</head>
		<body>
		  <p>Feedback</p>
		  <table>
			<tr>
			  <th>Email</th><td>'.$mailfrom.'</td>
			</tr>
			<tr>
			  <th>Message</th><td>'.$feedback.'</td>
			</tr>
			<tr>
			  <th>Url On</th><td>'.$urlat.'</td>
			</tr>
		  </table>
		</body>
		</html>
		';

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		// Additional headers
		$headers .= 'To: '.$mailto. "\r\n";
		$headers .= 'From: Support <support@larvol.com>' . "\r\n";

		// Mail it
		mail($mailto, $subject, $message, $headers);		

		echo 1;	
	}else{
		echo 0;
	}
	exit();
}
else{ 
?>
<link rel="stylesheet" href="scripts/slickgrid/css/smoothness/jquery-ui.1.10.1.custom.min.css" />
<script src="scripts/jquery-1.7.2.min.js"></script>
<script src="scripts/jquery.ui.1.10.1.dialog.min.js"></script>
<style>
#feedback-wrapper label,#feedback-wrapper input { display:block; }
input.text { margin-bottom:12px; width:95%; padding: .4em; }
fieldset { padding:0; border:0; margin-top:25px; }
h1 { font-size: 1.2em; margin: .6em 0; }
.ui-dialog .ui-state-error { padding: .3em; }
.validateTips { border: 1px solid transparent; padding: 0.3em; }
#feedback {width:98%;background-color:#fff !important;}
.message-red, .feedback-error  {color:red;}
.feedback-success {color:blue;}
.feedback-success, .feedback-error{text-align:center;margin-top:98px;margin-bottom: 127px;}
.feedback-container {width:100%;height:8px;}
#feedback-button {float:right;background:#4F2683;color:#fff;font-size:12px;font-weight:bold !important; border: 2px outset;border-radius: 0px;}
#dialog-form {display:none;height:auto !important;}
.ui-widget-header, .ui-dialog-buttonset .ui-button-text{background:#6A4E8F !important;color:#fff !important;font-size:13px;}
#feedback-wrapper,#dialog-form{font-size: 11px !important;clear:both;}
#feedback-wrapper{margin-bottom:13px;}
.ui-widget{font-size:16px;font-family: Verdana,Arial,sans-serif !important;}
.ui-widget-content,.ui-widget input, .ui-widget select, .ui-widget textarea, .ui-widget button {background-color:#fff !important;}
.ui-state-error, .ui-widget-content .ui-state-error, .ui-widget-header .ui-state-error{background: url("images/ui-bg_glass_95_fef1ec_1x400.png") repeat-x scroll 50% 50% #FEF1EC !important;color: #222222 !important;text-align:left !important;}
#email,#feedback {background: none repeat scroll 0 0 white !important;color: #222222 !important;text-align:left !important;}
.ui-widget-overlay {url("images/ui-bg_flat_0_aaaaaa_40x100.png") repeat-x scroll 50% 50% #AAAAAA !important;}
.ui-button {font-weight:normal !important;}
}
</style>
<script>
$(function() {
var email = $( "#email" ),
name = $( "#feedback" ),
allFields = $( [] ).add( email ).add( name ),
tips = $( ".validateTips" );
function updateTips( t ) {
tips
.text( t )
.addClass( "ui-state-highlight" );
setTimeout(function() {
tips.removeClass( "ui-state-highlight", 1500 );
}, 500 );
}
function checkLength( o, n, min, max ) {
if ((document.getElementById(n).value.length > max) || (document.getElementById(n).value.length < min) ) {
o.addClass( "ui-state-error" );
//document.getElementById(n).setAttribute("class", "ui-state-error");//css( "ui-state-error" );
updateTips( "Length of " + n + " must be between " +
min + " and " + max + "." );

return false;
} else {
return true;
}
}

function checkRegexp( o,m, regexp, n ) {
var estr = document.getElementById(m).value ;
if ( !( regexp.test( estr ) ) ) {
o.addClass( "ui-state-error" );
updateTips( n );
return false;
} else {
return true;
}
}
$( "#dialog-form" ).dialog({
autoOpen: false,
height: 300,
width: 350,
modal: true,
<!--position: [350, 150],-->
buttons: {
"Submit": function() {
var bValid = true;
allFields.removeClass( "ui-state-error" );
if($("#email").val() !=""){
	bValid = bValid && checkLength( email, "email", 6, 80 );
	bValid = bValid && checkRegexp( email, "email", /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i, "Please enter the valid Email" );
}
bValid = bValid && checkLength( name, "feedback", 3, 200 );
if($.trim($("#feedback").val()) != "")
{
	bValid = bValid && true;
}else{
//o.addClass( "ui-state-error" );
//updateTips( "Length of " + 3 + " must be between " +
//min + " and " + 200 + "." );
bValid = bValid && false;
}

if ( bValid ) {
	feedback = $( "#feedback" ).val();
	email = $( "#email" ).val();
$.post("feedback.php",{"status":"true","mailto":"<?php echo FEEDBACK_EMAIL; ?>","feedback":feedback,"email":email,"url":window.location.href},function(result){
	$(".ui-dialog-buttonpane").hide();
if(result==1){
	$("#dialog-form").html("<h4 class='feedback-success'>Thanks for your feedback!</h4>");
}else{
	$("#dialog-form").html("<h4 class='feedback-error'>Error: Cannot send the feedback</h4>");
}	
	
});
//$( this ).dialog( "close" );
}
},
Cancel: function() {
$( this ).dialog( "close" );
}
},
close: function() {
allFields.val( "" ).removeClass( "ui-state-error" );
}
});
$( "#feedback-button" )
.button()
.click(function() {
$( "#dialog-form" ).dialog( "open" );
});
});
</script>
<div id="feedback-wrapper">
<div id="dialog-form" title="Feedback">
<p class="validateTips">Fields marked as * are required.</p>
<form>
<fieldset>
<label for="email">Email</label>
<input type="text" name="email" id="email" value="" class="text ui-widget-content ui-corner-all" />
<label for="feedback">Message<font class="message-red">*</font></label>
<textarea name="feedback" id="feedback" class="text ui-widget-content ui-corner-all"></textarea>
</fieldset>
</form>
</div>

<div class="feedback-container"><button id="feedback-button" title="Feedback">Feedback</button></div>
<?php }?>
</div>
