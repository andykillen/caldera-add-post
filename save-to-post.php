<?php
/**
 * Functions/hooks for regular form to post type
 *
 * @package   caldera_custom_fields
 * @copyright 2014-2015 CalderaWP LLC and David Cramer 
 * @copyright 2016 Andrew Killen
 */
add_filter( 'caldera_forms_get_form_processors', 'caldera_extension_posttype_process' );
add_filter( 'caldera_forms_get_entry_detail', 'caldera_extension_entry_details', 10, 3);
add_filter( 'caldera_forms_render_get_entry', 'caldera_extension_get_post_type_entry', 10, 3);
add_filter( 'caldera_forms_get_entry_meta_db_storage', 'caldera_extension_meta_view');
add_action( 'caldera_forms_processor_templates', 'caldera_extension_meta_template');
add_filter( 'caldera_forms_render_pre_get_entry', 'caldera_extension_populate_form_edit', 11, 3 );
add_filter( 'caldera_forms_get_addons', 'caldera_extension_savetoposttype_addon' );

/**
 * Register this as a processor.
 *
 * @uses "caldera_forms_get_form_processors" filter
 *
 * @param $processors
 *
 * @return mixed
 */
function caldera_extension_posttype_process($processors){

	$processors['single_post_type'] = array(
		"name"				=>	__( 'Save as Post', 'caldera-extension' ),
		"author"            =>  'Andrew Killen',
		"description"		=>	__( 'Store form entries as a post with custom fields.', 'caldera-extension' ),
		"post_processor"	=>	'caldera_extension_capture_entry',
		"template"			=>	trailingslashit( CCF_PATH ) . "includes/to-post-type-config.php",
		"icon"				=>	CCF_URL . "/metabox-icon.png",
		"default"			=>	array(
			'post_status'	=>	"draft"
		),
		"meta_template"		=>	trailingslashit( CCF_PATH ) . "includes/meta_template.php",
		"magic_tags"		=>	array(
			"ID",
			"permalink"
		)

	);
	return $processors;

}

/**
 * Prepared from data for edit post.
 *
 * @since 1.1.0
 *
 * @uses "caldera_forms_render_pre_get_entry" filter
 *
 * @param array $data Form data.
 * @param array $form Form config.
 * @param $entry_id
 *
 * @return array
 */
function caldera_extension_populate_form_edit( $data, $form, $entry_id ){

	$processors = Caldera_Forms::get_processor_by_type( 'post_type', $form );
	if( !empty( $processors ) ){
		foreach( $processors as $processor ){
			if( !empty( $processor['config']['ID'] ) ){
				// ooo ID!!!
				$ID = Caldera_Forms::do_magic_tags( $processor['config']['ID'], $entry_id );
				if( !empty( $ID ) ){
					$post = get_post( $ID );
				}
				if( empty( $post ) ){
					return $data;
				}

				$data[ $processor['config']['post_title'] ] = $post->post_title;
				$data[ $processor['config']['post_content'] ] = $post->post_content;
				foreach( $form['fields'] as $field_id => $field ){
					if( $post->{$field['slug']} ){
						$data[ $field_id ] = $post->{$field['slug']};
					}
				}
			}
		}
	}
	return $data;
}

/**
 * Register the add-on
 *
 * @since 1.1.0
 *
 * @uses "caldera_forms_get_addons" filter
 *
 * @param array $addons
 *
 * @return array
 */
function caldera_extension_savetoposttype_addon($addons){
	$addons['savetopost'] = __FILE__;
	return $addons;

}

/**
 * Set template for meta fields
 *
 * @since 1.1.0
 *
 * @uses "caldera_forms_processor_templates" action
 */
function caldera_extension_meta_template(){
	?>
	<script type="text/html" id="post-meta-field-tmpl">
		<div class="caldera-config-group">
			<label>Field Name<input type="text" class="block-input field-config" name="{{_name}}[metakey][]" value="{{metakey}}"></label>
			<div class="caldera-config-field">
				<div>Value Field</div>
				<div style="width: 280px; display:inline-block;">{{{_field slug="meta_field" array="true"}}}</div>
				<button class="button remove-meta-field{{_id}}" type="button"><?php echo __('Remove', 'caldera-forms'); ?></button>
			</div>
		</div>
	</script>
	<?php
}

