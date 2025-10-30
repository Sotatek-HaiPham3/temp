<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SortLang extends Command
{
    const SOURCE = 'resources/lang';
    const SOURCE_EXTENSION = '.json';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'localization:sort';
    private $sourcePath;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->sourcePath = base_path(self::SOURCE);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->getSourceFiles()->map(function ($file) {
            return $this->getMessages($file);
        });

        $this->info('Sorted');
        $this->call('vue-i18n:generate');
        $this->info('Generated');
    }

    private function getSourceFiles()
    {
        return collect(scandir($this->sourcePath))->filter(function ($file) {
            return ends_with($file, self::SOURCE_EXTENSION);
        })->values();
    }

    private function getMessages($file)
    {
//        $locale = str_replace(self::SOURCE_EXTENSION, '', $file);
        $path = $this->sourcePath.DIRECTORY_SEPARATOR.$file;
        $content = file_get_contents($path);
        $decodeContent = collect(json_decode($content, false, 512, JSON_UNESCAPED_UNICODE))->toArray();
        ksort($decodeContent);

        file_put_contents($path, json_encode($decodeContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
