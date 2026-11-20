<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * .env.example is the only list of what a new server needs. It fell behind
 * by half once (#2403), so it is checked against what config/ actually reads.
 */
class EnvExampleTest extends TestCase
{
    /**
     * Settings read by a package's own config file rather than one in config/.
     */
    private const READ_BY_PACKAGES = ['NOCAPTCHA_SITEKEY', 'NOCAPTCHA_SECRET'];

    public function test_every_setting_config_reads_is_in_the_example(): void
    {
        $missing = array_diff($this->settingsReadByConfig(), $this->settingsInExample());

        $this->assertSame(
            [],
            array_values($missing),
            'Add these to .env.example, commented out with the default if they are optional.'
        );
    }

    public function test_the_example_has_no_settings_nothing_reads(): void
    {
        $unread = array_diff($this->settingsInExample(), $this->settingsReadByConfig(), self::READ_BY_PACKAGES);

        $this->assertSame(
            [],
            array_values($unread),
            'Nothing in config/ reads these. Remove them from .env.example, or list them in READ_BY_PACKAGES if a package does.'
        );
    }

    public function test_env_is_only_read_inside_config(): void
    {
        // env() returns null outside config files once the config is cached, as it is in production
        $offenders = [];
        $root = dirname(__DIR__, 2);

        foreach (['app', 'routes', 'resources/views', 'database'] as $directory) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/'.$directory, \FilesystemIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if ($file->getExtension() === 'php' && preg_match('/(?<![\w>:$])env\(\s*[\'"]/', (string) file_get_contents($file->getPathname()))) {
                    $offenders[] = substr($file->getPathname(), strlen($root) + 1);
                }
            }
        }

        $this->assertSame([], $offenders, 'Read these through config() instead of env().');
    }

    /**
     * @return list<string>
     */
    private function settingsReadByConfig(): array
    {
        $names = [];

        foreach (glob(dirname(__DIR__, 2).'/config/*.php') ?: [] as $file) {
            preg_match_all('/\benv\(\s*[\'"]([A-Z][A-Z0-9_]*)[\'"]/', (string) file_get_contents($file), $matches);
            $names = array_merge($names, $matches[1]);
        }

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }

    /**
     * Set or commented out: "KEY=value" and "# KEY=value" both count.
     *
     * @return list<string>
     */
    private function settingsInExample(): array
    {
        preg_match_all('/^#?\s*([A-Z][A-Z0-9_]*)=/m', (string) file_get_contents(dirname(__DIR__, 2).'/.env.example'), $matches);

        $names = array_values(array_unique($matches[1]));
        sort($names);

        return $names;
    }
}
