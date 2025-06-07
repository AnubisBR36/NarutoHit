<?PHP
do{ // Used if we want to display some error to the user and halt the rest of the script


$user_query = cute_query_string($QUERY_STRING, array( "comm_start_from","start_from", "archive", "subaction", "id", "ucat"));
$user_post_query = cute_query_string($QUERY_STRING, array( "comm_start_from", "start_from", "archive", "subaction", "id", "ucat"), "post");
//#############
// Define Categories
//#############
$cat_lines = file("$cutepath/data/category.db.php");
foreach($cat_lines as $single_line){
	$cat_arr = explode('|', $single_line);
	$cat[$cat_arr[0]] = $cat_arr[1];
	$cat_icon[$cat_arr[0]]=$cat_arr[2];
}

//////////////////////////////////////////
// Function:	Category ID to Name
// Description:	convert to category name from ID
if (!function_exists('catid2name')){
	function catid2name($thecat){
		global $cat;

		if(strstr($thecat, ',')){
			$thecat_arr = explode(',', $thecat);
			foreach($thecat_arr as $single_thecat){
				if($thecat_not_first){
					$thecat_str .= ', '. $cat[$single_thecat];
				}
				else{
					$thecat_str .= $cat[$single_thecat];
				}

				$thecat_not_first = TRUE;
			}

			return $thecat_str;
		}
		else{
			return $cat[$thecat];
		}
	}
}

//###########
// Define Users
//###########
$all_users = file($cutepath.'/data/users.db.php');
foreach($all_users as $user){
		if(strpos($member_db_line, '<'.'?') === false){
		$user_arr = explode('|', $user);
		if($user_arr[4] != ''){
			if($user_arr[7] != 1 and $user_arr[5] != ''){
				$my_names[$user_arr[2]] = '<a href="mailto:'.$user_arr[5].'">'.$user_arr[4].'</a>';
			}
			else{
				$my_names[$user_arr[2]] = $user_arr[4];
			}
			$name_to_nick[$user_arr[2]] = $user_arr[4];
		}
		else{
			if($user_arr[7] != 1 and $user_arr[5] != ''){
				$my_names[$user_arr[2]] = "<a href=\"mailto:$user_arr[5]\">$user_arr[2]</a>";
			}
			else{
				$my_names[$user_arr[2]] = "$user_arr[2]";
			}
			$name_to_nick[$user_arr[2]] = $user_arr[2];
		}

		if($user_arr[7] != 1){
			$my_mails[$user_arr[2]] = $user_arr[5];
		}
		else{
			$my_mails[$user_arr[2]] = '';
		}
		$my_passwords[$user_arr[2]] = $user_arr[3];
		$my_users[] = $user_arr[2];
	}
}
//###############
//  Activate Postponed Articles
//###############

ResynchronizePostponed();

//###############
// Auto-Archive Function
//###############

if($config_auto_archive == 'yes'){
	ResynchronizeAutoArchive();
}

//############
//  Add Comment
//############
if($allow_add_comment){

	$name = trim($name);
	$mail = trim($mail);
	$id = (int) $id; // Yes it's stupid how I didn't think about this :/

	//----------------------------------
	// Check the lenght of comment, include name + mail
	//----------------------------------

	if(strlen(utf8_decode($name)) > 50){
		echo '<div style="text-align: center">'.$say['name_long'].'</div>';
		$CN_HALT = TRUE;
		break 1;
	}
	if(strlen($mail) > 50){
		echo '<div style="text-align: center">'.$say['email_long'].'</div>';
		$CN_HALT = TRUE;
		break 1;
	}
	if(strlen(utf8_decode($comments)) > $config_comment_max_long and $config_comment_max_long != '' and $config_comment_max_long != '0'){
		echo '<div style="text-align: center">'.$say['comm_long'].'</div>';
		$CN_HALT = TRUE;
		break 1;
	}

	//------------
	// Get the IP
	//------------
	$foundip = TRUE;
	if($_SERVER['HTTP_CLIENT_IP']) $ip = $_SERVER['HTTP_CLIENT_IP'];
	else if($_SERVER['REMOTE_ADDR']) $ip = $_SERVER['REMOTE_ADDR'];
	else if($_SERVER['HTTP_X_FORWARDED_FOR']) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else{
		$ip = 'not detected';
		$foundip = FALSE;
	}

	if(!$foundip or !preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip)){
		$ip = 'not detected';
		$foundip = FALSE;
	} //ensure that what we have is a real IP
	//-------------------
	// Flood Protection
	//-------------------
	if($config_flood_time != 0 and $config_flood_time != ''){
		if(flooder($ip, $id) == TRUE ){
			echo('<div style="text-align: center">'.str_replace('{x}', $config_flood_time, $say['flood_warn']).'</div>');
			$CN_HALT = TRUE;
			break 1;
		}
	}

	//---------------------
	// Check if IP is blocked
	//---------------------
	$blockip = FALSE;
	$old_ips = file($cutepath.'/data/ipban.db.php');
	$new_ips = fopen($cutepath.'/data/ipban.db.php', 'w');
	@flock($new_ips, 2);
	foreach($old_ips as $old_ip_line){
		$ip_arr = explode('|', $old_ip_line);
		//implemented wildcard match
		$ip_check_matches = 0;
		$db_ip_split = explode('.', $ip_arr[0]);
		$this_ip_split = explode('.', $ip);

		for($i_i=0;$i_i<4;$i_i++){
			if($this_ip_split[$i_i] == $db_ip_split[$i_i] or $db_ip_split[$i_i] == '*'){
				$ip_check_matches += 1;
			}
		}

		if($ip_check_matches == 4){
			$countblocks = $ip_arr[1] = $ip_arr[1] + 1;
			fwrite($new_ips, "$ip_arr[0]|$countblocks||\n");
			$blockip = TRUE;
		}
		else {
			fwrite($new_ips, $old_ip_line);
		}
	}
	@flock($new_ips, 3);
	fclose($new_ips);
	if($blockip){
		echo('<div style="text-align: center">'.$say['ip_ban'].'</div>');
		$CN_HALT = TRUE;
		break 1;
	}

	//------------------------------
	// Check if the name is protected
	//------------------------------
	$is_member = FALSE;
	$name_ = utf8_strtolower(utf8_htmlentities(trim($name)));
	if($utf8_error){ echo $say['utf8']; $CN_HALT = true; break 2; }

	foreach($all_users as $member_db_line){
		if(strpos($member_db_line, '<'.'?') === false and $member_db_line != ''){
			$user_arr = explode('|', $member_db_line);

			//if the name is protected
			if(utf8_strtolower($user_arr[2]) == $name_ || utf8_strtolower($user_arr[4]) == $name_){

				require($cutepath.'/data/loginban.db.php');
				if(!isset($config_login_ban)){ $config_login_ban = 5; }
				if($config_login_ban > 0 && $loginban[$_SERVER['REMOTE_ADDR']] >= $config_login_ban && (time() - $loginban_stamp[$_SERVER['REMOTE_ADDR']]) < 6*3600){
					$minutez = ceil((6*3600 - (time() - $loginban_stamp[$_SERVER['REMOTE_ADDR']]))/60);
					echo str_replace(array('{x}', '{y}'), array($minutez, round($minutez/60, 2)), $say['ban']);
					$CN_HALT = true; break 2;
				}

				if($user_arr[3] != md5($password) && $name != ''){
					if(trim($password) != ''){
						$loginban[$_SERVER['REMOTE_ADDR']]++;
						$loginban_stamp[$_SERVER['REMOTE_ADDR']] = time();
						$open = fopen($cutepath.'/data/loginban.db.php', 'w');
						fwrite($open, '<'.'?php $loginban = '.var_export($loginban, true).'; $loginban_stamp = '.var_export($loginban_stamp, true).'; ?'.'>');
						fclose($open);
						echo $say['wrong_pw'].' '.str_replace('{x}', $config_login_ban - $loginban[$_SERVER['REMOTE_ADDR']], $say['attempts']);
					}

				$comments = utf8_htmlentities($comments);
				$name = utf8_htmlentities($name);
				$mail = str_replace(array('"', "\n", "\r"), array('&quot;', '', ''), $mail);
				if($utf8_error){ 
					echo $say['utf8'];
					$CN_HALT = true; break 2;
				}

				echo '<div style="text-align: center">'.$say['pass_prompt'].'<br />
				   <form name=passwordForm id=passwordForm method="post" accept-charset="utf-8" action="">
				   '.$say['password'].': <input type="password" name="password" />
				   <input type="hidden" name="name" value="'.$name.'" />
				   <input type="hidden" name="comments" value="'.$comments.'" />
				   <input type="hidden" name="mail" value="'.$mail.'" />
				   <input type="hidden" name="ip" value="'.$ip.'" />
				   <input type="hidden" name="subaction" value="addcomment" />
				   <input type="hidden" name="show" value="'.$show.'" />
				   <input type="hidden" name="ucat" value="'.$ucat.'" />
				   '.$user_post_query.'
				   <input type="submit" /> <br>
				   <input type="checkbox" name="CNrememberPass" value=1 /> '.$say['remember'].'
				   </form></div>';
				$CN_HALT = TRUE;
				break 2;
			} }

			if(strtolower($user_arr[2]) == strtolower($name)) $is_member = TRUE;
				//----------------------------------
				// Member wants to save his pass in cookie ?
				//----------------------------------
				if($CNrememberPass == 1){
					if(file_exists("$cutepath/remember.js")){
						echo "<script type=\"text/javascript\" src=\"$config_http_script_dir/remember.js\"></script>";
						echo "<script>CNRememberPass('".md5($password)."')</script>";
					}
				}
			}
		}

		//----------------------------------
		// Check if only members can comment
		//----------------------------------
		if($config_only_registered_comment == 'yes' and !$is_member){
			echo '<div style="text-align: center">'.str_replace('{x}', htmlspecialchars($name), $say['reg_only_comm']).'</div>';
			$CN_HALT = TRUE;
			break 1;
		}

		//----------------------------------
		// Convert name, mail, comment + validation
		//----------------------------------
		$comments = replace_comment('add', $comments);
		$name = replace_comment('add', preg_replace("/\n/", "", $name));
		$mail = replace_comment('add', preg_replace("/\n/", "", $mail));

		if(isset($utf8_error) && $utf8_error === true){
			msg('error', $say['error'], $say['utf8'], 'javascript:history.go(-1)');
		}

		if(trim($name) == ''){
			echo('<div style="text-align: center">'.$say['blank_user'].'<br /><a href="javascript:history.go(-1)">'.$say['back'].'</a></div>');
			$CN_HALT = TRUE;
			break 1;
		}
		if(trim($mail) == ''){
			$mail = 'none';
		}
		else{
			$ok = FALSE;
			if(preg_match("/^[\.A-z0-9_\-\+]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/", $mail)) $ok = TRUE;
			elseif($config_allow_url_instead_mail == 'yes' and preg_match("/((http(s?):\/\/)|(www\.))([\w\.]+)([\/\w+\.-?]+)/", $mail)) $ok = TRUE;
			elseif($config_allow_url_instead_mail != 'yes'){
				echo('<div style="text-align: center">'.$say['invalid_mail'].'<br /><a href="javascript:history.go(-1)">'.$say['back'].'</a></div>');
				$CN_HALT = TRUE;
				break 1;
			}
			else{
				echo('<div style="text-align: center">'.$say['invalid_mailurl'].'<br /><a href="javascript:history.go(-1)">'.$say['back'].'</a></div>');
				$CN_HALT = TRUE;
				break 1;
			}
		}

		if($comments == ''){
			echo('<div style="text-align: center">'.$say['blank_comm'].'<br /><a href="javascript:history.go(-1)">'.$say['back'].'</a></div>');
			$CN_HALT = TRUE;
			break 1;
		}

		$time = time()+($config_date_adjust*60);

		//-----------------
		// Wrap long words
		//-----------------
		if($config_auto_wrap > 1){
			$comments = utf8_wordwrap($comments, $config_auto_wrap);
		}

		//----------------------------------
		// Add The Comment ... Go Go GO!
		//----------------------------------

		$old_comments = file($comm_file);
		$new_comments = fopen($comm_file, 'w');
		@flock($new_comments, 2);
		$found = FALSE;
		foreach($old_comments as $old_comments_line){
			$old_comments_arr = explode('|>|', $old_comments_line);
			if($old_comments_arr[0] == $id){
				$old_comments_arr[1] = trim($old_comments_arr[1]);
				fwrite($new_comments, "$old_comments_arr[0]|>|$old_comments_arr[1]$time|$name|$mail|$ip|$comments||\n");
				$found = TRUE;
			}
			else{
				fwrite($new_comments, $old_comments_line);
//if we do not have the news ID in the comments.txt we are not doing anything (see comment below) (must make sure the news ID is valid)
			}
		}
		if(!$found){

			echo('<div style="text-align: center">'.$say['db_error'].'<br /><a href="javascript:history.go(-1)">'.$say['back'].'</a></div>');
			$CN_HALT = TRUE;
			break 1;
		}
		@flock($new_comments, 3);
		fclose($new_comments);

		//----------------------------------
		// Sign this comment in the Flood Protection
		//----------------------------------
		if($config_flood_time != '0' and $config_flood_time != ''){
			$flood_file = fopen($cutepath.'/data/flood.db.php', 'a');
			@flock($flood_file, 2);
			fwrite($flood_file, time()."|$ip|$id|\n");
			@flock($flood_file, 3);
			fclose($flood_file);
		}
		//----------------------------------
		// Notify for New Comment ?
		//----------------------------------

		if($config_notify_comment == 'yes' and $config_notify_status == 'active'){
			send_mail($config_notify_email, 'CuteNews - New Comment Added', "New Comment was added by $name:\n<br />--------------------------<br />$comments");
		}

		echo "<script type=\"text/javascript\">window.location=\"$PHP_SELF?subaction=showfull&id=$id&ucat=$ucat&archive=$archive&start_from=$start_from&$user_query\";</script>";
	}
