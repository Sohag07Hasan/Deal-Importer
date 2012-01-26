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
				$sq = get_post_meta($post_id, 'Square_Feet', true);					
				
				$table = $wpdb->prefix . 'trdmdeals';			
				$sq = preg_replace('/[^0-9]/', '', $sq);
				
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
				
				$args = array( 
					'post_type' => 'deal',
					'posts_per_page' => -1				 
				);
						 
				$category = $_POST['category'];
				
				if($category == ''){
					$message = '<div class="error"><p>Select any One Category!</p></div>';
				}
				elseif($category == 'all'){
					$wp_query = new WP_Query( $args );
				}
				else{
					
					$args['meta_query'] = array(
						array(
							'key' => 'Category',
							'value' => $category,				
						)	  
				  );
				  
				  $wp_query = new WP_Query( $args );
				}
				
				if($wp_query->have_posts()) :
					
					foreach($wp_query->posts as $key=>$post){
						wp_delete_post($post->ID, true);
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
						<option value='office'>Office</option>
						<option value='commercial'>Commercial</option>
						<option value='retail'>Retail</option>
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
