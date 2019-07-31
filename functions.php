<?php

/*
Plugin Name: Landscape Institute | MyLI WP Post Protect
Plugin URI: https://github.com/landscapeInstitute/my-landscapeinstitute-wp-post-protect
Description: Protect Posts by limiting their access using MyLI Permissions and oAuth Tokens
Version: 1.2
Author: Louis Varley
Author URI: http://www.landscapeinstitute.org
*/
/*
	Copyright 2019 Landscape Institute (email : louisvarley@googlemail.com)
	Licensed under the GPLv2 license: http://www.gnu.org/licenses/gpl-2.0.html
*/

/********************************************************************************/
/* Handles Plugin Updates */
/********************************************************************************/

require('updater.php');

add_action('admin_init',function(){
	new WP_GitHub_Updater(__FILE__);
});

register_activation_hook(__FILE__, function(){
    
    if ( !class_exists('myli_wp' ) ) {
        echo '<h3>'.__('MyLI API and oAuth2.0 is required for this plugin');
        @trigger_error(__('MyLI API and oAuth2.0 is required for this plugin.', 'ap'), E_USER_ERROR);
    }
});

/* Initialise Plugin */
add_action('myli_wp_loaded', function(){

	/* Setup the Post Protect */
	class myli_wp_protection extends myli_wp{
		
		/* call after_load to load our new functions after myli_wp starts */
		function after_load(){
	   
			add_action( 'add_meta_boxes', array($this,'adminMetaBox') );
			add_action( 'save_post', array($this,'postSave') );		
			add_action( 'template_redirect', array($this,'protectContent') );
		}
		
		/* When a post is saved, this saves the restrictions to post meta */
		function postSave( $post_id ) {
		 
			$is_valid_nonce = ( isset( $_POST[ 'prfx_nonce' ] ) && wp_verify_nonce( $_POST[ 'prfx_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
		 
			if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || !$is_valid_nonce ) {
				return;
			}
			
			$permissions = $this->myli->api->app->listpermissions->query();
			
			foreach ($permissions as $permission) {
				if( isset( $_POST['restrict-myli-' . $permission->id ] ) ) {
					update_post_meta( $post_id, 'restrict-myli-' . $permission->id, 'yes' );
				}else{
					update_post_meta( $post_id, 'restrict-myli-' . $permission->id, 'no' );	
				}
			}
			
		}

		/* When to Display the Meta Box */
		function adminMetaBox() {
			add_meta_box( 'prfx_meta', __( 'Restrict using MyLI Permissions', 'dynamics-textdomain' ), array($this,'adminMetaCallback'), $this->get_current_post_type(), 'side', 'high' );
			
		}
		
		/* Output Meta Box Content */
		function adminMetaCallback( $post ) {
			wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );
			$meta = get_post_meta( $post->ID );
			?>
			<div class="prfx-row-content">
				<?php

				$permissions = $this->myli->api->app->listpermissions->query();
					foreach ($permissions as $permission) {	
						$permissionMeta = $meta['restrict-myli-' . $permission->id][0]; ?>
						<label for="restrict-myli-to-<?php echo $permission->id ?>">
							<input type="checkbox" name="restrict-myli-<?php echo $permission->id ?>" id="<?php echo $permission->id ?>" <?php echo($permissionMeta=='yes' ? 'checked' : '') ?> value="yes" />
							<?php echo $permission->title ?>
						</label>
						<br />		
				<?php } ?>
			</div>   
		<?php
		}	
		
		/* True/False Is this post content protected */
		function isContentProtected(){
		
			global $post;

			if(empty($post->ID)){return false;}
			
			foreach(get_post_meta($post->ID) as $key=>$meta){
				if (strpos($key, 'restrict-myli') !== false) {	
					if($meta[0]=='yes'){
						return true;
					}
				}
			}
			
			return false;
		}
		
		function get_current_post_type() {
			
		  global $post, $typenow, $current_screen;
		  //we have a post so we can just get the post type from that
		  if ( $post && $post->post_type ) {
			return $post->post_type;
		  }
		  //check the global $typenow - set in admin.php
		  elseif ( $typenow ) {
			return $typenow;
		  }
		  //check the global $current_screen object - set in sceen.php
		  elseif ( $current_screen && $current_screen->post_type ) {
			return $current_screen->post_type;
		  }
		  //check the post_type querystring
		  elseif ( isset( $_REQUEST['post_type'] ) ) {
			return sanitize_key( $_REQUEST['post_type'] );
		  }
		  //lastly check if post ID is in query string
		  elseif ( isset( $_REQUEST['post'] ) ) {
			return get_post_type( $_REQUEST['post'] );
		  }
		  //we do not know the post type!
		  return null;
		}    
		
		function getPostPermissions(){
			
			global $post;

			$permissions = [];

			if(empty($post->ID)){return false;}
			
			/* Loops all meta and looks for a restrict that is protected == yes */
			foreach(get_post_meta($post->ID) as $key=>$meta){
				if (strpos($key, 'restrict-myli') !== false) {	
					if($meta[0]=='yes'){
						array_push($permissions,$key);
					}
				}
			}
			
			return $permissions;
			
		}
		
		/* Displays either full content or redirects */
		function protectContent() {

			/* This runs is the content is protected and there is not a valid session for MyLI */
			if ($this->isContentProtected()) {
		
				if(!$this->myli->has_access_token()){
					$this->myli->get_access_token();
				}

				$permissions = $this->getPostPermissions();
				
				foreach($permissions as $permission){

				    if($this->myli->api->me->haspermission->query(array('permissionID'=>$permission))){
				       $allow = true;
					   break;
					}
				 
				}

				if(!$allow || !current_user_can('editor')){
					return "You do not have permission to view this post";
				}
					
			}
			
		}	
		
	}

	$my_li_protection = new myli_wp_protection();
	
});

?>