//###############
// Show Full Story
//###############

if($allow_full_story){

	if(!file_exists($news_file)){ die('Error!<br>news file does not exists!'); }
	$all_active_news = file($news_file);

	foreach($all_active_news as $active_news){
		$news_arr = explode('|', $active_news);
		if($news_arr[0] == $id and (!$catid or $catid == $news_arr[6])){
			$found = TRUE;
/* DEBUG */		if($news_arr[4] == '' and (strpos($template_full, '{short-story}') === false) ){ $news_arr[4] = $news_arr[3]; }

			if($my_names[$news_arr[1]]){ $my_author = $my_names[$news_arr[1]]; }
			else{ $my_author = $news_arr[1]; }

			$output = str_replace('{title}', $news_arr[2], $template_full);
			$output = str_replace('{date}', date($config_timestamp_active, $news_arr[0]), $output);
			$output = str_replace('{author}', $my_author, $output);
			$output = str_replace('{short-story}', $news_arr[3], $output);
			$output = str_replace('{full-story}', $news_arr[4], $output);
			if($news_arr[5] != ''){
				$output = str_replace('{avatar}', '<img alt="" src="'.$news_arr[5].'" style="border: none;" alt="" />', $output);
			}
			else{ $output = str_replace('{avatar}', '', $output); }
			$output = str_replace('{avatar-url}', $news_arr[5], $output);
			$output = str_replace('{comments-num}', countComments($news_arr[0], $archive), $output);
			$output = str_replace('{category}', catid2name($news_arr[6]), $output);
			$output = str_replace('{category-id}', $news_arr[6], $output);
			if(strpos($news_arr[6], ',') !== false){
				$cat_arr = explode(',', $news_arr[6]);
				$cat_full = '';
				foreach($cat_arr as $cat_item){
					if($cat_icon[$cat_item] != ''){
						$cat_full .= '<img style="border: none" alt="'.$cat_icon[$cat_item].' icon" src="'.$cat_icon[$cat_item].'" />';
					}
				}
				$output = str_replace('{category-icon}', $cat_full, $output);
			}
			else if($cat_icon[$news_arr[6]] != ''){
				$output = str_replace('{category-icon}', '<img style="border: none" alt="'.$cat[$news_arr[6]].' icon" src="'.$cat_icon[$news_arr[6]].'" />', $output);
			}
			else{ $output = str_replace('{category-icon}', '', $output); }

			if($config_comments_popup == 'yes'){
				$output = str_replace('[com-link]', "<a href=\"#\" onclick=\"window.open('$config_http_script_dir/show_news.php?subaction=showcomments&amp;template=$template&amp;id=$news_arr[0]&amp;archive=$archive&amp;start_from=$my_start_from&amp;ucat=$news_arr[6]', '_News', '$config_comments_popup_string');return false;\">", $output);
			}
			else{
                                		$output = str_replace('[com-link]', "<a href=\"$PHP_SELF?subaction=showcomments&amp;id=$news_arr[0]&amp;archive=$archive&amp;start_from=$my_start_from&amp;ucat=$news_arr[6]&amp;$user_query\">", $output);
			}
			$output = str_replace('[/com-link]', '</a>', $output);
			$output = str_replace('{author-name}', $name_to_nick[$news_arr[1]], $output);

			if($my_mails[$news_arr[1]] != ''){
				$output = str_replace('[mail]', '<a href="mailto:'.$my_mails[$news_arr[1]].'">', $output);
				$output = str_replace('[/mail]', '</a>', $output);
			}
			else{
				$output = str_replace('[mail]', '', $output);
				$output = str_replace('[/mail]', '', $output);
			}
			$output = str_replace('{news-id}', $news_arr[0], $output);
			$output = str_replace('{archive-id}', $archive, $output);
			$output = str_replace('{php-self}', $PHP_SELF, $output);
			$output = str_replace('{cute-http-path}', $config_http_script_dir, $output);

			$output = replace_news('show', $output);
			echo $output;
		}
	}
	if(!$found){
		// Article ID was not found, if we have not specified an archive, try to find the article in some archive.

		// Auto-Find ID In archives
		//--------------------------

		if(!$archive or $archive == ''){
			//get all archives. (if any) and fit our lost id in the most proper archive.
			$lost_id = $id;
			$all_archives = FALSE;
			$hope_archive = FALSE;

			if(!$handle = opendir($cutepath.'/data/archives')){ echo("<!-- Can not open directory $cutepath/data/archives --> "); }
			while (false !== ($file = readdir($handle))){
				if($file != '.' and $file != '..' and !is_dir('./data/archives/'.$file) and substr($file, -9) == 'news.arch'){
					$file_arr = explode('.', $file);
					$all_archives[] = $file_arr[0];
				}
			}
			closedir($handle);

			if($all_archives){
				sort($all_archives);
				if(isset($all_archives[1])){
					foreach($all_archives as $this_archive){
						if($this_archive > $lost_id){ $hope_archive = $this_archive; break; }
					}
				}
				else{
					if($all_archives[0] > $lost_id){
						$hope_archive = $all_archives[0]; break;
					}
				}
			}
		}

		if($hope_archive){
			echo "
<center>You are now being redirected to the article in our archives<br>if the redirection fails, please <a href=\"$PHP_SELF?start_from=$start_from&amp;ucat=$ucat&amp;subaction=$subaction&amp;id=$id&archive=$hope_archive&amp;$user_query\">click here</a></center>
<SCRIPT LANGUAGE=\"JavaScript\">
<!--
window.location=\"$PHP_SELF?start_from=$start_from&ucat=$ucat&subaction=$subaction&id=$id&archive=$hope_archive&$user_query\";
// -->
</script>";
		}
		else{
			echo '<div style="text-align: center">'.str_replace('{x}', htmlspecialchars($id), $say['no_id']).'</div>';
		}

		$CN_HALT = TRUE;
		break 1;
	}
}
//###############
// Show Comments
//###############

