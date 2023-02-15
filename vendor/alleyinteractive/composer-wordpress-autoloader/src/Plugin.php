<?php

namespace ComposerWordPressAutoloader;

use Composer\Composer;
use Composer\Script\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;
use RuntimeException;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected Composer $composer;
    protected IOInterface $io;
    protected Filesystem $filesystem;
    protected AutoloadGenerator $generator;

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->filesystem = new Filesystem();
    }

    /**
     * Remove any hooks from Composer
     *
     * This will be called when a plugin is deactivated before being
     * uninstalled, but also before it gets upgraded to a new version
     * so the old one can be deactivated and the new one activated.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * Prepare the plugin to be uninstalled
     *
     * This will be called after deactivate.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        if ($this->filesystem->remove($this->getAutoloaderFilePath())) {
            $this->io->write('<info>Removed WordPress autoloader.</info>');
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump' => 'generateAutoloaderFile',
        ];
    }

    /**
     * Generate the autoloader file.
     *
     * @param Event $event
     * @return void
     */
    public function generateAutoloaderFile(Event $event): void
    {
        $this->filesystem->ensureDirectoryExists($this->composer->getConfig()->get('vendor-dir'));

        $this->generator = new AutoloadGenerator(
            $this->composer,
            $this->io,
        );

        $this->generator->setApcuMode(
            $this->composer->getConfig()->get('apcu-autoloader')
        );

        // Merge default configuration with the one provided in the composer.json file.
        $extra = array_merge(
            [
                'inject' => false,
            ],
            $this->composer->getPackage()->getExtra()['wordpress-autoloader'] ?? [],
        );

        /**
         * Determine if we should inject our autoloader into the
         * vendor/autoload.php from Composer.
         *
         * Injecting is not generally necessary any more since the file will
         * automatically be loaded. However, it is still possible to inject it
         * for circumstances where it is needed such as when dealing with symlinks.
         */
        $injecting = $extra['inject'] ?? false;

        $autoloaderFile = $this->generator->generate($injecting, $event->isDevMode());

        $partyEmoji = [
            '🪩',
            '🎉',
            '🎊',
            '🍾',
        ];

        $partyEmoji = $partyEmoji[array_rand($partyEmoji)];

        if (
            $this->filesystem->filePutContentsIfModified(
                $this->getAutoloaderFilePath(),
                $autoloaderFile,
            )
        ) {
            if (!$injecting) {
                $this->io->write("<info>{$partyEmoji} WordPress autoloader generated</info>");
            }
        }

        // Inject the autoloader into the existing autoloader.
        if ($injecting) {
            if (
                $this->filesystem->filePutContentsIfModified(
                    $this->composer->getConfig()->get('vendor-dir') . '/autoload.php',
                    $this->getInjectedAutoloaderFileContents($this->getAutoloaderFilePath()),
                )
            ) {
                $this->io->write(
                    "<info>{$partyEmoji} WordPress autoloader genearted and injected into vendor/autoload.php.</info>"
                );
            } else {
                $this->io->write('<error>⚠️ Error injecting Wordpress Autoloader.</error>');
            }
        }
    }

    /**
     * Retrieve the file path for the autoloader file.
     *
     * @return string
     */
    protected function getAutoloaderFilePath(): string
    {
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        return "{$vendorDir}/wordpress-autoload.php";
    }

    /**
     * Generate the injected autoloader file.
     *
     * @param string $autoloaderFileName The path to the WordPress autoloader file.
     * @return string
     */
    protected function getInjectedAutoloaderFileContents(string $autoloaderFileName): string
    {
        $filename = basename($autoloaderFileName);
        $autoloader = file_get_contents($this->composer->getConfig()->get('vendor-dir') . '/autoload.php');

        $contents = preg_replace_callback(
            '/^return (.*);$/m',
            function ($matches) use ($filename) {
                $autoloader = <<<AUTOLOADER
\$loader = {$matches[1]};

/*
  Composer WordPress Autoloader injected by alleyinteractive/composer-wordpress-autoloader

  To disable, set the "inject" key in the 'extra -> wordpress-autoloader'
  section of your composer.json file to false. Injecting the autoloader is
  not generally necessary as the autoloader is automatically loaded for you.
*/
require_once __DIR__ . '/{$filename}';

return \$loader;
AUTOLOADER;

                return "$autoloader\n";
            },
            $autoloader,
            1,
            $count,
        );

        if (!$count) {
            throw new RuntimeException('Error finding proper place to inject autoloader.');
        }

        return $contents;
    }
}
