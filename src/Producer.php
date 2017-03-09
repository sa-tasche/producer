<?php
/**
 * Command line tool for vendor code.
 *
 * PHP version 5
 *
 * @category   CommandLine
 *
 * @author     Francesco Bianco <bianco@javanile.org>
 * @license    https://goo.gl/KPZ2qI  MIT License
 * @copyright  2015-2017 Javanile.org
 */

namespace Javanile;

use Composer\Autoload\ClassLoader;
use Javanile\Producer\Commands\InitCommand;
use Javanile\Producer\Commands\CloneCommand;
use Javanile\Producer\Commands\PurgeCommand;
use Javanile\Producer\Commands\UpdateCommand;

/**
 * Class Producer.
 */
class Producer
{
    /**
     * Current working directory for running script.
     */
    private $cwd = null;

    /**
     * Producer constructor.
     *
     * @param string $cwd current working directory
     */
    public function __construct($cwd)
    {
        $this->cwd = $cwd;
    }

    /**
     * Entry point for command-line tool.
     */
    public static function cli()
    {
        global $argv;

        $cwd = getcwd();
        $cli = new self($cwd);

        echo $cli->run(array_slice($argv, 1));
    }

    /**
     * Script runner.
     */
    protected function run($args)
    {
        if (!isset($args[0])) {
            return "> Producer: Command required.\n";
        }

        switch (trim($args[0])) {
            case 'init':
                return $this->runInit($args);
            case 'test':
                return $this->cmdTest($args);
            case 'clone':
                return $this->cmdClone($args);
            case 'purge':
                return $this->cmdPurge($args);
            case 'update':
                return $this->runUpdate($args);
            case 'install':
                return $this->cmdInstall($args);
            case 'publish':
                return $this->cmdPublish($args);
            case '--version':
                return $this->cmdVersion($args);
            case '--help':
                return $this->cmdHelp($args);
            default:
                return "> Producer: undefined '{$args[0]}' command.\n";
        }
    }

    /**
     * Init script.
     */
    private function runInit($args)
    {
        $cmd = new InitCommand($this->cwd);

        return $cmd->run(array_slice($args, 1));
    }

    /**
     * Test runner command.
     *
     * @param array $args  Arguments from command line
     *
     * @return string|void
     */
    private function cmdTest($args)
    {
        // test if phpunit are installed
        $phpunit = $this->cwd.'/vendor/bin/phpunit';
        if (!file_exists($phpunit)) {
            return "> Producer: Install phpunit via composer (not global).\n";
        }

        // run all tests on all repository projects
        if (!isset($args[1]) || !$args[1]) {
            $test = 'tests';
            $path = $this->cwd.'/repository';

            foreach (scandir($path) as $name) {
                if ($name[0] != '.' && is_dir($path.'/'.$name)) {
                    echo shell_exec(__DIR__.'/../exec/test-dox.sh '.$this->cwd.' '.$name.' '.$test);
                }
            }

            return;
        }

        // run all tests on one repository project
        $name = $args[1];
        $test = 'tests';
        $path = $this->cwd.'/repository';
        if (is_dir($path.'/'.$name)) {
            return shell_exec(__DIR__.'/../exec/test-dox.sh '.$this->cwd.' '.$name.' '.$test);
        }

        // run env test if exist
        $file = $this->cwd.'/tests/'.$name.'.php';
        if (file_exists($file)) {
            $item = isset($args[2]) ? intval($args[2]) : null;
            if (!$item) {
                return shell_exec(__DIR__.'/../exec/test-env-dox.sh '.$this->cwd.' '.$name);
            }
            $classes = get_declared_classes();
            require_once $file;
            $diff = array_diff(get_declared_classes(), $classes);
            $class = array_pop($diff);
            if (!class_exists($class)) {
                return "> Producer: Test class '{$class}' not found.\n";
            }
            $methods = array_filter(get_class_methods($class), function ($method) {
                return preg_match('/^test[A-Z]/', $method);
            });
            if (!isset($methods[$item-1])) {
                return "> Producer: Test class '{$class}' have less than '{$item}' methods.\n";
            }
            $filter = "'/::".$methods[$item-1]."/'";

            return shell_exec(__DIR__.'/../exec/test-env-filter.sh '.$this->cwd.' '.$name.' '.$filter);
        }

        // run single unit test throught repository projects
        $test = 'tests/'.$args[1];
        foreach (scandir($path) as $name) {
            if ($name[0] == '.' || !is_dir($path.'/'.$name)) {
                continue;
            }
            $file = $path.'/'.$name.'/'.$test.'.php';
            if (!file_exists($file)){
                continue;
            }
            $item = isset($args[2]) ? intval($args[2]) : null;
            if (!$item) {
                return shell_exec(__DIR__.'/../exec/test-dox.sh '.$this->cwd.' '.$name.' '.$test);
            }
            $classes = get_declared_classes();
            require_once $file;
            $diff = array_diff(get_declared_classes(), $classes);
            $class = array_pop($diff);
            if (!class_exists($class)) {
                return "> Producer: Test class '{$class}' not found.\n";
            }
            $methods = array_filter(get_class_methods($class), function ($method) {
                return preg_match('/^test[A-Z]/', $method);
            });
            if (!isset($methods[$item-1])) {
                return "> Producer: Test class '{$class}' have less than '{$item}' methods.\n";
            }
            $filter = "'/::".$methods[$item-1]."/'";

            return shell_exec(__DIR__.'/../exec/test-filter.sh '.$this->cwd.' '.$name.' '.$test.' '.$filter);
        }

        return "> Producer: Test case '{$args[1]}' not found.\n";
    }