if($allow_comments){

	$comm_per_page = $config_comments_per_page;

	$total_comments = 0;
	$showed_comments = 0;
	$comment_number = 0;
	$showed = 0;
	$all_comments = file($comm_file);

	foreach($all_comments as $comment_line){
		$comment_line = trim($comment_line);
		$comment_line_arr = explode('|>|', $comment_line);
		if($id == $comment_line_arr[0]){
			$individual_comments = explode('||', $comment_line_arr[1]);

			$total_comments = @count($individual_comments) - 1;

			$iteration = 0;
			if($config_reverse_comments == 'yes'){
				$iteration = count($individual_comments)+1;
				$individual_comments = array_reverse($individual_comments);
			}

			foreach($individual_comments as $comment){
			if($config_reverse_comments == 'yes'){
				$iteration--;
			}
			else{
				$iteration++;
			}

			$comment_arr = explode('|', $comment);
			if($comment_arr[0] != ''){

				if(isset($comm_start_from) and $comm_start_from != ''){
					if($comment_number < $comm_start_from){
						$comment_number++;
						continue;
					}
					elseif($showed_comments == $comm_per_page){
						break;
					}
				}

				$comment_number++;
				$comment_arr[4] = stripslashes(rtrim($comment_arr[4]));

				$output = str_replace('{author-name}', $comment_arr[1], $template_comment);

				$not_mail = TRUE;
				if($comment_arr[2] != 'none'){
if(preg_match('/^[\.A-z0-9_\-\+]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z]{1,4}$/', $comment_arr[2])){
	$url_target = ''; $mail_or_url = 'mailto:'; $not_mail = FALSE;
}
else{
					$url_target = 'target="_blank"';
					$mail_or_url = '';
					if(substr($comment_arr[2],0,3) == 'www'){ $mail_or_url = 'http://'; }
}

if($not_mail){ $output = str_replace('{author-name}', '<a target="_blank" href="'.$mail_or_url.$comment_arr[2].'">'.$comment_arr[1].'</a>', $template_comment); }
$output = str_replace('{author}', "<a $url_target href=\"$mail_or_url".stripslashes($comment_arr[2])."\">".stripslashes($comment_arr[1])."</a>", $output);
				}
				else{ $output = str_replace('{author}', $comment_arr[1], $output); }


/*                        $comment_arr[4] = preg_replace("/\b((http(s?):\/\/)|(www\.))([\w\.]+)([&-~\%\/\w+\.-?]+)\b/i", "<a href=\"http$3://$4$5$6\" target=\"_blank\">$2$4$5$6</a>", $comment_arr[4]);
                        $comment_arr[4] = preg_replace("/([\w\.]+)(@)([-\w\.]+)/i", "<a href=\"mailto:$0\">$0</a>", $comment_arr[4]);
*/

                                        $output = str_replace('{mail}', $comment_arr[2], $output);
                                        $output = str_replace('{date}', date($config_timestamp_comment, $comment_arr[0]), $output);
                                        $output = str_replace('{comment-id}', $comment_arr[0],$output);
                                        $output = str_replace('{comment}', "<a name=\"".$comment_arr[0]."\"></a>$comment_arr[4]",$output);
                                        $output = str_replace('{comment-iteration}', $iteration ,$output);

                                        $output = replace_comment('show', $output);
                                        echo $output;
                                        $showed_comments++;
                                        if($comm_per_page != 0 and $comm_per_page == $showed_comments){ break; }
                                }
                        }
                }
        }

    //----------------------------------
    // Prepare the Comment Pagination
    //----------------------------------

    $prev_next_msg = $template_comments_prev_next;

    // Previous link
    if(isset($comm_start_from) and $comm_start_from != "" and $comm_start_from > 0){
            $prev = $comm_start_from - $comm_per_page;
        $prev_next_msg = preg_replace("'\[prev-link\](.*?)\[/prev-link\]'si", "<a href=\"$PHP_SELF?comm_start_from=$prev&amp;archive=$archive&amp;subaction=showcomments&amp;id=$id&amp;ucat=$ucat&amp;$user_query\">\\1</a>", $prev_next_msg);
    }else{ $prev_next_msg = preg_replace("'\[prev-link\](.*?)\[/prev-link\]'si", "\\1", $prev_next_msg); $no_prev = TRUE; }

    // Pages
        if($comm_per_page){
    $pages_count = @ceil($total_comments/$comm_per_page);
    $pages_start_from = 0;
    $pages = "";
    for($j=1;$j<=$pages_count;$j++){
        if($pages_start_from != $comm_start_from){ $pages .= "<a href=\"$PHP_SELF?comm_start_from=$pages_start_from&amp;archive=$archive&amp;subaction=showcomments&amp;id=$id&amp;ucat=$ucat&amp;$user_query\">$j</a> "; }
                else{ $pages .= " <strong>$j</strong> "; }
        $pages_start_from += $comm_per_page;
        }
        $prev_next_msg = str_replace("{pages}", $pages, $prev_next_msg);
        }

    // Next link
    if($comm_per_page < $total_comments and $comment_number < $total_comments){
        $prev_next_msg = preg_replace("'\[next-link\](.*?)\[/next-link\]'si", "<a href=\"$PHP_SELF?comm_start_from=$comment_number&amp;archive=$archive&amp;subaction=showcomments&amp;id=$id&amp;ucat=$ucat&amp;$user_query\">\\1</a>", $prev_next_msg);
    }else{ $prev_next_msg = preg_replace("'\[next-link\](.*?)\[/next-link\]'si", "\\1", $prev_next_msg); $no_next = TRUE;}

    if(!$no_prev or !$no_next){
	echo $prev_next_msg;
    }



        $template_form = str_replace('{config_http_script_dir}', $config_http_script_dir, $template_form);
    //----------------------------------
    // Check if the remember script exists
    //----------------------------------
    $CN_remember_include = '';
    $CN_remember_form = '';
    if(file_exists("$cutepath/remember.js")){
            $CN_remember_include = "<script type=\"text/javascript\" src=\"$config_http_script_dir/remember.js\"></script><script>CNreadCookie();</script>";
            $CN_remember_form = "onsubmit=\"return CNSubmitComment()\"";
    }


    $smilies_form = "\n<script type=\"text/javascript\">
        //<![CDATA[
        function insertext(text){
        document.comment.comments.value+=\" \"+ text;
        document.comment.comments.focus();
        }
        //]]></script>
        <noscript>".$say['no_js']."
        </noscript>".insertSmilies('short', FALSE);

	$template_form = str_replace('{smilies}', $smilies_form, $template_form);

    echo "<form accept-charset=\"utf-8\" $CN_remember_form  method=\"post\" name=\"comment\" id=\"comment\" action=\"\">".$template_form."<div><input type=\"hidden\" name=\"subaction\" value=\"addcomment\" /><input type=\"hidden\" name=\"ucat\" value=\"$ucat\" /><input type=\"hidden\" name=\"show\" value=\"$show\" />$user_post_query</div></form>
                    \n $CN_remember_include";

}
//############
//    Active News
//############

