<?php

namespace App\Commands;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use GuzzleHttp\Client;
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

        $modules = Cache::remember('modules', 1000, function () use ($driver) {
            $modules = new Client();
            $response = $modules->get("https://api-club.hotmart.com/hot-club-api/rest/v3/navigation?newContentAmount=5", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'club'          => 'acasadedavi',
                ]
            ]);
            return (json_decode($response->getBody()->getContents()));
        });

        foreach ($modules->modules as $module) {

            foreach ($module->pages as $page) {

                $page = Cache::remember("produto_{$page->hash}", 1000, function () use ($page) {
                    $modules = new Client();
                    $response = $modules->get("https://api-club.hotmart.com/hot-club-api/rest/v3/page/{$page->hash}", [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'club'          => 'acasadedavi',
                        ]
                    ]);
                    return (json_decode($response->getBody()->getContents()));
                });

                foreach ($page->mediasSrc as $media) {
//                    mkdir(storage_path("video_ts/{$media->mediaCode}"), 0755, true)

                    $mp3_url = sprintf("https://contentplayer.hotmart.com/video/%s/hls/540/segment-%s.ts", $media->mediaCode, 1);
                    $filename = storage_path(sprintf('video_ts/%s/%s.ts', $media->mediaCode, 1));

                    $modules = new Client();
                    $response = $modules->request("GET", "https://contentplayer.hotmart.com/video/prRAEBQ6R1/hls/540/segment-1.ts", [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'club'          => 'acasadedavi',
                        ],
                        'sink'    => $filename
                    ]);


                    dd($response->getBody()->getContents());
//                    file_put_contents($filename, file_get_contents("https://contentplayer.hotmart.com/video/prRAEBQ6R1/hls/540/segment-1.ts"));
                    die();
                }
            }
        }


    }

    function jqueryAjaxFinished(): callable
    {
        return static function ($driver): bool {
            return $driver->executeScript('return jQuery.active === 0;');
        };
    }
}
