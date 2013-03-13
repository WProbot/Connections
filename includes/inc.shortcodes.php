<?php
/**
 * Template tag to call the entry list. All options can be passed as an
 * associative array. The options are identical to those available to the
 * shortcode.
 *
 * EXAMPLE:   connectionsEntryList( array('id' => 325) );
 *
 * @access public
 * @since unknown
 * @version 1.0
 * @param array $atts
 * @return string
 */
function connectionsEntryList($atts) {
	echo connectionsList($atts);
}

/**
 * @access public
 * @since 0.7.3
 * @version 1.0
 * @uses get_query_var()
 * @param array $atts
 * @param string $content [optional]
 * @return string
 */
function connectionsView( $atts , $content = NULL ) {
	global $connections;

	/*$getAllowPublic = $connections->options->getAllowPublic();
	var_dump($getAllowPublic);
	$getAllowPublicOverride = $connections->options->getAllowPublicOverride();
	var_dump($getAllowPublicOverride);
	$getAllowPrivateOverride = $connections->options->getAllowPrivateOverride();
	var_dump($getAllowPrivateOverride);*/

	/*
	 * Only show this message under the following condition:
	 * - ( The user is not logged in AND the 'Login Required' is checked ) AND ( neither of the shortcode visibility overrides are enabled ).
	 */
	if ( ( ! is_user_logged_in() && ! $connections->options->getAllowPublic() ) && ! ( $connections->options->getAllowPublicOverride() || $connections->options->getAllowPrivateOverride() ) ) {
		$message = $connections->settings->get( 'connections', 'connections_login', 'message' );

		// Format and texturize the message.
		$message = wptexturize( wpautop( $message ) );

		// Make any links and such clickable.
		$message = make_clickable( $message );

		// Apply the shortcodes.
		$message = do_shortcode( $message );

		return $message;
	}

	switch ( get_query_var('cn-view') ) {

		case 'landing':

			return '<p>' . __( 'Future home of the landing pages, such a list of categories.', 'connections' ) . '</p>';

			break;

		// Show the standard result list.
		case 'list':

			return connectionsList( $atts, $content );

			break;

		// Show the "View All" result list using the "Names" template.
		case 'all':

			$atts['template'] = 'names';

			return connectionsList( $atts, $content );

			break;

		// Show the entry detail using a template based on the entry type.
		case 'detail':

			// Ensure an array is passed the the cnRetrieve::entries method.
			if ( ! is_array( $atts ) ) $atts = (array) $atts;

			$results = $connections->retrieve->entries( $atts );
			//var_dump($results);

			$atts['list_type'] = $connections->settings->get( 'connections', 'connections_display_single', 'template' ) ? $results[0]->entry_type : NULL;

			// Disable the output of the following because they do no make sense to display for a single entry.
			$atts['show_alphaindex']   = FALSE;
			$atts['repeat_alphaindex'] = FALSE;
			$atts['show_alphahead']    = FALSE;

			return connectionsList( $atts, $content );

			break;

		// Show the standard result list.
		default:

			return connectionsList( $atts, $content );

			break;
	}
}

add_shortcode('connections', 'connectionsView');

