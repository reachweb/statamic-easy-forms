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

        // Easy Forms 2 published the template to the vendor folder. With an email view folder, it's now
        // published elsewhere, so forms may still send with the old one.
        $legacyPath = resource_path('views/vendor/statamic-easy-forms/emails/form-submission.antlers.html');

        if ($legacyPath !== EmailTemplate::path() && $this->isVersion2EmailTemplate($legacyPath)) {
            $this->warn('  Forms that send with vendor/statamic-easy-forms/emails/form-submission still use the Easy Forms 2 template, which doesn\'t escape the submitted answers. Set their HTML view to '.EmailTemplate::view().' and delete the old template.');
        }

        // Replace an Easy Forms 2 template unless told otherwise, since it doesn't escape the answers
        $isVersion2 = $this->isVersion2EmailTemplate(EmailTemplate::path());

        if ($isVersion2) {
            $this->warn("  The email template at {$path} is from Easy Forms 2, which doesn't escape the submitted answers.");
        }

        if (File::exists(EmailTemplate::path()) && ! $this->confirm('The email template is already published. Do you want to overwrite it?', $isVersion2)) {
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

    /**
     * Check whether the file is the email template published by Easy Forms 2.
     */
    protected function isVersion2EmailTemplate(string $path): bool
    {
        return File::exists($path) && str_contains(File::get($path), 'real_url = config:app:url');
    }
}
