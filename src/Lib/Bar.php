<?php

namespace App\Lib;

use Cake\Console\ConsoleIo;


/**
 *
 * This class is modified to Cakephp from project
 *
 * @see: https://github.com/MacroMan/PHPTerminalProgressBar
 *
 */
class Bar
{

    const MOVE_START  = "\033[1G";
    const HIDE_CURSOR = "\033[?25l";
    const SHOW_CURSOR = "\033[?25h";
    const ERASE_LINE  = "\033[2K";

    /**
     * Available screen width
     *
     * @var int
     */
    private int $width;

    /**
     * Output string format
     *
     * @var string
     */
    private string $format;

    /**
     * Time the progress bar was initialised in seconds (with millisecond precision)
     *
     * @var float|string
     */
    private string|float $startTime;

    /**
     * Time since the last draw
     *
     * @var float|string
     */
    private string|float $timeSinceLastCall;

    /**
     * Pre-defined tokens in the format
     *
     * @var string[]
     */
    private array $ouputFind = [':current', ':total', ':elapsed', ':percent', ':eta', ':rate'];

    /**
     * Do not run drawBar more often than this (bypassed by interupt())
     *
     * @var float
     */
    public float $throttle = 0.1;

    /**
     * The symbol to denote completed parts of the bar
     *
     * @var string
     */
    public string $symbolComplete = '=';

    /**
     * The symbol to denote incomplete parts of the bar
     *
     * @var string
     */
    public string $symbolIncomplete = ' ';

    /**
     * Number of decimal places to use for seconds units
     *
     * @var int
     */
    public int $secondPrecision = 0;

    /**
     * Number of decimal places used for percentage units
     *
     * @var int
     */
    public int $percentPrecision = 1;

    /**
     * Current tick number
     *
     * @var int
     */
    public int $current = 0;

    /**
     * Maximum number of ticks
     *
     * @var int
     */
    public int $total = 1;

    /**
     * Seconds elapsed
     *
     * @var float
     */
    public float $elapsed = 0;

    /**
     * Current percentage complete
     *
     * @var float
     */
    public float $percent = 0;

    /**
     * Estimated time until completion
     *
     * @var float
     */
    public float $eta = 0;

    /**
     * Current rate
     *
     * @var float
     */
    public float $rate = 0;

    private ConsoleIo $io;


    public function __construct(ConsoleIo $io) {
        $this->io = $io;
    }

    public function init(array $options): void {
        $options += [
            'total'  => 100,
            'width'  => 0, // du not print
            'format' => 'Progress: [:bar] - :current/:total - :percent% - Elapsed::elapseds - ETA::etas - Rate::rate/s',
        ];

        $total  = $options['total'];
        $format = $options['format'];

        // Get the terminal width
        $width = $options['width'] ?? exec('tput cols 2>/dev/null');
        if (!is_numeric($width) || 200 < $width) {
            // Default to 80 columns, mainly for windows users with no tput
            $width = 80;
        }
        $this->width = (int)$width;

        $this->total  = (int)$total;
        $this->format = $format;

        // Initialise the display
        //$this->io->out(self::HIDE_CURSOR);
//        fwrite($this->stream, self::MOVE_START);

        // Set the start time
        $this->startTime         = microtime(true);
        $this->timeSinceLastCall = microtime(true);

        $this->drawBar();
    }

    /**
     * Increment by $amount ticks
     *
     * @param int $amount
     */
    public function tick(int $amount = 1): void {
        $this->update($this->current + $amount);
    }

    /**
     * Set the increment and re-calculate data
     *
     * @param int $amount
     */
    public function update(int $amount): void {
        $this->current = $amount;
        $drawElapse    = microtime(true) - $this->timeSinceLastCall;

        if ($drawElapse > $this->throttle) {
            $this->elapsed = microtime(true) - $this->startTime;
            $this->percent = $this->current / $this->total * 100;

            $this->rate = $this->current / $this->elapsed;
            $this->eta  = ($this->current) ? ($this->elapsed / $this->current * $this->total - $this->elapsed) : false;

            $this->drawBar();
        }
    }

    /**
     * Add a message on a newline before the progress bar
     */
    public function interupt(string $message): void {
//        fwrite($this->stream, self::MOVE_START);
//        fwrite($this->stream, self::ERASE_LINE);
//        fwrite($this->stream, $message . "\n");
        $this->io->out();
        $this->io->out($message);
        $this->drawBar();
    }

    /**
     * Does the actual drawing
     */
    private function drawBar(): void {
        $this->timeSinceLastCall = microtime(true);

        $replace = [
            str_pad($this->current, strlen((string)$this->total), ' ', STR_PAD_LEFT),
            $this->total,
            $this->roundAndPad($this->elapsed, $this->secondPrecision),
            $this->roundAndPad($this->percent, $this->percentPrecision),
            $this->roundAndPad($this->eta, $this->secondPrecision),
            $this->roundAndPad($this->rate),
        ];

        $output = str_replace($this->ouputFind, $replace, $this->format);

        if (str_contains($output, ':bar') && 0 < $this->width) {
            $done   = (int)($this->width * ($this->percent / 100));
            $left   = $this->width - $done;
            $output = str_replace(':bar', str_repeat($this->symbolComplete, max($done, 0)) . str_repeat($this->symbolIncomplete, max($left, 0)), $output);
        }

        $this->io->overwrite($output, 0);
    }

    /**
     * Adds 0 and space padding onto floats to ensure the format is fixed length nnn.nn
     *
     * @param float|int $input
     * @param int       $precision
     *
     * @return string
     */
    private function roundAndPad(float|int $input, int $precision = 1): string {
        return str_pad(number_format($input, $precision, '.', ''), 6, ' ', STR_PAD_LEFT);
    }

    /**
     * Cleanup
     */
    public function end(): void {
        $this->io->out(PHP_EOL . self::SHOW_CURSOR);
    }

    public function __destruct() {
        //$this->end();
    }

}
