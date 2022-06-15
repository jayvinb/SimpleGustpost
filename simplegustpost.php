<?php

defined('ABSPATH') or die('No script kiddies please');

/*
  Plugin Name: Simple gust post
  Description: A plugin to submit and manage WordPress posts from frontend with or without logging in by using Shortcode
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
	//add_action( 'template_redirect', array($this, 'sgp_template_redirection')  );
   }
    
// public function sgp_template_redirection( $template ) {	
// 	if ( !empty( $_POST['sgp_form_submitted'] ) ) {	    
// 	    $this->sgp_process_submit_form();
// 	} else {
// 	    return $template;
// 	}		
//     }
    
public function sgp_add_settings_menu() {
    //add_options_page( __('SGP Guest Post Submit Options', 'Gust Posts'), __('SGP Guest Post Submit', 'Gust Posts'), 'administrator', __FILE__, array($this, 'sgp_display_menu_page') );
	add_options_page( 'SGP Guest Post Submit Options', 'SGP Guest Post Submit', 'administrator', __FILE__, array($this, 'sgp_display_menu_page') );
    }
    
public function sgp_display_menu_page(){
    ?>
	<div id="tt-general" class="wrap">
            <h2><?php _e('SGP Guest Post Submit Options','Gust Posts'); ?></h2>
            <div id="short-code">Shortcode for this plugin: [sgp-submit-post]</div>
            <div id="short-code">Shortcode for list pading post: [sgp_gust_posts-list]</div>
            
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
	add_settings_section( 'sgp_general_settings_section', __('General Settings', 'sgp_text_domain'), array($this, 'sgp_general_setting_section_callback'), 'sgp_settings_section' );
	
	/*GENERAL SESGPINGS*/
    add_settings_field( 'sgp_txt_contact_email', __('Email for Notification', 'sgp_text_domain'), array($this,'sgp_display_text_field'), 'sgp_settings_section', 'sgp_general_settings_section', array( 'name' => 'sgp_txt_contact_email', 'txt_type' => 'email', 'place_holder' =>'Email Address For Sending Notification', 'size'=>50  ) );
    add_settings_field( 'sgp_txt_confirmation_msg', __('Post Submit Confirmation Message', 'sgp_text_domain'), array($this,'sgp_display_text_field'), 'sgp_settings_section', 'sgp_general_settings_section', array( 'name' => 'sgp_txt_confirmation_msg', 'txt_type' => 'text', 'place_holder' =>'Type Message To Show When Post Submit Successfull', 'size'=>50  ) );
    add_settings_field( 'sgp_txt_failure_msg', __('Post Submit Failure Message', 'sgp_text_domain'), array($this,'sgp_display_text_field'), 'sgp_settings_section', 'sgp_general_settings_section', array( 'name' => 'sgp_txt_failure_msg', 'txt_type' => 'text', 'place_holder' =>'Type Message To Show When Post Submit Fails', 'size'=>50  ) );
        }
public function sgp_general_setting_section_callback() {
    echo "<p class='fullv-msg'>Thank You For Using SGP gust post plugin";
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
	    $template_str .= '<form id="sgp-form" method="post" enctype="multipart/form-data">
			    <div id="wrapping" class="clearfix">
                                <section id="aligned">';
                                            
				    
					$template_str .= '<input  type="text" class="txtinput postfield" id="title" name="title" title= "'.__("Please Enter a Post Title","sgp_text_domain").'" x-moz-errormessage="'.__("Please Enter a Post Title","sgp_text_domain").'" size="72"';
					$template_str .= ' required="required" ';
					$template_str .= 'placeholder="'.__("Post Title Here", "sgp_text_domain").'">';// . wp_nonce_field();
			        $template_str .= '<textarea class="txtblock postfield" name="content" title="'.__("Please Enter Contents", "sgp_text_domain").'" x-moz-errormessage="'.__("Please Enter Contents", "sgp_text_domain").'" rows="10" cols="72" ';
					$template_str .= ' required="required" ';
					$template_str .= 'placeholder="'.__("Write Your Post Contents", "Gust Posts").'"></textarea>';
					$template_str .= '<textarea class="ecpery postfield" name="excerpt" title="'.__("Please Enter Expert", "sgp_text_domain").'" x-moz-errormessage="'.__("Please Enter Expert", "sgp_text_domain").'" rows="5" cols="72" ';
					$template_str .= ' required="required" ';
					$template_str .= 'placeholder="'.__("Write Your Post Expert", "Gust Posts").'"></textarea>';
					$template_str .= '<p id="fi-title">'. __("Upload Featured Image and Additional Images","Gust Posts") . '</p>
							    <div class="featured-img postfield">
								<input name="featured-img" type="file" id="featured-img"';
					$template_str .= ' required="required" ';
					$template_str .= ' multiple="multiple"><br>
							    </div>';
				    
				    $template_str .= '<input type="hidden" value="on" name="notify_flag">';

				    $template_str .= '<input type="hidden" value="'. $to_mail .'" name="to_email">' . 
                                '</section>
                                <section id="buttons">
                                        <input type="reset" name="reset" id="resetbtn" class="resetbtn" value="reset">
                                        <input type="submit" name="submit" id="submit" class="submitbtn" tabindex="7" value="submit">
                                        <br style="clear:both;">
                                </section>
                            </div>
                        </form>';
                         
         return $template_str; 
}
    
    public function check_and_set_value($val){
	if(isset($this->options[$val])){
	    return $this->options[$val];
	}else{
	    return "";
	}
}



} // End of Class
add_action( 'init', 'sgp_plugin_init' );
function sgp_plugin_init() {
	$sgpsObj = new SGP_GuestPostSubmit();
	
}

