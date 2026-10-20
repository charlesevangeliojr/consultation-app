<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GeneratePushKeysTest extends TestCase
{
    private function environment(string $content): string
    {
        $directory = storage_path('framework/testing');
        File::ensureDirectoryExists($directory);
        $path = tempnam($directory, 'push-test-env-');
        file_put_contents($path, $content);
        $this->app->useEnvironmentPath(dirname($path));
        $this->app->loadEnvironmentFrom(basename($path));
        $this->beforeApplicationDestroyed(fn () => unlink($path));
        return $path;
    }

    public function test_write_saves_keys_without_printing_them_or_changing_other_environment_settings(): void
    {
        $path = $this->environment("APP_NAME=PreserveMe\nWEBPUSH_ENABLED=false\nWEBPUSH_PUBLIC_KEY=\nWEBPUSH_PRIVATE_KEY=\n");
        $this->artisan('push:generate-keys', ['--write' => true])->assertSuccessful();
        $content = file_get_contents($path);
        preg_match('/WEBPUSH_PUBLIC_KEY=([^\n]+)/', $content, $public);
        preg_match('/WEBPUSH_PRIVATE_KEY=([^\n]+)/', $content, $private);
        $this->assertSame(87, strlen($public[1]));
        $this->assertSame(43, strlen($private[1]));
        $this->assertStringContainsString('APP_NAME=PreserveMe', $content);
        $this->assertStringContainsString('WEBPUSH_ENABLED=false', $content);
        $this->assertStringNotContainsString($private[1], Artisan::output());
        $this->artisan('push:generate-keys', ['--write' => true])->assertSuccessful();
        $this->assertSame($content, file_get_contents($path));
    }

    public function test_existing_or_incomplete_keys_are_never_overwritten(): void
    {
        $content = "WEBPUSH_PUBLIC_KEY=existing-public\nWEBPUSH_PRIVATE_KEY=existing-private\n";
        $path = $this->environment($content);
        $this->artisan('push:generate-keys', ['--write' => true])->assertSuccessful();
        $this->assertSame($content, file_get_contents($path));
        $incomplete = "WEBPUSH_PRIVATE_KEY=existing-private\n";
        file_put_contents($path, $incomplete);
        $this->artisan('push:generate-keys', ['--write' => true])->assertFailed();
        $this->assertSame($incomplete, file_get_contents($path));
    }
}
