<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GeneratePushKeys extends Command
{
    protected $signature = 'push:generate-keys {--write : Save a new pair to .env without printing keys or replacing an existing pair}';
    protected $description = 'Generate a Web Push key pair for private deployment configuration';

    public function handle(): int
    {
        if ($this->option('write')) return $this->writeKeys();
        $this->warn('Keep the private key secret. Save these values in the hosted .env, never Git or a public file.');
        $keys = VAPID::createVapidKeys();
        $this->line('WEBPUSH_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('WEBPUSH_PRIVATE_KEY='.$keys['privateKey']);
        $this->info('Set WEBPUSH_ENABLED=true and WEBPUSH_SUBJECT to a real mailto: contact or HTTPS site URL.');
        return self::SUCCESS;
    }

    private function writeKeys(): int
    {
        $path = $this->laravel->environmentFilePath();
        if (!is_file($path) || !is_writable($path)) {
            $this->error('A writable .env file is required. No keys were saved.');
            return self::FAILURE;
        }
        $content = file_get_contents($path);
        $lines = preg_split('/\r\n|\n|\r/', $content);
        $existing = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*(?:export\s+)?(WEBPUSH_PUBLIC_KEY|WEBPUSH_PRIVATE_KEY)\s*=\s*(.*)$/', $line, $matches)) {
                $value = trim($matches[2], " \t\"'");
                if ($value !== '') $existing[$matches[1]] = $value;
            }
        }
        if (!empty($existing['WEBPUSH_PUBLIC_KEY']) && !empty($existing['WEBPUSH_PRIVATE_KEY'])) {
            $this->info('An existing Web Push key pair was preserved. No keys were printed or changed.');
            return self::SUCCESS;
        }
        if (!empty($existing['WEBPUSH_PUBLIC_KEY']) || !empty($existing['WEBPUSH_PRIVATE_KEY'])) {
            $this->error('An incomplete key pair exists. Nothing was overwritten; recover the matching pair before continuing.');
            return self::FAILURE;
        }
        $keys = VAPID::createVapidKeys();
        $values = ['WEBPUSH_PUBLIC_KEY' => $keys['publicKey'], 'WEBPUSH_PRIVATE_KEY' => $keys['privateKey']];
        $written = [];
        foreach ($lines as &$line) {
            foreach ($values as $name => $value) {
                if (preg_match('/^\s*(?:export\s+)?'.preg_quote($name, '/').'\s*=/', $line)) {
                    $line = $name.'='.$value;
                    $written[$name] = true;
                    break;
                }
            }
        }
        unset($line);
        foreach ($values as $name => $value) if (!isset($written[$name])) $lines[] = $name.'='.$value;
        $newline = str_contains($content, "\r\n") ? "\r\n" : "\n";
        $this->laravel->make('files')->replace($path, rtrim(implode($newline, $lines), "\r\n").$newline, 0600);
        $this->info('Generated and saved Web Push keys to the local .env. No keys were printed.');
        $this->line('WEBPUSH_ENABLED and WEBPUSH_SUBJECT were left unchanged. Keep this file private.');
        return self::SUCCESS;
    }
}
