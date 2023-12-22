# Features
Features should be PHP classes that implement the [Alley\WP\Types\Feature interface](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/types/interface-feature.php).

Features should be located in the `src/features` directory of the plugin and have namespace `Create_WordPress_Plugin\Features;`

Files in the features directory will be autoloaded, but features will not be instantiated. Features are instantiated via the `Feature_Manager` static class.

The following variable is passed to the `Class_Hello` feature in each of the following examples. This shows how we can remove any business logic from the feature and pass it in when the feature is added.

```
$lyrics = "Hello, Dolly
    Well, hello, Dolly
    It's so nice to have you back where you belong
    You're lookin' swell, Dolly
    I can tell, Dolly
    You're still glowin', you're still crowin'
    You're still goin' strong
    I feel the room swayin'
    While the band's playin'
    One of our old favorite songs from way back when
    So, take her wrap, fellas
    Dolly, never go away again";
```

## There are two ways to add a feature:
### Add a feature using the `add_features` method
```
$features = [
    'Create_WordPress_Plugin\Features\Hello' => [ 'lyrics' => $lyrics ],
];

Feature_Manager::add_features( $features );
```

> 💡 If you `apply_filters` to the features array before passing it to `add_features`, you can modify it with a filter.
```
$features = apply_filters( 'create_wordpress_plugin_features', $features );
```

### Add a feature using the `add_feature` method
```
Feature_Manager::add_feature( 'Create_WordPress_Plugin\Features\Hello', [ 'lyrics' => $lyrics ] );
```
## Get the instance of an added feature with the `get_feature` method
```
$hello_feature = Feature_Manager::get_feature( 'Create_WordPress_Plugin\Features\Hello' );
```
## Once we have the instance, we can remove hooks from inside the instance
```
remove_action( 'admin_head', [ $hello_feature, 'dolly_css' ] );
```

## Example feature class:
This is a port of the infamous WordPress `hello.php` plugin to a feature. The lyrics would be passed in when the feature was called, as shown above.
```
<?php
/**
 * Feature implementation of hello.php
 *
 * @package Create_WordPress_Plugin
 */

namespace Create_WordPress_Plugin\Features;

use Alley\WP\Types\Feature;

/**
 * Hello class file
 */
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

	/**
	 * Gets a random lyric from the lyric string.
	 *
	 * @return string
	 */
	public function hello_dolly_get_lyric(): string {
		// Here we split the lyrics into lines.
		$lyrics = explode( "\n", $this->lyrics );

		// And then randomly choose a line.
		return wptexturize( $lyrics[ wp_rand( 0, count( $lyrics ) - 1 ) ] );
	}

	/**
	 * Echos the chosen line.
	 */
	public function hello_dolly(): void {
		$chosen = $this->hello_dolly_get_lyric();
		$lang   = '';
		if ( 'en_' !== substr( get_user_locale(), 0, 3 ) ) {
			$lang = ' lang="en"';
		}

		printf(
			'<p id="dolly"><span class="screen-reader-text">%s </span><span dir="ltr"%s>%s</span></p>',
			esc_html__( 'Quote from Hello Dolly song, by Jerry Herman:' ),
			esc_attr( $lang ),
			esc_html( $chosen )
		);
	}

	/**
	 * Output css to position the paragraph.
	 */
	public function dolly_css(): void {
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
```