<?php

namespace App\Console\Commands;

use App\Services\RegistrationTokenService;
use Illuminate\Console\Command;

class RotateRegistrationTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lsp:rotate-registration-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate/randomize the weekly asesi registration token (proindo + 4 digits)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $newToken = RegistrationTokenService::generateNewToken();

        $this->info("Token registrasi asesi berhasil diperbarui: {$newToken}");

        return self::SUCCESS;
    }
}
