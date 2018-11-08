<?php
	//todo: write code to check titles
	//insert video keys
	
	//ini_set('display_errors', 1); //display errors on the page
	error_reporting(E_ALL); // what level of errors to display

    //you might need to change these
	include './specs/creds.json';
	require_once './RCClientLibrary/AdferoArticlesVideoExtensions/AdferoVideoClient.php';
	require_once './RCClientLibrary/AdferoArticles/AdferoClient.php';
	require_once './RCClientLibrary/AdferoPhotos/AdferoPhotoClient.php';
	
	//get list of titles
	$crude = file_get_contents("./specs/creds.json");
    $refined = json_decode($crude);
    // Define Constants
    define("brafton_video_publicKey",$refined->brafton_video_publicKey);
    define("brafton_video_secretKey",$refined->brafton_video_privateKey);
    define("wistia_apiKey",$refined->wistia_api);
    define("project", $refined->project);
    define("domain", $refined->domain);
    $existing_videos = get('https://api.wistia.com/v1/medias.json');
	$titles = array();
	if(empty($existing_videos)){
		echo "no videos exist yet! <br/>";
	}
	else {
		foreach($existing_videos as $video){
            $titles[] = $video->name;          
        }
	}

    //list_projects();

    import_videos($titles);
	
	function import_videos($titles){
        $params = array('max'=>99);
        $domain = preg_replace('/https:\/\//','',domain);
        $baseURL = 'http://livevideo.'.str_replace('http://', '',$domain).'/v2/';
        $videoClient = new AdferoVideoClient($baseURL, brafton_video_publicKey, brafton_video_secretKey);
        $client = new AdferoClient($baseURL, brafton_video_publicKey, brafton_video_secretKey);
        $videoOutClient = $videoClient->videoOutputs();

        $photos = $client->ArticlePhotos();
        $photoURI = 'http://'.str_replace('api', 'pictures',$domain).'/v2/';
        $photoClient = new AdferoPhotoClient($photoURI);
        $scale_axis = 500;
        $scale = 500;
        $feeds = $client->Feeds();
        $feedList = $feeds->ListFeeds(0,10);
        $articleClient=$client->Articles();

        //CHANGE FEED NUM HERE
        $articles = $articleClient->ListForFeed($feedList->items[0]->id,'live',0,100);

        
        $articles_imported = 0;

       foreach ($articles->items as $a) {

            $articles_imported++;
            if($articles_imported>3) break;
            //max of five articles imported

            $thisArticle = $client->Articles()->Get($a->id);
            $brafton_id = $a->id;

            $strPost = '';
            $createCat = array();

            $post_title = $thisArticle->fields['title'];
            
            // check against existing posts here.  Use title.

            if (array_search($post_title,$titles) != false) {
                echo $post_title . " exists<br/>";
                continue;
            } else {
                echo "posting: $post_title <br/>";
            }
            // Enter Author Tag
            //$author = author;
            
            // $photos = $a->getPhotos();  
        
            // $image = $photos[0]->getLarge();
            
            // $post_image = $image->getUrl();
            
            // if(!empty($post_image)){

            //     $image_id = $photos[0]->getId();                

            //     $image_small = $photos[0]->getThumb();
                
            //     $post_image_small = $image_small->getURL();

            //     $post_excerpt = $post_excerpt.'<img src = "'.$post_image.'" alt ="" /><p>'.$post_content.'</p>' ;
            // }

            $presplash = $thisArticle->fields['preSplash'];
            $postsplash = $thisArticle->fields['postSplash'];
                            
            $videoList=$videoOutClient->ListForArticle($brafton_id,0,10);
            $list=$videoList->items;
            $mp4=true;
            $HDmp4=false;

            foreach($list as $listItem){
                    $output=$videoOutClient->Get($listItem->id);
                    if($output->type=="htmlmp4") {
                        $path = $output->path;
                        $ext = pathinfo($path, PATHINFO_EXTENSION);
                        if($ext == "mp4") $HDmp4 = $path;
                    }
            }
			if($HDmp4){
            //upload HD video!
                upload($HDmp4,urlencode($post_title));
            } else if($mp4){
			//upload standard video here!
				upload($path,urlencode($post_title));
			} else echo "no mp4's or HD MP4's!<br/>";
        }        
    }

    function list_projects(){
        $existing_projects = get('https://api.wistia.com/v1/projects.json');
        $projects = array();
        if(empty($existing_projects)){
            echo "no projects exist yet! <br/>";
        }
        else {
            foreach($existing_projects as $project){
                echo $project->name . " " . $project->id . "<br/>";
            }
        }
    }

	
	function upload($fileurl, $name){
        $vars = "?username=api&api_password=" . wistia_apiKey . "&url=$fileurl&name=$name";
        
        if(project != "") $vars .= "&project_id=" . project;
        post($vars);
	}
        

    function get($url){
        $username = 'api';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:'. wistia_apiKey);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $output = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch); 
        if ($errno > 0) {
            echo 'cURL error: ' . $error;
        } else {
            return json_decode($output);
        }
    }

	function post($vars){
        // intialize cURL and send POST data
        var_dump($vars);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . wistia_apiKey);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_URL, "https://upload.wistia.com/$vars");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/x-www-form-urlencoded')                                                                       
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
       // if ($formenc)   // new
       //     curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); // new
        
        $output = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch); 
        if ($errno > 0) {
            echo 'cURL error: ' . $error;
        } else {
            echo "<pre>" . var_dump(json_decode($output)) . "</pre>";
        }
    }
?>