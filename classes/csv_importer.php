<?php
set_time_limit(0);
/*
Plugin Name: TRDM Deals Importer
Description: Import data as posts from a CSV file. <em>You can reach the author at <a href="mailto:d.v.kobozev@gmail.com">d.v.kobozev@gmail.com</a></em>.
Version: 0.3.6
Author: Jon Baer (based on Denis Kobozev)
*/

/**
 * LICENSE: The MIT License {{{
 *
 * Copyright (c) <2009> <Denis Kobozev>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Denis Kobozev <d.v.kobozev@gmail.com>
 * @copyright 2009 Denis Kobozev
 * @license   The MIT License
 * }}}
 */

class CSVImporterException extends Exception {}

class CSVImporterPlugin {
    var $defaults = array(
        'csv_post_title' => null,
        'csv_post_post' => null,
        'csv_post_type' => null,
        'csv_post_excerpt' => null,
        'csv_post_date' => null,
        'csv_post_tags' => null,
        'csv_post_categories' => null,
        'csv_post_author' => null,
        'csv_post_slug' => null,
        'csv_post_parent' => 0,
    );

    var $log = array();

    // determine value of option $name from database, $default value or $params,
    // save it to the db if needed and return it
    function process_option($name, $default, $params) {
        if (array_key_exists($name, $params)) {
            $value = stripslashes($params[$name]);
        } elseif (array_key_exists('_'.$name, $params)) {
            // unchecked checkbox value
            $value = stripslashes($params['_'.$name]);
        } else {
            $value = null;
        }
        $stored_value = get_option($name);
        if ($value == null) {
            if ($stored_value === false) {
                if (is_callable($default) &&
                    method_exists($default[0], $default[1])) {
                    $value = call_user_func($default);
                } else {
                    $value = $default;
                }
                add_option($name, $value);
            } else {
                $value = $stored_value;
            }
        } else {
            if ($stored_value === false) {
                add_option($name, $value);
            } elseif ($stored_value != $value) {
                update_option($name, $value);
            }
        }
        return $value;
    }

    // Plugin's interface
    function form() {
				
		
        $opt_draft = $this->process_option('csv_importer_import_as_draft', 'publish', $_POST);
        $opt_cat = $this->process_option('csv_importer_cat', 0, $_POST);

        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $this->post(compact('opt_draft', 'opt_cat'));
        }

        // form HTML {{{
?>

<div class="wrap">
	<?php screen_icon('tools'); ?>
    <h2>Import Deals (CSV)</h2> 
      
    <div style=''>
		
		<h4>CSV FORMAT</h4>
		<strong style='color:#4C2B2B'>
			category, post_title, Price, Size Info/square feet, Landlord, Landlord Representative, Tenant/buyer, Tentant Representative, Full Address, Notes, Issue, Date
		</strong>
		<h4>Category</h4>
		<ul>
			<li>olease => office</li>
			<li>rlease => retail</li>
			<li>csales => commercial</li>
		</ul>
		
		<h3>Important</h3>
		Alwasy use the prescribed CSV FORMAT exactly. For example see the the attached <a href="#">csv_example.csv</a> and read the <a href="#">csv_format.txt</a>
		
    </div>
    
    <form class="add:the-list: validate" method="post" enctype="multipart/form-data">
                