if($allow_active_news){

	$all_news = file($news_file);
	if($reverse == TRUE){ $all_news = array_reverse($all_news); }

	$count_all = 0;
	if(isset($category) and $category != ''){
		foreach($all_news as $news_line){

			$news_arr = explode('|', $news_line);

			$is_in_cat = FALSE;
			if(strstr($news_arr[6],',')){ //if the article is in multiple categories
				$this_cats_arr = explode(',',$news_arr[6]);
				foreach($this_cats_arr as $this_single_cat){
					if($requested_cats and $requested_cats[$this_single_cat] == TRUE){ $is_in_cat=TRUE; break;}
				}
			}
			else{
				if($requested_cats and $requested_cats[$news_arr[6]] == TRUE){ $is_in_cat=TRUE;}
			}

			if($is_in_cat){ $count_all ++; }
			else{ continue; }
		}
	}
	else{
		$count_all = count($all_news);
	}

	$i = 0;
	$showed = 0;
	$repeat = TRUE;
	$url_archive = $archive;
	while($repeat != FALSE){

		foreach($all_news as $news_line){
			$news_arr = explode('|', $news_line);

			$is_in_cat = FALSE;
			if(strstr($news_arr[6],',')){ //if the article is in multiple categories
				$this_cats_arr = explode(',',$news_arr[6]);
				foreach($this_cats_arr as $this_single_cat){
					if($requested_cats and $requested_cats[$this_single_cat] == TRUE){ $is_in_cat=TRUE;}
				}
			}
			else{
				if($requested_cats and $requested_cats[$news_arr[6]] == TRUE){ $is_in_cat=TRUE;}
			}

			if(!$is_in_cat and $category != '' and isset($category)){ continue; }

			if(isset($start_from) and $start_from != ""){
				if($i < $start_from){ $i++; continue; }
				elseif($showed == $number){  break; }
			}

			if($my_names[$news_arr[1]]){ $my_author = $my_names[$news_arr[1]]; }
			else{ $my_author = $news_arr[1]; }

			$output = $template_active;
			$output = str_replace('{title}', $news_arr[2], $output);
			$output = str_replace('{author}', $my_author, $output);
			if($news_arr[5] != ''){
$output = str_replace('{avatar}', "<img alt=\"\" src=\"$news_arr[5]\" style=\"border: none;\" />", $output);
			}
			else{ $output = str_replace('{avatar}', '', $output); }
			$output = str_replace('{avatar-url}', $news_arr[5], $output);
			$output = str_replace('[link]',"<a href=\"$PHP_SELF?subaction=showfull&amp;id=$news_arr[0]&amp;archive=$archive&amp;start_from=$my_start_from&amp;ucat=$news_arr[6]&amp;$user_query\">", $output);
			$output = str_replace('[/link]',"</a>", $output);
			$output = str_replace('{comments-num}', countComments($news_arr[0], $archive), $output);
			$output = str_replace('{short-story}', $news_arr[3], $output);
			$output = str_replace('{full-story}', $news_arr[4], $output);
			$output = str_replace('{category}', catid2name($news_arr[6]), $output);
			$output = str_replace('{category-id}', $news_arr[6], $output);
			if(strpos($news_arr[6], ',') !== false){
				$cat_arr = explode(',', $news_arr[6]);
				$cat_full = '';
				foreach($cat_arr as $cat_item){
					if($cat_icon[$cat_item] == ''){ continue; }
					$cat_full .= '<img src="'.$cat_icon[$cat_item].'" alt="'.$cat[$cat_item].' icon" style="border: none" />';
				}
				$output = str_replace('{category-icon}', $cat_full, $output);
			}
			else if($cat_icon[$news_arr[6]] != ''){
$output = str_replace('{category-icon}', "<img alt=\"".$cat[$news_arr[6]]." icon\" style=\"border: none;\" src=\"".$cat_icon[$news_arr[6]]."\" />", $output);
			}
			else{ $output = str_replace('{category-icon}', '', $output); }

			$output = str_replace('{author-name}', $name_to_nick[$news_arr[1]], $output);

			if($my_mails[$news_arr[1]] != ''){
			 $output = str_replace('[mail]',"<a href=\"mailto:".$my_mails[$news_arr[1]]."\">", $output);
			 $output = str_replace('[/mail]','</a>', $output);
			}
			else{
			 $output = str_replace('[mail]', '', $output);
			 $output = str_replace('[/mail]','', $output);
			}

			$output = str_replace('{news-id}', $news_arr[0], $output);
			$output = str_replace('{archive-id}', $archive, $output);
			$output = str_replace('{php-self}', $PHP_SELF, $output);
			$output = str_replace('{cute-http-path}', $config_http_script_dir, $output);


			//vars for RSS
			if($template == 'rss'){
				$output = str_replace('{date}', date('r', $news_arr[0]), $output);
				if($rss_news_include_url == '' or !$rss_news_include_url){
					$rss_news_include_url = '$config_http_script_dir/show_news.php';
				}
				$output = str_replace('{rss-news-include-url}', $rss_news_include_url, $output);
			}
			else{
				$output = str_replace('{date}', date($config_timestamp_active, $news_arr[0]), $output);
			}

			$output = replace_news('show', $output);


			if($news_arr[4] != '' or $action == 'showheadlines'){//if full story
				if($config_full_popup == 'yes'){

                    $output = preg_replace("/\\[full-link\\]/","<a href=\"#\" onclick=\"window.open('$config_http_script_dir/show_news.php?subaction=showfull&amp;id=$news_arr[0]&amp;archive=$archive&amp;template=$template', '_News', '$config_full_popup_string');return false;\">", $output);
            }else{
                    $output = str_replace("[full-link]","<a href=\"$PHP_SELF?subaction=showfull&amp;id=$news_arr[0]&amp;archive=$archive&amp;start_from=$my_start_from&amp;ucat=$news_arr[6]&amp;$user_query\">", $output);
            }
                $output = str_replace("[/full-link]","</a>", $output);
                }else{
                        $output = preg_replace("'\\[full-link\\].*?\\[/full-link\\]'si","<!-- no full story-->", $output);
                }

                if($config_comments_popup == "yes"){
                        $output = str_replace("[com-link]","<a href=\"#\" onclick=\"window.open('$config_http_script_dir/show_news.php?subaction=showcomments&amp;template=$template&amp;id=$news_arr[0]&amp;archive=$archive&amp;start_from=$my_start_from&amp;ucat=$news_arr[6]', '_News', '$config_comments_popup_string');return false;\">", $output);
                }else{
                        $output = str_replace("[com-link]","<a href=\"$PHP_SELF?subaction=showcomments&amp;id=$news_arr[0]&amp;archive=$archive&amp;start_from=$my_start_from&amp;ucat=$news_arr[6]&amp;$user_query\">", $output);
                }
                $output = str_replace("[/com-link]","</a>", $output);


                echo $output;
                $showed++;
        $i++;

        if($number != 0 and $number == $i){ break; }
            }
        $used_archives[$archive] = TRUE;

// Archives Loop
        if($i < $number and $only_active != TRUE){

                        if(!$handle = opendir("$cutepath/data/archives")){ die("<div style=\"text-align: center;\">Cannot open directory $cutepath/data/archives</div>"); }
                         while (false !== ($file = readdir($handle))){
                                 if($file != "." and $file != ".." and substr($file, -9) == 'news.arch'){
			$file_arr = explode('.', $file);
			$archives_arr[$file_arr[0]] = $file_arr[0];
		       }
                         }
                        closedir($handle);

            $archives_arr[$in_use]="";
            $in_use = max($archives_arr);

                        if($in_use != "" and !$used_archives[$in_use]){
                                $all_news = file("$cutepath/data/archives/$in_use.news.arch");
                                $archive = $in_use;
                $used_archives[$in_use] = TRUE;
                        }else{ $repeat = FALSE; }

            }else{ $repeat = FALSE; }
        }

// << Previous   &   Next >>

    $prev_next_msg = $template_prev_next;

//--------------
// Previous link
//--------------
	if(isset($start_from) and $start_from != "" and $start_from > 0){
		$prev = $start_from - $number;
		$prev_next_msg = preg_replace("'\[prev-link\](.*?)\[/prev-link\]'si", "<a href=\"$PHP_SELF?start_from=$prev&amp;ucat=$ucat&amp;archive=$url_archive&amp;subaction=$subaction&amp;id=$id&amp;$user_query\">\\1</a>", $prev_next_msg);
	}
	else{
		$prev_next_msg = preg_replace("'\[prev-link\](.*?)\[/prev-link\]'si", "\\1", $prev_next_msg); $no_prev = TRUE; }

//----------
// Pages
//----------
	if($number){
	$pages_count = @ceil($count_all/$number);
	$pages_start_from = 0;
	$pages = '';
	for($j=1;$j<=$pages_count;$j++){
		if($pages_start_from != $start_from){ $pages .= "<a href=\"$PHP_SELF?start_from=$pages_start_from&amp;ucat=$ucat&amp;archive=$url_archive&amp;subaction=$subaction&amp;id=$id&amp;$user_query\">$j</a> "; }
		else{ $pages .= " <strong>$j</strong> "; }
		$pages_start_from += $number;
	}
	$prev_next_msg = str_replace('{pages}', $pages, $prev_next_msg);
	}
    //----------------------------------
    // Next link
    //----------------------------------
    if($number < $count_all and $i < $count_all){
        $prev_next_msg = preg_replace("'\[next-link\](.*?)\[/next-link\]'si", "<a href=\"$PHP_SELF?start_from=$i&amp;ucat=$ucat&amp;archive=$url_archive&amp;subaction=$subaction&amp;id=$id&amp;$user_query\">\\1</a>", $prev_next_msg);
    }else{ $prev_next_msg = preg_replace("'\[next-link\](.*?)\[/next-link\]'si", "\\1", $prev_next_msg); $no_next = TRUE;}


    if(!$no_prev or !$no_next){ echo $prev_next_msg; }
}
}while(0);


