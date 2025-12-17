<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckEmailConfig extends Command
{
    protected $signature = 'email:check';
    protected $description = 'Check email configuration and test connection';

    public function handle()
    {
        $this->info('=== Email Configuration Check ===');
        $this->newLine();
        
        // Check configuration
        $this->info('Mail Driver: ' . config('mail.default'));
        $this->info('SMTP Host: ' . config('mail.mailers.smtp.host'));
        $this->info('SMTP Port: ' . config('mail.mailers.smtp.port'));
        $this->info('SMTP Encryption: ' . (config('mail.mailers.smtp.encryption') ?: 'none'));
        $this->info('SMTP Username: ' . config('mail.mailers.smtp.username'));
        $this->info('SMTP Password: ' . (config('mail.mailers.smtp.password') ? '***SET***' : 'NOT SET'));
        $this->info('From Address: ' . config('mail.from.address'));
        $this->info('From Name: ' . config('mail.from.name'));
        $this->info('Frontend URL: ' . env('FRONTEND_URL', 'NOT SET'));
        
        $this->newLine();
        
        if (!config('mail.mailers.smtp.username') || !config('mail.mailers.smtp.password')) {
            $this->error('✗ Email credentials not configured properly!');
            return 1;
        }
        
        $this->info('✓ Configuration looks good!');
        $this->newLine();
        
        // Test email sending
        if ($this->confirm('Do you want to send a test email?', true)) {
            $email = $this->ask('Enter recipient email address', config('mail.from.address'));
            
            try {
                $this->info('Sending test email to: ' . $email);
                
                Mail::raw('This is a test email from your Laravel application. If you received this, your email configuration is working correctly!', function ($message) use ($email) {
                    $message->to($email)
                            ->subject('Test Email - Configuration Check');
                });
                
                $this->info('✓ Test email sent successfully!');
                $this->info('Please check the inbox (and spam folder) at: ' . $email);
                return 0;
            } catch (\Exception $e) {
                $this->error('✗ Failed to send test email');
                $this->error('Error: ' . $e->getMessage());
                $this->newLine();
                $this->info('Common solutions:');
                $this->info('1. Check if 2-Step Verification is enabled for Gmail');
                $this->info('2. Use App Password instead of regular password');
                $this->info('3. Try port 465 with SSL encryption instead of 587 with TLS');
                $this->info('4. Check Laravel logs: storage/logs/laravel.log');
                return 1;
            }
        }
        
        return 0;
    }
}
