<?php
/*
Template Name: Deals
*/
?>
<?php get_header(); ?>
<div class="main-holder">
	
	<div id="content">
		
	<?php // include_once(TEMPLATEPATH.'/block-trending.php'); ?>
		
<script type="text/javascript" charset="utf-8">
	jQuery(document).ready(function(){
		$("tr:even").addClass("even");
	});
</script>
<style type="text/css" media="screen">
	#content {
		width: 960px;
	}
	.even {
		background-color: #eee; 
	}
	.page-numbers {
		padding: 7px;
		border: 1px solid #ccc;
	}
	
	.updated{
		-moz-border-radius: 3px 3px 3px 3px;
		border-style: solid;
		border-width: 1px;
		margin: 5px 0 15px;
		padding: 0 0.6em;
		background-color: #FFFFE0;
		border-color: #E6DB55;
		font-size: 18px;
	}
	
	
</style>

<?php wp_nav_menu(array(
					'menu' => 'Deals Menu',
					'menu_id' => 'deals_menu',
					'menu_class' => 'deals_menu',
					'container' => 'div',
					'link_before' => '',
					'link_after' => ''
				  )); ?>
<?php
	
	global $post;
	$action = get_permalink($post->ID);
	
	global $deal_category;
	global $deal_count;
	$deal_count = 0;
	$deal_category = "commercial"; // Default category
	
	global $wp_rewrite;
	
		
	if (preg_match('/building-sales/', $_SERVER['REQUEST_URI']) || $_REQUEST['category'] == "building-sale") $deal_category = "office";
	if (preg_match('/retail-leases/', $_SERVER['REQUEST_URI']) || $_REQUEST['category'] == "retail-leases") $deal_category = "retail";
	if (preg_match('/office-leases/', $_SERVER['REQUEST_URI']) || $_REQUEST['category'] == "office-leases") $deal_category = "office";
		
	
	// filtering the inner join
	function inner_join($join, $query){
		global $deal_category;
		global $wpdb;
		$table = $wpdb->prefix . 'trdmdeals';
		$join .= ' INNER JOIN ' . $table . ' ON (' . $wpdb->posts . '.ID = ' . $table . '.post_id)';
		
		return $join;
	}
	
		add_filter('posts_join_paged', 'inner_join', 100, 2);
	
	
	function order_by($orderby){
		global $wpdb, $deal_category;
		$table = $wpdb->prefix . 'trdmdeals';
		//$orderby = $table . '.issue DESC, ' . $table . '.sq_feet DESC' ;
		
		if($deal_category == 'commercial'){
			$orderby = $table . '.issue DESC, ' . $table . '.price DESC' ;
		}
		else{
			$orderby = $table . '.issue DESC, ' . $table . '.sq_feet DESC' ;
		}				
		return $orderby;
	}
	
	
		add_filter('posts_orderby', 'order_by');
	
	
	function group_by($orderby){
		global $wpdb;
		$table = $wpdb->prefix . 'trdmdeals';
		return $table . '.issue';
	}
	//add_filter('posts_groupby', 'group_by');
	
	
	//seach things are here
	if($_REQUEST['deal_search'] == 'Y') :
		add_filter('posts_where_paged', 'query_changing',10,2);
		add_filter('posts_results', 'result_changing',10,2);
	endif;
	
	
	
	
	function query_changing($where, &$wp_query){		
	//	echo $where . '<br/><br/>';
		$string = $_REQUEST['search_string'];
		if($string == '') return $where;
				
		global $wpdb;
		$where = 'AND ' . '(((' . $wpdb->posts . '.post_title LIKE ' . "'%$string%'" . ') OR (' . $wpdb->posts . '.post_content LIKE ' . "'%$string%'" . ') OR (' . $wpdb->postmeta . '.meta_value LIKE ' . "'%$string%'" . ')))' . 'AND ' . $wpdb->posts . '.post_type IN ' . "('deal')" . ' AND ' . $wpdb->posts . '.post_status = ' . "'publish'";
		//echo $where;
		//exit;		
		return $where;
		
	}
	
	function result_changing($posts, $obj){
		global $deal_count;
		if(empty($posts)) return $posts;
		$newposts = array();
		$finalposts = array();
		global $deal_category;
		
		foreach($posts as $post) :
			if(get_post_meta($post->ID, 'Category', true) == $deal_category) $newposts[] = $post;			
		endforeach;
		
		$min = (int) $_REQUEST['sqmin'];	
		$max = (int) $_REQUEST['sqmax'];
		
		if($max > $min) :
			foreach($newposts as $post) :				
				$sq_val = get_post_meta($post->ID, 'Square_Feet', true);
				if($sq_val >= $min && $sq_val <= $max) $finalposts[] = $post;
			endforeach;
			$newposts = $finalposts;
		elseif($min > 0) :
			foreach($newposts as $post) :
				$sq_val = get_post_meta($post->ID, 'Square_Feet', true);
				if($sq_val >= $min) $finalposts[] = $post;
			endforeach;
			$newposts = $finalposts;
			
		elseif($max > 10) :			
			foreach($newposts as $post) :
				$sq_val = get_post_meta($post->ID, 'Square_Feet', true);
				if($sq_val <= $max) $finalposts[] = $post;
			endforeach;
			$newposts = $finalposts;
		else :
			$deal_count = count($newposts);
			return $newposts;
		endif;
						
		$deal_count = count($newposts);
		return $newposts;
	}
	
		
	global $wp_rewrite;
			
	$args = array( 
		'post_type' => 'deal',
		'posts_per_page' => 10, 
		'paged' => $paged,		
		'meta_query' => array(
			array(
				'key' => 'Category',
				'value' => $deal_category				
			)	  
		  )
		 
	);
	
	
	if($_REQUEST['deal_search'] == 'Y') :
		$args['posts_per_page'] = -1;
	endif;
	
	
	$wp_query = new WP_Query( $args );
	$wp_query->query_vars['paged'] > 1 ? $current = $wp_query->query_vars['paged'] : $current = 1;
	
	if($_REQUEST['deal_search'] != 'Y') :
	
		$pagination = array(
			'base' => @add_query_arg('page','%#%'),
			'format' => '',
			'total' => $wp_query->max_num_pages,
			'current' => $current,
			'show_all' => false,
			'type' => 'plain'
			);

		if( $wp_rewrite->using_permalinks() )
			$pagination['base'] = user_trailingslashit( trailingslashit( remove_query_arg( 's', get_pagenum_link( 1 ) ) ) . 'page/%#%/', 'paged' );

		if( !empty($wp_query->query_vars['s']) )
			$pagination['add_args'] = array( 's' => get_query_var( 's' ) );
			
		echo '<div align="right" style="margin: 10px 0 0 0;">' . paginate_links( $pagination ) . '</div>';	
		
		
		echo '<div>';
		
	endif;
	
	?>
		<!-- deal search -->
				
		<div class="deal_search">
			<form action="<?php echo $action; ?>" method='get'>
				<input type='hidden' name='deal_search' value='Y' />
				<table class='form-table'>
					<tr>
						<td>Search <?php echo $deal_category;?> Deal</td>
					</tr>
					
					<tr>
						<td><input type='text' name='search_string' value="<?php echo $_REQUEST['search_string'];?>" /></td>
						<td><input type='submit' value='Search' /></td>
						<td>&nbsp;</td>
					</tr>
					
					<tr>
						<td>Filter Results By Square Feet</td>						
					</tr>
					
					<tr>
						<td>Min</td>
						<td>Max</td>
					</tr>
					
					<tr>
						<td><input type='text' name='sqmin' value="<?php echo $_REQUEST['sqmin']; ?>" /></td>
						<td><input type='text' name='sqmax' value="<?php echo $_REQUEST['sqmax']; ?>" /></td> 
						<td><input type='submit' value='Filter' /></td>
						
					</tr>
				</table>			
				  
			</form>
		</div>
		
		
		<?php
		
		echo '<div style="padding-bottom: 15px; margin: 10px 0 0 0;">Please submit deals to: <a href="deals@therealdeal.com">deals@therealdeal.com</a></div>';
		
			if($_REQUEST['deal_search'] == 'Y'){
				if($deal_count > 0){
					$message =  $deal_count . ' ' . $deal_category . ' Deals found' ;
				}
				else{
					$message = 'No results found';
				}
				echo '<p class="updated">' . $message . '</p>';
			}	
				
		?>
	
	<?php
	
	function square_feet_sanitizing($sq_feet){
		global $deal_category;
		if($deal_category == 'commercial') return $sq_feet;
		
		$sq_feet = (int)preg_replace('/[^0-9]/', '', $sq_feet);
		return number_format($sq_feet);
	}
	
	echo '<table border="0" cellspacing="1" cellpadding="10"><tr>';
	/*
	 * echo '<tr><th>Address</th><th>Square Feet</th><th>Tenant</th><th>Representative</th><th>Landlord</th><th>Landlord Representative</th><th>Notes</th><th>Issue</th></tr>';
	 */
	 
	 
	 if($deal_category == 'commercial'):	 
		echo '<tr><th>Address</th><th>Size Info</th><th>Price (in millions)</th><th>Buyer</th><th>Buyer Representative</th><th>Seller</th><th>Seller Representative</th><th>Notes</th><th>Issue</th></tr>';
	 else :
		echo '<tr><th>Address</th><th>Square Feet</th><th>Tenant</th><th>Tenant Representative</th><th>Landlord</th><th>Landlord Representative</th><th>Notes</th><th>Issue</th></tr>';
	 endif;
	/*
	 * Now it is time for sorting
	 * */
	 global $wpdb;
	 $table = $wpdb->prefix . 'trdmdeals';
	
			
	while ( $wp_query->have_posts() ) : $wp_query->the_post();
		
		$post_id = get_the_ID();
	
		$custom_fields = get_post_custom($post_id);
		echo '<td width="120">' . $custom_fields['Full_Address'][0] . '<br/><a href="http://maps.google.com/maps?q=' . $custom_fields['Full_Address'][0] . '"><b>MAP</b></a>';
		if ( current_user_can('manage_options') ) { 
			echo '<br/><a href="/wp-admin/post.php?post=' . $key . '&action=edit"><b>EDIT</b></a>'; 
		} 
		echo '</td>';
		echo '<td width="120" align="center">' . square_feet_sanitizing($custom_fields['Square_Feet'][0]) . '</td>';
		
		if($deal_category == 'commercial'):
			echo '<td width="120" align="center">' . $custom_fields['Price'][0] . '</td>';
		endif;
		
		echo '<td width="120">' . $custom_fields['Tenant'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Representative'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Landlord'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Landlord_Representative'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Notes'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Issues'][0] . '</td>';
		//echo '<td width="120">' . $custom_fields['Date'][0] . '</td>';
		echo '</tr>';
		
		endwhile;
	
	
		echo '</tr></table>';
		echo '</div>';
		if($_REQUEST['deal_search'] != 'Y') : 
			echo '<div style="padding-top: 25px;" align="center">' . paginate_links( $pagination ) . '</div>';
		endif;		
?>
</div>
<?php // get_sidebar(); ?>
</div>
	<?php global $hide_featured_gallery;
$hide_featured_gallery = true; ?>
<?php get_footer(); ?>
