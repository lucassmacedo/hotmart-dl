<?php

namespace App\Commands;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use LaravelZero\Framework\Commands\Command;

use Facebook\WebDriver\Remote\RemoteWebDriver;

class DownloadCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'download';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';
    /**
     * The description of the command.
     *
     * @var string
     */
    protected $token = 'Display an inspiring quote';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // Chromedriver (if started using --port=4444 as above)
        $serverUrl = 'http://localhost:4444';
        $driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::chrome());

        $this->token = Cache::remember('token', 1000, function () use ($driver) {
            return $this->login($driver);
        });
        dd($this->token);

        $this->get_modules_list($driver);

    }

    /**
     * @param RemoteWebDriver $driver
     */
    private function login(RemoteWebDriver $driver)
    {
        $driver->get("https://acasadedavi.club.hotmart.com/login");
        // Find search element by its id, write 'PHP' inside and submit
        $driver->findElement(WebDriverBy::name('login'))->sendKeys(env('LOGIN'));
        $driver->findElement(WebDriverBy::name('password'))->sendKeys(ENV('PASSWORD'));
        $driver->findElement(WebDriverBy::className('btn-login'))->click();
        sleep(10);
        return ($driver->manage()->getCookieNamed('hmVlcIntegration')->getValue());
    }

    /**
     * @param RemoteWebDriver $driver
     */
    private function get_modules_list(RemoteWebDriver $driver)
    {

    }

    function jqueryAjaxFinished(): callable
    {
        return static function ($driver): bool {
            return $driver->executeScript('return jQuery.active === 0;');
        };
    }
}
