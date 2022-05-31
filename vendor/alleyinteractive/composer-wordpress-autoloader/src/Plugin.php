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

        // Determine if we should inject our autoloader into the vendor/autoload.php from Composer.
        $injecting = !empty($this->composer->getPackage()->getExtra()['wordpress-autoloader']['inject']);

        $autoloaderFile = $this->generator->generate($injecting, $event->isDevMode());

        // Inject the autoloader into the existing autoloader.
        if ($injecting) {
            if (
                $this->filesystem->filePutContentsIfModified(
                    $this->composer->getConfig()->get('vendor-dir') . '/autoload.php',
                    $this->getInjectedAutoloaderFileContents($autoloaderFile),
                )
            ) {
                $this->io->write('<info>WordPress Autoloader generated and injected.</info>');
            } else {
                $this->io->write('<error>Error injecting Wordpress Autoloader.</error>');
            }
        } else {
            if (
                $this->filesystem->filePutContentsIfModified(
                    $this->getAutoloaderFilePath(),
                    $autoloaderFile,
                )
            ) {
                $this->io->write('<info>WordPress Autoloader generated.</info>');
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
     * @param string $contents File contents to inject.
     * @return string
     */
    protected function getInjectedAutoloaderFileContents(string $contents): string
    {
        $autoloader = file_get_contents($this->composer->getConfig()->get('vendor-dir') . '/autoload.php');

        $contents = preg_replace_callback(
            '/^return (.*);$/m',
            function ($matches) use ($contents) {
                $contents = trim($contents);
                $autoloader = <<<AUTOLOADER
\$loader = {$matches[1]};

{$contents}

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
