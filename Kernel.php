<?php
/**
 * @author Maximilian Ruta <mr@xtain.net>
 */

namespace XTAIN\Composer\Symfony\Util;

use Composer\Script\CommandEvent;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Class Kernel
 *
 * @author Maximilian Ruta <mr@xtain.net>
 * @package XTAIN\Composer\Symfony\Util
 */
class Kernel
{
    /**
     * @var CommandEvent
     */
    protected $event;

    /**
     * @var Console
     */
    protected $console;

    /**
     * Construct
     *
     * @param CommandEvent $event      The command event.
     */
    public function __construct(CommandEvent $event)
    {
        $this->event = $event;
        $this->console = new Console($event);
    }

    /**
     * @return string[]
     * @author Maximilian Ruta <mr@xtain.net>
     */
    public function getBundles()
    {
        require_once $this->console->getAppDir() . '/AppKernel.php';

        $input = new ArgvInput();
        $env = $input->getParameterOption(array('--env', '-e'), getenv('SYMFONY_ENV') ?: 'dev');
        $debug = getenv('SYMFONY_DEBUG') !== '0' && !$input->hasParameterOption(array('--no-debug', '')) && $env !== 'prod';

        $kernel = new \AppKernel($env, $debug);
        $bundles = $kernel->registerBundles();

        $bundleMap = [];

        foreach ($bundles as $bundle) {
            $reflector = new \ReflectionClass($bundle);
            $bundlePath = dirname($reflector->getFileName());
            $bundleMap[$bundle->getName()] = $bundlePath;
        }

        return $bundleMap;
    }
}