/**
 * Register the [connections] shortcode
 *
 * Filters:
 * 		cn_list_template_init			=> Change the list type [affects the default loaded template] or template to be loaded and intialized.
 * 										   The shortcode atts are passed, However the associative array will be limited to list_type and template so only these values can / should be altered.
 * 		cn_list_atts_permitted			=> The permitted shortcode attributes validated using the WordPress function shortcode_atts().
 * 										   The permitted shortcode associative array is passed. Return associative array.
 * 		cn_list_atts					=> Alter the shortcode attributes before validation via the WordPress function shortcode_atts().
 * 										   The shortcode atts are passed. Return associative array.
 * 		cn_list_retrieve_atts			=> Alter the query attributes to be used.
 * 										   The shortcode atts are passed. however the retrieve method will filter and use only the valid atts. Return associative array.
 * 		cn_list_results					=> Filter the returned results before being processed for display. Return indexed array of entry objects.
 * 		cn_list_before					=> Can be used to add content before the output of the list.
 * 										   The entry list results are passed. Return string.
 * 		cn_list_after					=> Can be used to add content after the output of the list.
 * 										   The entry list results are passed. Return string.
 * 		cn_list_entry_before			=> Can be used to add content before the output of the entry.
 * 										   The entry data is passed. Return string.
 * 		cn_list_entry_after				=> Can be used to add content after the output of the entry.
 * 										   The entry data is passed. Return string.
 * 		cn_list_no_result_message		=> Change the 'no results message'.
 * 		cn_list_index					=> Can be used to modify the index before the output of the list.
 * 										   The entry list results are passed. Return string.
 *
 * @access private
 * @since unknown
 * @param array $atts
 * @param string $content [optional]
 * @return string
 */
add_shortcode('connections_list', 'connectionsView'); /** @deprecated since version 0.7.0.4 */

