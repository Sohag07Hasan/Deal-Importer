<?php
/*
 * makes an options page to set if the data is to be sorted by asc or desc
 * 
 * */

if(!class_exists('deals_options')) :
 	class deals_options{
 		function __construct(){
 			register_activation_hook(TRDM_CSV_FILE, array($this, 'table_creation'));
 			 add_action('admin_menu', array($this,'submenu_page'));
 			 add_action('save_post', array($this, 'save_metadata'));
 			 add_action('deleted_post', array($this, 'deleted_post'));
 			 add_action('init', array($this, 'register_trdm_post_types'));
 		}
 		
 		/*
 		 * Custom Posttype creation
 		 * */
 		function register_trdm_post_types(){
			if(function_exists('register_post_type')) {
				// workouts
				$labels = array(
					'name' => _x('Deals', 'post type general name'),
					'singular_name' => _x('Deal', 'post type singular name'),
					'add_new' => _x('Add New', 'Deal'),
					'add_new_item' => __('Add New Deal'),
					'edit_item' => __('Edit Deal'),
					'new_item' => __('New Deal'),
					'view_item' => __('View Deal'),
					'search_items' => __('Search Deals'),
					'not_found' =>  __('No Deals found'),
					'not_found_in_trash' => __('No Deals found in Trash'),
					'parent_item_colon' => ''
				);
				$args = array(
					'labels' => $labels,
					'public' => true,
					'publicly_queryable' => true,
					'show_ui' => true,
					'query_var' => true,
					'rewrite' => true,
					'capability_type' => 'post',
					'hierarchical' => false,
					'menu_position' => 5,
					'supports' => array('title','editor','author','thumbnail','excerpt','custom-fields')
				);

				register_post_type('deal',$args);
				
			}
			
		}
 		
 		
 		/*
 		 * clears the table if post is deleted
 		 * */
 		 
 		 function deleted_post($post_id){
			global $wpdb;
			$table = $wpdb->prefix . 'trdmdeals';
			
			$wpdb->query("DELETE FROM $table WHERE post_id = '$post_id'");
		 }
 		
 		function save_metadata($post_id){
			global $post;
			
			if(in_array($post->post_status, array('draft', 'publish')) && $post->post_type == 'deal') :			
				global $wpdb;
				$table = $wpdb->prefix . 'trdmdeals';
							
				$sq = get_post_meta($post_id, 'Issues', true);							
				$issue = strip_tags($sq);
				$issue = preg_replace('/[^a-zA-Z0-9 ]/', '', $issue);
				$sq = @ strtotime($issue);				
				
				$check = $wpdb->get_var("SELECT id FROM $table WHERE post_id = '$post_id'");
				if($check){
					$wpdb->update($table, array('sq_feet'=>$sq), array('post_id'=>$post_id), array('%d'), array('%d'));
				}
				else{
					$wpdb->insert($table, array('post_id'=>$post_id, 'sq_feet'=>$sq), array('%d', '%d'));
				}
			endif;
			
		}
 		
 		/*
 		 * menu page under Deals
 		 * */
 		function submenu_page(){
			add_submenu_page('edit.php?post_type=deal', 'Clear Deals', 'Clear Deals', 'manage_options', 'deal_delete', array($this, 'deal_subpage'));
		}
 		
 		/*
 		 * Creation of table
 		 * */
 		 function table_creation(){
			global $wpdb;
			$table = $wpdb->prefix . 'trdmdeals';
			$sql = "CREATE TABLE IF NOT EXISTS $table(
				id bigint unsigned NOT NULL AUTO_INCREMENT,
				post_id bigint unsigned NOT NULL,
				sq_feet int DEFAULT 0,
				PRIMARY KEY(id),
				UNIQUE(post_id)
			)";
			
			if(!function_exists('dbDelta')) :
				require_once(ABSPATH.'wp-admin/includes/upgrade.php');
			endif;
			dbDelta($sql);
		 }
 		
 		
     /* Submenu page to delete all the deals data
		 * 
		 * */
		 function deal_subpage(){
			 $message = '';
			 
			 if($_POST['csv-deal-clear'] == 'Y') :
								
						 
				$category = $_POST['category'];
				
				if($category == ''){
					$message = '<div class="error"><p>Select any One Category!</p></div>';
				}				
				else{
					global $wpdb;
					$posts = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'deal'");
									
				}
								
				if($posts) :
					
					foreach($posts as $key=>$post){
						wp_delete_post($post, true);
					}					
					$message = '<div class="updated"><p>Operation Successfull!</p></div>';
					
				endif; 
							
				
			 endif;	 
			 
			?>
			
			<div class="wrap">
				<?php screen_icon('tools'); ?>
				<h2>Clear The Deals</h2>
				<?php echo $message; ?>
				<p>This Operation will delete the deals' data from the database. This cannot be undo. Be sure before doing this operation</p>
				<strong>Select any category and Press the Clear!</strong>
				<br />
				<form action='' method='post'>
					<input type='hidden' name='csv-deal-clear' value='Y' />
					<Select name='category'>
						<option value=''>Select</option>						
						<option value='all'>All Deal</option>
					</Select>
					<input class ="button-primary" type="submit" value="Clear" />
				</form>
			</div>
			
			<?
		 }
 		
 	}
 	
 	$deals_obj = new deals_options();
endif;
