<?php
/**
 * The ItemImage block class.
 *
 * @package ProfileBlocksLastFM
 */

namespace ProfileBlocksLastFM\BlockLibrary\Blocks;

/**
 * The ItemImage block class.
 */
class ItemImage extends BaseBlock {
	const FALLBACK_IMAGE = 'https://lastfm.freetls.fastly.net/i/u/174s/2a96cbd8b46e442fc41c2b86b821562f.png';

	/**
	 * Renders the block on the server.
	 *
	 * @return string Returns the block content.
	 */
	public function render() {
		$item = $this->get_block_context( 'item' );

		if ( ! $item ) {
			return '';
		}

		$item_image = $this->getByPath( $item, $this->get_block_attribute( 'itemImageProp' ) );
		$item_link  = $this->getByPath( $item, $this->get_block_attribute( 'itemLinkProp' ) );

		$item_image_size = $this->get_block_attribute( 'itemImageSize' );

		$item_image_url = empty( $item_image[ $item_image_size ] )
			? self::FALLBACK_IMAGE
			: $item_image[ $item_image_size ];

		$width = empty( $this->get_block_attribute( 'width' ) )
			? 64 :
			intval( $this->get_block_attribute( 'width' ) );

		$image = sprintf(
			'<img src="%s" width="%s" alt="" />',
			esc_url( $item_image_url ),
			esc_attr( $width )
		);

		if ( $this->get_block_attribute( 'isLink' ) && $item_link ) {
			$image = sprintf(
				'<a href="%s" target="%s">%s</a>',
				esc_url( $item_link ),
				esc_attr( $this->get_block_attribute( 'linkTarget' ) ),
				$image
			);
		}

		return sprintf(
			'<div %s>%s</div>',
			get_block_wrapper_attributes(
				array(
					'class' => str_replace( '.', '-', $this->get_block_attribute( 'itemImageProp' ) ),
				)
			),
			$image
		);
	}
}
