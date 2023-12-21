<?php
/**
 * Feature implementation of hello.php
 *
 * @package Create_WordPress_Plugin
 */

namespace Create_WordPress_Plugin\Features;

use Alley\WP\Types\Feature;

final class Hello implements Feature {
	/**
	 * Set up.
	 *
	 * @param string $lyrics The lyrics to Hello Dolly.
	 */
	public function __construct(
		private readonly string $lyrics,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'admin_notices', [ $this, 'hello_dolly' ] );
		add_action( 'admin_head', [ $this, 'dolly_css' ] );
	}

	public function hello_dolly_get_lyric() {
		// Here we split the lyrics into lines.
		$lyrics = explode( "\n", $this->lyrics );

		// And then randomly choose a line.
		return wptexturize( $lyrics[ mt_rand( 0, count( $lyrics ) - 1 ) ] );
	}

	// This just echoes the chosen line, we'll position it later.
	public function hello_dolly() {
		$chosen = $this->hello_dolly_get_lyric();
		$lang   = '';
		if ( 'en_' !== substr( get_user_locale(), 0, 3 ) ) {
			$lang = ' lang="en"';
		}

		printf(
			'<p id="dolly"><span class="screen-reader-text">%s </span><span dir="ltr"%s>%s</span></p>',
			__( 'Quote from Hello Dolly song, by Jerry Herman:' ),
			$lang,
			$chosen
		);
	}

	// We need some CSS to position the paragraph.
	public function dolly_css() {
		echo "
		<style type='text/css'>
		#dolly {
			float: right;
			padding: 5px 10px;
			margin: 0;
			font-size: 12px;
			line-height: 1.6666;
		}
		.rtl #dolly {
			float: left;
		}
		.block-editor-page #dolly {
			display: none;
		}
		@media screen and (max-width: 782px) {
			#dolly,
			.rtl #dolly {
				float: none;
				padding-left: 0;
				padding-right: 0;
			}
		}
		</style>
		";
	}
}
