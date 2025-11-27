<?php
/**
 * The BlockLibraryServiceProvider class.
 *
 * @package ProfileBlocksLastFM
 */

namespace ProfileBlocksLastFM\BlockLibrary;

use ProfileBlocksLastFM\BlockLibrary\Blocks\Friends;
use ProfileBlocksLastFM\BlockLibrary\Blocks\TopCharts;
use ProfileBlocksLastFM\BlockLibrary\Blocks\RecentTracks;
use ProfileBlocksLastFM\BlockLibrary\Blocks\WeeklyCharts;
use ProfileBlocksLastFM\BlockLibrary\Blocks\DynamicTemplate;
use ProfileBlocksLastFM\BlockLibrary\Blocks\ItemImage;
use ProfileBlocksLastFM\BlockLibrary\Blocks\ItemName;
use ProfileBlocksLastFM\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use ProfileBlocksLastFM\Dependencies\League\Container\ServiceProvider\BootableServiceProviderInterface;

/**
 * The BlockLibraryServiceProvider class.
 */
class BlockLibraryServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {
	/**
	 * Get the services provided by the provider.
	 *
	 * @param string $id The service to check.
	 *
	 * @return array
	 */
	public function provides( string $id ): bool {
		$services = array();

		return in_array( $id, $services, true );
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register(): void {}

	/**
	 * Bootstrap any application services by hooking into WordPress with actions/filters.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );

		// Only register block patterns if the api_key and user settings are set.
		if (
			get_option( 'profile_blocks_lastfm_api_key' ) ||
			get_option( 'profile_blocks_lastfm_profile' )
		) {
			add_action( 'init', array( $this, 'register_block_patterns' ) );
		}
	}

	/**
	 * Register the blocks.
	 */
	public function register_blocks() {
		$blocks = array(
			TopCharts::class,
			DynamicTemplate::class,
			ItemName::class,
			ItemImage::class,
		);

		foreach ( $blocks as $block ) {
			/** @var Blocks\BaseBlock */ // phpcs:ignore
			$block_object = new $block();

			register_block_type(
				$block_object->block_type_metadata(),
				array(
					'render_callback' => array( $block_object, 'render_block' ),
				)
			);
		}
	}

	public function register_block_patterns() {
		register_block_pattern_category(
			'profile-blocks',
			array( 'label' => __( 'Profile Blocks', 'profile-blocks-lastfm' ) )
		);

		$grid_template = function ( $type, $label ) {
			$prop_key = rtrim( $type, 's' );
			return '
                <!-- wp:profile-blocks-lastfm/top-charts {"collection":"' . esc_attr( $type ) . '","period":"1month","itemsToShow":8} -->
                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading">' . esc_html( $label ) . '</h3>
                <!-- /wp:heading -->

                <!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"left","orientation":"horizontal"}} -->
                <div class="wp-block-group"><!-- wp:profile-blocks-lastfm/dynamic-template {"style":{"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex","justifyContent":"left"}} -->
                <!-- wp:profile-blocks-lastfm/item-image {"itemImageProp":"' . esc_attr( $prop_key ) . '.images","itemLinkProp":"' . esc_attr( $prop_key ) . '.url","itemImageSize":"large","width":174,"isLink":true,"style":{"layout":{"selfStretch":"fill","flexSize":null},"color":[]}} /-->
                <!-- /wp:profile-blocks-lastfm/dynamic-template --></div>
                <!-- /wp:group -->
                <!-- /wp:profile-blocks-lastfm/top-charts -->
            ';
		};

		register_block_pattern(
			'profile-blocks-lastfm/top-artists-grid',
			array(
				'title'         => __( 'Top Artists Grid', 'profile-blocks-lastfm' ),
				'blockTypes'    => array( 'profile-blocks-lastfm/top-charts' ),
				'categories'    => array( 'profile-blocks' ),
				'viewportWidth' => 696,
				'content'       => $grid_template( 'artists', __( 'Top Artists', 'profile-blocks-lastfm' ) ),
			)
		);