        <p><label for="csv_import">Upload file:</label><br/>
            <input name="csv_import" id="csv_import" type="file" value="" aria-required="true" /></p>
        <p class="submit"><input type="submit" class="button" name="submit" value="Import" /></p>
    </form>
</div><!-- end wrap -->

<?php
        // end form HTML }}}

    }

    function print_messages() {
        if (!empty($this->log)) {

        // messages HTML {{{
?>

<div class="wrap">
    <?php if (!empty($this->log['error'])): ?>

    <div class="error">

        <?php foreach ($this->log['error'] as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>

    </div>

    <?php endif; ?>

    <?php if (!empty($this->log['notice'])): ?>

    <div class="updated fade">

        <?php foreach ($this->log['notice'] as $notice): ?>
            <p><?php echo $notice; ?></p>
        <?php endforeach; ?>

    </div>

    <?php endif; ?>
</div><!-- end wrap -->

<?php
        // end messages HTML }}}

            $this->log = array();
        }
    }

    // Handle POST submission
    function post($options) {
		
		
        if (empty($_FILES['csv_import']['tmp_name'])) {
            $this->log['error'][] = 'No file uploaded, aborting.';
            $this->print_messages();
            return;
        }

        //require_once 'File_CSV_DataSource/DataSource.php';

        $time_start = microtime(true);
        $csv = new File_CSV_DataSource;
        $file = $_FILES['csv_import']['tmp_name'];
        $this->stripBOM($file);

        if (!$csv->load($file)) {
            $this->log['error'][] = 'Failed to load file, aborting.';
            $this->print_messages();
            return;
        }

        // pad shorter rows with empty values
        $csv->symmetrize();

        // WordPress sets the correct timezone for date functions somewhere
        // in the bowels of wp_insert_post(). We need strtotime() to return
        // correct time before the call to wp_insert_post().
        $tz = get_option('timezone_string');
        if ($tz && function_exists('date_default_timezone_set')) {
            date_default_timezone_set($tz);
        }

        $skipped = 0;
        $imported = 0;
        $comments = 0;  
        
        /*
         * removing all the hooks from savepost
         * */     
        global $deals_obj;
         remove_action('save_post', array($deals_obj, 'save_metadata'));
                      
        foreach ($csv->getRawArray() as $csv_data) {
			$new_csv = array();
			
			foreach($csv_data as $k=>$v){
				$new_csv[$k] = trim($v);
			}
						
            if ($post_id = $this->create_post($new_csv, $options)) {
                $imported++;
              //  $comments += $this->add_comments($post_id, $csv_data);
                $this->create_custom_fields($post_id, $new_csv);
            } else {
                $skipped++;
            }
        }

        if (file_exists($file)) {
            @unlink($file);
        }

        $exec_time = microtime(true) - $time_start;

        if ($skipped) {
            $this->log['notice'][] = "<b>Skipped {$skipped} deals (most likely due to empty title, body and excerpt).</b>";
        }
        $this->log['notice'][] = sprintf("<b>Imported {$imported} deals and {$comments} comments in %.2f seconds.</b>", $exec_time);
        $this->print_messages();
    }

    function create_post($data, $options) {
		
				
        extract($options);

        $data = array_merge($this->defaults, $data);
        $type = $data['csv_post_type'] ? $data['csv_post_type'] : 'post';
        $valid_type = (function_exists('post_type_exists') &&
            post_type_exists($type)) || in_array($type, array('post', 'page'));

        if (!$valid_type) {
            $this->log['error']["type-{$type}"] = sprintf(
                'Unknown post type "%s".', $type);
        }

		$type = 'deal';
		//$type = 'post';
		
		

        $new_post = array(
            'post_title' => convert_chars($data[1]),
            'post_content' => wpautop(convert_chars($data[1])),
            'post_status' => 'publish',
            'post_type' => $type            
        );      
       

        // create!
        $id = wp_insert_post($new_post);
		
		if($id > 0){
			$this->update_issue_value($id, $data);
		}
		
        
        return $id;
    }
    
    // square feet data update
    function update_issue_value($post_id, $data){
		global $wpdb;
		$table = $wpdb->prefix . 'trdmdeals';
		
		//issue			
		$issue = strip_tags($data[10]);
		$issue = preg_replace('/[^a-zA-Z0-9 ]/', '', $issue);
		$issue = @ strtotime($issue);
		$issue = $this->timestamp_to_key($issue);
	
		$price = (float) preg_replace('/[^0-9.]/', '', $data[2]);		
		$sq_feet = (float) preg_replace('/[^0-9.]/', '', $data[3]);
		
		$cat = $this->doc_type(preg_replace('/[^a-z]/', '', $data[0]));		
					
		$wpdb->insert($table, array('post_id'=>$post_id, 'issue'=>$issue, 'price'=>$price, 'sq_feet'=>$sq_feet, 'cat'=>$cat), array('%d', '%d', '%f', '%f', '%s'));			
		
	}
	
	/*
	 * create unique key from a timesatmp yyyymm
	 * */
	 function timestamp_to_key($time){
		return date('Y', $time) . date('m', $time);
	}
	 

    /**
     * Return an array of category ids for a post.
     *
     * @param string $data csv_post_categories cell contents
     * @param integer $common_parent_id common parent id for all categories
     *
     * @return array() category ids
     */
    function create_or_get_categories($data, $common_parent_id) {
        $ids = array(
            'post' => array(),
            'cleanup' => array(),
        );
        $items = array_map('trim', explode(',', $data['csv_post_categories']));
        foreach ($items as $item) {
            if (is_numeric($item)) {
                if (get_category($item) !== null) {
                    $ids['post'][] = $item;
                } else {
                    $this->log['error'][] = "Category ID {$item} does not exist, skipping.";
                }
            } else {
                $parent_id = $common_parent_id;
                // item can be a single category name or a string such as
                // Parent > Child > Grandchild
                $categories = array_map('trim', explode('>', $item));
                if (count($categories) > 1 && is_numeric($categories[0])) {
                    $parent_id = $categories[0];
                    if (get_category($parent_id) !== null) {
                        // valid id, everything's ok
                        $categories = array_slice($categories, 1);
                    } else {
                        $this->log['error'][] = "Category ID {$parent_id} does not exist, skipping.";
                        continue;
                    }
                }
                foreach ($categories as $category) {
                    if ($category) {
                        $term = is_term($category, 'category', $parent_id);
                        if ($term) {
                            $term_id = $term['term_id'];
                        } else {
                            $term_id = wp_insert_category(array(
                                'cat_name' => $category,
                                'category_parent' => $parent_id,
                            ));
                            $ids['cleanup'][] = $term_id;
                        }
                        $parent_id = $term_id;
                    }
                }
                $ids['post'][] = $term_id;
            }
        }
        return $ids;
    }

    // Parse taxonomy data from the file
    //
    // array(
    //      // hierarchical taxonomy name => ID array
    //      'my taxonomy 1' => array(1, 2, 3, ...),
    //      // non-hierarchical taxonomy name => term names string
    //      'my taxonomy 2' => array('term1', 'term2', ...),
    // )
    function get_taxonomies($data) {
        $taxonomies = array();
        foreach ($data as $k => $v) {
            if (preg_match('/^csv_ctax_(.*)$/', $k, $matches)) {
                $t_name = $matches[1];
                if (is_taxonomy($t_name)) {
                    $taxonomies[$t_name] = $this->create_terms($t_name,
                        $data[$k]);
                } else {
                    $this->log['error'][] = "Unknown taxonomy $t_name";
                }
            }
        }
        return $taxonomies;
    }

    // Return an array of term IDs for hierarchical taxonomies or the original
    // string from CSV for non-hierarchical taxonomies. The original string
    // should have the same format as csv_post_tags.
    function create_terms($taxonomy, $field) {
        if (is_taxonomy_hierarchical($taxonomy)) {
            $term_ids = array();
            foreach ($this->_parse_tax($field) as $row) {
                list($parent, $child) = $row;
                $parent_ok = true;
                if ($parent) {
                    $parent_info = is_term($parent, $taxonomy);
                    if (!$parent_info) {
                        // create parent
                        $parent_info = wp_insert_term($parent, $taxonomy);
                    }
                    if (!is_wp_error($parent_info)) {
                        $parent_id = $parent_info['term_id'];
                    } else {
                        // could not find or create parent
                        $parent_ok = false;
                    }
                } else {
                    $parent_id = 0;
                }

                if ($parent_ok) {
                    $child_info = is_term($child, $taxonomy, $parent_id);
                    if (!$child_info) {
                        // create child
                        $child_info = wp_insert_term($child, $taxonomy,
                            array('parent' => $parent_id));
                    }
                    if (!is_wp_error($child_info)) {
                        $term_ids[] = $child_info['term_id'];
                    }
                }
            }
            return $term_ids;
        } else {
            return $field;
        }
    }

    // hierarchical taxonomy fields are tiny CSV files in their own right
    function _parse_tax($field) {
        $data = array();
        if (function_exists('str_getcsv')) { // PHP 5 >= 5.3.0
            $lines = explode("\n", $field);

            foreach ($lines as $line) {
                $data[] = str_getcsv($line, ',', '"');
            }
        } else {
            // Use temp files for older PHP versions. Reusing the tmp file for
            // the duration of the script might be faster, but not necessarily
            // significant.
            $handle = tmpfile();
            fwrite($handle, $field);
            fseek($handle, 0);

            while (($r = fgetcsv($handle, 999999, ',', '"')) !== false) {
                $data[] = $r;
            }
            fclose($handle);
        }
        return $data;
    }

    function add_comments($post_id, $data) {
        // First get a list of the comments for this post
        $comments = array();
        foreach ($data as $k => $v) {
            // comments start with cvs_comment_
            if (    preg_match('/^csv_comment_([^_]+)_(.*)/', $k, $matches) &&
                    $v != '') {
                $comments[$matches[1]] = 1;
            }
        }
        // Sort this list which specifies the order they are inserted, in case
        // that matters somewhere
        ksort($comments);

        // Now go through each comment and insert it. More fields are possible
        // in principle (see docu of wp_insert_comment), but I didn't have data
        // for them so I didn't test them, so I didn't include them.
        $count = 0;
        foreach ($comments as $cid => $v) {
            $new_comment = array(
                'comment_post_ID' => $post_id,
                'comment_approved' => 1,
            );

            if (isset($data["csv_comment_{$cid}_author"])) {
                $new_comment['comment_author'] = convert_chars(
                    $data["csv_comment_{$cid}_author"]);
            }
            if (isset($data["csv_comment_{$cid}_author_email"])) {
                $new_comment['comment_author_email'] = convert_chars(
                    $data["csv_comment_{$cid}_author_email"]);
            }
            if (isset($data["csv_comment_{$cid}_url"])) {
                $new_comment['comment_author_url'] = convert_chars(
                    $data["csv_comment_{$cid}_url"]);
            }
            if (isset($data["csv_comment_{$cid}_content"])) {
                $new_comment['comment_content'] = convert_chars(
                    $data["csv_comment_{$cid}_content"]);
            }
            if (isset($data["csv_comment_{$cid}_date"])) {
                $new_comment['comment_date'] = $this->parse_date(
                    $data["csv_comment_{$cid}_date"]);
            }

            $id = wp_insert_comment($new_comment);
            if ($id) {
                $count++;
            } else {
                $this->log['error'][] = "Could not add comment $cid";
            }
        }
        return $count;
    }
    
    function doc_type($v){
		$doctype = '';
		switch($v){
			case "olease" :
				$doctype = "office";
				break;
			case "rlease" :
				$doctype = "retail";
				break;
			case "csales" :
				$doctype = "commercial";
				break;
		}
		
		return $doctype;		
		
	}

/*
 * 	custom fields update
 * */
    function create_custom_fields($post_id, $data) {
		$doctype = null;
        foreach ($data as $k => $v) {
			$v = trim($v);

			switch($k) {

				case 0 :
					$doc = $this->doc_type($v);
					update_post_meta($post_id, "Category", $doc);
					break;

				case 2 : 
					update_post_meta($post_id, "Price", $v);
					break;
				case 3 :
					 update_post_meta($post_id, "Square_Feet", $v);
					 break;
				case 4 :
					update_post_meta($post_id, "Landlord", $v);
					break;
				case 5:
					update_post_meta($post_id, "Landlord_Representative", $v);
					break;
				case 6 :
					update_post_meta($post_id, "Tenant", $v);
					break;
				case 7 :
					update_post_meta($post_id, "Representative", $v);
					break;
				case 8 :
					update_post_meta($post_id, "Full_Address", $v);
					break;
				case 9: 
					update_post_meta($post_id, "Notes", $v);
					break;
				case 10: 
					update_post_meta($post_id, "Issues", $v);
					break;
				case 11: 
					update_post_meta($post_id, "Date", $v);
					break;
			}


        }
    }

    function get_auth_id($author) {
        if (is_numeric($author)) {
            return $author;
        }
        $author_data = get_userdatabylogin($author);
        return ($author_data) ? $author_data->ID : 0;
    }

    // Convert date in CSV file to 1999-12-31 23:52:00 format
    function parse_date($data) {
        $timestamp = strtotime($data);
        if (false === $timestamp) {
            return '';
        } else {
            return date('Y-m-d H:i:s', $timestamp);
        }
    }

    // delete BOM from UTF-8 file
    function stripBOM($fname) {
        $res = fopen($fname, 'rb');
        if (false !== $res) {
            $bytes = fread($res, 3);
            if ($bytes == pack('CCC', 0xef, 0xbb, 0xbf)) {
                $this->log['notice'][] = 'Getting rid of byte order mark...';
                fclose($res);

                $contents = file_get_contents($fname);
                if (false === $contents) {
                    trigger_error('Failed to get file contents.', E_USER_WARNING);
                }
                $contents = substr($contents, 3);
                $success = file_put_contents($fname, $contents);
                if (false === $success) {
                    trigger_error('Failed to put file contents.', E_USER_WARNING);
                }
            } else {
                fclose($res);
            }
        } else {
            $this->log['error'][] = 'Failed to open file, aborting.';
        }
    }

}

function csv_admin_menu() {
    require_once ABSPATH . '/wp-admin/admin.php';
    $plugin = new CSVImporterPlugin;
    add_management_page('edit.php', 'Deals Importer', 9, __FILE__, array($plugin, 'form'));
   
}

add_action('admin_menu', 'csv_admin_menu');
