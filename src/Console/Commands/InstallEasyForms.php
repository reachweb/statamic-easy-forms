<?php

namespace Reach\StatamicEasyForms\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

class InstallEasyForms extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easy-forms:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Statamic Easy Forms';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Installing Statamic Easy Forms...');
        $this->newLine();

        if ($this->confirm('Do you want to publish the form views? (recommended)', true)) {
            $this->publishViews();
        }

        if ($this->confirm('Do you want to publish the default theme? (recommended)', true)) {
            $this->publishTheme();
        }

        if ($this->confirm('Do you want to publish the email template? (recommended)', true)) {
            $this->publishEmailTemplate();
        }

        $this->newLine();
        $this->info('✓ Installation complete! Start creating beautiful, accessible forms.');
        $this->newLine();
        $this->comment('Next steps:');
        $this->line('  • Create a form in the Statamic control panel');
        $this->line('  • Add {{ easyform handle="your-form" }} to your templates');
        $this->line('  • Visit the documentation: https://easy-forms.dev');
        $this->newLine();

        return Command::SUCCESS;
    }

    protected function publishViews()
    {
        $this->info('Publishing form views...');

        $this->callSilent('vendor:publish', [
            '--tag' => 'easy-forms-views',
        ]);

        $this->line('  ✓ Views published to resources/views/vendor/statamic-easy-forms');

        return $this;
    }

    protected function publishTheme()
    {
        $this->info('Publishing default theme...');

        $this->callSilent('vendor:publish', [
            '--tag' => 'easy-forms-theme',
        ]);

        $this->line('  ✓ Theme published to resources/css/vendor/statamic-easy-forms');

        return $this;
    }

    protected function publishEmailTemplate()
    {
        $this->info('Publishing email template...');

        $this->callSilent('vendor:publish', [
            '--tag' => 'easy-forms-emails',
        ]);

        $this->line('  ✓ Email template published to resources/views/vendor/statamic-easy-forms/emails');

        return $this;
    }
}
