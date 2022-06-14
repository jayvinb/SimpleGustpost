<?php

defined('ABSPATH') or die('No script kiddies please');

/*
  Plugin Name: Simple gust post
  Description: A plugin to submit and manage WordPress posts from frontend with or without logging in by using sort codes
  Version:     1.0.0
  Author:      Jayvin busa
  Author URI:  
  Plugin URI: 
  License:     GPL2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Domain Path: /languages
  Text Domain: simple-gust-post
 */

include dirname( __FILE__ ).'/sgp-functions.php';
class SGP_GuestPostSubmit{
    
    public function __construct(){
        wp_enqueue_style('sgp-style', plugins_url('sgp-style.css',__FILE__));

	/*
	wp_enqueue_script('tinymce_min', includes_url('js/tinymce/tinymce.min.js',__FILE__));
	wp_enqueue_script('tiny_mce', plugins_url('tiny_mce.js',__FILE__));
	*/
	if (is_admin()){
	    add_action( 'admin_menu', array($this, 'sgp_add_settings_menu') );
        add_action( 'admin_init', array($this, 'sgp_init_settings') );
	}
	$this->options = get_option( 'sgp_options' );
    $this->enable_shortcode();
	add_action( 'template_redirect', array($this, 'sgp_template_redirection')  );

    }
    
    public function sgp_template_redirection( $template ) {	
	if ( !empty( $_POST['sgp_form_submitted'] ) ) {	    
	    $this->sgp_process_submit_form();
	} else {
	    return $template;
	}		
    }
    
    public function sgp_add_settings_menu() {
	//add_options_page( __('SGP Guest Post Submit Options', 'sgp_text_domain'), __('SGP Guest Post Submit', 'sgp_text_domain'), 'administrator', __FILE__, array($this, 'sgp_display_menu_page') );
	add_options_page( 'SGP Guest Post Submit Options', 'SGP Guest Post Submit', 'administrator', __FILE__, array($this, 'sgp_display_menu_page') );
    }
    
    public function sgp_display_menu_page(){

	?>
	<div id="tt-general" class="wrap">
            <h2><?php _e('SGP Guest Post Submit Options','sgp_text_domain'); ?></h2>
            <div id="short-code">Shortcode for this plugin: [sgp-submit-post]</div>
            <form name="sgp_options_form_settings_api" method="post" action="options.php">
		<?php settings_fields( 'sgp_settings' ); ?>
		<?php do_settings_sections( 'sgp_settings_section' ); ?> 
		<input type="submit" value="Submit" class="button-primary" id="sgp" />
            </form>
	</div>
	<?php
    }
    
    public function sgp_init_settings(){
	    register_setting( 'sgp_settings', 'sgp_options');
		add_settings_section( 'sgp_general_settings_section', __('General Settings', 'sgp_text_domain'), 'sgp_settings_section' );
	    
	/*GENERAL SESGPINGS*/
        add_settings_field( 'sgp_chk_notifyfield', __('Send Notification via Email', 'sgp_text_domain'), array($this,'sgp_display_check_box'), 'sgp_settings_section', 'sgp_general_settings_section', array('name' => 'sgp_chk_notifyfield' ));
        add_settings_field( 'sgp_txt_contact_email', __('Email for Notification', 'sgp_text_domain'), array($this,'sgp_display_text_field'), 'sgp_settings_section', 'sgp_general_settings_section', array( 'name' => 'sgp_txt_contact_email', 'txt_type' => 'email', 'place_holder' =>'Email Address For Sending Notification', 'size'=>50  ) );
        add_settings_field( 'sgp_txt_confirmation_msg', __('Post Submit Confirmation Message', 'sgp_text_domain'), array($this,'sgp_display_text_field'), 'sgp_settings_section', 'sgp_general_settings_section', array( 'name' => 'sgp_txt_confirmation_msg', 'txt_type' => 'text', 'place_holder' =>'Type Message To Show When Post Submit Successfull', 'size'=>50  ) );
        add_settings_field( 'sgp_txt_failure_msg', __('Post Submit Failure Message', 'sgp_text_domain'), array($this,'sgp_display_text_field'), 'sgp_settings_section', 'sgp_general_settings_section', array( 'name' => 'sgp_txt_failure_msg', 'txt_type' => 'text', 'place_holder' =>'Type Message To Show When Post Submit Fails', 'size'=>50  ) );
        
     }
    
    public function sgp_display_text_field( $data = array() ) {
	    extract( $data );
	//$options = get_option( 'sgp_options' ); 
	?>
        <input type="<?php echo $txt_type ?>" name="sgp_options[<?php echo $name; ?>]" placeholder="<?php echo $place_holder; ?>" <?php if($txt_type=="number"){echo ' min="0"';} ?> size="<?php echo $size; ?>" <?php echo " ".$disabled; ?> value="<?php echo esc_html( $this->options[$name] ); ?>"/>
	<?php
        if($second_field){
            ?>
            <label>&nbsp;X&nbsp;</label><input type="number" placeholder="Width In Pixel" min="0" name="sgp_options[<?php echo 'sgp_txt_imagewidth'; ?>]" disabled value="<?php echo esc_html( $this->options['sgp_txt_imagewidth'] ); ?>"/>
            <?php
        }else{
            //echo "<br />";
        }
    }
    ?>
    
