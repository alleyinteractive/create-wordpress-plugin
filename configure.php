#!/usr/bin/env php
<?php
/**
 * Configure the WordPress Plugin interactively.
 */

if ( ! defined( 'STDIN' ) ) {
	die( 'Not in CLI mode.' );
}

function ask( string $question, string $default = '' ): string {
	$answer = readline( $question . ( $default ? " ({$default})" : null ) . ': ' );

	if ( ! $answer ) {
		return $default;
	}

	return $answer;
}

function confirm( string $question, bool $default = false ): bool {
	$answer = ask( $question . ' (' . ( $default ? 'Y/n' : 'y/N' ) . ')' );

	if ( ! $answer ) {
		return $default;
	}

	return strtolower( $answer ) === 'y';
}

function writeln( string $line ): void {
	echo $line . PHP_EOL;
}

function run( string $command ): string {
	return trim( shell_exec( $command ) );
}

function str_after( string $subject, string $search ): string {
	$pos = strrpos( $subject, $search );

	if ( $pos === false ) {
		return $subject;
	}

	return substr( $subject, $pos + strlen( $search ) );
}

function slugify( string $subject ): string {
	return strtolower( trim( preg_replace( '/[^A-Za-z0-9-]+/', '-', $subject ), '-' ) );
}

function title_case( string $subject ): string {
	return str_replace( ' ', '', ucwords( str_replace( [ '-', '_' ], ' ', $subject ) ) );
}

function replace_in_file( string $file, array $replacements ): void {
	$contents = file_get_contents( $file );

	file_put_contents(
		$file,
		str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$contents
		)
	);
}

function remove_readme_paragraphs( string $file ): void {
	$contents = file_get_contents( $file );

	file_put_contents(
		$file,
		preg_replace( '/<!--delete-->.*<!--\/delete-->/s', '', $contents ) ?: $contents
	);
}

function determine_separator( string $path ): string {
	return str_replace( '/', DIRECTORY_SEPARATOR, $path );
}

function replace_for_windows(): array {
	return preg_split( '/\\r\\n|\\r|\\n/', run( 'dir /S /B * | findstr /v /i .git\ | findstr /v /i vendor | findstr /v /i ' . basename( __FILE__ ) . ' | findstr /r /i /M /F:/ ":author :vendor :package VendorName skeleton vendor_name vendor_slug author@domain.com"' ) );
}

function replace_for_all_other_oses(): array {
	return explode( PHP_EOL, run( 'grep -E -r -l -i ":author|:vendor|:package|VendorName|skeleton|vendor_name|vendor_slug|author@domain.com" --exclude-dir=vendor ./* ./.github/* | grep -v ' . basename( __FILE__ ) ) );
}

$git_name   = run( 'git config user.name' );
$author_name = ask( 'Author name', $git_name );

$git_email    = run( 'git config user.email' );
$author_email = ask( 'Author email', $git_email );

$username_guess  = explode( ':', run( 'git config remote.origin.url' ) )[1];
$username_guess  = dirname( $username_guess );
$username_guess  = basename( $username_guess );
$author_username = ask( 'Author username', $username_guess );

$vendor_name      = ask( 'Vendor name', $author_username );
$vendor_slug      = slugify( $vendor_name );
$vendor_namespace = ucwords( $vendor_name );
$vendor_namespace = ask( 'Vendor namespace', $vendor_namespace );

$current_dir = getcwd();
$folder_name       = basename( $current_dir );

$package_name = ask( 'Package name', $folder_name );
$package_slug = slugify( $package_name );

$class_name   = title_case( $package_name );
$class_name   = ask( 'Class name', $class_name );
$description = ask( 'Package description', "This is my package {$package_slug}" );

writeln( '------' );
writeln( "Author     : {$author_name} ({$author_username}, {$author_email})" );
writeln( "Vendor     : {$vendor_name} ({$vendor_slug})" );
writeln( "Package    : {$package_slug} <{$description}>" );
writeln( "Namespace  : {$vendor_namespace}\\{$class_name}" );
writeln( "Class name : {$class_name}" );
writeln( '------' );

writeln( 'This script will replace the above values in all relevant files in the project directory.' );

if ( ! confirm( 'Modify files?', true ) ) {
	exit( 1 );
}

$files = ( str_starts_with( strtoupper( PHP_OS ), 'WIN' ) ? replace_for_windows() : replace_for_all_other_oses() );

foreach ( $files as $file ) {
	replace_in_file(
		$file,
		[
			':author_name'         => $author_name,
			':author_username'     => $author_username,
			'author@domain.com'    => $author_email,
			':vendor_name'         => $vendor_name,
			':vendor_slug'         => $vendor_slug,
			'VendorName'           => $vendor_namespace,
			':package_name'        => $package_name,
			':package_slug'        => $package_slug,
			'Skeleton'             => $class_name,
			':package_description' => $description,
		]
	);

	match (true) {
		str_contains( $file, determine_separator( 'src/SkeletonClass.php' ) ) => rename( $file, determine_separator( './src/' . $class_name . 'Class.php' ) ),
		str_contains( $file, 'README.md' ) => remove_readme_paragraphs( $file ),
		default => [],
	};

}

confirm( 'Execute `composer install` and run tests?' ) && run( 'composer install && composer test' );

confirm( 'Let this script delete itself?', true ) && unlink( __FILE__ );
