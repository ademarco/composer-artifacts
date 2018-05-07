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

    /**
     * Custom callback that update a package properties.
     *
     * @param \Composer\Package\Package $package
     *   The package.
     */
    private function setArtifactDist(Package $package)
    {
        $this->io->writeError(
          "  - Installing artifact of <info>" . $package->getName() . "</info> instead of regular package.");

        $tokens = $this->getPackageTokens($package);

        $distUrl = strtr($this->config[$package->getName()]['dist']['url'], $tokens);
        $distType = strtr($this->config[$package->getName()]['dist']['type'], $tokens);

        $package->setDistUrl($distUrl);
        $package->setDistType($distType);
    }

    /**
     * Get tokens from a package.
     *
     * @param \Composer\Package\Package $package
     *
     * @return string[]
     *   The list of tokens and their associated values.
     */
    private function getPackageTokens(Package $package)
    {
        $tokens = [
          'version' => $package->getVersion(),
          'name' => $package->getName(),
          'stability' => $package->getStability(),
          'type' => $package->getType(),
          'checksum' => $package->getDistSha1Checksum(),
        ];

        foreach ($tokens as $name => $value) {
            unset($tokens[$name]);
            $mappings['{' . $name . '}'] = $value;
        }

        return $tokens;
    }
}