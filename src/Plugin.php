<?php

namespace OpenEuropa\ComposerArtifacts;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var mixed[]
     */
    static $config = [];

    /** @var IOInterface */
    static $io;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        self::$io = $io;

        $extra = $composer->getPackage()->getExtra() + ['artifacts' => []];

        // Make sure that package name are in lowercase.
        $extra['artifacts'] = array_combine(
          array_map(
            function($name) {
                return strtolower($name);
            },
            array_keys($extra['artifacts'])
          ),
          $extra['artifacts']);

        self::$config = $extra['artifacts'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
          PackageEvents::PRE_PACKAGE_INSTALL => 'prePackageInstall',
          PackageEvents::PRE_PACKAGE_UPDATE => 'prePackageUpdate',
        ];
    }

    /**
     * Pre package install callback.
     *
     * @param \Composer\Installer\PackageEvent $event
     *   The event.
     */
    public static function prePackageInstall(PackageEvent $event)
    {
        /** @var Package $package */
        $package = $event->getOperation()->getPackage();

        if (in_array($package->getName(), array_keys(self::$config))) {
            self::setArtifactDist($package);
        }
    }

    /**
     * Pre package update callback.
     *
     * @param \Composer\Installer\PackageEvent $event
     *   The event.
     */
    public static function prePackageUpdate(PackageEvent $event)
    {
        /** @var Package $package */
        $package = $event->getOperation()->getInitialPackage();
        if (in_array($package->getName(), array_keys(self::$config))) {
            self::setArtifactDist($package);
        }
    }

    /**
     * Custom callback that update a package properties.
     *
     * @param \Composer\Package\Package $package
     *   The package.
     */
    private static function setArtifactDist(Package $package)
    {
        self::$io->writeError(
          "  - Installing artifact of <info>" . $package->getName() . "</info> instead of regular package.");
        $package->setDistUrl(self::$config[$package->getName()]['dist']['url']);
        $package->setDistType(self::$config[$package->getName()]['dist']['type']);
    }
}