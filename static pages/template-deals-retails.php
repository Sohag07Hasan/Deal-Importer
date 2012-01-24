<?php
/*
Template Name: Retail Leases
*/ 
?>
<?php get_header(); ?>
<div class="main-holder">
	
	<div id="content">
		
	<?php include_once(TEMPLATEPATH.'/block-trending.php'); ?>
		
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
</style>

<div style="float: left; width: 50%"><a href="/deal-sheet/commericial-leases/">Commerical Leases</a> | <a href="/deal-sheet/retail-leases/">Retail Leases</a> | <a href="/deal-sheet/building-sales/">Building Sales</a></div>

<?php
	
	$deal_category = 'retail';
	
	global $wp_rewrite;
	$wp_query->query_vars['paged'] > 1 ? $current = $wp_query->query_vars['paged'] : $current = 1;		
	$args = array( 
		'post_type' => 'deal',
		'posts_per_page' => 10, 
		'paged' => $paged,
		'meta_query' => array(
			array(
				'key' => 'Category',
				'value' => $deal_category,				
			),	  
		  ),
		 
	);
	
	$wp_query = new WP_Query( $args );
			
	
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
		
	echo '<div align="right" style="float: left; width: 50%; padding-bottom: 15px;">' . paginate_links( $pagination ) . '</div>';
	
	echo '<div style="padding-bottom: 15px;">Please submit deals to: <a href="deals@therealdeal.com">deals@therealdeal.com</a></div>';
	
	echo '<div>';
	echo '<table border="0" cellspacing="1" cellpadding="10"><tr>';
	echo '<tr><th>Address</th><th>Square Feet </th><th>Price</th><th>Tenant</th><th>Representative</th><th>Landlord</th><th>Landlord Representitive</th><th>Notes</th><th>Issue</th></tr>';
	$sort_values = array();
	while ( $wp_query->have_posts() ) : $wp_query->the_post();
		//$custom_fields = get_post_custom(get_the_ID());
		$sort_values[get_the_ID()] = get_post_meta(get_the_ID(),'Square_Feet',true);				
	endwhile;
	
	$opt = get_option('deals_front_end');
	
	if($opt[$deal_category] == 'ASC'){
		asort($sort_values);
	}
	else{
		arsort($sort_values);
	}
	
	foreach ($sort_values as $key=>$value) :
		$custom_fields = get_post_custom($key);
		echo '<td width="120">' . $custom_fields['Full_Address'][0] . '<br/><a href="http://maps.google.com/maps?q=' . $custom_fields['Full_Address'][0] . '"><b>MAP</b></a>';
		if ( current_user_can('manage_options') ) { 
			echo '<br/><a href="/wp-admin/post.php?post=' . get_the_ID() . '&action=edit"><b>EDIT</b></a>'; 
		} 
		echo '</td>';
		echo '<td width="120" align="center">' . $custom_fields['Square_Feet'][0] . '</td>';
		echo '<td width="120" align="center">' . $custom_fields['Price'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Tenant'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Representative'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Landlord'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Landlord_Representative'][0] . '</td>';
		echo '<td width="120">' . $custom_fields['Notes'][0] . '</td>';
		echo '<td width="120" align="center"><a href="/issues/' . $custom_fields['Issue Val'][0] . '"><b>ISSUE</b></a></td>';		
		echo '</tr>';
	endforeach;
	
		echo '</tr></table>';
		echo '</div>';
		
		echo '<div style="padding-top: 25px;" align="center">' . paginate_links( $pagination ) . '</div>';
				
?>
</div>
<?php // get_sidebar(); ?>
</div>
<?php get_footer(); ?>
