<?php
/**
 * The LastFM class.
 *
 * @package ProfileBlocksLastFM
 */

namespace ProfileBlocksLastFM\Plugin;

/**
 * The LastFM class.
 */
class LastFM {
	/**
	 * The API endpoint for Last.FM.
	 *
	 * @var string
	 */
	private static $api_endpoint = 'https://ws.audioscrobbler.com/2.0/';

	/**
	 * Sends a request to the Last.fm API with the specified parameters.
	 *
	 * @param array $params Optional. An associative array of parameters to include in the API request.
	 *
	 * @return mixed The response from the Last.fm API.
	 */
	public static function api_request( $params = array() ) {
		$defaults = array(
			'api_key' => rawurlencode( get_option( 'profile_blocks_lastfm_api_key' ) ),
			'user'    => rawurlencode( get_option( 'profile_blocks_lastfm_profile' ) ),
			'format'  => 'json',
		);

		$params = wp_parse_args( array_filter( $params ), $defaults );

		$transient_key = 'profile_blocks_lastfm_' . md5( http_build_query( $params ) );
		$cached_data   = get_transient( $transient_key );

		if ( $cached_data ) {
			return $cached_data;
		}

		$allowed_methods = array(
			'artist.getinfo',
			'album.getinfo',
			'track.getinfo',
			'user.gettopalbums',
			'user.gettopartists',
			'user.gettoptracks',
		);

        // Validate the 'method' parameter.
		if (
			empty( $params['method'] ) ||
			! in_array( $params['method'], $allowed_methods, true )
		) {
			return new \WP_Error(
                'invalid_method',
				__( 'Invalid or missing method parameter for Last.fm API request.', 'profile-blocks-lastfm' ),
				array( 'status' => 400 )
			);
		}

		$response = wp_remote_get( add_query_arg( $params, self::$api_endpoint ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'json_decode_error',
				__( 'Failed to decode Last.fm API response.', 'profile-blocks-lastfm' ),
				array(
					'status' => 500,
					'error'  => json_last_error_msg(),
				)
			);
		}

		set_transient( $transient_key, $data, HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Maps LastFM image sizes to a custom format.
	 *
	 * Iterates through the provided array of images and returns a mapped array
	 * where image sizes are associated with their respective URLs.
	 *
	 * @param array $images Array of images from LastFM, each containing size and URL information.
	 *
	 * @return array Mapped array of image sizes to their corresponding URLs.
	 */
	private static function map_image_sizes( $images ) {
		$allowed_sizes = array( 'small', 'medium', 'large', 'extralarge' );
		return array_reduce(
			$images,
			function ( $acc, $img ) use ( $allowed_sizes ) {
				if ( in_array( $img['size'], $allowed_sizes, true ) ) {
					$acc[ $img['size'] ] = $img['#text'];
				}
				return $acc;
			},
			array()
		);
	}

	/**
	 * Retrieves information about a specified artist from LastFM.
	 *
	 * @param string      $artist The name of the artist to fetch information for.
	 * @param string|null $user Optional LastFM username for user-specific data.
	 *
	 * @return array Associative array containing artist details such as name, URL, images, and playcount.
	 */
	private static function get_artist_info( $artist, $user = null ) {
		$params = array(
			'method' => 'artist.getinfo',
			'artist' => $artist,
			'user'   => $user,
		);

		$data = self::api_request( $params );

		if ( is_wp_error( $data ) || empty( $data['artist'] ) ) {
			return array();
		}

		return array(
			'name'      => $data['artist']['name'],
			'url'       => $data['artist']['url'],
			'images'    => self::map_image_sizes( $data['artist']['image'] ),
			'playcount' => isset( $data['artist']['stats']['userplaycount'] ) ? intval( $data['artist']['stats']['userplaycount'] ) : 0,
		);
	}

	/**
	 * Retrieves information about a specific album from LastFM.
	 *
	 * @param string      $album  The name of the album.
	 * @param string      $artist The name of the artist.
	 * @param string|null $user Optional LastFM username for user-specific data.
	 *
	 * @return array Associative array containing album details such as name, URL, images, and playcount.
	 */
	private static function get_album_info( $album, $artist, $user = null ) {
		$params = array(
			'method' => 'album.getinfo',
			'album'  => $album,
			'artist' => $artist,
			'user'   => $user,
		);

		$data = self::api_request( $params );

		if ( is_wp_error( $data ) || empty( $data['album'] ) ) {
			return array();
		}

		return array(
			'name'      => $data['album']['name'],
			'url'       => $data['album']['url'],
			'images'    => self::map_image_sizes( $data['album']['image'] ),
			'playcount' => isset( $data['album']['userplaycount'] ) ? intval( $data['album']['userplaycount'] ) : 0,
		);
	}

	/**
	 * Retrieves detailed information about a specific track.
	 *
	 * @param string      $track  The name of the track.
	 * @param string      $album  The name of the album the track belongs to.
	 * @param string      $artist The name of the artist who performed the track.
	 * @param string|null $user Optional LastFM username for user-specific data.
	 *
	 * @return array Associative array containing track details such as name, URL, images, and playcount.
	 */
	private static function get_track_info( $track, $album, $artist, $user = null ) {
		$params = array(
			'method' => 'track.getinfo',
			'track'  => $track,
			'album'  => $album,
			'artist' => $artist,
			'user'   => $user,
		);

		$data = self::api_request( $params );

		if ( is_wp_error( $data ) || empty( $data['track'] ) ) {
			return array();
		}

		return array(
			'name'      => $data['track']['name'],
			'url'       => $data['track']['url'],
			'images'    => self::map_image_sizes( $data['track']['album']['image'] ),
			'playcount' => isset( $data['track']['userplaycount'] ) ? intval( $data['track']['userplaycount'] ) : 0,
		);
	}

	/**
	 * Retrieves the top albums from LastFM based on the provided parameters.
	 *
	 * @param array $params Optional. An array of parameters to filter or modify the request.
	 *
	 * @return array|WP_Error Returns an array of top albums or a WP_Error on failure.
	 */
	public static function get_top_albums( $params = array() ) {
		$defaults = array(
			'method' => 'user.gettopalbums',
		);

		$data = self::api_request(
			wp_parse_args( $params, $defaults )
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( empty( $data['topalbums']['album'] ) ) {
			return array();
		}

		$collection = array_map(
			function ( $item ) {
				return array(
					'artist' => array(
						'name' => $item['artist']['name'],
						'url'  => $item['artist']['url'],
					),
					'album'  => array(
						'name'      => $item['name'],
						'url'       => $item['url'],
						'playcount' => $item['playcount'],
						'images'    => self::map_image_sizes( $item['image'] ),
					),
				);
			},
			$data['topalbums']['album']
		);

		return $collection;
	}

	/**
	 * Retrieves the top artists from LastFM based on provided parameters.
	 *
	 * @param array $params Optional. An array of parameters to filter or modify the request.
	 *
	 * @return array|WP_Error Returns an array of top artists data or a WP_Error on failure.
	 */
	public static function get_top_artists( $params = array() ) {
		$defaults = array(
			'method' => 'user.gettopartists',
		);

		$data = self::api_request(
			wp_parse_args( $params, $defaults )
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( empty( $data['topartists']['artist'] ) ) {
			return array();
		}

		$collection = array_map(
			function ( $item ) {
				$artist = self::get_artist_info( $item['name'] );
				return array(
					'artist' => empty( $artist ) ? array(
						'name'      => $item['name'],
						'url'       => $item['url'],
						'playcount' => $item['playcount'],
						'images'    => self::map_image_sizes( $item['image'] ),
					) : array_merge( $artist, array( 'playcount' => $item['playcount'] ) ),
				);
			},
			$data['topartists']['artist']
		);

		return $collection;
	}

	/**
	 * Retrieves the top tracks from LastFM based on provided parameters.
	 *
	 * @param array $params Optional. An array of parameters to filter or modify the request.
	 *
	 * @return array|WP_Error Returns an array of top tracks data or a WP_Error on failure.
	 */
	public static function get_top_tracks( $params = array() ) {
		$defaults = array(
			'method' => 'user.gettoptracks',
		);

		$data = self::api_request(
			wp_parse_args( $params, $defaults )
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( empty( $data['toptracks']['track'] ) ) {
			return array();
		}

		$collection = array_map(
			function ( $item ) {
				$track = self::get_track_info( $item['name'], '', $item['artist']['name'] );

				return array(
					'artist' => array(
						'name' => $item['artist']['name'],
						'url'  => $item['artist']['url'],
					),
					'album'  => array(
						'name' => '',
						'url'  => '',
					),
					'track'  => empty( $track ) ? array(
						'name'      => $item['name'],
						'url'       => $item['url'],
						'playcount' => $item['playcount'],
						'images'    => self::map_image_sizes( $item['image'] ),
					) : array_merge( $track, array( 'playcount' => $item['playcount'] ) ),
				);
			},
			$data['toptracks']['track']
		);

		return $collection;
	}
}
