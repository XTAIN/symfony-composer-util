<?php
/**
 * This file is part of the XTAIN Symfony Composer Utils package.
 *
 * (c) Maximilian Ruta <mr@xtain.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XTAIN\Composer\Symfony\Util;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Composer\Script\CommandEvent;

/**
 * Class Console
 *
 * @author Maximilian Ruta <mr@xtain.net>
 * @package XTAIN\Composer\Symfony\Util
 */
class Console
{
    /**
     * Composer variables are declared static so that an event could update
     * a composer.json and set new options, making them immediately available
     * to forthcoming listeners.
     */
    protected static $options = array(
        'symfony-web-dir' => 'web',
    );

    /**
     * Construct
     *
     * @param CommandEvent $event      The command event.
     */
    public function __construct(CommandEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Executes a Symfony Command
     *
     */
    public function execute($command, $arguments = array())
    {
        $options = $this->getOptions();
        $consoleDir = $this->getConsoleDir();

        if (null === $consoleDir) {
            return;
        }

        $argumentString = '';

        foreach ($arguments as $argument) {
            $argumentString .= escapeshellarg($argument) . ' ';
        }

        $this->executeCommand($consoleDir, $command . ' ' . $argumentString);
    }

    /**
     * Returns a relative path to the directory that contains the `console` command.
     *
     * @return string|null The path to the console directory, null if not found.
     */
    protected function getConsoleDir()
    {
        $options = $this->getOptions();

        if (static::useNewDirectoryStructure($options)) {
            if (!$this->hasDirectory('symfony-bin-dir', $options['symfony-bin-dir'])) {
                return;
            }

            return $options['symfony-bin-dir'];
        }

        if (!$this->hasDirectory('symfony-app-dir', $options['symfony-app-dir'])) {
            return;
        }

        return $options['symfony-app-dir'];
    }

    /**
     * Returns true if the new directory structure is used.
     *
     * @param array $options Composer options
     *
     * @return bool
     */
    protected static function useNewDirectoryStructure(array $options)
    {
        return isset($options['symfony-var-dir']) && is_dir($options['symfony-var-dir']);
    }

    public function getOptions(array $options = array())
    {
        $options = array_merge($options, $this->event->getComposer()->getPackage()->getExtra());

        $options['process-timeout'] = $this->event->getComposer()->getConfig()->get('process-timeout');

        return $options;
    }

    /**
     * @param string $configName
     * @param string $path
     *
     * @return bool
     * @author Maximilian Ruta <mr@xtain.net>
     */
    public function hasDirectory($configName, $path)
    {
        if (!is_dir($path)) {
            $this->event->getIO()->write(sprintf('The %s (%s) specified in composer.json was not found in %s.', $configName, $path, getcwd()));

            return false;
        }

        return true;
    }

    protected function executeCommand($consoleDir, $cmd, $timeout = 300)
    {
        $event = $this->event;
        $php = escapeshellarg(static::getPhp(false));
        $phpArgs = implode(' ', array_map('escapeshellarg', static::getPhpArguments()));
        $console = escapeshellarg($consoleDir.'/console');
        if ($this->event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        $process = new Process($php.($phpArgs ? ' '.$phpArgs : '').' '.$console.' '.$cmd, null, null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event) { $event->getIO()->write($buffer, false); });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', escapeshellarg($cmd)));
        }
    }

    protected static function getPhpArguments()
    {
        $arguments = array();

        $phpFinder = new PhpExecutableFinder();
        if (method_exists($phpFinder, 'findArguments')) {
            $arguments = $phpFinder->findArguments();
        }

        if (false !== $ini = php_ini_loaded_file()) {
            $arguments[] = '--php-ini='.$ini;
        }

        return $arguments;
    }

    protected static function getPhp($includeArgs = true)
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find($includeArgs)) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }
}