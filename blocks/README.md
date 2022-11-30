### Blocks Directory

Custom blocks in this directory can be created by running the `create-block` script.

### Scaffold a block

1. In the root directory run `npm run create-block`
2. Follow the prompts to create a custom block.

There are 2 variants of blocks which you can create:

1. `static` - scaffolds a [basic block](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/writing-your-first-block-type/) with edit.js and save.js functions.
2. `dynamic` - scaffolds a [dynamic block](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/) with a `render.php` file for server side output on the front end.

The create-block script will create the block files in a directory using the `slug` field entered from the prompts when scaffolding the block.

The script uses the [@wordpress/create-block](https://github.com/WordPress/gutenberg/tree/trunk/packages/create-block#create-block) script with the `--no-plugin` flag for scaffolding block files only, and the `--template` flag setting the block template files to be scaffolded. See the create-block script in `package.json`. 

You can also scaffold a quick block by navigating to the blocks directory in your terminal and run the following command by passing in a slug for quick static block scaffolding:
```
npx @wordpress/create-block my-slug --template ../bin/create-block --no-plugin
```

For **static blocks** the following files will be generated:

```
blocks/
└───static-block-slug
    │   block.json
    │   edit.jsx
    |   editor.scss
    |   index.js
    |   index.php
    |   save.jsx
    |   styles.scss
```

For **dynmanic blocks** the following files will be generated:

```
blocks/
└───dynamic-block-slug
    │   block.json
    │   edit.jsx
    |   editor.scss
    |   index.js
    |   index.php
    |   styles.scss
    |   render.php
```

The `index.php` contains the PHP block registration and will be autoloaded once the block has been built by running `npm run build`.

Block attributes should be defined in the `block.json` file. [Learn more about block.json in the block editor handbook.](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/)

Running `npm run build` will compile the JavaScript and copy the PHP files to a directory in the `build` folder using `wp-scripts`. The blocks will be enqueued via block.json after block registration. The block `index.php` file will be read by the `load_scripts()` function found in the `function.php` file.