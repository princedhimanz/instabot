<?php
set_time_limit(0);
//Don't Remove sleep it prevent you to detect by instagram Bots. 
sleep(rand(6,20));
require 'insta_class.php';
require 'insta_config.php';
$username = 'PUT YOUR INSTAGRAM USERNAME HERE';
$password = 'PUT YOUR INSTAGRAM PASSWORD HERE';
$insta = new instagram();
if(!file_exists('cookies.txt'))
{
	$response = $insta->Login($username, $password);
	//print_r($response);
	$csrf_token=$response['config']['csrf_token'];
	file_put_contents('csrf.txt',$csrf_token);
	$entry_data=$response['entry_data'];
	$feedpage=$entry_data['FeedPage'][0]['graphql'];
	$user=$feedpage['user'];
	echo 'User Id='.$user["id"].'<br>ProfilePic<br><img src='.$user["profile_pic_url"].'><br>Username='.$user["username"].'';
}

$search = $searchtag[array_rand($searchtag)];
$res = $insta->SearchTag($search);
echo "<h4>Tag:$search</h4>";
//print_r($res);
$media_data=$res['entry_data']['TagPage']['0']['tag']['media']['nodes'];
$last=end($media_data);
//print_r($last);
$length = count($media_data);
$l=2;
   flush();
   ob_flush();
foreach ($media_data as $media) {
	$media_id = $media['id'];
	$user_id = $media['owner']['id'];
	$short_code=$media['code'];
	$like_count = $media['likes']['count'];
	$comment_count = $media['comments']['count'];
	$media_detail = $insta->get_Media_detail($short_code, $search);
	$haslike = $media_detail['viewer_has_liked'];
	$owner_data = $media_detail['owner'];
	//print_r($owner_data);
	$username = $owner_data['username'];
	$fakecomment = $praise[array_rand($praise)];
	if (!$owner_data['followed_by_viewer'] && !$owner_data['is_private'] && !$owner_data['requested_by_viewer'])
	{
		$resp=$insta->PostFollow($user_id, $short_code, $search);
		//print_r($res);
		if(strstr($resp,'following'))
   		{
       		file_put_contents("following.txt", "$user_id,", FILE_APPEND);
        	echo "<a href=https://www.instagram.com/$username>$username</a> Followed<br>";
   		}
   		else
   		{
   			print_r($resp);
   		}

   			flush();
   			ob_flush();
  			sleep(rand(6,20));
   		if (!$haslike) {
					echo "Like And Comment";
   					flush();
  					ob_flush();
					$res=$insta->PostLike($media_id, $short_code, $search);
					//print_r($res);
					//echo "<br>$fakecomment<br>";
   					flush();
   					ob_flush();
					sleep(rand(6,20));
					$res=$insta->PostComment($fakecomment, $media_id, $short_code, $search);
					print_r($res);
					sleep(rand(6,20));	
				}	
		else {
					if (!$haslike) {
					echo "<br> Like only";
					sleep(rand(6,20));
					$res=$insta->PostLike($media_id, $short_code, $search);	
					}
			}
		

	}
	
unset($res);
if(strstr($resp,'following'))
   		{
       		die();
   		}
	echo "<br>$media_id <br>$user_id <br>$short_code <br>$like_count <br>$comment_count <br> $haslike <br> $username";
}


?>