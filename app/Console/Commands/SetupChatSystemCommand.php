<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class SetupChatSystemCommand extends Command
{
    protected $signature = 'chat:setup {--force : Save without final confirmation}';

    protected $description = 'Interactive setup wizard for configuring the chat system.';

    public function handle(): int
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (! File::exists($envPath)) {
            if (! File::exists($envExamplePath)) {
                $this->error('.env file was not found and .env.example is missing.');

                return self::FAILURE;
            }

            File::copy($envExamplePath, $envPath);
            info('Created .env from .env.example');
        }

        $envContent = (string) File::get($envPath);

        intro('SChat Setup Wizard');

        $updates = array_merge(
            $this->promptApplicationSettings($envContent),
            $this->promptDatabaseSettings($envContent),
            $this->promptBroadcastSettings($envContent),
            $this->promptChatSettings($envContent),
        );

        table(
            headers: ['Setting', 'Value'],
            rows: $this->buildSummaryRows($updates),
        );

        if (! $this->option('force') && ! confirm('Apply these settings to your .env file?', default: true)) {
            warning('Setup cancelled. No changes were written.');

            return self::SUCCESS;
        }

        $updatedContent = $this->applyEnvUpdates($envContent, $updates);
        File::put($envPath, $updatedContent);
        info('Environment file updated.');

        if ($this->readEnvValue($updatedContent, 'APP_KEY') === '' && confirm('APP_KEY is empty. Generate one now?', default: true)) {
            Artisan::call('key:generate', ['--force' => true]);
            info('APP_KEY generated.');
        }

        if (confirm('Clear cached config/routes/views now (optimize:clear)?', default: true)) {
            Artisan::call('optimize:clear');
            info('Application caches cleared.');
        }

        outro('Setup complete. You can now run: php artisan migrate');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function promptApplicationSettings(string $envContent): array
    {
        $environmentOptions = [
            'local' => 'Local',
            'staging' => 'Staging',
            'production' => 'Production',
        ];

        $currentAppName = $this->readEnvValue($envContent, 'APP_NAME', (string) config('app.name', 'SChat'));
        $currentEnvironment = $this->readEnvValue($envContent, 'APP_ENV', 'local');
        $currentAppUrl = $this->readEnvValue($envContent, 'APP_URL', 'http://localhost');
        $currentAppDebug = $this->readEnvBoolean($envContent, 'APP_DEBUG', true);

        $appName = text(
            label: 'Application name',
            default: $currentAppName !== '' ? $currentAppName : 'SChat',
            hint: 'Example: SChat Production',
            required: 'Application name is required.',
        );

        $appEnvironment = select(
            label: 'Application environment',
            options: $environmentOptions,
            default: $this->resolveSelectDefault($environmentOptions, $currentEnvironment, 'local'),
        );

        $appDebug = confirm(
            label: 'Enable APP_DEBUG?',
            default: $currentAppDebug,
        );

        $appUrl = text(
            label: 'Application URL',
            default: $currentAppUrl !== '' ? $currentAppUrl : 'http://localhost',
            hint: 'Example: https://chat.example.com',
            validate: function (string $value): ?string {
                return filter_var($value, FILTER_VALIDATE_URL) === false
                    ? 'Enter a valid URL.'
                    : null;
            },
        );

        return [
            'APP_NAME' => $appName,
            'APP_ENV' => $appEnvironment,
            'APP_DEBUG' => $this->toBooleanString($appDebug),
            'APP_URL' => $appUrl,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function promptDatabaseSettings(string $envContent): array
    {
        $connectionOptions = [
            'sqlite' => 'SQLite',
            'mysql' => 'MySQL',
            'pgsql' => 'PostgreSQL',
        ];

        $currentConnection = $this->readEnvValue($envContent, 'DB_CONNECTION', (string) config('database.default', 'sqlite'));
        $connection = select(
            label: 'Database driver',
            options: $connectionOptions,
            default: $this->resolveSelectDefault($connectionOptions, $currentConnection, 'sqlite'),
        );

        $updates = [
            'DB_CONNECTION' => $connection,
        ];

        if ($connection === 'sqlite') {
            $sqlitePath = text(
                label: 'SQLite database path',
                default: $this->readEnvValue($envContent, 'DB_DATABASE', database_path('database.sqlite')),
                hint: 'Use an absolute path or project-relative path.',
                required: 'SQLite path is required.',
            );

            $updates['DB_DATABASE'] = $sqlitePath;

            return $updates;
        }

        $dbHost = text(
            label: 'Database host',
            default: $this->readEnvValue($envContent, 'DB_HOST', '127.0.0.1'),
            required: 'Database host is required.',
        );

        $defaultPort = $connection === 'pgsql' ? 5432 : 3306;
        $dbPort = $this->promptInteger(
            label: 'Database port',
            default: $this->readEnvInteger($envContent, 'DB_PORT', $defaultPort),
            min: 1,
            max: 65535,
        );

        $dbName = text(
            label: 'Database name',
            default: $this->readEnvValue($envContent, 'DB_DATABASE', 'schat'),
            required: 'Database name is required.',
        );

        $dbUser = text(
            label: 'Database username',
            default: $this->readEnvValue($envContent, 'DB_USERNAME', 'root'),
            required: 'Database username is required.',
        );

        $currentPassword = $this->readEnvValue($envContent, 'DB_PASSWORD', '');
        $dbPasswordInput = password(
            label: 'Database password (leave empty to keep current)',
            hint: $currentPassword !== '' ? 'Current password exists.' : 'No password is currently set.',
        );

        $updates['DB_HOST'] = $dbHost;
        $updates['DB_PORT'] = (string) $dbPort;
        $updates['DB_DATABASE'] = $dbName;
        $updates['DB_USERNAME'] = $dbUser;
        $updates['DB_PASSWORD'] = $dbPasswordInput !== '' ? $dbPasswordInput : $currentPassword;

        return $updates;
    }

    /**
     * @return array<string, string>
     */
    private function promptBroadcastSettings(string $envContent): array
    {
        $connectionOptions = [
            'reverb' => 'Reverb (Realtime)',
            'log' => 'Log only',
            'null' => 'Disabled',
        ];

        $currentConnection = $this->readEnvValue($envContent, 'BROADCAST_CONNECTION', 'reverb');
        $broadcastConnection = select(
            label: 'Broadcast connection',
            options: $connectionOptions,
            default: $this->resolveSelectDefault($connectionOptions, $currentConnection, 'reverb'),
        );

        $updates = [
            'BROADCAST_CONNECTION' => $broadcastConnection,
        ];

        if ($broadcastConnection !== 'reverb') {
            return $updates;
        }

        $schemeOptions = [
            'http' => 'HTTP',
            'https' => 'HTTPS',
        ];

        $reverbAppId = text(
            label: 'Reverb app ID',
            default: $this->readEnvValue($envContent, 'REVERB_APP_ID', 'schat'),
            required: 'Reverb app ID is required.',
        );

        $reverbAppKey = text(
            label: 'Reverb app key',
            default: $this->readEnvValue($envContent, 'REVERB_APP_KEY', 'schat-key'),
            required: 'Reverb app key is required.',
        );

        $currentSecret = $this->readEnvValue($envContent, 'REVERB_APP_SECRET', 'schat-secret');
        $reverbAppSecretInput = password(
            label: 'Reverb app secret (leave empty to keep current)',
            hint: 'Used to authenticate websocket clients.',
        );

        $reverbHost = text(
            label: 'Reverb host (public host for clients)',
            default: $this->readEnvValue($envContent, 'REVERB_HOST', '127.0.0.1'),
            required: 'Reverb host is required.',
        );

        $reverbPort = $this->promptInteger(
            label: 'Reverb port (public port for clients)',
            default: $this->readEnvInteger($envContent, 'REVERB_PORT', 8080),
            min: 1,
            max: 65535,
        );

        $reverbScheme = select(
            label: 'Reverb scheme',
            options: $schemeOptions,
            default: $this->resolveSelectDefault($schemeOptions, $this->readEnvValue($envContent, 'REVERB_SCHEME', 'http'), 'http'),
        );

        $reverbServerHost = text(
            label: 'Reverb server bind host',
            default: $this->readEnvValue($envContent, 'REVERB_SERVER_HOST', '0.0.0.0'),
            required: 'Reverb server host is required.',
        );

        $reverbServerPort = $this->promptInteger(
            label: 'Reverb server bind port',
            default: $this->readEnvInteger($envContent, 'REVERB_SERVER_PORT', 8080),
            min: 1,
            max: 65535,
        );

        $updates['REVERB_APP_ID'] = $reverbAppId;
        $updates['REVERB_APP_KEY'] = $reverbAppKey;
        $updates['REVERB_APP_SECRET'] = $reverbAppSecretInput !== '' ? $reverbAppSecretInput : $currentSecret;
        $updates['REVERB_HOST'] = $reverbHost;
        $updates['REVERB_PORT'] = (string) $reverbPort;
        $updates['REVERB_SCHEME'] = $reverbScheme;
        $updates['REVERB_SERVER_HOST'] = $reverbServerHost;
        $updates['REVERB_SERVER_PORT'] = (string) $reverbServerPort;
        $updates['VITE_REVERB_APP_KEY'] = '${REVERB_APP_KEY}';
        $updates['VITE_REVERB_HOST'] = '${REVERB_HOST}';
        $updates['VITE_REVERB_PORT'] = '${REVERB_PORT}';
        $updates['VITE_REVERB_SCHEME'] = '${REVERB_SCHEME}';

        return $updates;
    }

    /**
     * @return array<string, string>
     */
    private function promptChatSettings(string $envContent): array
    {
        $updates = [
            'CHAT_PUBLIC_ENABLED' => $this->toBooleanString(confirm(
                label: 'Enable public chat room?',
                default: $this->readEnvBoolean($envContent, 'CHAT_PUBLIC_ENABLED', true),
            )),
            'CHAT_ONLINE_LIST_ENABLED' => $this->toBooleanString(confirm(
                label: 'Show online users list?',
                default: $this->readEnvBoolean($envContent, 'CHAT_ONLINE_LIST_ENABLED', true),
            )),
            'CHAT_IMAGES_ENABLED' => $this->toBooleanString(confirm(
                label: 'Allow image uploads?',
                default: $this->readEnvBoolean($envContent, 'CHAT_IMAGES_ENABLED', true),
            )),
            'CHAT_AUTO_OPEN_INCOMING_PRIVATE_CHAT' => $this->toBooleanString(confirm(
                label: 'Auto-open incoming private chats?',
                default: $this->readEnvBoolean($envContent, 'CHAT_AUTO_OPEN_INCOMING_PRIVATE_CHAT', false),
            )),
            'CHAT_TTL_MINUTES' => (string) $this->promptInteger(
                label: 'Message retention time (minutes)',
                default: $this->readEnvInteger($envContent, 'CHAT_TTL_MINUTES', 120),
                min: 1,
            ),
        ];

        $configureAdvanced = confirm(
            label: 'Configure advanced chat limits now?',
            default: false,
        );

        if (! $configureAdvanced) {
            return $updates;
        }

        $updates['CHAT_MAX_TEXT_LENGTH'] = (string) $this->promptInteger(
            label: 'Maximum text message length',
            default: $this->readEnvInteger($envContent, 'CHAT_MAX_TEXT_LENGTH', 2000),
            min: 100,
        );

        $updates['CHAT_MAX_IMAGE_KB'] = (string) $this->promptInteger(
            label: 'Maximum image size (KB)',
            default: $this->readEnvInteger($envContent, 'CHAT_MAX_IMAGE_KB', 2048),
            min: 100,
        );

        $updates['CHAT_RATE_LIMIT_LOGIN_PER_MINUTE'] = (string) $this->promptInteger(
            label: 'Login attempts limit per minute',
            default: $this->readEnvInteger($envContent, 'CHAT_RATE_LIMIT_LOGIN_PER_MINUTE', 10),
            min: 1,
        );

        $updates['CHAT_RATE_LIMIT_MESSAGE_PER_MINUTE'] = (string) $this->promptInteger(
            label: 'Message send limit per minute',
            default: $this->readEnvInteger($envContent, 'CHAT_RATE_LIMIT_MESSAGE_PER_MINUTE', 30),
            min: 1,
        );

        return $updates;
    }

    private function promptInteger(string $label, int $default, int $min = 1, ?int $max = null): int
    {
        $value = text(
            label: $label,
            default: (string) $default,
            validate: function (string $value) use ($min, $max): ?string {
                if (! preg_match('/^\d+$/', $value)) {
                    return 'Enter a valid number.';
                }

                $number = (int) $value;
                if ($number < $min) {
                    return sprintf('Value must be at least %d.', $min);
                }

                if ($max !== null && $number > $max) {
                    return sprintf('Value must be at most %d.', $max);
                }

                return null;
            },
        );

        return (int) $value;
    }

    /**
     * @param  array<string, string>  $updates
     * @return array<int, array<int, string>>
     */
    private function buildSummaryRows(array $updates): array
    {
        $secretKeys = [
            'DB_PASSWORD',
            'REVERB_APP_SECRET',
            'APP_KEY',
        ];

        $rows = [];

        foreach ($updates as $key => $value) {
            $displayValue = in_array($key, $secretKeys, true)
                ? ($value !== '' ? '********' : '(empty)')
                : ($value !== '' ? $value : '(empty)');

            $rows[] = [$key, $displayValue];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $updates
     */
    private function applyEnvUpdates(string $envContent, array $updates): string
    {
        $updatedContent = $envContent;

        foreach ($updates as $key => $value) {
            $updatedContent = $this->upsertEnvValue($updatedContent, $key, $value);
        }

        return rtrim($updatedContent).PHP_EOL;
    }

    private function upsertEnvValue(string $envContent, string $key, string $value): string
    {
        $line = $key.'='.$this->formatEnvValue($value);
        $escapedKey = preg_quote($key, '/');
        $pattern = '/^'.$escapedKey.'=.*$/m';

        if (preg_match($pattern, $envContent) === 1) {
            $replaced = preg_replace($pattern, $line, $envContent, 1);

            return is_string($replaced) ? $replaced : $envContent;
        }

        return rtrim($envContent).PHP_EOL.$line.PHP_EOL;
    }

    private function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|["\'#=]/', $value) === 1) {
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return '"'.$escaped.'"';
        }

        return $value;
    }

    private function readEnvValue(string $envContent, string $key, string $default = ''): string
    {
        $escapedKey = preg_quote($key, '/');
        $pattern = '/^'.$escapedKey.'=(.*)$/m';

        if (preg_match($pattern, $envContent, $matches) !== 1) {
            return $default;
        }

        $value = trim((string) $matches[1]);

        if ($value === 'null' || $value === '(null)') {
            return '';
        }

        if (
            (Str::startsWith($value, '"') && Str::endsWith($value, '"'))
            || (Str::startsWith($value, '\'') && Str::endsWith($value, '\''))
        ) {
            $value = substr($value, 1, -1);
        }

        return str_replace(['\\"', '\\\\'], ['"', '\\'], $value);
    }

    private function readEnvBoolean(string $envContent, string $key, bool $default): bool
    {
        $value = Str::lower($this->readEnvValue($envContent, $key, $default ? 'true' : 'false'));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function readEnvInteger(string $envContent, string $key, int $default): int
    {
        $value = $this->readEnvValue($envContent, $key, (string) $default);

        if (! preg_match('/^-?\d+$/', $value)) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * @param  array<string, string>  $options
     */
    private function resolveSelectDefault(array $options, string $current, string $fallback): string
    {
        if (array_key_exists($current, $options)) {
            return $current;
        }

        if (array_key_exists($fallback, $options)) {
            return $fallback;
        }

        $firstOption = array_key_first($options);

        return is_string($firstOption) ? $firstOption : $fallback;
    }

    private function toBooleanString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
