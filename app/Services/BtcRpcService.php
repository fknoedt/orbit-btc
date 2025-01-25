<?php

namespace App\Services;

/**
 * @see https://developer.bitcoin.org/reference/rpc/index.html
 * @see https://github.com/denpamusic/laravel-bitcoinrpc
 */
class BtcRpcService
{
    /**
     * Get list of commands from BTC-RPC's `help` endpoint, parse and return them as an array group by topics
     */
    public function help(string $search = null): array
    {
        $group = '';
        $commands = [];
        foreach (explode(PHP_EOL, bitcoind()->help()->get()) as $line) {
            if (str_starts_with($line, '== ')) {
                $group = trim(str_replace('==', '', $line));
                continue;
            }
            $line = str_replace(['( ', ' )'], '', $line);
            if (empty($line)) {
                continue;
            }
            if ($search && !str_contains($line, $search)) {
                continue;
            }
            $commands[$group][] = explode(' ', $line);
        }

        return $commands;
    }

    public function getCommands(string $search = null): array
    {
        $commands = [];
        foreach ($this->help($search) as $command) {
            $commands = array_merge($commands, array_column($command, 0 ));
        }

        return $commands;
    }

    public function getCommandsWithArguments(): array
    {
        $commands = [];
        foreach ($this->help() as $topicCommands) {
            foreach ($topicCommands as $topicCommand) {
                $commands[] = [
                    'command' => $topicCommand[0],
                    'arguments' => implode(' ', array_slice($topicCommand, 1)),
                    'can_run' => count($topicCommand) === 1
                ];
            }
        }

        return $commands;
    }
}
