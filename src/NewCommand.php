<?php

namespace CodeIgniter\Installer\Console;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class NewCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new CodeIgniter application')
            ->setDescription('Powered by Cart53.com')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->write(PHP_EOL . "<fg=red> _                               _
   ____          _      ___            _ _            _  _
  / ___|___   __| | ___|_ _|__ _ _ __ (_| |_ ___ _ __| || |
 | |   / _ \ / _` |/ _ \| |/ _` | '_ \| | __/ _ | '__| || |_
 | |__| (_) | (_| |  __/| | (_| | | | | | ||  __| |  |__   _|
  \____\___/ \__,_|\___|___\__, |_| |_|_|\__\___|_|     |_|  .
                           |___/                             </>" . PHP_EOL . PHP_EOL);

        sleep(1);

        $name = $input->getArgument('name');

        $directory = $name !== '.' ? getcwd() . '/' . $name : '.';

        $composer = $this->findComposer();

        $commands = [
            $composer . " create-project codeigniter4/appstarter \"$directory\" --prefer-dist",
        ]; // create-project codeigniter4/appstarter project-root

        if (PHP_OS_FAMILY != 'Windows') {
            $commands[] = "chmod 755 \"$directory/spark\"";
        }

        $this->verifyApplicationDoesntExist($directory);

        if (($process = $this->runCommands($commands, $input, $output))->isSuccessful()) {
            if ($name !== '.') {
                $this->replaceInFile(
                    '# CI_ENVIRONMENT = production',
                    'CI_ENVIRONMENT = development',
                    $directory . '/env'
                );

                $this->replaceInFile(
                    'database.default.database = ci4',
                    'database.default.database = ' . str_replace('-', '_', strtolower($name)),
                    $directory . '/env'
                );

                $this->replaceInFile(
                    'database.default.password = root',
                    'database.default.password = ',
                    $directory . '/env'
                );

                $this->replaceInFile(
                    'encryption.key =',
                    'encryption.key =' . base64_encode(uniqid(time(), true)),
                    $directory . '/env'
                );

                $this->renameFile(
                    $directory . '/env',
                    $directory . '/.env'
                );
            }

            $output->writeln(PHP_EOL . '<comment>Application ready! Build something amazing.</comment>');
        }

        return $process->getExitCode();
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        $composerPath = getcwd() . '/composer.phar';

        if (file_exists($composerPath)) {
            return '"' . PHP_BINARY . '" ' . $composerPath;
        }

        return 'composer';
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  array  $env
     * @return \Symfony\Component\Process\Process
     */
    protected function runCommands($commands, InputInterface $input, OutputInterface $output, array $env = [])
    {
        if (!$output->isDecorated()) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value . ' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value . ' --quiet';
            }, $commands);
        }

        $process = Process::fromShellCommandline(implode(' && ', $commands), null, $env, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: ' . $e->getMessage());
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write('    ' . $line);
        });

        return $process;
    }

    /**
     * Replace the given string in the given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $file
     * @return void
     */
    protected function replaceInFile(string $search, string $replace, string $file)
    {
        file_put_contents(
            $file,
            str_replace($search, $replace, file_get_contents($file))
        );
    }
    protected function renameFile(string $file, string $new)
    {
        rename($file, $new);
    }
}
