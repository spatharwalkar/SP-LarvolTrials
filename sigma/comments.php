<link rel="stylesheet" type="text/css" href="../comments/css/stylesheet.css"/><style>.cmtx_social_images a, .cmtx_rss_block a, .cmtx_buttons a{	display:inline;}.cmtx_buttons a{	float:none;}</style><?php$cmtx_identifier = null;switch(basename($_SERVER['PHP_SELF'])){	case "company.php":	{			$cmtx_identifier = ((isset($_REQUEST['CompanyId']))?$_REQUEST['CompanyId']:$_REQUEST['e1'])."-company-".$tab;		break;	}	case "product.php":	{			$cmtx_identifier = ((isset($_REQUEST['e1']))?$_REQUEST['e1']:'')."-product-".$tab;			break;	}	case "moa.php":	{			$cmtx_identifier = ((isset($_REQUEST['MoaId']))?$_REQUEST['MoaId']:'')."-moa-".$tab;				break;	}	case "disease.php":	{			$cmtx_identifier = ((isset($_REQUEST['DiseaseId']))?$_REQUEST['DiseaseId']:'')."-disease-".$tab;		break;	}	case "investigator.php":	{			$cmtx_identifier = ((isset($_REQUEST['InvestigatorId']))?$_REQUEST['InvestigatorId']:'')."-investigator-".$tab;		break;	}}if($cmtx_identifier !== null){	switch(strtolower($tab))	{		case "companies":			$cmtx_reference = $CompanyName."-Companies";			break;		case "company":		case "moa":			$cmtx_reference = $CompanyName."-Product";			break;		case "diseasetrac":			$cmtx_reference = $DiseaseName."-Diseases";			break;		case "products":			$cmtx_reference = $ProductName."-Product";			break;			case "investigatortrac":		case "investigators":			$cmtx_reference = $InvestigatorName."-Investigator";			break;			case "moas":			$cmtx_reference = $InvestigatorName."-Mechanisms of Action";			break;			case "otttrac":		case "ott":		case "diseaseott":		case "investigatorott":			$cmtx_reference = $ProductName."-Trials";			break;				}		$cwd = getcwd();	chdir ("..");	$cmtx_path = 'comments/';	define('IN_COMMENTICS', 'true'); //no need to edit this line	require $cmtx_path . 'includes/commentics.php'; //no need to edit this line		chdir ($cwd);?><style> #footer{	display:block !important; }</style><?php}?><br /><br />