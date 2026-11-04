<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('muk:check-environment')]
#[Description('Command description')]
class CheckMukEnvironmentCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
