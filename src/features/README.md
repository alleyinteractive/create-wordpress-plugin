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

## There are three ways to add a feature:
### Add a feature using the `create_wordpress_plugin_features` filter.
```
add_filter( 'create_wordpress_plugin_features', function ( array $features ) use ( $lyrics ): array {
    $features[ 'Create_WordPress_Plugin\Features\Hello' ] = [ 'lyrics' => $lyrics ];
    return $features;
} );
```
### Add a feature using the add_features method
```
$features = [
    'Create_WordPress_Plugin\Features\Hello' => [ 'lyrics' => $lyrics ],
];
$features = apply_filters( 'create_wordpress_plugin_features', $features );

Feature_Manager::add_features( $features );
```
### Add a feature using the add_feature method
```
Feature_Manager::add_feature( 'Create_WordPress_Plugin\Features\Hello', [ 'lyrics' => $lyrics ] );
```
## Get the instance of an added feature
```
$hello_feature = Feature_Manager::get_feature( 'Create_WordPress_Plugin\Features\Hello' );
```
## Once we have the instance, we can remove hooks from inside the instance
```
remove_action( 'admin_head', [ $hello_feature, 'dolly_css' ] );
```