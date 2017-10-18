<?php

declare(strict_types = 1);

namespace InteractiveSolutions\HoneycombScripts\app\commands;

use InteractiveSolutions\HoneycombCore\Console\HCCommand;

/**
 * Class HCEnv
 * @package InteractiveSolutions\HoneycombScripts\commands
 */
class HCEnv extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates honeycomb optimized project .env file with your given configuration';

    /**
     * Environment file data holder
     *
     * @var
     */
    private $envData;

    /**
     * Execute the console command.
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $this->welcomeMessage();
        $this->info("Environment configuration:");

        if (file_exists('.env')) {
            unlink('.env');
        }

        $this->configureEnvironment();

        // generate application key
        $this->call('key:generate');

        $this->comment('-');

        $this->info("Next step during installation: \n");
        $this->info("php artisan hc:update \n");
    }

    /**
     * Shows welcome message
     */
    protected function welcomeMessage()
    {
        $this->comment('');
        $this->comment('**************************************************');
        $this->comment(' Welcome to HoneyComb CMS initial configuration!!!');
        $this->comment('**************************************************');
        $this->info('');
    }

    /**
     * Database configuration
     */
    protected function configureEnvironment()
    {
        $this->configureApp();
        $this->configureDriverSettings();
        $this->configureDatabase();
        $this->configureMailSettings();

        if (!$this->_createEnvFile()) {
            $this->info('');
            $this->error('File not created');
            exit;
        }

        $this->info('');
    }

    /**
     * App configuration
     */
    protected function configureApp()
    {
        $this->envData['APP_ENV'] = $this->choice("Choose environment. [local|production]", ['local', 'production'], 0);
        $this->envData['APP_KEY'] = "";
        $this->envData['APP_DEBUG'] = $this->confirm("Enable debugging?", 'yes') ? "true" : "false";
        $this->envData['APP_URL'] = http_validate($this->ask("Your application url", "http://localhost") . "\n");
        $this->info('By default application presumes the page is http://');
        //TODO implement the letsEncrypt
    }

    /**
     * Driver settings configuration
     */
    protected function configureDriverSettings()
    {
        $this->envData['CACHE_DRIVER'] = "file";
        $this->envData['SESSION_DRIVER'] = "file";
        $this->envData['QUEUE_DRIVER'] = "sync\n";
    }

    /**
     * Configure database settings
     *
     * @return mixed
     */
    private function configureDatabase()
    {
        $this->comment("Database configuration:");

        $db['host'] = $this->choice("Database hostname: ", ['localhost', 'custom'], 0);
        $db['name'] = $this->ask("Database name: ");
        $db['username'] = $this->ask("Database username: ");
        $db['password'] = $this->secret("Database password: ");

        if (!$this->_connected($db)) {
            $this->info('');
            $this->error('Not connected to database');
            $this->info('');

            return $this->configureDatabase();
        }

        $this->envData['DB_HOST'] = $db['host'];
        $this->envData['DB_DATABASE'] = $db['name'];
        $this->envData['DB_USERNAME'] = $db['username'];
        $this->envData['DB_PASSWORD'] = $db['password'] . "\n";

        $this->info('');
        $this->comment('Database configured successfully!');
        $this->info('');
    }

    /**
     * Checks if connected to db
     *
     * @param $db
     * @return bool
     */
    private function _connected(array $db)
    {
        try {
            $connection = mysqli_connect($db['host'], $db['username'], $db['password'], $db['name']);

            return $connection ? true : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Configure mail driver settings
     *
     * @return array
     */
    private function configureMailSettings()
    {
        $this->comment('Configure mail driver settings');

        $choice = $this->choice("Choose MAIL DRIVER. [log|mandrill|mailgun|sparkpost|set_up_later]",
            ['log', 'mailgun', 'mandrill', 'sparkpost', 'set_up_later'], 0);

        switch ($choice) {

            case 'log' :
                $this->envData['MAIL_DRIVER'] = 'log';

                break;

            case 'mandrill' :
                $this->envData['MAIL_DRIVER'] = 'mandrill';
                $this->envData['MANDRILL_SECRET'] = $this->ask("Mandrill secret code: ") . "\n\n";

                break;

            case 'mailgun' :
                $this->envData['MAIL_DRIVER'] = 'mailgun';
                $this->envData['MAILGUN_DOMAIN'] = $this->ask("Mailgun domain: ");
                $this->envData['MAILGUN_SECRET'] = $this->ask("Mailgun secret: ") . "\n\n";

                break;

            case 'sparkpost' :
                $this->envData['MAIL_DRIVER'] = 'sparkpost';
                $this->envData['SPARKPOST_SECRET'] = $this->ask("Sparkpost secret: ") . "\n\n";

                break;

            default:
                break;
        }
    }

    /**
     * Function which creates .env file
     *
     * @return bool
     */
    private function _createEnvFile()
    {
        $fileName = '.env';

        $content = "";

        foreach ($this->envData as $key => $value) {
            $content .= "$key=$value\n";
        }

        $path = base_path($fileName);

        if (file_put_contents($path, $content)) {
            return true;
        }

        return false;
    }
}
