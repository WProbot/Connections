<?php

namespace Connections_Directory\Content_Blocks\Entry\Related;

use Connections_Directory\Content_Blocks\Entry\Related;

/**
 * Class Title
 *
 * @package Connections_Directory\Content_Blocks\Entry
 */
class Title extends Related {

	/**
	 * @since 9.8
	 * @var string
	 */
	const ID = 'entry-related-title';

	/**
	 * @since 9.7
	 * @var array
	 */
	private $properties = array(
		'relation' => 'title',
	);

	/**
	 * Related constructor.
	 *
	 * @since 9.8
	 *
	 * @param $id
	 */
	public function __construct( $id ) {

		$atts = array(
			'context'             => 'single',
			'name'                => __( 'Related Entries by Title', 'connections' ),
			'permission_callback' => '__return_true',
			'heading'             => __( 'Related by Title', 'connections' ),
		);

		parent::__construct( $id, $atts );

		$this->setProperties( $this->properties );
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 9.8
	 */
	private function hooks() {

		// Add the title to the Content Block heading.
		add_filter(
			'Connections_Directory/Entry/Related/Query_Parameters',
			function( $queryParameters ) {

				if ( is_array( $queryParameters ) && array_key_exists( 'title', $queryParameters ) ) {

					$this->set(
						'heading',
						sprintf( __( 'Related by Title - %s', 'connections' ), $queryParameters['title'] )
					);
				}

				return $queryParameters;
			}
		);
	}

}
