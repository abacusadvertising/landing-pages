<?phpadd_action('admin_init', 'lp_rebuild_permalinks');function lp_rebuild_permalinks(){	$activation_check = get_option('lp_activate_rewrite_check',0);	if ($activation_check)	{		global $wp_rewrite;		$wp_rewrite->flush_rules();		update_option( 'lp_activate_rewrite_check', '0');	}}add_action('init', 'landing_page_register');function landing_page_register() {	$slug = get_option( 'lp-main-landing-page-permalink-prefix', 'go' );    $labels = array(        'name' => _x('Landing Pages', 'post type general name'),        'singular_name' => _x('Landing Page', 'post type singular name'),        'add_new' => _x('Add New', 'Landing Page'),        'add_new_item' => __('Add New Landing Page'),        'edit_item' => __('Edit Landing Page'),        'new_item' => __('New Landing Page'),        'view_item' => __('View Landing Page'),        'search_items' => __('Search Landing Page'),        'not_found' =>  __('Nothing found'),        'not_found_in_trash' => __('Nothing found in Trash'),        'parent_item_colon' => ''    );    $args = array(        'labels' => $labels,        'public' => true,        'publicly_queryable' => true,        'show_ui' => true,        'query_var' => true,        'menu_icon' => LANDINGPAGES_URLPATH . '/images/plus.gif',        'rewrite' => array("slug" => "$slug"),        'with_front' => false,        'capability_type' => 'post',        'hierarchical' => false,        'menu_position' => null,        'supports' => array('title','custom-fields','editor','thumbnail', 'excerpt')      );    register_post_type( 'landing-page' , $args );	//flush_rewrite_rules( false );}if (!post_type_exists('wp-lead')){	add_action('init', 'lp_wpleads_register');	function lp_wpleads_register() {		//echo $slug;exit;		$labels = array(			'name' => _x('Leads', 'post type general name'),			'singular_name' => _x('Lead', 'post type singular name'),			'add_new' => _x('Add New', 'Lead'),			'add_new_item' => __('Add New Lead'),			'edit_item' => __('Edit Lead'),			'new_item' => __('New Leads'),			'view_item' => __('View Leads'),			'search_items' => __('Search Leads'),			'not_found' =>  __('Nothing found'),			'not_found_in_trash' => __('Nothing found in Trash'),			'parent_item_colon' => ''		);		$args = array(			'labels' => $labels,			'public' => false,			'publicly_queryable' => false,			//'show_ui' => true,			'show_ui' => false,			'query_var' => true,			//'menu_icon' => WPL_URL . '/images/leads.png',			'capability_type' => 'post',			'hierarchical' => false,			'menu_position' => null,			'supports' => array('custom-fields','thumbnail')		  );		register_post_type( 'wp-lead' , $args );		//flush_rewrite_rules( false );	}}add_action('init', 'landing_page_category_registerTaxonomy');function landing_page_category_registerTaxonomy() {    $args = array(            'hierarchical' => true,            'label' => "Categories",            'singular_label' => "Landing Page Category",            'show_ui' => true,            'query_var' => true,			"rewrite" => true    		);    register_taxonomy('landing_page_category', array('landing-page'), $args);    // Set category transient for use in other areas    $terms = get_terms('landing_page_category', array('hide_empty' => false));    $lp_cats = get_transient( 'landing-page-cats' );	if ( false === $lp_cats ) {		$options_categories = array();		$options_categories['all'] = 'All Landing Page Categories';		    foreach ($terms as $term) {		    	$options_categories[$term->term_id] = $term->name;		    }	set_transient('landing-page-cats', $options_categories, 24 * HOUR_IN_SECONDS);	}}// Change except box titleadd_action( 'admin_init', 'lp_change_excerpt_to_summary' );function lp_change_excerpt_to_summary() {	$post_type = "landing-page";	if ( post_type_supports($post_type, 'excerpt') ) {	add_meta_box('postexcerpt', __('Short Description'), 'post_excerpt_meta_box', $post_type, 'normal', 'core'); }}/*  This piece is for the customizer? I'm not sure - H */add_filter('admin_url','lp_add_fullscreen_param');function lp_add_fullscreen_param( $link ){	if (isset($_GET['page']))		return $link;	if (  ( isset($post) && 'landing-page' == $post->post_type ) || ( isset($_REQUEST['post_type']) && $_REQUEST['post_type']=='landing-page' ) )	{		$params['frontend'] = 'false';		if(isset($_GET['frontend']) && $_GET['frontend'] == 'true') {	        $params['frontend'] = 'true';	    }	    if(isset($_REQUEST['frontend']) && $_REQUEST['frontend'] == 'true') {	        $params['frontend'] = 'true';	    }	    $link = add_query_arg( $params, $link );	}	return $link;}/*********PREPARE COLUMNS FOR IMPRESSIONS AND CONVERSIONS***************/if (is_admin()){	//include_once(LANDINGPAGES_PATH.'filters/filters.post-type.php');	//add_filter('manage_edit-landing-page_sortable_columns', 'lp_column_register_sortable');	add_filter("manage_edit-landing-page_columns", 'lp_columns');	add_action("manage_posts_custom_column", "lp_column");	add_filter('landing-page_orderby','lp_column_orderby', 10, 2);	// remove SEO filter	if ( (isset($_GET['post_type']) && ($_GET['post_type'] == 'landing-page') ) )		{ add_filter( 'wpseo_use_page_analysis', '__return_false' ); }	//define columns for landing pages	function lp_columns($columns)	{		$columns = array(			"cb" => "<input type=\"checkbox\" />",						//"ID" => "ID",			"thumbnail-lander" => "Preview",			"title" => "Landing Page Title",			"stats" => "Variation Testing Stats",			"impressions" => "Total<br>Visits",			"actions" => "Total<br>Conversions",			"cr" => "Total<br>Conversion Rate"		);		return $columns;	}	function lp_show_stats_list() {		global $post;		$permalink = get_permalink($post->ID);		$variations = get_post_meta($post->ID, 'lp-ab-variations', true);		if ($variations)		{			$variations = explode(",", $variations);			$variations = array_filter($variations,'is_numeric');			//echo "<b>".$lp_impressions."</b> visits";			echo "<span class='show-stats button'>Show Variation Stats</span>";			echo "<ul class='lp-varation-stat-ul'>";			$first_status = get_post_meta($post->ID,'lp_ab_variation_status', true); // Current status			$first_notes = get_post_meta($post->ID,'lp-variation-notes', true);			$cr_array = array();			$i = 0;			$impressions = 0;			$conversions = 0;			foreach ($variations as $vid)			{				$letter = lp_ab_key_to_letter($vid); // convert to letter				$each_impression = get_post_meta($post->ID,'lp-ab-variation-impressions-'.$vid, true); // get impressions				$v_status = get_post_meta($post->ID,'lp_ab_variation_status-'.$vid, true); // Current status				if ($i === 0) { $v_status = $first_status; } // get status of first				(($v_status === "")) ? $v_status = "1" : $v_status = $v_status; // Get on/off status				$each_notes = get_post_meta($post->ID,'lp-variation-notes-'.$vid, true); // Get Notes				if ($i === 0) { $each_notes = $first_notes; } // Get first notes				$each_conversion = get_post_meta($post->ID,'lp-ab-variation-conversions-'.$vid, true);				(($each_conversion === "")) ? $final_conversion = 0 : $final_conversion = $each_conversion;				$impressions += get_post_meta($post->ID,'lp-ab-variation-impressions-'.$vid, true);				$conversions += get_post_meta($post->ID,'lp-ab-variation-conversions-'.$vid, true);				if ($each_impression != 0)				{					$conversion_rate = $final_conversion / $each_impression;				}				else				{					$conversion_rate = 0;				}				$conversion_rate = round($conversion_rate,2) * 100;				$cr_array[] = $conversion_rate;				if ($v_status === "0")				{					$final_status = "(Paused)";				}				else				{					$final_status = "";				}				/*if ($cr_array[$i] > $largest) {				$largest = $cr_array[$i];				 }				(($largest === $conversion_rate)) ? $winner_class = 'lp-current-winner' : $winner_class = ""; */				(($final_conversion === "1")) ? $c_text = 'conversion' : $c_text = "conversions";				(($each_impression === "1")) ? $i_text = 'visit' : $i_text = "visits";				(($each_notes === "")) ? $each_notes = 'No notes' : $each_notes = $each_notes;				$data_letter = "data-letter=\"".$letter."\"";				$popup = "data-notes=\"<span class='lp-pop-description'>".$each_notes."</span><span class='lp-pop-controls'><span class='lp-pop-edit button-primary'><a href='/wp-admin/post.php?post=".$post->ID."&lp-variation-id=".$vid."&action=edit'>Edit This Varaition</a></span><span class='lp-pop-preview button'><a title='Click to Preview this variation' class='thickbox' href='".$permalink."?lp-variation-id=".$vid."&iframe_window=on&post_id=".$post->ID."&TB_iframe=true&width=640&height=703' target='_blank'>Preview This Varaition</a></span><span class='lp-bottom-controls'><span class='lp-delete-var-stats' data-letter='".$letter."' data-vid='".$vid."' rel='".$post->ID."'>Clear These Stats</span></span></span>\"";				echo "<li rel='".$final_status."' data-postid='".$post->ID."' data-letter='".$letter."' data-lp='' class='lp-stat-row-".$vid." ".$post->ID. '-'. $conversion_rate ." status-".$v_status. "'><a ".$popup." ".$data_letter." class='lp-letter' title='click to edit this variation' href='/wp-admin/post.php?post=".$post->ID."&lp-variation-id=".$vid."&action=edit'>" . $letter . "</a><span class='lp-numbers'> <span class='lp-impress-num'>" . $each_impression . "</span><span class='visit-text'>".$i_text." with</span><span class='lp-con-num'>". $final_conversion . "</span> ".$c_text."</span><a ".$popup." ".$data_letter." class='cr-number cr-empty-".$conversion_rate."' href='/wp-admin/post.php?post=".$post->ID."&lp-variation-id=".$vid."&action=edit'>". $conversion_rate . "%</a></li>";				$i++;			}			echo "</ul>";			$winning_cr = max($cr_array); // best conversion rate			if ($winning_cr != 0) {			 echo "<span class='variation-winner-is'>".$post->ID. "-".$winning_cr."</span>";			}			//echo "Total Visits: " . $impressions;			//echo "Total Conversions: " . $conversions;		}		else		{			$notes = get_post_meta($post->ID,'lp-variation-notes', true); // Get Notes			$cr = lp_show_aggregated_stats("cr");			(($notes === "")) ? $notes = 'No notes' : $notes = $notes;			$popup = "data-notes=\"<span class='lp-pop-description'>".$notes."</span><span class='lp-pop-controls'><span class='lp-pop-edit button-primary'><a href='/wp-admin/post.php?post=".$post->ID."&lp-variation-id=0&action=edit'>Edit This Varaition</a></span><span class='lp-pop-preview button'><a title='Click to Preview this variation' class='thickbox' href='".$permalink."?lp-variation-id=0&iframe_window=on&post_id=".$post->ID."&TB_iframe=true&width=640&height=703' target='_blank'>Preview This Varaition</a></span><span class='lp-bottom-controls'><span class='lp-delete-var-stats' data-letter='A' data-vid='0' rel='".$post->ID."'>Clear These Stats</span></span></span>\"";			echo "<ul class='lp-varation-stat-ul'><li rel='' data-postid='".$post->ID."' data-letter='A' data-lp=''><a ".$popup." data-letter=\"A\" class='lp-letter' title='click to edit this variation' href='/wp-admin/post.php?post=".$post->ID."&lp-variation-id=0&action=edit'>A</a><span class='lp-numbers'> <span class='lp-impress-num'>" . lp_show_aggregated_stats("impressions") . "</span><span class='visit-text'>visits with</span><span class='lp-con-num'>". lp_show_aggregated_stats("actions") . "</span> conversions</span><a class='cr-number cr-empty-".$cr."' href='/wp-admin/post.php?post=".$post->ID."&lp-variation-id=0&action=edit'>". $cr . "%</a></li></ul>";			echo "<div class='no-stats-yet'>No A/B Tests running for this landing page. <a href='/wp-admin/post.php?post=".$post->ID."&lp-variation-id=1&action=edit&new-variation=1&lp-message=go'>Start one</a></div>";		}	}	function lp_show_aggregated_stats($type_of_stat)	{		global $post;		$variations = get_post_meta($post->ID, 'lp-ab-variations', true);		$variations = explode(",", $variations);		$impressions = 0;		$conversions = 0;		foreach ($variations as $vid)		{			$each_impression = get_post_meta($post->ID,'lp-ab-variation-impressions-'.$vid, true);			$each_conversion = get_post_meta($post->ID,'lp-ab-variation-conversions-'.$vid, true);			(($each_conversion === "")) ? $final_conversion = 0 : $final_conversion = $each_conversion;			$impressions += get_post_meta($post->ID,'lp-ab-variation-impressions-'.$vid, true);			$conversions += get_post_meta($post->ID,'lp-ab-variation-conversions-'.$vid, true);		}		if ($type_of_stat === "actions")		{			return $conversions;		}		if ($type_of_stat === "impressions")		{			return $impressions;		}		if ($type_of_stat === "cr")		{			if ($impressions != 0) {			$conversion_rate = $conversions / $impressions;			} else {			$conversion_rate = 0;			}			$conversion_rate = round($conversion_rate,2) * 100;			return $conversion_rate;		}	}	//populate collumsn for landing pages	function lp_column($column)	{		global $post;		if ("ID" == $column)		{			echo $post->ID;		}		else if ("title" == $column)		{		}		else if ("author" == $column)		{		}		else if ("date" == $column)		{		}		else if ("thumbnail-lander" == $column)		{			$template = get_post_meta($post->ID, 'lp-selected-template', true);			$permalink = get_permalink($post->ID);			$datetime = the_modified_date('YmjH',null,null,false);			$permalink = $permalink = $permalink.'?dt='.$datetime;			$thumbnail = 'http://s.wordpress.com/mshots/v1/' . urlencode(esc_url($permalink)) . '?w=140';			echo "<a title='Click to Preview this variation' class='thickbox' href='".$permalink."?lp-variation-id=0&iframe_window=on&post_id=".$post->ID."&TB_iframe=true&width=640&height=703' target='_blank'><img src=".$thumbnail."' style='width:150px;height:110px;' title='Click to Preview'></a>";		}		else		{			$lp_impressions = lp_get_page_views($post->ID);			$lp_conversions = lp_get_conversions($post->ID);			if ($lp_conversions>0){				$lp_cr = round(($lp_conversions/$lp_impressions), 2);			} else {				$lp_cr = "0.0";			}		}		if ("stats" == $column)		{			$lp_impressions =  apply_filters('lp_col_impressions',$lp_impressions);			lp_show_stats_list();		}		elseif ("impressions" == $column)		{			echo lp_show_aggregated_stats("impressions");		}		elseif ("actions" == $column)		{			echo lp_show_aggregated_stats("actions");		}		elseif ("cr" == $column)		{			 echo lp_show_aggregated_stats("cr") . "%";		}		elseif ("template" == $column) {			$template_used = get_post_meta($post->ID, 'lp-selected-template', true);			echo $template_used;		}	}	// Add category sort to landing page list	function lp_taxonomy_filter_restrict_manage_posts()	{	    global $typenow;		if ($typenow === "landing-page") {	    $post_types = get_post_types( array( '_builtin' => false ) );	    if ( in_array( $typenow, $post_types ) ) {	    	$filters = get_object_taxonomies( $typenow );	        foreach ( $filters as $tax_slug ) {	            $tax_obj = get_taxonomy( $tax_slug );	            (isset($_GET[$tax_slug])) ? $current = $_GET[$tax_slug] : $current = 0;	            wp_dropdown_categories( array(	                'show_option_all' => __('Show All '.$tax_obj->label ),	                'taxonomy' 	  => $tax_slug,	                'name' 		  => $tax_obj->name,	                'orderby' 	  => 'name',	                'selected' 	  => $current,	                'hierarchical' 	  => $tax_obj->hierarchical,	                'show_count' 	  => false,	                'hide_empty' 	  => true	            ) );		        }		    }		}	}	add_action( 'restrict_manage_posts', 'lp_taxonomy_filter_restrict_manage_posts' );	function convert_landing_page_category_id_to_taxonomy_term_in_query($query) {		global $pagenow;		$qv = &$query->query_vars;		if( $pagenow=='edit.php' && isset($qv['landing_page_category']) && is_numeric($qv['landing_page_category']) ) {			$term = get_term_by('id',$qv['landing_page_category'],'landing_page_category');			$qv['landing_page_category'] = $term->slug;		}	}	add_filter('parse_query','convert_landing_page_category_id_to_taxonomy_term_in_query');	// Make these columns sortable	add_filter( 'manage_edit-landing-page_sortable_columns', 'lp_sortable_columns' );	function lp_sortable_columns() {	  return array(		'title' => 'title',		'impressions'      => 'impressions',		'actions' => 'actions',		'cr'     => 'cr'	  );	}	//START Custom styling of post state (eg: pretty highlighting of post_status on landing pages page	add_filter( 'display_post_states', 'lp_custom_post_states' );	function lp_custom_post_states( $post_states ) {	   foreach ( $post_states as &$state ){	   $state = '<span class="'.strtolower( $state ).' states">' . str_replace( ' ', '-', $state ) . '</span>';	   }	   return $post_states;	}	//***********ADDS 'CLEAR STATS' BUTTON TO POSTS EDITING AREA******************/	add_filter('post_row_actions', 'lp_add_clear_tracking',10,2);	function lp_add_clear_tracking($actions, $post) {			if ($post->post_type=='landing-page')			{				$actions['clear'] = '<a href="#clear-stats" id="lp_clear_'.$post->ID.'" class="clear_stats" title="'				. esc_attr(__("Clear impression and conversion records", 'inboundnow_clear_stats'))				. '" >' .  __('Clear All Stats', 'Clear impression and conversion records') . '</a><span class="hover-description">Hover over the letters to the right for more options</span>';			}			return $actions;	}	/* perform trash actions for landing pages */	add_action('wp_trash_post', 'lp_trash_lander');	function lp_trash_lander($post_id) {		global $post;		if (!isset($post)||isset($_POST['split_test']))			return;		if ($post->post_type=='revision')		{			return;		}		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||(isset($_POST['post_type'])&&$_POST['post_type']=='revision'))		{			return;		}		if ($post->post_type=='landing-page')		{			$lp_id = $post->ID;			$args=array(			  'post_type' => 'landing-page-group',			  'post_satus'=>'publish'			);			$my_query = null;			$my_query = new WP_Query($args);			if( $my_query->have_posts() )			{				$i=1;				while ($my_query->have_posts()) : $my_query->the_post();					$group_id = get_the_ID();					$group_data = get_the_content();					$group_data = json_decode($group_data,true);					$lp_ids = array();					foreach ($group_data as $key=>$value)					{						$lp_ids[] = $key;					}					if (in_array($lp_id,$lp_ids))					{						unset($group_data[$lp_id]);						$this_data = json_encode($group_data);						//print_r($this_data);						$new_post = array(							'ID' => $group_id,							'post_title' => get_the_title(),							'post_content' => $this_data,							'post_status' => 'publish',							'post_date' => date('Y-m-d H:i:s'),							'post_author' => 1,							'post_type' => 'landing-page-group'						);						//print_r($new_post);						$post_id = wp_update_post($new_post);					}				endwhile;			}		}	}}if (!post_type_exists('wp-lead')){	add_action('init', 'lp_wpleads_register');	function lp_wpleads_register() {		//echo $slug;exit;		$labels = array(			'name' => _x('Leads', 'post type general name'),			'singular_name' => _x('Lead', 'post type singular name'),			'add_new' => _x('Add New', 'Lead'),			'add_new_item' => __('Add New Lead'),			'edit_item' => __('Edit Lead'),			'new_item' => __('New Leads'),			'view_item' => __('View Leads'),			'search_items' => __('Search Leads'),			'not_found' =>  __('Nothing found'),			'not_found_in_trash' => __('Nothing found in Trash'),			'parent_item_colon' => ''		);		$args = array(			'labels' => $labels,			'public' => false,			'publicly_queryable' => false,			//'show_ui' => true,			'show_ui' => false,			'query_var' => true,			//'menu_icon' => WPL_URL . '/images/leads.png',			'capability_type' => 'post',			'hierarchical' => false,			'menu_position' => null,			'supports' => array('custom-fields','thumbnail')		  );		register_post_type( 'wp-lead' , $args );		//flush_rewrite_rules( false );	}}