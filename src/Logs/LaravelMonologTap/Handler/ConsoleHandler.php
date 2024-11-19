<?php

namespace FlorinMotoc\LaravelAddon\Logs\LaravelMonologTap\Handler;

use FlorinMotoc\LaravelAddon\Logs\LaravelMonologTap\Formatter\ConsoleFormatter;
use Illuminate\Http\Request;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleHandler extends AbstractProcessingHandler
{
    const OUTPUT_FORMAT = "%datetime% %start_tag%%level_name%%end_tag% <comment>[%channel%]</> %message%%context%%extra%";

    /** @var bool */
    private $shouldHandle;

    /** @var ConsoleOutput */
    private $output;

    /** @var int|mixed */
    private $verbosity = OutputInterface::VERBOSITY_QUIET;

    /** @var array */
    private $verbosityMap = [
        '-q' => OutputInterface::VERBOSITY_QUIET,
        '-v' => OutputInterface::VERBOSITY_VERBOSE,
        '-vv' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        '-vvv' => OutputInterface::VERBOSITY_DEBUG,
    ];

    /** @var array */
    private $monologVerbosityMap = [
        Logger::DEBUG => OutputInterface::VERBOSITY_DEBUG,
        Logger::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
        Logger::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
        Logger::WARNING => OutputInterface::VERBOSITY_VERBOSE,
        Logger::ERROR => OutputInterface::VERBOSITY_VERBOSE,
        Logger::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
        Logger::ALERT => OutputInterface::VERBOSITY_NORMAL,
        Logger::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
    ];

    public function __construct($level = Logger::DEBUG, $verbosity = null, $bubble = true, Request $request = null, ConsoleOutput $output = null)
    {
        parent::__construct($level, $bubble);

        $this->shouldHandle = app()->runningInConsole();

        if (!$this->shouldHandle) {
            return;
        }

        $this->output = $output;

        if ($verbosity !== null) {
            $this->verbosity = $verbosity;
        } else {
            $argv = $request->server('argv');
            foreach ($argv as $value) {
                if (isset($this->verbosityMap[$value])) {
                    $this->verbosity = $this->verbosityMap[$value];
                    if ($this->verbosity == OutputInterface::VERBOSITY_QUIET) {
                        break;
                    }
                }
            }
        }

        $this->output->setVerbosity($this->verbosity);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter(): FormatterInterface
    {
        return new ConsoleFormatter(['format' => self::OUTPUT_FORMAT]);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(\Monolog\LogRecord|array $record): void
    {
        if (!$this->shouldHandle) {
            return;
        }

        $this->output->writeln($record['formatted'] ?? json_encode($record), $this->monologVerbosityMap[$record['level']]);
    }
}