/**
 * Prepare DB storage
 *
 * @uses "caldera_forms_get_entry_meta_db_storage" filter
 *
 * @param $meta
 *
 * @return mixed
 */
function caldera_extension_meta_view($meta){
	$postid = $meta['meta_value'];
	$meta['meta_key'] = _('Post Name', 'caldera-extension' );
	$meta['meta_value'] = get_the_title($meta['meta_value']);
	$meta['meta_value'] .= '<div><a href="post.php?post='.$postid.'&action=edit" target="_blank">'.__('Edit').'</a> | <a href="' . get_permalink( $postid ) . '" target="_blank">'.__('View').'</a></div>';
	$meta['post'] = get_post( $postid );
	return $meta;
}

/**
 * Check if a form has the post type processor
 *
 * @since 1.1.0
 *
 * @param array $form Form config.
 * @param string $entry_id Entry ID.
 *
 * @return array|bool False if not in processor, else post object/ config.
 */
function caldera_extension_has_pr_processor($form, $entry_id){
	if(!empty($form['processors'])){
		foreach($form['processors'] as $processor){
			if($processor['type'] === 'db_storage'){
				$post = get_post($entry_id);
				if(empty($post)){
					return false;
				}

				if($post->post_type === $processor['config']['post_type']){
					return array(
						'post'	=> $post,
						'config'=> $processor['config']
					);
				}
			}
		}
	}

	return false;
}

/**
 * Get entry details
 *
 * @since 1.1.0
 *
 * @param array $entry Entry details
 * @param string $entry_id Entry ID
 * @param array $form
 *
 * @return array
 */
function caldera_extension_entry_details($entry, $entry_id, $form){


	if($processor = caldera_extension_has_pr_processor($form,$entry_id)){

		$entry = array(
			'id'		=>	$entry_id,
			'form_id'	=>	$form['ID'],
			'user_id'	=>	$processor['post']->post_author,
			'datestamp'     =>	$processor['post']->post_date
		);

	}

	return $entry;

}

/**
 * Render saved entry when editing posts.
 *
 * @since 1.1.0
 *
 * @uses "caldera_forms_render_get_entry" filter
 *
 * @param array $data Rendered data.
 * @param array $form Form config.
 * @param string $entry_id Entry ID.
 *
 * @return array
 */
function caldera_extension_get_post_type_entry($data, $form, $entry_id){

	if($processor = caldera_extension_has_pr_processor($form, $entry_id)){
		$fields = $form['fields'];

		$data = array();
		$data[$fields[$processor['config']['post_title']]['slug']] = $processor['post']->post_title;
		unset($fields[$processor['config']['post_title']]);

		if(!empty($processor['config']['post_content'])){
			$data[$fields[$processor['config']['post_content']]['slug']] = $processor['post']->post_content;
			unset($fields[$processor['config']['post_content']]);
		}


		foreach($fields as $field){
			$data[$field['slug']] = get_post_meta( $processor['post']->ID, $field['slug'], true );
		}
	}
	return $data;
}



/**
 * Process entry and save as post/ post meta.
 *
 * @since 1.1.0
 *
 * @param array $config Processor config.
 * @param array $form From config.
 *
 * @return array
 */
