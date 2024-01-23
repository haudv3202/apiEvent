<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailApi;
use App\Models\notification;

class SendMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:send-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentDateTime = Carbon::now()->toDateTimeString();
        $emails = notification::where('time_send', '<=', $currentDateTime)
            ->with(['event' => function ($query) {
                $query->with('attendances.user');
            }])
            ->whereNull('sent_at')
            ->get();

        if ($emails->count() > 0) {
            $notificationsToUpdate = [];
            foreach ($emails as $email) {
                $data = [
                    'title' => $email->title,
                    'message' => $email->content,
                ];
                foreach ($email->event->attendances as $userSend) {
                    Mail::to($userSend->user->email)->send(new EmailApi($data));
                }
                $notificationsToUpdate[] = $email->id;
            }
            notification::whereIn('id', $notificationsToUpdate)->update(['sent_at' => now()]);
        }

        return 0;
    }
}
