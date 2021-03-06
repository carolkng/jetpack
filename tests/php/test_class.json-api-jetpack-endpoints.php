<?php

class WP_Test_Jetpack_Json_Api_endpoints extends WP_UnitTestCase {

	/**
	 * Inserts globals needed to initialize the endpoint.
	 */
	private function set_globals() {

		$_SERVER['REQUEST_METHOD'] = 'Get';
		$_SERVER['HTTP_HOST']      = '127.0.0.1';
		$_SERVER['REQUEST_URI']    = '/';

	}

	public function setUp() {
		if ( ! defined( 'WPCOM_JSON_API__BASE' ) ) {
			define( 'WPCOM_JSON_API__BASE', 'public-api.wordpress.com/rest/v1' );
		}

		parent::setUp();

		if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			// Loading the API breaks in 5.2.x due to `const` declarations, and
			// this will still happen even though all tests are skipped (why?)
			return;
		}

		$this->set_globals();

		// Force direct method. Running the upgrade via PHPUnit can't detect the correct filesystem method.
		add_filter( 'filesystem_method', array( $this,  'filesystem_method_direct' ) );

		require_once dirname( __FILE__ ) . '/../../modules/module-extras.php';
		require_once dirname( __FILE__ ) . '/../../class.json-api.php';
		require_once dirname( __FILE__ ) . '/../../class.json-api-endpoints.php';
	}

	/**
	 * @author lezama
	 * @covers Jetpack_JSON_API_Plugins_Modify_Endpoint
	 * @group external-http
	 * @requires PHP 5.3.2
	 */
	public function test_Jetpack_JSON_API_Plugins_Modify_Endpoint() {
		$endpoint = new Jetpack_JSON_API_Plugins_Modify_Endpoint( array(
			'description'     => 'Update a Plugin on your Jetpack Site',
			'group'           => 'plugins',
			'stat'            => 'plugins:1:update',
			'method'          => 'GET',
			'path'            => '/sites/%s/plugins/%s/update/',
			'path_labels' => array(
				'$site'   => '(int|string) The site ID, The site domain',
				'$plugin' => '(string) The plugin file name',
			),
			'response_format' => Jetpack_JSON_API_Plugins_Endpoint::$_response_format,
			'example_request_data' => array(
				'headers' => array(
					'authorization' => 'Bearer YOUR_API_TOKEN'
				),
			),
			'example_request' => 'https://public-api.wordpress.com/rest/v1/sites/example.wordpress.org/plugins/hello/update'
		) );

		/**
		 * Changes the Accessibility of the protected upgrade_plugin method.
		 */
		$class = new ReflectionClass('Jetpack_JSON_API_Plugins_Modify_Endpoint');
		$update_plugin_method = $class->getMethod( 'update' );
		$update_plugin_method->setAccessible( true );

		$plugin_property = $class->getProperty( 'plugins' );
		$plugin_property->setAccessible( true );
		$plugin_property->setValue ( $endpoint , array( 'the/the.php' ) );

		$the_plugin_file = 'the/the.php';
		$the_real_folder = WP_PLUGIN_DIR . '/the';
		$the_real_file = WP_PLUGIN_DIR . '/' . $the_plugin_file;

		/*
		 * Create an oudated version of 'The' plugin
		 */

		// Check if 'The' plugin folder is already there.
		if ( ! file_exists( $the_real_folder ) ) {
			mkdir( $the_real_folder );
			$clean = true;
		}

		file_put_contents( $the_real_file,
			'<?php
			/*
			 * Plugin Name: The
			 * Version: 1.0
			 */'
		);

		// Invoke the upgrade_plugin method.
		$result = $update_plugin_method->invoke( $endpoint );

		$this->assertTrue( $result );

		if ( isset( $clean ) ) {
			$this->rmdir( $the_real_folder );
		}

	}

	public function create_get_category_endpoint() {
		// From json-endpoints/class.wpcom-json-api-get-taxonomy-endpoint.php :(
		return new WPCOM_JSON_API_Get_Taxonomy_Endpoint( array(
			'description' => 'Get information about a single category.',
			'group'       => 'taxonomy',
			'stat'        => 'categories:1',

			'method'      => 'GET',
			'path'        => '/sites/%s/categories/slug:%s',
			'path_labels' => array(
				'$site'     => '(int|string) Site ID or domain',
				'$category' => '(string) The category slug'
			),

			'example_request'  => 'https://public-api.wordpress.com/rest/v1/sites/en.blog.wordpress.com/categories/slug:community'
		) );
	}

	/**
	 * @author nylen
	 * @covers WPCOM_JSON_API_Get_Taxonomy_Endpoint
	 * @group json-api
	 * @requires PHP 5.3
	 */
	public function test_get_term_feed_url_pretty_permalinks() {
		global $blog_id;

		$this->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );
		// Reset taxonomy URL structure after changing permalink structure
		create_initial_taxonomies();

		$category = wp_insert_term( 'test_category', 'category' );

		$endpoint = $this->create_get_category_endpoint();
		// Initialize some missing stuff for the API
		WPCOM_JSON_API::init()->token_details = array( 'blog_id' => $blog_id );

		$response = $endpoint->callback(
			sprintf( '/sites/%d/categories/slug:test_category', $blog_id ),
			$blog_id,
			'test_category'
		);

		$this->assertStringEndsWith(
			'/category/test_category/feed/',
			$response->feed_url
		);
	}

	/**
	 * @author nylen
	 * @covers WPCOM_JSON_API_Get_Taxonomy_Endpoint
	 * @group json-api
	 * @requires PHP 5.3
	 */
	public function test_get_term_feed_url_ugly_permalinks() {
		global $blog_id;

		$this->set_permalink_structure( '' );
		// Reset taxonomy URL structure after changing permalink structure
		create_initial_taxonomies();

		$category = wp_insert_term( 'test_category', 'category' );

		$endpoint = $this->create_get_category_endpoint();
		// Initialize some missing stuff for the API
		WPCOM_JSON_API::init()->token_details = array( 'blog_id' => $blog_id );

		$response = $endpoint->callback(
			sprintf( '/sites/%d/categories/slug:test_category', $blog_id ),
			$blog_id,
			'test_category'
		);

		$this->assertStringEndsWith(
			'/?feed=rss2&amp;cat=' . $category['term_id'],
			$response->feed_url
		);
	}

	/**
	 * @author tonykova
	 * @covers Jetpack_API_Plugins_Install_Endpoint
	 * @group external-http
	 * @requires PHP 5.3.2
	 */
	public function test_Jetpack_API_Plugins_Install_Endpoint() {
		$endpoint = new Jetpack_JSON_API_Plugins_Install_Endpoint( array(
			'stat'            => 'plugins:1:new',
			'method'          => 'POST',
			'path'            => '/sites/%s/plugins/new',
			'path_labels' => array(
				'$site'   => '(int|string) The site ID, The site domain',
			),
			'request_format' => array(
				'plugin'       => '(string) The plugin slug.'
			),
			'response_format' => Jetpack_JSON_API_Plugins_Endpoint::$_response_format,
			'example_request_data' => array(
				'headers' => array(
					'authorization' => 'Bearer YOUR_API_TOKEN'
				),
				'body' => array(
					'plugin' => 'buddypress'
				)
			),
			'example_request' => 'https://public-api.wordpress.com/rest/v1/sites/example.wordpress.org/plugins/new'
		) );

		$the_plugin_file = 'the/the.php';
		$the_real_folder = WP_PLUGIN_DIR . '/the';
		$the_real_file = WP_PLUGIN_DIR . '/' . $the_plugin_file;
		$the_plugin_slug = 'the';

		// Check if 'The' plugin folder is already there.
		if ( file_exists( $the_real_folder ) ) {
			$this->markTestSkipped( 'The plugin the test tries to install (the) is already installed. Skipping.' );
		}

		$class = new ReflectionClass('Jetpack_JSON_API_Plugins_Install_Endpoint');

		$plugins_property = $class->getProperty( 'plugins' );
		$plugins_property->setAccessible( true );
		$plugins_property->setValue ( $endpoint , array( $the_plugin_slug ) );

		$validate_plugins_method = $class->getMethod( 'validate_plugins' );
		$validate_plugins_method->setAccessible( true );
		$result = $validate_plugins_method->invoke( $endpoint );
		$this->assertTrue( $result );

		$install_plugin_method = $class->getMethod( 'install' );
		$install_plugin_method->setAccessible( true );

		$result = $install_plugin_method->invoke( $endpoint );

		$this->assertTrue( $result );
		$this->assertTrue( file_exists( $the_real_folder ) );

		// Clean up
		$this->rmdir( $the_real_folder );
	}

	function filesystem_method_direct( $method ) {

		return 'direct';

	}

	function rmdir( $dir ) {

		foreach ( scandir( $dir ) as $file ) {
			if ( is_dir( $file ) )
				continue;
			else unlink( "$dir/$file" );
		}
		rmdir( $dir );

	}

}
