<?php

namespace App\Traits;

use Symfony\Component\Console\Style\OutputStyle;

trait OutputBufferTrait
{
    protected ?OutputStyle $output = null;

    protected array $buffer = [];

    public function setOutput(OutputStyle $output): void
    {
        $this->output = $output;
    }

    public function getOutputBuffer(bool $html = false): string
    {
        $separator = $html ? '' : PHP_EOL;

        return implode($separator, $this->buffer);
    }

    protected function output(string $text, string $method = 'writeln'): void
    {
        if (!$this->output) {
            throw new \RuntimeException('Output must be set before writing.');
        }
        if (! method_exists($this->output, $method)) {
            throw new \RuntimeException("Output method `{$method}` does not exist.");
        }

        $this->output->$method($text);
        switch ($method) {
            case 'success':
                $text = '<div class="success">' . $text . '</div>';
                break;
            case 'error':
                $text = '<div class="error">' . $text . '</div>';
                break;
            case 'warning':
                $text = '<div class="warning">' . $text . '</div>';
                break;
            case 'note':
                $text = '<div class="note">' . $text . '</div>';
                break;
            case 'info':  // Alias for note, but styled similarly or differently if needed
                $text = '<div class="info">' . $text . '</div>';
                break;
            case 'caution':
                $text = '<div class="caution">' . $text . '</div>';
                break;
            case 'title':
                $text = '<h1>' . $text . '</h1>';
                break;
            case 'section':
                $text = '<h2>' . $text . '</h2>';
                break;
            case 'writeln':
            case 'text':  // Plain text methods
            default:
                $text = '<p>' . $text . '</p>';  // Wrap plain text for better email formatting
                break;
        }
        $this->buffer[] = $text;
    }
}
