<?php

namespace Reach\StatamicEasyForms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Reach\StatamicEasyForms\EmailTemplate;
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

        $this->line('  ✓ Theme published to resources/css/easy-forms-theme.css');

        return $this;
    }

    protected function publishEmailTemplate()
    {
        $this->info('Publishing email template...');

        $path = 'resources/views/'.EmailTemplate::view().'.antlers.html';

        if (File::exists(EmailTemplate::path()) && ! $this->confirm('The email template is already published. Do you want to overwrite it?', false)) {
            $this->line("  • Kept the existing email template at {$path}");
        } else {
            $this->callSilent('vendor:publish', [
                '--tag' => 'easy-forms-emails',
                '--force' => true,
            ]);

            $this->line("  ✓ Email template published to {$path}");
        }

        $this->newLine();
        $this->comment('To send submissions with it, open a form in the control panel, go to Connect → Email and set:');
        $this->line('  • HTML view: '.EmailTemplate::view());
        $this->line('  • Text view: leave it empty, and keep Markdown off');
        $this->line('  • Message (optional): shown above the submitted answers');

        return $this;
    }
}
