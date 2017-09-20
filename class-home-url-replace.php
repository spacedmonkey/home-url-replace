<?php
/**
 * Replace hardcoded references to home url with current domain
 *
 *
 * @package   Home_Url_Replace
 * @author    Jonathan Harris <jon@spacedmonkey.co.uk>
 * @license   GPL-2.0+
 * @link      http://www.spacedmonkey.com/
 * @copyright 2017 Spacedmonkey
 *
 * @wordpress-muplugin
 * Plugin Name:        Home Url Replace
 * Plugin URI:         https://www.github.com/spacedmonkey/home-url-replace
 * Description:        Replace hardcoded references to home url with current domain
 * Version:            1.0.0
 * Author:             Jonathan Harris
 * Author URI:         http://www.spacedmonkey.com/
 * Text Domain:        home-url-replace
 * License:            GPL-2.0+
 * License URI:        http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:        /languages
 * GitHub Plugin URI:  https://www.github.com/spacedmonkey/home-url-replace
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Home_Url_Replace
 */
class Home_Url_Replace {

	/**
	 * @var null
	 */
	private $home_url_db = null;
	/**
	 * @var null
	 */
	private $home_url = null;
	/**
	 * @var bool
	 */
	private $add_filters = true;
	/**
	 * @var array
	 */
	private $filters_frontend = [
		'the_content',
		'the_content_export',
		'the_content_feed',
		'the_excerpt_rss',
		'get_comment_author_link',
		'get_comment_excerpt',
		'get_comment_text',
		'get_the_excerpt',
		'get_the_guid',
		'term_description',
		'widget_text'
	];

	/**
	 *
	 */
	public function register() {

		$this->switch_blog();
		add_action( 'switch_blog', [ $this, 'switch_blog' ], 10, 2 );

	}

	/**
	 * Determines if we should add the filters or not
	 */
	public function set_add_filters() {
		$this->add_filters = ( $this->home_url != $this->home_url_db );
	}

	/**
	 * Checks if we should add the filters or not
	 *
	 * @return bool
	 */
	public function get_add_filters() {
		return $this->add_filters;
	}

	/**
	 * Returns formatted domain
	 *
	 * @param $url
	 *
	 * @return string
	 */
	private function get_domain( $url ) {
		return "//" . parse_url( $url, PHP_URL_HOST );
	}

	/**
	 * Adds all the necessary filters to FE and BE
	 */
	public function add_home_url_filters() {
		if ( $this->get_add_filters() ) {

			if ( ! is_admin() ) {

				foreach ( $this->filters_frontend as $filter ) {
					add_filter( $filter, [ $this, 'force_frontend_home_url' ], 9 );
				}

			} else {

				add_action( 'init', array( $this, 'term_description_filter' ) );
				add_filter( 'wp_insert_post_data', [ $this, 'force_backend_home_url' ], 99 );
				add_filter( 'wp_insert_attachment_data', [ $this, 'force_backend_home_url' ], 99 );

			}

		}
	}

	/**
	 * Add necessary filters to taxonomies
	 */
	public function term_description_filter() {

		// Get public taxonomies
		$taxonomies = get_taxonomies();

		/* Loop through the taxonomies, adding filter */
		foreach ( $taxonomies as $taxonomy ) {
			add_filter( 'pre_' . $taxonomy . '_description', [ $this, 'replace_live_home_url' ], 5 );
		}

	}

	/**
	 * Initialises class vars
	 */
	public function switch_blog() {
		$this->home_url    = $this->get_domain( home_url() );
		$this->home_url_db = $this->get_domain( get_option( 'home' ) );
		$this->set_add_filters();
		$this->add_home_url_filters();
	}

	/**
	 * Replaces the www live url for the current home url
	 *
	 * @param $text
	 *
	 * @return mixed
	 */
	public function force_frontend_home_url( $text ) {
		$text = str_replace( $this->home_url_db, $this->home_url, $text );

		return $text;
	}

	/**
	 * Replaces the current home url for the corresponding www live url
	 *
	 * @param $text
	 *
	 * @return mixed
	 */
	public function replace_live_home_url( $text ) {
		$text = str_replace( $this->home_url, $this->home_url_db, $text );

		return $text;
	}

	/**
	 * Loop through all the array items and replaces the current home url for the corresponding www live url
	 *
	 * @param array $value
	 *
	 * @return array
	 */
	public function force_backend_home_url( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'force_backend_home_url' ], $value );
		}

		return $this->replace_live_home_url( $value );
	}

}

add_action( 'plugins_loaded', function () {
	$home_url_class = new Home_Url_Replace;
	$home_url_class->register();
} );