    /**
     * Clone script.
     */
    private function cmdClone($args)
    {
        $cmd = new CloneCommand($this->cwd);

        return $cmd->run(array_slice($args, 1));
    }

    /**
     * Purge script.
     */
    private function cmdPurge($args)
    {
        $cmd = new PurgeCommand($this->cwd);

        return $cmd->run(array_slice($args, 1));
    }

    /**
     * Publish script.
     */
    private function runUpdate($args)
    {
        $cmd = new UpdateCommand($this->cwd);

        return $cmd->run(array_slice($args, 1));
    }

    /**
     * Install script.
     */
    private function cmdInstall($args)
    {
        // Installation process are performed
        // throught 'bin/producer' scripting file
        return "> Producer: Installation complete.\n";
    }

    /**
     * Publish script.
     */
    private function cmdPublish($args)
    {
        if (!isset($args[1]) || !$args[1]) {
            $path = $this->cwd.'/repository';

            foreach (scandir($path) as $name) {
                if ($name[0] != '.' && is_dir($path.'/'.$name)) {
                    echo "\n> $name\n--------------\n";
                    echo shell_exec(__DIR__.'/../exec/publish.sh '.$this->cwd.' '.$name);
                }
            }
        } else {
            return shell_exec(__DIR__.'/../exec/publish.sh '.$this->cwd.' '.$args[1]);
        }
    }

    /**
     * Return version.
     */
    private function cmdVersion()
    {
        $json = json_decode(file_get_contents(__DIR__.'/../composer.json'));
        return "> Producer: version {$json->version}\n";
    }

    /**
     * Return commanline helps.
     */
    private function cmdHelp($args)
    {
        return file_get_contents(__DIR__.'/../help/help.txt');
    }

    /**
     * Tool to register Psr4 'namespaces vs paths'.
     *
     * @param array $paths a pairs of namespace and path to register
     */
    public static function addPsr4($paths)
    {
        $loader = new ClassLoader();

        foreach ($paths as $namespace => $path) {
            $loader->addPsr4($namespace, $path);
        }

        $loader->register();
    }

    /**
     * Log messages on 'producer.log' file.
     *
     *
     */
    public static function log($object)
    {
        $cwd = getcwd();
        $log = $cwd.'/producer.log';

        file_put_contents($log, "> ".trim($object)."\n", FILE_APPEND);
    }
}
