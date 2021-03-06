<div id='profileArea'>
<?php
	// boldShow($type, $content) returns escaped html with $type in bold followed by
	//							 $content in normal style.
	//							 If the profile is a form, boldShow will turn it into
	//							 an input field.
	function boldShow($type, $content, $profileType = "", $textblock = false)
	{	
		
		$type = htmlentities($type, ENT_QUOTES, "UTF-8");
		$content = htmlentities($content, ENT_QUOTES, "UTF-8");
		return "<p class='profiledata'><b>$type:</b> $content</p>\n";
	}
	
	// Calculates the current age based on the birthdate
	function getAge($birthdate)
	{
		list($year, $month, $day) = explode("-", $birthdate);
		$year_diff  = date("Y") - $year;
    	$month_diff = date("m") - $month;
    	$day_diff   = date("d") - $day;
    	
    	// If the month-difference is smaller then 0, or is 0 with a smaller day-difference,
    	// the user hasn't had his/her birthday this year yet.
    	if($month_diff < 0) {
    		$year_diff--;
    	}
    	else if($month_diff == 0 && $day_diff < 0) {
    		$year_diff--;
    	}
    	return $year_diff;
	}
	
	function getLikePicture(array $likestatus)
	{
		$status = array();
		foreach($likestatus as $liked) {
			if($liked) {
				$status[] = "liked";
			}
			else {
				$status[] = "pending";
			}
		}
		return '<img class="likepicture" src="' . base_url() . "img/$status[0]-$status[1].jpg"
		        . '" width="90" height="60" />';
	}

	foreach($profiles as $profile) {
		
		
		// A profilebox is opened and the username and gender for the public profile are echoed.
		// If the profile is big, the profilebox is a section.
		if($profileType == "big") {
			$boxType = "<div id='bigprofile' ";
		}
		// If the profile is small, it is a link
		else {
			$boxType = "<a href=\"". base_url(). 
				"/index.php/viewprofile/showprofile/". $profile['userId']. "\" ";
		}
		
		// The gender is converted to readable matter
		if($profile['gender'] == '0') {
			$gender = "M";
		}
		else {
			$gender = "F";
		}
		// The username and gender are displayed extra big
		echo $boxType. "class='profilebox'>\n". heading(htmlentities($profile['username']. " (".
				 $gender. ")", ENT_QUOTES, "UTF-8"), 3);
		
		$loggedIn = $this->authentication->userLoggedIn();
		$imgfile;
		if($loggedIn && $profile['picture'])
		{
		    // The file name does not depend on user input and therefore does not need to be 
		    // escaped. 
		    $imgfile = 'pictures/' . $profile['picture'];
		}
		else
		{
		    $imgfile = 'silhouette-' . ($profile['gender'] == 0 ? 'man' : 'woman') . '.jpg';
		}
		
		// Show thumbnail.
		$imgurl = base_url() . 'img/' . $imgfile;
		echo '<img class="profilepicture" src="' . $imgurl . '" alt="Profile photo" width="125" height="150" />';
		
		// The thumbnail is shown. If the user is logged in, the real picture, else, a silhouette.
		if($loggedIn) 
		{
			// If there is a mutual like, show the users name
			if(isset($profile['likestatus']) && $profile['likestatus'][0] && $profile['likestatus'][1]) {
				echo boldShow("Name", $profile['firstName']. " ". $profile['lastName']);
				echo boldShow("Email", $profile['email']);
			}
		} 
		
		// The age is displayed
		echo boldShow("Age", getAge($profile['birthdate']), $profileType);
		
		// If the profileType is small, the discription should show only the first line.
		if($profileType == "small") {
			$point = strpos($profile['description'], '.');		// Lines usually end with a point.
			
			// However, if the line is longer then 100 characters, it is cut off.
			if($point === false or $point > 100) {
				$disc = substr($profile['description'], 0, 100). "...";
			}
			else {
				$disc = substr($profile['description'], 0, $point+1);
			}
		}
		else {
			$disc = $profile['description'];
		}
		
		// And the discription, in correct length, is displayed.
		// The true turns it into a textarea if the profile is a form.
		echo boldShow("About me", $disc, $profileType, true);
		
		// The usertype and preference are displayed
		echo boldShow("Personality", $profile['personality']);
		echo boldShow("Preference", $profile['preference']);
		
		// If the profile is small, only a maximum of 4 random brands are shown
		if($profileType == 'small') {
			$maxBrands = min(array(3, count($profile['brands']) - 1));
			shuffle($profile['brands']);
		}
		// else, if the profile is big, all brands are shown
		else $maxBrands = count($profile['brands']) - 1;
		
		$brands = "";
		for($b = 0; $b < $maxBrands; $b++) {
			$brands .= $profile['brands'][$b]. ", ";
		}
		// To avoid a comma at the end, the last brand is added after the loop.
		$brands .= $profile['brands'][$b];
		echo boldShow("Favorite brands", $brands);
		
		// The like-status should be displayed.
		if($this->authentication->userLoggedIn()) {
			if($profile['likestatus'][0]) 
			{
				if($profile['likestatus'][1])
				{
				    echo "You both like each other". br();
				}
				else
				{
				    echo "You liked this user, but have yet to receiev a like back...". br();
				}
			}
			else if($profile['likestatus'][1])  
			{
			    echo "The other user liked you, like back?". br();
			}
			else
			{ 
			    echo "this user has not liked you yet, but you can be the first...". br();
			}
			
			if($profileType == 'big' && !$profile['likestatus'][0]) {
				
				echo "<h3 id='likebutton'>". htmlspecialchars("<< Click here to like user >>"). "</h3>";
			}
			else {
				echo getLikePicture($profile['likestatus']);
			}
		}
		
		// If the profilebox is big, the div is closed
		if($profileType == "big") {
			echo "</div>";
		}
		else {	// and if it is small, it's link is closed.
			echo "</a>";
		}
	}
?>
</div>

<script type="text/javascript">
// Add mouse listener to the likebutton. 

$('#likebutton').click(function()
{
	var ok = confirm("<?php echo 'Do you want to like '. $profile['username']. '?'; ?>");

	if(ok)
	{
		// Send like request. 
		$.post('<?php echo base_url(). '/index.php/viewprofile/like'; ?>',
			   {otherId: <?php echo $profile['userId'] ?>},
			   function()
			   {
					// Refresh page when done. 
					window.location.reload();
			   });
	}
});
</script>