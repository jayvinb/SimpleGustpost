<?php

defined('ABSPATH') or die('No script kiddies please');

//Write file for submit post and send mail
//Write function for submit post
function submit_post_function()
    {
//Add condition for further use
	if ( $_POST["new_submit_post"] ) {
	    $formarr=[];
        wp_parse_str($_POST["new_submit_post"],$formarr);
        print_r($formarr);
        
	    $title = $formarr['title'];
	    $content =$formarr['content'];
	    $excerpt = $formarr['excerpt'];
	    //$nonce=$_POST["_wpnonce"];
	    $poststatus = 'pending';
	
	    
		$new_post = array(
		    'post_title'    => $title,
		    'post_content'  => $content,
		    'post_excerpt' => $excerpt,  
		    'post_status'   => $poststatus,  // Choose: publish, preview, future, draft, etc.
		    'post_type' => 'gust_post',  //'post',page' or use a custom post type if you want to
		    
		);
		
		$pid = wp_insert_post($new_post);
		add_post_meta($pid, 'author', $author, true);
		add_post_meta($pid, 'author-email', $email, true);
		add_post_meta($pid, 'author-website', $site, true);
        
		//Snippet for handle feutere image uploads   
		if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
                if ( $_FILES ) {
                    $files = $_FILES['featured-img'];
                    
                        
                     $file = array(
			                'name'     => $files['name'],
                            'type'     => $files['type'],
                            'tmp_name' => $files['tmp_name'],
                            'error'    => $files['error'],
                            'size'     => $files['size']
                            );
                           
    
                            $_FILES = array("featured-img" => $file);
                                
                            $counter = 1;    
                            foreach ($_FILES as $file => $array) {
                                if($counter == 1){
                                    $newupload = insert_attachment($file,$pid, true);
                                }else{
                                    $newupload = insert_attachment($file,$pid, false);    
                                }
                                ++$counter;
                            }// End of outer foreach
                }               // End of if($_FILES)
            }                   //  $_POST["new_submit_post"]
	    
            //check flag for mail
            if($_POST['notify_flag']=="on"){
                sgp_send_confirmation_email($to_email);
            }
            
                }// End of Function
    
function insert_attachment($file_handler, $post_id, $setthumb) {
 
        // check to make sure its a successful upload
        if ($_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK) __return_false();
       
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
        require_once(ABSPATH . "wp-admin" . '/includes/media.php');
       
        $attach_id = media_handle_upload( $file_handler, $post_id );
       
        if ($setthumb) update_post_meta($post_id,'_thumbnail_id',$attach_id);
        return $attach_id;
    }
    
function check_and_set_value($val){
	if(isset($_POST[$val])){
	    return $_POST[$val];
	}else{
	    return "";
	}
    }

    //mail templet snipet
    function sgp_send_confirmation_email($to_email) {

        $headers = 'Content-type: text/html';
        $message = __('A user submitted a new post to your Wordpress site database.','sgp_text_domain').'<br /><br />';
        $message .= __('Post Title: ','sgp_text_domain') . check_and_set_value('title') ;
        $message .= '<br />';
        $message .= '<a href="';
        $message .= add_query_arg( array(
                                'post_status' =>'padding',
                                'post_type' => 'Gust post' ),
                                admin_url( 'edit.php' ) );
        $message .= '">'.__('Moderate new post', 'sgp_text_domain').'</a>';
        $email_title = htmlspecialchars_decode( get_bloginfo(), ENT_QUOTES ) . __(" - New Post Added: ", "sgp_text_domain") . htmlspecialchars( check_and_set_value('title') );
        // Send e-mail
        wp_mail( $to_email, $email_title, $message, $headers );
          
    }
    
    ?>
