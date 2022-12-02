<?php

namespace ComposerWordPressAutoloader;

use Alley_Interactive\Autoloader\Autoloader;

class AutoloadFactory
{
    protected static ?string $apcuPrefix = null;

    /**
     * Set the APCu prefix to use.
     *
     * @param string|null $apcuPrefix|
     */
    public static function setApcuPrefix(?string $apcuPrefix): void
    {
        static::$apcuPrefix = $apcuPrefix;
    }

    /**
     * Generate an autoloader from a set of rules.
     *
     * @param array<string, array<string>> $rules Array of rules.
     * @return array<Autoloader>
     */
    public static function generateFromRules(array $rules)
    {
        $loaders = [];

        foreach ($rules as $namespace => $paths) {
            $loaders = array_merge(
                $loaders,
                array_map(function ($path) use ($namespace) {
                    $loader = new Autoloader($namespace, $path);

                    if (static::$apcuPrefix) {
                        $loader->set_apcu_prefix(static::$apcuPrefix);
                    }

                    return $loader;
                }, $paths),
            );
        }

        return $loaders;
    }

    /**
     * Register autoloaders from rules.
     *
     * @param array<string, string> $rules Array of rules.
     * @return void
     */
    public static function registerFromRules(array $rules)
    {
        foreach (static::generateFromRules($rules) as $autoloader) {
            $autoloader->register();
        }
    }
}
