<?php
	// Register global variable.
	$baseURL = "";
	$actionURL = "";
	$sid = "";

	// The hasTag is used to prevent the robot from accessing accidentally by unexpected users.
	$hashTag = "";

	// Please put the forum ID here. For example, 314.
	$forumID = "";

	// Please type the host URL of PHPBB 3.
	$hostURL = "http://localhost/phpbb3";

	$loginURL = "$hostURL/ucp.php?mode=login";
	// Account and password that can access the forum ID. 
	$account = "admin";
	$password = "admin";

	// Include this if you want to modify the above configurations.
	include_once('config.php');
?>
<?php
	function Login($loginURL, $account, $password)
	{
		// get the content 
		global $baseURL, $actionURL, $sid;

		preg_match('/([:A-Za-z0-9\/.]+)\/[\w_]*.php[\w\?#&=]+/', $loginURL , $matches, PREG_OFFSET_CAPTURE);
		$baseURL = (is_array($matches) && sizeof($matches) >= 1) ? $matches[1][0]:"";

		$doc = new DomDocument;
		$doc->loadHtml(file_get_contents($loginURL));
		$elementList = $doc->getElementsByTagName("form");

		$actionURL = "";
		$sid = "";

		foreach($elementList as $element)
		{


			if($element->hasAttribute("action") && strstr($element->getAttribute("action"), "ucp.php"))
			{
				$actionURL = str_replace("./", "", $element->getAttribute("action"));

				preg_match('/sid=([\w]+)/', $actionURL, $matches, PREG_OFFSET_CAPTURE);
				$sid = (is_array($matches) && sizeof($matches) >= 1) ? $matches[1][0]:"";
				$actionURL = $baseURL . "/" . $actionURL;

				break;
			}	
		}

		// Post the form. 
		$postdata = http_build_query(
				array( 
					"username" => $account,
					"password" => $password,
					"sid" => $sid,
					"login" => "login"
					) );

		$opts = array("http" =>
				array (
					"method" => "POST",
					"header" => "Content-type: application/x-www-form-urlencoded",
					"content" => $postdata
					)
				);

		$context = stream_context_create($opts);

		$result = file_get_contents($actionURL, false, $context);
		
		return $result;

	}

	function GetNewSid($result)
	{

		$sid = "";
		$doc = new DomDocument();
		$doc->loadHtml($result);
		$element = $doc->getElementById("wrapcentre");
		$list = $element->getElementsByTagName("a");

		foreach($list as $item)
		{
			$tempURL = $item->getAttribute('href');
			if( strstr($tempURL, "index.php") !== FALSE)
			{

				preg_match('/sid=([\w]+)/', $tempURL, $matches, PREG_OFFSET_CAPTURE);
				$sid = (is_array($matches) && sizeof($matches) >= 1) ? $matches[1][0]:"";
				// echo "URL:" . $tempURL . "<BR>";
				// echo "SID: " . $sid . "<BR>";
				break;
			}
		}
		return $sid;
	}

	function BrowsingForumTopics($browsingURL, & $count)
	{
		global $baseURL;

		echo "Browsing Forum URL: " . $browsingURL . "<BR>";

		$doc = new DomDocument();
		$doc->loadHtml(file_get_contents($browsingURL));

		$element = $doc->getElementById("pagecontent");
		$list = $element->getElementsByTagName("a");

		foreach($list as $item)
		{
			$tempURL = $item->getAttribute('href');
			if( strstr($tempURL, "viewtopic.php") !== FALSE && $item->hasAttribute('title'))
			{
				$tempURL = $baseURL . "/" . str_replace("./", "", $tempURL);
				echo "Find URL:<a href='" . $tempURL . "'>" . $item->textContent . "</a><br>";
				if( strlen(file_get_contents($tempURL)) > 0 )
				{
					echo "Browsing Okay !<BR>";
					$count++;
				}
			}
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<title>Hack PHPBB3</title>
<meta charset="UTF-8" />
</head>
<body>
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_NOTICE);

if(isset($_GET['hashTag']) && strcmp($_GET['hashTag'], $hashTag) == 0)
{
	$result = Login($loginURL, $account, $password);

	$sid = GetNewSid($result);

	$count = 0;
	$forumURLList = array();

	$browsingURL = $baseURL . "/" . "viewforum.php?f=" . $forumID . "&sid=" . $sid;
	echo "Browsing Forum URL: " . $browsingURL . "<BR>";

	$doc = new DomDocument();
	$doc->loadHtml(file_get_contents($browsingURL));

	$element = $doc->getElementById("pagecontent");
	$list = $element->getElementsByTagName("a");
	
	foreach($list as $item)
	{
		$tempURL = $item->getAttribute('href');
		if( strstr($tempURL, "viewtopic.php") !== FALSE && $item->hasAttribute('title'))
		{
			$tempURL = $baseURL . "/" . str_replace("./", "", $tempURL);
			echo "Find URL:<a href='" . $tempURL . "'>" . $item->textContent . "</a><br>";
			if( strlen(file_get_contents($tempURL)) > 0 )
			{
				echo "Browsing Okay !<BR>";
				$count++;
			}
		}
		else if( strstr($tempURL, "viewforum.php") !== FALSE && strstr($tempURL, "start=") !== FALSE )
		{
			$forumURL = $baseURL . "/" . str_replace("./", "", $tempURL);
			if(isset($forumURLList[$forumURL]) === FALSE)
			{
				$forumURLList[$forumURL] = true;
				echo "Find Forum URL:" . $forumURL . "<BR>";
			}
		}
	}

	$list = array_keys($forumURLList);
	foreach($list as $forumURL)
	{
		BrowsingForumTopics($forumURL, $count);
	}

	echo "The number of pages refreshed:" . $count;
}
else
{
	echo "Sorry. You do not have the permission to access it";
}

?>

</body>
</html>

