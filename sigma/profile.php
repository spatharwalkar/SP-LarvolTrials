<?php
define("SIGMA","1");
$cwd = getcwd();
chdir ("..");
require_once('db.php');
chdir ($cwd);
$user = $db->user->id;

if(isset($_GET['user']))
{
	$user = (int)$_GET['user'];
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Profile - Larvol Sigma</title>
<style type="text/css">
body
{
	font-family:Arial;
	font-size:14px;
	color:#000000;
}

a {color:#1122cc;}      /* unvisited link */
a:visited {color:#6600bc;}  /* visited link */
/*a:hover {color:#FF00FF;}  /* mouse over link */
/*a:active {color:#0000FF;}  /* selected link */

.SearchBox
{
	/*outline:none;*/
	height:27px;
	width:600px;
}
.SearchBox:focus
{
	box-shadow:inset 0px 1px 2px rgba(0,0,0,0.3);
	-moz-box-shadow:inset 0px 1px 2px rgba(0,0,0,0.3);
	-webkit-box-shadow:inset 0px 1px 2px rgba(0,0,0,0.3);
	-moz-border-radius:1px;
	-webkit-border-radius:1px;
	border-radius:1px;
	border:1px solid #4d90fe;
	outline:none;
	height:27px;
}

.SearchBttn1
{
	width:100px;
	height:35px;
	background-color:#4f2683;
	font-weight:bold;
	color:#FFFFFF;
}

.FoundResultsTb
{
	background-color:#aa8ece;
	border:0;
	border-top:#4f2683 solid 2px;
}

.pagination {
	line-height: 1.6em;
	width:100%;
	float:none;
	margin-right:10px;
	float: left; 
	padding-top:2px; 
	vertical-align:bottom;
	font-weight:bold;
	padding-bottom:25px;
	color:#4f2683;
}

.pagination a:hover {
	background-color: #aa8ece;
	color: #FFFFFF;
	font-weight:bold;
}

.pagination a {
	margin: 0 2px;
	border: 1px solid #CCC;
	background-color:#4f2683;
	font-weight: bold;
	padding: 2px 5px;
	text-align: center;
	color: #FFFFFF;
	text-decoration: none;
	display:inline;
}

.pagination span {
	padding: 2px 5px;
}

.alpharow {
	line-height: 1.6em;
	width:100%;
	float:none;
	margin-right:10px;
	float: left; 
	padding-top:4px; 
	vertical-align:bottom;
	font-weight:bold;
	padding-bottom:4px;
	color:#4f2683;
}

.alpharow a:hover {
	background-color: #aa8ece;
	color: #FFFFFF;
	font-weight:bold;
}

.alpharow a {
	margin: 0 2px;
	border: 1px solid #CCC;
	/*background-color:#4f2683;*/
	font-weight: bold;
	padding: 2px 5px;
	text-align: center;
	color: #4f2683;
	text-decoration: none;
	display:inline;
}

.alphanormal {
	font-weight:normal;
	color:#000000;
	font-size:13px;
}

.alpharow span {
	padding: 2px 5px;
}

.searchTypes
{
	font-weight:bold;
	font-size:12px;
}

.autocomplete-w1 
{ 
	background:url(../images/shadow.png) no-repeat bottom right; 
	position:absolute; 
	top:0px; 
	left:0px; 
	margin:8px 0 0 6px; 
	/* IE6 fix: */ _background:none; _margin:0; 
}
.autocomplete 
{ 
	border:1px solid #999; 
	background:#FFF; 
	cursor:default; 
	text-align:left; 
	max-height:350px; 
	overflow:auto; 
	margin:-6px 6px 6px -6px; 
	/* IE6 specific: */ 
	_height:350px;  
	_margin:0;
	_overflow-x:hidden;
}
.autocomplete .selected { 
	background:#F0F0F0; 
}
.autocomplete div { 
	padding:2px 5px; 
	white-space:wrap;
}
.autocomplete strong { 
	font-weight:normal; 
	color:#3399FF; 
}
.profile
{
	margin: 20px 1%;
}
.profile tr {
	color:#4f2683; 
	/*font-size:16px*/
}
.profile tr td{ 
	font-weight:normal; 
}
.profile tr th{ 
	text-align:right; 
}
.profile-title{
	/*text-decoration:underline;*/
	color:#B68DCE;
	border-bottom: 1px solid;
}	
.user-comments{
	margin-left:1%; 
	color:#4f2683; 
	margin-bottom:20px;
}
.message{
	margin:15% auto;
	color:#4f2683;
	text-align:center;
}
.profile-comment-nav{
	text-decoration:none;
}
.profile-comments{
	color:#4f2683;
	margin-left:1%; 
	margin-bottom:10px;
    border-color: #B6B6B6;
    border-style: dotted;
    width: 41%;
	padding-left:5px;
}
.profile-comments a{
	color:#4f2683;
}
.profile-comments a p{
	margin:2px 0 2px 2px;
	width: 98%;
    word-wrap: break-word;
}
</style>
<script type="text/javascript" src="scripts/jquery-1.7.2.min.js"></script>
</head>
<body>
<?php include "searchbox.php";?>
<br />
<table width="100%" border="0" class="FoundResultsTb">
	<tr>
        <td width="50%" align="left" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; font-size:23px; vertical-align:middle;">
        	<table>
				<tr>
					<td style="vertical-align:top;">
						<a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="profile.php">Profile</a>
					</td>					
				</tr>
			</table>
        </td>
		<td>
		<?php
		if($db->loggedIn()) {
			echo('<div style="padding-left:10px;float:right;font-weight: bold;">Welcome, <a href="profile.php">'
				. htmlspecialchars($db->user->username) . '</a> :: <a href="login.php?logout=true">Logout</a> &nbsp; </div>');
		} else {
			echo ('<div style="padding-left:10px;float:right;font-weight: bold;"><a href="login.php">login</a></div>');
		}
		?>
		</td>
	</tr>
</table>
<?php
if(trim($user) != "")
{
?>
<h3 class="profile-title">Profile for the User <?php echo $user ?></h3>
<table class="profile">
<?php
$query = 'SELECT username,userlevel,realname,country,linkedin_url FROM users WHERE id=' . $user . ' LIMIT 1';
$res = mysql_query($query);
if($res === false) die("Couldn't find user.");
$res = mysql_fetch_assoc($res);
foreach($res as $field => $value)
{
	if($field == 'userlevel')
		echo '<tr><th>User Type:</th><td>'.($value == 'public' ? 'Member' : 'Larvol Representative').'</td></tr>';
	else
		echo '<tr><th>'.ucfirst($field).':</th><td>'.$value.'</td></tr>';
}
?>
</table>
<h3 class="profile-title">Comments</h3>
<?php
$query = 'SELECT COUNT(*) AS `total` FROM commentics_comments WHERE userid=' . $user . ' AND `name` != ""';
$res = mysql_query($query);
if($res === false) die("Couldn't get comment count.");
$res = mysql_fetch_assoc($res);
$res = $res['total'];
?>
<div class="user-comments">
	User has made <?php echo $res; ?> comments.
</div>

<?php
$query = 'SELECT c.id, c.name, c.email, c.comment, c.page_id, c.dated, p.url, p.reference FROM commentics_comments c, commentics_pages p WHERE c.userid=' . $user . ' AND c.name != "" AND c.is_approved =1 AND c.page_id = p.id AND p.is_form_enabled = 1 ORDER BY c.id DESC LIMIT 10';
$res = mysql_query($query);
if($res === false) die("Couldn't find comment.");
while($row = mysql_fetch_array($res))
{
?>
	<div class="profile-comments">
			<p><?php echo '<a href="'.$_SERVER['PHP_SELF'].'" class="profile-comment-nav"><b>'.$row['name'].'</b></a>&nbsp;&nbsp;&nbsp;&nbsp;'.date('M d Y H.i', strtotime($row['dated'])).'&nbsp;&nbsp;&nbsp;&nbsp;';?>
				<a href="<?php echo $row['url'].'&cmtx_perm='.$row['id'].'#cmtx_perm_'.$row['id']; ?>" class="profile-comment-nav">
				<?php echo 'Commented Page:&nbsp;'.$row['reference']; ?></a>
			</p>
			<p><?php echo $row['comment']; ?></p>		
	</div>
<?php	
}
?>

<?php
}else{
?>
<div class="message">
	You are not logged in!
</div>
<?php 
}
include "footer.php" ?>
</body>
</html>
