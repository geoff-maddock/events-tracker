<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Illuminate\Console\Command;
use Storage;

/**
 * Sets files to public.
 */
class PublicizeFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:publicize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets all files as public';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $access = 'public';

        // get all the external files
        $photos = Photo::orderBy('name', 'ASC')->get();
        foreach ($photos as $photo) {
            $this->info(sprintf('The photo %s was set to %s', $photo->name, $access));
            Storage::disk('external')->setVisibility($photo->getStorageThumbnail(), $access);
            Storage::disk('external')->setVisibility($photo->getStoragePath(), $access);
        }
    }
}
