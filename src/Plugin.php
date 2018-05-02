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
    static $config = [];

    public function activate(Composer $composer, IOInterface $io)
    {
        $extra = $composer->getPackage()->getExtra() + ['artifacts' => []];
        self::$config = $extra['artifacts'];
    }

    public static function getSubscribedEvents()
    {
        return array(
          PackageEvents::PRE_PACKAGE_INSTALL => 'prePackageInstall',
          PackageEvents::PRE_PACKAGE_UPDATE => 'prePackageUpdate',
        );
    }

    public static function prePackageInstall(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
        if (in_array($package->getName(), self::$config)) {
            self::setArtifactDist($package);
        }
    }

    public static function prePackageUpdate(PackageEvent $event)
    {
        $package = $event->getOperation()->getInitialPackage();
        if (in_array($package->getName(), self::$config)) {
            self::setArtifactDist($package);
        }
    }

    private static function setArtifactDist(Package $package)
    {
        $package->setDistUrl(self::$config[$package->getName()]['dist']['url']);
        $package->setDistType(self::$config[$package->getName()]['dist']['type']);
    }
}