function caldera_extension_capture_entry($config, $form){

	$user_id = get_current_user_id();
	if( !empty( $config['post_author'] ) ){
		$user_id = Caldera_Forms::do_magic_tags( $config['post_author'] );
	}

	$entry = array(
		'post_title'    => Caldera_Forms::get_field_data( $config['post_title'], $form ),
		'post_status'   => Caldera_Forms::do_magic_tags( $config['post_status'] ),
		'post_type'		=> $config['post_type'],
		'post_content'	=> Caldera_Forms::get_field_data( $config['post_content'], $form ),
		'post_parent'	=> Caldera_Forms::do_magic_tags( $config['post_parent'] ),
		'to_ping'		=> Caldera_Forms::do_magic_tags( $config['to_ping'] ),
		'post_password'	=> Caldera_Forms::do_magic_tags( $config['post_password'] ),
		'post_excerpt'	=> Caldera_Forms::do_magic_tags( $config['post_excerpt'] ),
		'comment_status'=> $config['comment_status'],
	);
	
	if( empty( $entry[ 'post_content' ] ) ){
		$entry[ 'post_content' ] = '';
	}

	// set the ID
	if( !empty( $config['ID'] ) ){
		$is_post_id = Caldera_Forms::do_magic_tags( $config['ID'] );
		$post = get_post( $is_post_id );
		if( !empty( $post ) && $post->post_type == $entry['post_type'] ){
			$entry['ID'] = $is_post_id;
		}

	}

	// set author
	if( !empty( $user_id ) ){
		$entry['post_author'] = $user_id;
	}

	//is edit?
	if(!empty($_POST['_cf_frm_edt'])){
		// need to work on this still. SIGH!
	}else{
		// Insert the post into the database
		$entry_id = wp_insert_post( $entry );
		if(empty($entry_id)){
			return;

		}

	}

	// do upload + attach
	if( !empty( $config['featured_image'] ) ){
		$featured_image = Caldera_Forms::get_field_data( $config['featured_image'], $form );
		foreach( (array) $featured_image as $filename ){
			$featured_image = caldera_extension_attach_file( $filename, $entry_id );
			update_post_meta($entry_id, '_thumbnail_id', $featured_image );
		}

	}

	//handle taxonomies
	$terms_saved = false;
	$tax_fields = caldera_extension_get_taxonomy_fields( $config );
	if ( ! empty( $tax_fields ) ) {
		$terms_saved = caldera_extension_save_terms( $tax_fields, $entry_id );
		if ( $terms_saved ) {
			$term_values = wp_list_pluck( $tax_fields, 'terms' );
		}
	}

	//get post fields into an array of fields not to save as meta.
	$post_fields = array_keys( $entry );
	// get all submission data
	$data = Caldera_Forms::get_submission_data( $form );
        
        //error_log(print_r($data,true),0,"/tmp/php.log");
	update_post_meta( $entry_id, '_cf_form_id', $form['ID'] );
	foreach($data as $field=>$value){
		if ( '_entry_token' != $field && '_entry_id' != $field ) {
			if ( in_array( $field, $post_fields )  || in_array( $form['fields'][ $field ]['ID'], $post_fields ) ) {
				continue;
			}

		}
                
                if(is_array($value)){
                    // required to generate attachment metadata
                    require_once ( ABSPATH . 'wp-admin/includes/image.php' );
                    // should be the ajax files
                        $counter = 1;
                        foreach($value as $file){
                            
                            // change the path to absolute from URI
                            $filepath = str_replace(content_url(), WP_CONTENT_DIR, $file);
                            
                            // Get the path to the upload directory.
                            $wp_upload_dir = wp_upload_dir();
                            // get mime details
                            $filetype = wp_check_filetype( basename( $filepath ), null );
                            // attache file details
                            $attach_args = array(
                                'guid'           => $wp_upload_dir['url'] . '/' . basename( $file ), 
                                'post_mime_type' => $filetype['type'],
                                "post_title"   => preg_replace( '/\.[^.]+$/', '', basename( $file ) ),
                                "post_content" => "",
                                'post_status'    => 'inherit'
                            );
                            // insert file in the media lib
                            $attach_id = wp_insert_attachment($attach_args, $filepath, $entry_id);
                            // make meta data
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $filepath );
                            // add meta data
                            wp_update_attachment_metadata( $attach_id, $attach_data );
                            // check if round 1 of adding files
                            if($counter == 1){
                                // set first as post thumb nail
                                set_post_thumbnail( $entry_id, $attach_id );
                            }                            
                            $counter ++;                            
                        }
                    }
                
                if(is_array($field)){
                     error_log("two");
                    error_log(print_r($field,true),0,"/tmp/php.log");
                }
               

		if ( $terms_saved ) {
			if ( is_array( $value ) ) {
				$_value = implode( ', ', $value );
			} else {
				$_value = $value;
			}

			if( in_array( $_value, $term_values  ) ){
				continue;

			}
		}



		if(empty($form['fields'][$field])){
			continue;
		}
		if( in_array( $form['fields'][$field]['type'], array( 'button', 'html' ) ) ){
			continue;
		}
		if( $form['fields'][$field]['type'] == 'file' ){
			if( $field == $config['featured_image'] ){
				continue; // dont attache twice.
			}
			foreach( (array) $value as $file ){
				caldera_extension_attach_file( $file , $entry_id );
			}
		}

		$slug = $form['fields'][$field]['slug'];

		/**
		 * Filter value before saving using to post type processor
		 *
		 * @since 2.0.3
		 *
		 * @param mixed $value The value to be saved
		 * @param string $slug Slug of field
		 * @param int $entry ID of post
		 */
		$value = apply_filters( 'caldera_extension_pre_save_meta_key_to_post_type', $value, $slug, $entry_id );
		update_post_meta( $entry_id, $slug, $value );
	}

	return array('Post ID' => $entry_id, 'ID' => $entry_id, 'permalink' => get_permalink( $entry_id ) );
}

