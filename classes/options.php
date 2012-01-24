<?php
/*
 * makes an options page to set if the data is to be sorted by asc or desc
 * 
 * */

if(!class_exists('deals_options')) :
 	class deals_options{
 		function __construct(){
 			add_action('admin_menu',array($this,'optionspage'));
 		}
 		
 		//optiosnpage
 		function optionspage(){
			add_options_page('Deals Information','Deals Front End','activate_plugins','deals-information',array($this,'optionsPageDetails'));
		}
		
		//details
		function optionsPageDetails(){
			if($_REQUEST['deal_information_submit'] == 'Y'){
				$options = array(
					'retail' => $_REQUEST['retail'],
					'office' => $_REQUEST['office'],
					'commercial' => $_REQUEST['commercial']
				);
				
				update_option('deals_front_end',$options);
									
			}

			$opt = get_option('deals_front_end');
			
			?>
			
			<div class="wrap">
				<?php screen_icon('options-general'); ?>				
				<h2>Deals Front End/ SQUARE FEET</h2>
				<p> Set the options to sort the front end data with SQUARE FEET </p>
				<?php 
					if($_REQUEST['deal_information_submit'] == 'Y'){
						echo '<div class="updated"><p>Saved</p></div>';
					}
				?>
				<form action="" method="post">
					<input type="hidden" name="deal_information_submit" value="Y" />								
					<table class="form-table">
														
							<tr valign="top"><th scope="row"> Retail </th>							
								<td colspan="2">
									<select name="retail">
										<option value="DESC" <?php selected('DESC',$opt['retail']); ?> >DESC</option>
										<option value="ASC" <?php selected('ASC',$opt['retail']); ?> >ASC</option>
									</select>
								</td>												
							</tr>
							
							<tr valign="top"><th scope="row"> Office </th>
								<td colspan="2">
									<select name="office">
										<option value="DESC" <?php selected('DESC',$opt['office']); ?> >DESC</option>
										<option value="ASC" <?php selected('ASC',$opt['office']); ?> >ASC</option>
									</select>
								</td>																				
							</tr>
							
							<tr valign="top"><th scope="row"> Commercial </th>
								<td colspan="2">
									<select name="commercial">
										<option value="DESC" <?php selected('DESC',$opt['commercial']); ?> >DESC</option>
										<option value="ASC" <?php selected('ASC',$opt['commercial']); ?> >ASC</option>
									</select>
								</td>
							</tr>														
					</table>
					<input type="submit" name="su" value="Save Changes" class="button-primary"  />
				</form>
			</div>
			
			<?php 
		}
 	}
 	
 	$deals_obj = new deals_options();
endif;