	<?php
    }
    
    public function enable_shortcode(){
        add_shortcode('sgp-submit-post', array($this, 'sgp_guest_submit_post_shortcode') );
    }
    
    public function sgp_guest_submit_post_shortcode($atts){
	
	$user = get_user_by('login', $this->options['sgp_drp_account']);
	extract(shortcode_atts(array(
                            'author' => $user->ID,
                            'redirect_url' => $this->options['sgp_txt_redirect'], //get_permalink(),
                            ), $atts )
                );
        if (is_user_logged_in()){
            $author = get_current_user_id();    
        }else{
	    
	    $user = get_user_by('login', $this->options['sgp_drp_account']);
	    $author = $user->ID;
	}
	
	$to_mail = "";
	if(empty($this->options['sgp_txt_contact_email'])){
	    $to_mail = get_option('admin_email');
	}else{
	    $to_mail = $this->options['sgp_txt_contact_email'];
	}
	
	$template_str = "";

	//Display confirmation message to users who submit post
	if ( isset ( $_GET['submission_success'] ) && $_GET['submission_success'] ) {
	    
	    $template_str = '<div class="message-box">' . 
				$this->options['sgp_txt_confirmation_msg'] .
			    '</div>';
	}

	//Post variable to indicate user-submitted items
	$template_str .= '<input type="hidden" name="sgp_form_submitted" value="1" />';
        $template_str .= '<form id="sgp-form" action="" method="post" enctype="multipart/form-data">
			    <div id="wrapping" class="clearfix">
                                <section id="aligned">';
                                            
				    
					$template_str .= '<input  type="text" class="txtinput" id="title" name="title" title= "'.__("Please Enter a Post Title","sgp_text_domain").'" x-moz-errormessage="'.__("Please Enter a Post Title","sgp_text_domain").'" size="72"';
					$template_str .= ' required="required" ';
					$template_str .= 'placeholder="'.__("Post Title Here", "sgp_text_domain").'">';// . wp_nonce_field();
			        $template_str .= '<textarea class="txtblock" name="content" title="'.__("Please Enter Contents", "sgp_text_domain").'" x-moz-errormessage="'.__("Please Enter Contents", "sgp_text_domain").'" rows="15" cols="72" maxlength="'.$this->options['sgp_txt_maxlength'].'"';
					$template_str .= ' required="required" ';
					$template_str .= 'placeholder="'.__("Write Your Post Contents", "sgp_text_domain").'"></textarea>';
					
				    }
				       
					$args = array(
						'orderby' => 'name',
						'order' => 'ASC'
						);
					$categories = get_categories($args);
					$template_str .= '<select name="catdrp" class="postform" id="catdrp" ';
					$template_str .= (isset($this->options['sgp_chk_categoryfield_req']) && $this->options['sgp_chk_categoryfield_req']=="on") ? ' required="required" ' : ' ';
					$template_str .= '> <option value="">'.__("Select a Category", "sgp_text_domain").'</option>';
					foreach($categories as $category) { 
					    $template_str .= '<option value="' . $category->cat_ID . '">'.$category->name.'</option>';
					}
					$template_str .= '</select>';
				    
				                    
		 
				   $template_str .= '<p id="fi-title">'. __("Upload Featured Image and Additional Images","sgp_text_domain") . '</p>
							    <div class="featured-img">
								<input name="featured-img[]" type="file" id="featured-img"';
								$template_str .= ' required="required" ';
								$template_str .= ' multiple="multiple"><br>
							    </div>';
				    
				        $template_str .= '<input type="hidden" value="'. $author .'" name="authorid">
							  <input type="hidden" value="'. $redirect_url .'" name="redirect_url">
							  <input type="hidden" value="'. $this->options["sgp_drp_status"] .'" name="post_status">
							  
							  <input type="hidden" value="';
							  $template_str .= isset($this->options["sgp_chk_notifyfield"])?$this->options["sgp_chk_notifyfield"]:"";
							  $template_str .= '" name="notify_flag">
							  
	
							  
							  <input type="hidden" value="'. $to_mail .'" name="to_email">
							  <input type="hidden" name="sgp_form_submitted" value="1" />' . 
                                '</section>
                                <section id="buttons">
                                        <input type="reset" name="reset" id="resetbtn" class="resetbtn" value="'.__("Reset", "sgp_text_domain").'">
                                        <input type="submit" name="submit" id="submitbtn" class="submitbtn" tabindex="7" value="'.__("Submit Post", "sgp_text_domain").'">
                                        <br style="clear:both;">
                                </section>
                            </div>
                        </form>';
         return $template_str; 
    
    
    public function check_and_set_value($val){
	if(isset($this->options[$val])){
	    return $this->options[$val];
	}else{
	    return "";
	}
	
    
    
    public function sgp_process_submit_form(){
	submit_post_function();
    } // End of function

} // End of Class



?>