function connectionsList($atts, $content = NULL) {
	global $wpdb, $wp_filter, $current_user, $connections;

	$out = '';
	$form = new cnFormObjects();
	$convert = new cnFormatting();
	$format =& $convert;
	$filterRegistry = array();

	// $template =& $connections->template;

	$previousLetter = '';
	$alternate = '';

	/*
	 * Parse the user supplied shortcode atts for the values only required to load the template.
	 * This will permit templates to apply a filter to alter the permitted shortcode atts.
	 */
	$preLoadAtts = $atts;

	$preLoadAtts = apply_filters( 'cn_list_template_init', $preLoadAtts );

	$preLoadAtts = shortcode_atts( array(
			'list_type'     => NULL,
			'template'      => NULL, /** @since version 0.7.1.0 */
			'template_name' => NULL /** @deprecated since version 0.7.0.4 */
		), $preLoadAtts );


	if ( ! empty( $preLoadAtts['list_type'] ) ) {

		$permittedListTypes = array('individual', 'organization', 'family', 'connection_group');

		// Convert to array. Trim the space characters if present.
		$preLoadAtts['list_type'] = explode( ',' , str_replace(' ', '', $preLoadAtts['list_type']) );

		// Set the template type to the first in the entry type from the supplied if multiple list types are provided.
		if ( (bool) array_intersect( (array) $preLoadAtts['list_type'], $permittedListTypes ) ) {

			$templateType = $preLoadAtts['list_type'][0];

			// Change the list type to family from connection_group to maintain compatibility with versions 0.7.0.4 and earlier.
			if ( $templateType == 'connection_group' ) $templateType = 'family';
		}

	} else {
		// If no list type was specified, set the default ALL template.
		$templateType = 'all';
	}

	/*
	 * If the legacy shortcode option template is being used, set the template shortcode option to its value.
	 * It's been over two years, so, remove the template_name option in the release after 0.7.3.
	 */
	if ( ! empty( $preLoadAtts['template_name'] ) ) {
		$preLoadAtts['template'] = $preLoadAtts['template_name'];
		$preLoadAtts['template_name'] = NULL;
	}

	/*
	 * If a list type was specified in the shortcode, load the template based on that type.
	 * However, if a specific template was specifed, that should preempt the template to be loaded based on the list type if it was specified..
	 */
	if ( ! empty( $preLoadAtts['template'] ) ) {
		$template = cnTemplateFactory::getTemplate( $preLoadAtts['template'] );
	} else {
		$templateSlug = $connections->options->getActiveTemplate( $templateType );
		$template = cnTemplateFactory::getTemplate( $templateSlug );
	}
	//$out .= '<pre style="display: none;">' . print_r($template , TRUE) . '</pre>';

	// If no template was found, exit return an error message.
	if ( $template == FALSE )
		return '<p style="color:red; font-weight:bold; text-align:center;">' . sprintf( __( 'ERROR: Template %1$s not found.', 'connections' ), $preLoadAtts['template_name'] . $preLoadAtts['template'] ). "</p>";

	do_action( 'cn_register_template_actions' );
	do_action( 'cn_action_include_once-' . $template->getSlug() );
	do_action( 'cn_action_js-' . $template->getSlug() );

	/*
	 * Now that the template has been loaded, Validate the user supplied shortcode atts.
	 */
	$permittedAtts = array(
		'id'                    => NULL,
		'slug'                  => NULL,
		'category'              => NULL,
		'category_in'           => NULL,
		'exclude_category'      => NULL,
		'category_name'         => NULL,
		'category_slug'         => NULL,
		'wp_current_category'   => 'false',
		'allow_public_override' => 'false',
		'private_override'      => 'false',
		'show_alphaindex'       => $connections->settings->get( 'connections', 'connections_display_results', 'index' ),
		'repeat_alphaindex'     => $connections->settings->get( 'connections', 'connections_display_results', 'index_repeat' ),
		'show_alphahead'        => $connections->settings->get( 'connections', 'connections_display_results', 'show_current_character' ),
		'list_type'             => NULL,
		'order_by'              => NULL,
		'limit'                 => NULL,
		'offset'                => NULL,
		'family_name'           => NULL,
		'last_name'             => NULL,
		'title'                 => NULL,
		'organization'          => NULL,
		'department'            => NULL,
		'city'                  => NULL,
		'state'                 => NULL,
		'zip_code'              => NULL,
		'country'               => NULL,
		'near_addr'             => NULL,
		'latitude'              => NULL,
		'longitude'             => NULL,
		'radius'                => 10,
		'unit'                  => 'mi',
		'template'              => NULL, /** @since version 0.7.1.0 */
		'template_name'         => NULL /** @deprecated since version 0.7.0.4 */,
		'width'                 => NULL
	);

	$permittedAtts = apply_filters( 'cn_list_atts_permitted' , $permittedAtts );
	$permittedAtts = apply_filters( 'cn_list_atts_permitted-' . $template->getSlug() , $permittedAtts );

	$atts = shortcode_atts( $permittedAtts , $atts ) ;
	//$out .= print_r($atts, TRUE);
	//$out .= var_dump($atts);

	$atts = apply_filters( 'cn_list_atts' , $atts );
	$atts = apply_filters( 'cn_list_atts-' . $template->getSlug() , $atts );
	//$filterRegistry[] = 'cn_list_atts-' . $template->slug;

	/*
	 * Convert some of the $atts values in the array to boolean.
	 */
	$convert->toBoolean( $atts['allow_public_override'] );
	$convert->toBoolean( $atts['private_override'] );
	$convert->toBoolean( $atts['show_alphaindex'] );
	$convert->toBoolean( $atts['repeat_alphaindex'] );
	$convert->toBoolean( $atts['show_alphahead'] );
	$convert->toBoolean( $atts['wp_current_category'] );
	// $out .= var_dump($atts);

	/*
	 * The WP post editor entity encodes the post text we have to decode it
	 * so a match can be made when the query is run.
	 */
	$atts['family_name'] = html_entity_decode ($atts['family_name'] );
	$atts['last_name'] = html_entity_decode( $atts['last_name'] );
	$atts['title'] = html_entity_decode( $atts['title'] );
	$atts['organization'] = html_entity_decode( $atts['organization'] );
	$atts['department'] = html_entity_decode( $atts['department'] );
	$atts['city'] = html_entity_decode( $atts['city'] );
	$atts['state'] = html_entity_decode( $atts['state'] );
	$atts['zip_code'] = html_entity_decode( $atts['zip_code'] );
	$atts['country'] = html_entity_decode( $atts['country'] );
	$atts['category_name'] = html_entity_decode( $atts['category_name'] );

	$atts = apply_filters('cn_list_retrieve_atts' , $atts );
	$atts = apply_filters('cn_list_retrieve_atts-' . $template->getSlug() , $atts );

	$results = $connections->retrieve->entries( $atts );
	//$out .= print_r($connections->lastQuery , TRUE);
	//$out .= print_r($results , TRUE);

	if ( ! empty( $results ) ) $results = apply_filters( 'cn_list_results', $results );
	if ( ! empty( $results ) ) $results = apply_filters( 'cn_list_results-' . $template->getSlug() , $results );
	if ( ! empty( $results ) ) $filterRegistry[] = 'cn_list_results-' . $template->getSlug();

	// Prints the template's CSS file.
	ob_start();
		do_action( 'cn_action_css-' . $template->getSlug() );
		$out .= ob_get_contents();
	ob_end_clean();

	// The return to top anchor
	$out .= '<div id="cn-top" style="position: absolute; top: 0; right: 0;"></div>';

	$out .= '<div class="cn-list" id="cn-list" data-connections-version="' .
		$connections->options->getVersion() . '-' .
		$connections->options->getDBVersion() . '"' .
		( ( empty($atts['width']) ) ? '' : ' style="width: ' . $atts['width'] . 'px;"' ) . '>' . "\n";

		$out .= "\n" . '<div class="cn-template cn-' . $template->getSlug() . '" id="cn-' . $template->getSlug() . '" data-template-version="' . $template->getVersion() . '">' . "\n";

		$out .= '<ul id="cn-action-list">';

			if ( $connections->settings->get( 'connections', 'connections_display_list_actions', 'view_all' ) && get_query_var( 'cn-view' ) !== 'all' )
				$out .= '<li class="cn-action-list-item">' . $connections->url->permalink( array( 'type' => 'all', 'text' => __( 'View All', 'connections' ), 'rel' => 'canonical', 'return' => TRUE ) ) . '</li>';

		$out .= '</ul>';

			$out .= "\n" . '<div class="cn-list-head cn-clear" id="cn-list-head">' . "\n";

				ob_start();
					do_action( 'cn_action_list_before' , $results );
					do_action( 'cn_action_list_before-' . $template->getSlug() , $results );
					$filterRegistry[] = 'cn_action_list_before-' . $template->getSlug();

					do_action( 'cn_action_list_both' , $results  );
					do_action( 'cn_action_list_both-' . $template->getSlug() , $results );
					$filterRegistry[] = 'cn_action_list_both-' . $template->getSlug();

					$out .= ob_get_contents();
				ob_end_clean();

				$out .= apply_filters( 'cn_list_before' , '' , $results );
				$out .= apply_filters( 'cn_list_before-' . $template->getSlug() , '' , $results );
				$filterRegistry[] = 'cn_list_before-' . $template->getSlug();

				/*
				 * The alpha index is only displayed if set set to true and not set to repeat using the shortcode attributes.
				 * If alpha index is set to repeat, that is handled separately.
				 */
				if ( $atts['show_alphaindex'] && ! $atts['repeat_alphaindex'] ) {
					$index = "\n" . '<div class="cn-alphaindex">' . $form->buildAlphaIndex(). '</div>' . "\n";
					$index = apply_filters( 'cn_list_index' , $index , $results );
					$index = apply_filters( 'cn_list_index-' . $template->getSlug() , $index , $results );
					$filterRegistry[] = 'cn_list_index-' . $template->getSlug();

					$out .= $index;
				}

			$out .= "\n" . '</div>' . "\n";

			$out .= '<div class="connections-list cn-clear" id="cn-list-body">' . "\n";

			// If there are no results no need to proceed and output message.
			if ( empty( $results ) ) {
				$noResultMessage = apply_filters( 'cn_list_no_result_message' , __('No results.', 'connections') );
				$noResultMessage = apply_filters( 'cn_list_no_result_message-' . $template->getSlug() , __('No results.', 'connections') );
				$filterRegistry[] = 'cn_list_no_result_message-' . $template->getSlug();

				$out .=  "\n" . '<p class="cn-list-no-results">' . $noResultMessage . '</p>' . "\n";
			} else {
				/*
				 * When an entry is assigned multiple categories and the RANDOM order_by shortcode attribute
				 * is used, this will cause the entry to show once for every category it is assigned.
				 *
				 * The same issue occurs when an entry has been assigned multiple address and each address
				 * falls within the geo bounds when performing a geo-limiting query.
				 */
				$skipEntry = array();

				foreach ( (array) $results as $row ) {

					$entry = new cnvCard( $row );
					$vCard =& $entry;
					$repeatIndex = '';
					$setAnchor = '';

					// @TODO --> Fix this somehow in the query, see comment above for $skipEntry.
					if ( in_array( $entry->getId() , $skipEntry ) ) continue;
					$skipEntry[] = $entry->getId();

					/*
					 * Checks the first letter of the last name to see if it is the next
					 * letter in the alpha array and sets the anchor.
					 *
					 * If the alpha index is set to repeat it will append to the anchor.
					 *
					 * If the alpha head set to true it will append the alpha head to the anchor.
					 */
					$currentLetter = strtoupper( mb_substr( $entry->getSortColumn(), 0, 1 ) );


					if ( $currentLetter != $previousLetter ) {

						$out .= "\n" . '<div class="cn-list-section-head cn-clear" id="' . $currentLetter . '">' . "\n";

						if ( $atts['show_alphaindex'] && $atts['repeat_alphaindex'] ) {

							$repeatIndex = "\n" . '<div class="cn-alphaindex">' . $form->buildAlphaIndex() . '</div>' . "\n";
							$repeatIndex = apply_filters( 'cn_list_index' , $repeatIndex , $results );
							$repeatIndex = apply_filters( 'cn_list_index-' . $template->getSlug() , $repeatIndex , $results );
							$filterRegistry[] = 'cn_list_index-' . $template->getSlug();
						}

						if ( $atts['show_alphahead'] ) $setAnchor .= "\n" . '<h4 class="cn-alphahead">' . $currentLetter . '</h4>' . "\n";

						/*
						 * The anchor and/or the alpha head is displayed if set to true using the shortcode attributes.
						 */
						if ( $atts['show_alphaindex'] || $atts['show_alphahead'] ) $out .= $repeatIndex . $setAnchor;

						$out .= "\n" . '</div>' . "\n";

						$previousLetter = $currentLetter;
					}


					$alternate == '' ? $alternate = '-alternate' : $alternate = '';

					$out .= "\n" . '<div class="cn-list-row' . $alternate . ' vcard ' . $entry->getEntryType() . ' ' . $entry->getCategoryClass(TRUE) . '" id="' . $entry->getSlug() . '">' . "\n";

						$out .= apply_filters( 'cn_list_entry_before' , '' , $entry );
						$out .= apply_filters( 'cn_list_entry_before-' . $template->getSlug() , '' , $entry );
						$filterRegistry[] = 'cn_list_entry_before-' . $template->getSlug();

						ob_start();
							do_action( 'cn_action_card-' . $template->getSlug(), $entry, $content, $template, $atts, $connections, $vCard );
							$out .= ob_get_contents();
					    ob_end_clean();

						$out .= apply_filters( 'cn_list_entry_after' , '' , $entry );
						$out .= apply_filters( 'cn_list_entry_after-' . $template->getSlug() , '' , $entry );
						$filterRegistry[] = 'cn_list_entry_after-' . $template->getSlug();

					$out .= "\n" . '</div>' . "\n";

				}
			}

			$out .= "\n" . '</div>' . "\n";

			$out .= "\n" . '<div class="cn-clear" id="cn-list-foot">' . "\n";

				$out .= apply_filters( 'cn_list_after' , '' , $results );
				$out .= apply_filters( 'cn_list_after-' . $template->getSlug() , '' , $results );
				$filterRegistry[] = 'cn_list_after-' . $template->getSlug();

				ob_start();
					do_action( 'cn_action_list_after' , $results );
					do_action( 'cn_action_list_after-' . $template->getSlug() , $results );
					$filterRegistry[] = 'cn_action_list_after-' . $template->getSlug();

					do_action( 'cn_action_list_both' , $results  );
					do_action( 'cn_action_list_both-' . $template->getSlug() , $results );
					$filterRegistry[] = 'cn_action_list_both-' . $template->getSlug();

					$out .= ob_get_contents();
				ob_end_clean();

			$out .= "\n" . '</div>' . "\n";

		$out .= "\n" . '</div>' . "\n";

	$out .= "\n" . '</div>' . "\n";

	// $template->reset();

	/*
	 * Remove any filters a template may have added
	 * so it is not run again if more than one template
	 * is in use on the same page.
	 */

	foreach ( $filterRegistry as $filter ) {
		if ( isset( $wp_filter[ $filter ] ) ) unset( $wp_filter[ $filter ] );
	}

	if ( $connections->settings->get( 'connections', 'connections_compatibility', 'strip_rnt' ) ) {
		$search = array( "\r\n", "\r", "\n", "\t" );
		$replace = array( '', '', '', '' );
		$out = str_replace( $search , $replace , $out );
	}

	return $out;
}