/**
 * Handle file fields.
 *
 * @since 1.1.0
 *
 * @param string $file File path.
 * @param string $entry_id The entry ID
 *
 * @return int Attachment ID.
 */
function caldera_extension_attach_file( $file, $entry_id ){

	// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	// Check the type of file. We'll use this as the 'post_mime_type'.
	$filetype = wp_check_filetype( basename( $file ), null );

	// Get the path to the upload directory.
	$wp_upload_dir = wp_upload_dir();

	$filename = $wp_upload_dir['path'] . '/' . basename( $file );
	$attachment = array(
		'guid'           => $wp_upload_dir['url'] . '/' . basename( $file ),
		'post_mime_type' => $filetype['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file ) ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	// Insert the attachment.
	$attach_id = wp_insert_attachment( $attachment, $filename, $entry_id );

	// Generate the metadata for the attachment, and update the database record.
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	return $attach_id;

}


function caldera_extension_taxonomy_ui(){
	$taxonomies = get_taxonomies( array(), 'objects' );
	$fields = array();
	$args = array(
		'magic' => true,
		'block' => true,
		'type'  => 'text',
	);
	foreach( $taxonomies as $taxonomy => $obj ){
		$args[ 'id' ] = 'cf-custom-fields-tax-' . $taxonomy;
		$args[ 'label' ] = $obj->labels->singular_name;
		$args[ 'extra_classes' ] = $taxonomy;
		$fields[] = Caldera_Forms_Processor_UI::config_field(  $args );
	}

	return implode( "\n\n", $fields );
}

/**
 * Find taxonomy fields and values
 *
 * @since 2.1.0
 * 
 * @param $all_fields
 *
 * @return array
 */
function caldera_extension_get_taxonomy_fields( $all_fields ){
	$tax_fields = array();
	foreach( $all_fields as $field => $value ){
		if( false !== strpos( $field, 'cf-custom-fields-tax-') ){
			if ( ! empty( $value ) ) {
				$tax_fields[ $field ] = array(
					'taxonomy' => str_replace( 'cf-custom-fields-tax-', '', $field ),
					'terms'    => Caldera_Forms::do_magic_tags( $value )
				);
			}
		}
	}

	return $tax_fields;
}

/**
 * Save taxonomy terms
 * 
 * @since 2.1.0
 * 
 * @param array $tax_fields Taxonomy fields to save
 * @param int $post_id Post ID
 *
 * @return bool
 */
function caldera_extension_save_terms( $tax_fields, $post_id ){
	if ( is_array( $tax_fields ) ) {
		foreach ( $tax_fields as $taxonomy => $data ) {
			if( empty( $data[ 'terms' ] ) ){
				continue;
			}
			$terms = $data[ 'terms' ];
			if( is_numeric( $terms ) && false === strpos( $terms, ',' ) ){
				$terms = (int) $terms;

			}elseif( is_string( $terms ) && false != strpos( $terms, ',' ) ){
				$terms = explode( ',', $terms );
				foreach( $terms as $i => $term ){
					$terms[ $i ] = intval( $terms[ $i ] );
				}
			}elseif( is_array( $terms ) ){
				//yolo(?)
			}else {
				continue;
			}

			$updated = wp_set_object_terms( $post_id, $terms, $data[ 'taxonomy'] );
			
		}


	}

	return true;
}