		register_block_pattern(
			'profile-blocks-lastfm/top-albums-grid',
			array(
				'title'         => __( 'Top Albums Grid', 'profile-blocks-lastfm' ),
				'blockTypes'    => array( 'profile-blocks-lastfm/top-charts' ),
				'categories'    => array( 'profile-blocks' ),
				'viewportWidth' => 696,
				'content'       => $grid_template( 'albums', __( 'Top Albums', 'profile-blocks-lastfm' ) ),
			)
		);

		register_block_pattern(
			'profile-blocks-lastfm/top-tracks-grid',
			array(
				'title'         => __( 'Top Tracks Grid', 'profile-blocks-lastfm' ),
				'blockTypes'    => array( 'profile-blocks-lastfm/top-charts' ),
				'categories'    => array( 'profile-blocks' ),
				'viewportWidth' => 696,
				'content'       => $grid_template( 'tracks', __( 'Top Tracks', 'profile-blocks-lastfm' ) ),
			)
		);

		$list_template = function ( $type, $label ) {
			$prop_key = rtrim( $type, 's' );
			return '
                <!-- wp:profile-blocks-lastfm/top-charts {"collection":"' . esc_attr( $type ) . '","period":"1month"} -->
                <!-- wp:heading {"level":3} -->
                <h3 class="wp-block-heading">' . esc_html( $label ) . '</h3>
                <!-- /wp:heading -->

                <!-- wp:profile-blocks-lastfm/dynamic-template {"layout":{"type":"flex"}} -->
                <!-- wp:profile-blocks-lastfm/item-image {"itemImageProp":"' . esc_attr( $prop_key ) . '.images","itemLinkProp":"' . esc_attr( $prop_key ) . '.url"} /-->

                <!-- wp:group {"style":{"layout":{"selfStretch":"fill"},"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap","orientation":"vertical"}} -->
                <div class="wp-block-group"><!-- wp:profile-blocks-lastfm/item-name {"itemTextProp":"' . esc_attr( $prop_key ) . '.name","itemLinkProp":"' . esc_attr( $prop_key ) . '.url","isLink":true,"style":{"typography":{"fontStyle":"normal","fontWeight":700}}} /--></div>
                <!-- /wp:group -->

                <!-- wp:group {"style":{"spacing":{"blockGap":"0.25em"},"typography":{"fontSize":"0.8em"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
                <div class="wp-block-group" style="font-size:0.8em"><!-- wp:profile-blocks-lastfm/item-name {"itemTextProp":"' . esc_attr( $prop_key ) . '.playcount"} /-->

                <!-- wp:paragraph -->
                <p>plays</p>
                <!-- /wp:paragraph --></div>
                <!-- /wp:group -->
                <!-- /wp:profile-blocks-lastfm/dynamic-template -->
                <!-- /wp:profile-blocks-lastfm/top-charts -->
            ';
		};

		register_block_pattern(
			'profile-blocks-lastfm/top-artists-list',
			array(
				'title'         => __( 'Top Artists List', 'profile-blocks-lastfm' ),
				'blockTypes'    => array( 'profile-blocks-lastfm/top-charts' ),
				'categories'    => array( 'profile-blocks' ),
				'viewportWidth' => 696,
				'content'       => $list_template( 'artists', __( 'Top Artists', 'profile-blocks-lastfm' ) ),
			)
		);

		register_block_pattern(
			'profile-blocks-lastfm/top-albums-list',
			array(
				'title'         => __( 'Top Albums List', 'profile-blocks-lastfm' ),
				'blockTypes'    => array( 'profile-blocks-lastfm/top-charts' ),
				'categories'    => array( 'profile-blocks' ),
				'viewportWidth' => 696,
				'content'       => $list_template( 'albums', __( 'Top Albums', 'profile-blocks-lastfm' ) ),
			)
		);

		register_block_pattern(
			'profile-blocks-lastfm/top-tracks-list',
			array(
				'title'         => __( 'Top Tracks List', 'profile-blocks-lastfm' ),
				'blockTypes'    => array( 'profile-blocks-lastfm/top-charts' ),
				'categories'    => array( 'profile-blocks' ),
				'viewportWidth' => 696,
				'content'       => $list_template( 'tracks', __( 'Top Tracks', 'profile-blocks-lastfm' ) ),
			)
		);
	}
}