/**
 * Template tag to call the upcoming list. All options can be passed as an
 * associative array. The options are identical to those available to the
 * shortcode.
 *
 * EXAMPLE:   connectionsUpcomingList(array('days' => 30));
 *
 * @param array $atts
 * @return string
 */
function connectionsUpcomingList( $atts ) {
	echo _upcoming_list( $atts );
}

add_shortcode( 'upcoming_list', '_upcoming_list' );

function _upcoming_list( $atts, $content = NULL ) {
    global $connections, $wpdb;

	// $template =& $connections->template;
	$convert = new cnFormatting();
	$out = '';
	$alternate = '';

	$atts = shortcode_atts( array(
			'list_type'        => 'birthday',
			'days'             => '30',
			'include_today'    => TRUE,
			'private_override' => FALSE,
			'date_format'      => 'F jS',
			'show_lastname'    => FALSE,
			'show_title'       => TRUE,
			'list_title'       => NULL,
			'template'         => NULL
		), $atts ) ;

	/*
	 * Convert some of the $atts values in the array to boolean.
	 */
	$convert->toBoolean( $atts['include_today'] );
	$convert->toBoolean( $atts['private_override'] );
	$convert->toBoolean( $atts['show_lastname'] );
	$convert->toBoolean( $atts['repeat_alphaindex'] );
	$convert->toBoolean( $atts['show_title'] );

	/*
	 * If a list type was specified in the shortcode, load the template based on that type.
	 * However, if a specific template was specifed, that should preempt the template to be loaded based on the list type if it was specified..
	 */
	if ( ! empty( $atts['template'] ) ) {
		$template = cnTemplateFactory::getTemplate( $atts['template'] );
	} else {
		$templateSlug = $connections->options->getActiveTemplate( $atts['list_type'] );
		$template = cnTemplateFactory::getTemplate( $templateSlug, $atts['list_type'] );
	}

	// No template found retuen error message.
	if ( $template == FALSE )
		return '<p style="color:red; font-weight:bold; text-align:center;>' . sprintf( __( 'ERROR: Template %1$s not found.', 'connections' ), $atts['template'] ) . "</p>";

	do_action( 'cn_register_template_actions' );
	do_action( 'cn_action_include_once-' . $template->getSlug() );
	do_action( 'cn_action_js-' . $template->getSlug() );

	/*
	 * Set the query vars and run query.
	 */

	// Show only public or private [if permitted] entries.
	if ( is_user_logged_in() || $atts['private_override'] != FALSE ) {
		$visibilityfilter = " AND (visibility='private' OR visibility='public') AND (" . $atts['list_type'] . " != '')";
	} else {
		$visibilityfilter = " AND (visibility='public') AND (`" . $atts['list_type'] . "` != '')";
	}

	// Get the current date from WP which should have the current time zone offset.
	$wpCurrentDate = date( 'Y-m-d', $connections->options->wpCurrentTime );

	// Whether or not to include the event occurring today or not.
	( $atts['include_today'] ) ? $includeToday = '<=' : $includeToday = '<';

	$newSQL = "SELECT * FROM ".CN_ENTRY_TABLE." WHERE"
		. "  (YEAR(DATE_ADD('$wpCurrentDate', INTERVAL ".$atts['days']." DAY))"
        . " - YEAR(DATE_ADD(FROM_UNIXTIME(`".$atts['list_type']."`), INTERVAL ".$connections->options->sqlTimeOffset." SECOND)) )"
        . " - ( MID(DATE_ADD('$wpCurrentDate', INTERVAL ".$atts['days']." DAY),5,6)"
        . " < MID(DATE_ADD(FROM_UNIXTIME(`".$atts['list_type']."`), INTERVAL ".$connections->options->sqlTimeOffset." SECOND),5,6) )"
        . " > ( YEAR('$wpCurrentDate')"
        . " - YEAR(DATE_ADD(FROM_UNIXTIME(`".$atts['list_type']."`), INTERVAL ".$connections->options->sqlTimeOffset." SECOND)) )"
        . " - ( MID('$wpCurrentDate',5,6)"
        . " ".$includeToday." MID(DATE_ADD(FROM_UNIXTIME(`".$atts['list_type']."`), INTERVAL ".$connections->options->sqlTimeOffset." SECOND),5,6) )"
		. $visibilityfilter;
	//$out .= print_r($newSQL , TRUE);

	$results = $wpdb->get_results( $newSQL );
	//$out .= print_r($results , TRUE);

	// If there are no results no need to proceed and output message.
	if ( empty( $results ) ) {

		$noResultMessage = __( 'No results.', 'connections' );
		$noResultMessage = apply_filters( 'cn_upcoming_no_result_message', $noResultMessage );
		$out .= '<p class="cn-upcoming-no-results">' . $noResultMessage . '</p>';

	} else {
		/*The SQL returns an array sorted by the birthday and/or anniversary date. However the year end wrap needs to be accounted for.
		Otherwise earlier months of the year show before the later months in the year. Example Jan before Dec. The desired output is to show
		Dec then Jan dates.  This function checks to see if the month is a month earlier than the current month. If it is the year is changed to the following year rather than the current.
		After a new list is built, it is resorted based on the date.*/
		foreach ( $results as $key => $row ) {

			if ( gmmktime(23, 59, 59, gmdate('m', $row->$atts['list_type']), gmdate('d', $row->$atts['list_type']), gmdate('Y', $connections->options->wpCurrentTime) ) < $connections->options->wpCurrentTime ) {
				$dateSort[] = $row->$atts['list_type'] = gmmktime(0, 0, 0, gmdate('m', $row->$atts['list_type']), gmdate('d', $row->$atts['list_type']), gmdate('Y', $connections->options->wpCurrentTime) + 1 );
			} else {
				$dateSort[] = $row->$atts['list_type'] = gmmktime(0, 0, 0, gmdate('m', $row->$atts['list_type']), gmdate('d', $row->$atts['list_type']), gmdate('Y', $connections->options->wpCurrentTime) );
			}
		}

		array_multisort( $dateSort, SORT_ASC, $results );

		if ( empty( $atts['list_title'] ) ) {

			switch ($atts['list_type']) {

				case 'birthday':
					if ( $atts['days'] >= 1 ) {
						$list_title = 'Upcoming Birthdays the next ' . $atts['days'] . ' days';
					} else {
						$list_title = 'Today\'s Birthdays';
					}
				break;

				case 'anniversary':
					if ( $atts['days'] >= 1 ) {
						$list_title = 'Upcoming Anniversaries the next ' . $atts['days'] . ' days';
					} else {
						$list_title = 'Today\'s Anniversaries';
					}
				break;

			}

		} else {
			$list_title = $atts['list_title'];
		}


		// Prints the template's CSS file.
		if ( method_exists( $template, 'printCSS' ) ) $out .= $template->printCSS();

		$out .= '<div class="connections-list cn-upcoming cn-' . $atts['list_type'] . '" id="cn-list" data-connections-version="' . $connections->options->getVersion() . '-' . $connections->options->getDBVersion() . '">' . "\n";

			$out .= "\n" . '<div class="cn-template cn-' . $template->getSlug() . '" id="cn-' . $template->getSlug() . '">' . "\n";

				$out .= "\n" . '<div class="cn-clear" id="cn-list-head">' . "\n";
					if ( $atts['show_title'] ) $out .= '<div class="cn-upcoming-title">' . $list_title  . '</div>';
				$out .= "\n" . '</div>' . "\n";

				$out .= '<div class="cn-clear" id="cn-list-body">' . "\n";

					foreach ( $results as $row ) {

						$entry = new cnvCard($row);
						$vCard =& $entry;

						$entry->name = '';

						$alternate == '' ? $alternate = '-alternate' : $alternate = '';

						/*
						 * Whether or not to show the last name.
						 * Setting $entry->name is for compatibility to versions prior to 0.7.1.6
						 */
						( ! $atts['show_lastname'] ) ? $entry->name = $entry->getFirstName() : $entry->name = $entry->getFullFirstLastName();
						if ( ! $atts['show_lastname'] ) $entry->setLastName('');

						$out .= '<div class="cn-upcoming-row' . $alternate . ' vcard ' . '">' . "\n";
							ob_start();
							do_action( 'cn_action_card-' . $template->getSlug(), $entry, $content, $template, $atts, $connections, $vCard );
						    $out .= ob_get_contents();
						    ob_end_clean();
						$out .= '</div>' . "\n";

					}

				$out .= "\n" . '</div>' . "\n";

				$out .= "\n" . '<div class="cn-clear" id="cn-list-foot">' . "\n";
				$out .= "\n" . '</div>' . "\n";

			$out .= "\n" . '</div>' . "\n";

		$out .= "\n" . '</div>' . "\n";

	}

	if ( $connections->settings->get( 'connections', 'connections_compatibility', 'strip_rnt' ) ) {
		$search = array( "\r\n", "\r", "\n", "\t" );
		$replace = array( '', '', '', '' );
		$out = str_replace( $search , $replace , $out );
	}

	return $out;
}

add_shortcode( 'connections_vcard', '_connections_vcard' );

function _connections_vcard( $atts , $content = NULL ) {

	$atts = shortcode_atts( array(
			'id' => NULL
		), $atts ) ;

	if ( empty( $atts['id'] ) || ! is_numeric( $atts['id'] ) || empty( $content ) ) return '';

	$qTipContent = '<span class="cn-qtip-content-vcard" style="display: none">' . _connections_list( array( 'id' => $atts['id'] , 'template' => 'qtip-vcard' ) ) . '</span>';

	return '<span class="cn-qtip-vcard">' . $content . $qTipContent . '</span>';
}

add_shortcode( 'connections_qtip', '_connections_qtip' );

function _connections_qtip( $atts , $content = NULL )
{
	$atts = shortcode_atts( array(
			'id' => NULL
		), $atts ) ;

	if ( empty( $atts['id'] ) || ! is_numeric ($atts['id'] ) || empty( $content ) ) return '';

	$qTipContent = '<span class="cn-qtip-content-card" style="display: none">' . _connections_list( array( 'id' => $atts['id'] , 'template' => 'qtip-card' ) ) . '</span>';

	return '<span class="cn-qtip-card">' . $content . $qTipContent . '</span>';
}