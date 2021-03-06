<?php

namespace Connections_Directory\Content_Blocks\Entry;

use cnEntry;
use cnFormatting;
use cnSanitize;
use cnTemplatePart;
use cnTerm;
use Connections_Directory\Content_Block;

/**
 * Class Entry_Categories
 *
 * @package Connections_Directory\Content_Block
 */
class Categories extends Content_Block {

	/**
	 * @since 9.7
	 * @var string
	 */
	const ID = 'entry-categories';

	///**
	// * @since 9.7
	// * @var array
	// */
	//private $properties = array();

	/**
	 * Entry_Categories constructor.
	 *
	 * @param string $id
	 */
	public function __construct( $id ) {

		$atts = array(
			'name'                => __( 'Entry Categories', 'connections' ),
			'register_option'     => false,
			'permission_callback' => array( $this, 'permission' ),
			'heading'             => __( 'Categories', 'connections' ),
		);

		parent::__construct( $id, $atts );

		$this->setProperties( $this->defaults() );
	}

	/**
	 * @since 9.7
	 *
	 * @return array {
	 * Optional. An array of arguments.
	 *
	 *     @type string $container_tag    The HTML tag to be used for the container element.
	 *                                    Default: div
	 *     @type string $label_tag        The HTML tag to be used for the category label element.
	 *                                    Default: span
	 *     @type string $item_tag         The HTML tag to be used for the category element.
	 *                                    Default: span
	 *     @type string $type             The display type to be used to display the categories.
	 *                                    Accepts: block|list
	 *                                    Default: block
	 *     @type string $list             If the $type is list, which type?
	 *                                    Accepts: ordered|unordered
	 *                                    Default: unordered
	 *     @type string $label            The label to be displayed before the categories.
	 *                                    Default: Categories:
	 *     @type string $separator        The category separator used when separating categories when $type == list
	 *                                    Default: ', '
	 *     @type string $parent_separator The separator to be used when displaying the category's hierarchy.
	 *                                    Default: ' &raquo; '
	 *     @type bool   $link             Whether or not render the categories as permalinks.
	 *                                    Default: false
	 *     @type bool   $parents          Whether or not to display the category hierarchy.
	 *                                    Default: false
	 *     @type int    $child_of         Term ID to retrieve child terms of.
	 *                                    If multiple taxonomies are passed, $child_of is ignored.
	 *                                    Default: 0
	 * }
	 */
	private function defaults() {

		$defaults = array(
			'container_tag'    => 'div',
			'label_tag'        => 'span',
			'item_tag'         => 'span',
			'type'             => 'block',
			'list'             => 'unordered',
			'label'            => __( 'Categories:', 'connections' ) . ' ',
			'separator'        => ', ',
			'parent_separator' => ' &raquo; ',
			//'before'           => '',
			//'after'            => '',
			'link'             => FALSE,
			'parents'          => FALSE,
			'child_of'         => 0,
			//'return'           => FALSE,
		);

		return apply_filters(
			'Connections_Directory/Content_Block/Entry/Categories/Attributes',
			$defaults
		);
	}

	/**
	 * @since 9.7
	 *
	 * @param string $property
	 * @param mixed  $value
	 */
	public function set( $property, $value ) {

		if ( in_array( $property, array( 'link', 'parents' ) ) ) {

			cnFormatting::toBoolean( $value );
		}

		parent::set( $property, $value );
	}

	/**
	 * @since 9.7
	 *
	 * @return bool
	 */
	public function permission() {

		return true;
	}

	/**
	 * Displays the category list in a HTML list or custom format.
	 *
	 * NOTE: This is the Connections equivalent of @see get_the_category_list() in WordPress core ../wp-includes/category-template.php
	 *
	 * @since 9.7
	 */
	public function content() {

		global $wp_rewrite;

		$entry = $this->getObject();

		if ( ! $entry instanceof cnEntry ) {

			return;
		}

		$categories = $entry->getCategory( array( 'child_of' => $this->get( 'child_of' ) ) );
		$properties = cnSanitize::args( $this->getProperties(), $this->defaults() );
		$count      = count( $categories );
		$html       = '';
		$label      = '';
		$items      = array();

		if ( empty( $categories ) ) {

			return;
		}

		if ( 'list' == $this->get( 'type' ) ) {

			$this->set( 'item_tag', 'li' );
		}

		if ( 0 < strlen( $this->get( 'label' ) ) ) {

			$label = sprintf(
				'<%1$s class="cn_category_label">%2$s</%1$s> ',
				$this->get( 'label_tag' ),
				esc_html( $this->get( 'label' ) )
			);
		}

		$i = 1;

		foreach ( $categories as $category ) {

			$text = '';

			if ( $this->get( 'parents' ) ) {

				// If the term is a root parent, skip.
				if ( 0 !== $category->parent ) {

					$text .= cnTemplatePart::getCategoryParents(
						$category->parent,
						array(
							'link'       => $this->get( 'link' ),
							'separator'  => $this->get( 'parent_separator' ),
							'force_home' => $entry->directoryHome['force_home'],
							'home_id'    => $entry->directoryHome['page_id'],
						)
					);
				}
			}

			if ( $this->get( 'link' ) ) {

				$rel = is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ? 'rel="category tag"' : 'rel="category"';

				$url = cnTerm::permalink(
					$category,
					'category',
					array(
						'force_home' => $entry->directoryHome['force_home'],
						'home_id'    => $entry->directoryHome['page_id'],
					)
				);

				$text .= '<a href="' . $url . '" ' . $rel . '>' . esc_html( $category->name ) . '</a>';

			} else {

				$text .= esc_html( $category->name );
			}

			$items[] = apply_filters(
				'cn_entry_output_category_item',
				sprintf(
					'<%1$s class="cn-category-name cn_category cn-category-%2$d">%3$s%4$s</%1$s>', // The `cn_category` class is named with an underscore for backward compatibility.
					$this->get( 'item_tag' ),
					$category->term_id,
					$text,
					$count > $i && 'list' !== $this->get( 'type' ) ? esc_html( $this->get( 'separator' ) ) : ''
				),
				$category,
				$count,
				$i,
				$properties,
				$this
			);

			$i++; // Increment here so the correct value is passed to the filter.
		}

		/*
		 * Remove NULL, FALSE and empty strings (""), but leave values of 0 (zero).
		 * Filter our these in case someone hooks into the `cn_entry_output_category_item` filter and removes a category
		 * by returning an empty value.
		 */
		$items = array_filter( $items, 'strlen' );

		/**
		 * @since 8.6.12
		 */
		$items = apply_filters( 'cn_entry_output_category_items', $items );

		if ( 'list' == $this->get( 'type' ) ) {

			$html .= sprintf(
				'<%1$s class="cn-category-list">%2$s</%1$s>',
				'unordered' === $this->get( 'list' ) ? 'ul' : 'ol',
				implode( '', $items )
			);

		} else {

			$html .= implode( '', $items );
		}

		do_action(
			"Connections_Directory/Content_Block/Entry/{$this->shortName}/Before",
			$entry,
			$items
		);

		echo apply_filters(
			'cn_entry_output_category_container',
			sprintf(
				'<%1$s class="cn-categories">%2$s</%1$s>' . PHP_EOL,
				$this->get( 'container_tag' ),
				$label . $html
			),
			$properties
		);

		do_action(
			"Connections_Directory/Content_Block/Entry/{$this->shortName}/After",
			$entry,
			$items
		);
	}
}