//Add gust post post type
add_action( 'init', 'add_gust_post_type');

    //add gust post post type to manage all gust post
function add_gust_post_type() {
        //labels array added inside the function and precedes args array
        
        $labels = array(
        'name' => _x( 'Gust Post', 'post type general name' ),
        'singular_name' => _x( 'Gust Post', 'post type singular name' ),
        'add_new' => _x( 'Add New', 'Gust Post' ),
        'add_new_item' => __( 'Add New Gust Post' ),
        'edit_item' => __( 'Edit Gust Post' ),
        'new_item' => __( 'New Gust Post' ),
        'all_items' => __( 'All Gust Posts' ),
        'view_item' => __( 'View Gust Post' ),
        'search_items' => __( 'Search Gust Post' ),
        'not_found' => __( 'No Gust Post found' ),
        'not_found_in_trash' => __( 'No Gust Post found in the Trash' ),
        'parent_item_colon' => '',
        'menu_name' => 'Gust Posts'
        );
        
        // args array
        
        $args = array(
        'labels' => $labels,
        'description' => 'Displays Gust Post submitted by gust user',
        'public' => true,
        'menu_position' => 4,
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
        'has_archive' => true,
        );
        
        register_post_type( 'gust_post', $args );
        }

        //rafister hook for admin ajex
        add_action('wp_ajax_new_submit_post','submit_post_function');
        add_action( 'wp_ajax_nopriv_new_submit_post', 'submit_post_function' ); 
        
        function footer_ajax_script(){ ?>
        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
                        <script>
                        
                            jQuery('#sgp-form').on('submit', function ()
                            {

                                event.preventDefault();
                                var files = jQuery('#featured-img')[0].files;
                                var link="<?php echo admin_url('admin-ajax.php')?>";
                                var form=jQuery('#sgp-form').serialize();
                                var formData=new FormData;
                                formData.append('action','new_submit_post');
                                formData.append('new_submit_post',form);
                                formData.append('featured-img',files[0]);
                                console.log(files[0]);
                                jQuery.ajax(
                                {
                                  url:link,
                                  data:formData, 
                                  processData:false,
                                  contentType:false,
                                  type:'post',
                                  success: function (response) {
                                        console.log(response);
                                        jQuery('#resetbtn').click()
                                    }, 
                                  fail: function (err) {
                                        alert("There was an error: " + err);
                                    }
                                 
                                }); 
                            });
                          </script>          
        <?php } 
        
//Add javascript in wordpress footer 
add_action('wp_footer', 'footer_ajax_script'); 

// >> Create Shortcode to Display Movies Post Types
  
function sgp_create_shortcode_gust_posts_post_type(){
    if ( current_user_can( 'manage_options' ) )
  {
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $args = array(
                    'post_type'      => 'gust_post',
                    'posts_per_page' => '10',
                    'post_status' => 'pending',
                    'paged' => $paged,
                    
                 );
  
    $query = new WP_Query($args);
  
    if($query->have_posts()) :
        $result .= '<table class="gust_post-item"><tr><th>Post imge</th><th>Post title</th><th>Post status</th></tr>';
        while($query->have_posts()) :
  
            $query->the_post() ;
                      
        
        $result .= '<tr>';
        $result .= '<td class="gust_post-poster">' . get_the_post_thumbnail() . '</td>';
        $result .= '<td class="gust_post-name">'. get_the_title() .'</td>';
        
        $result .= '<td class="gust_post-name">'.get_post_status(). '</td>';
        

        $result .= ''; 
        $result .= '</tr>';
  
        endwhile;

        
        $total_pages = $query->max_num_pages;

    if ($total_pages > 1){

        $current_page = max(1, get_query_var('paged'));
        $result .= '<tr class="paged"><td>';
  
        $result .= paginate_links(array(
            'base' => get_pagenum_link(1) . '%_%',
            'format' => '/page/%#%',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text'    => __('« prev'),
            'next_text'    => __('next »'),
        ));
        $result .= '</td></tr>';
  
    }
        wp_reset_postdata();
  
    endif;    
  
    return $result;            
}
else
{
    echo 'you have not access to view';
}
}
  
add_shortcode( 'sgp_gust_posts-list', 'sgp_create_shortcode_gust_posts_post_type' ); 
  
// shortcode code ends here
        
        ?>