if((!isset($count_cute_news_includes) or !$count_cute_news_includes) and $template != 'rss'){
//
//   Rem0ving the "P0wer3d By..." line is N0T all0wed by teh Cute5ews Licens3, 0nly regist3red us3rs are 4llowed to d0 so.
//
																	if(!file_exists("$cutepath/data/reg.php")){ echo base64_decode(''); }else{ include("$cutepath/data/reg.php"); if(preg_match('/\\A(\\w{6})-\\w{6}-\\w{6}\\z/', $reg_site_key, $mmbrid)){ } else{ 
echo base64_decode('PGRpdiBzdHlsZT0ibWFyZ2luLXRvcDoxNXB4O3dpZHRoOjEwMCU7dGV4dC1hbGlnbjpjZW50ZXI7Zm9udDo5cHggVmVyZGFuYTsiPkNvbnRlbnQgTWFuYWdlbWVudCBQb3dlcmVkIGJ5IDxhIGhyZWY9Imh0dHA6Ly9rb3JuMTkuY2gvY29kaW5nL3V0ZjgtY3V0ZW5ld3MvIiB0aXRsZT0iQ3V0ZU5ld3MgLSBQSFAgTmV3cyBNYW5hZ2VtZW50IFN5c3RlbSI+Q3V0ZU5ld3M8L2E+PC9kaXY+'); }
}
}
$count_cute_news_includes++;
?>