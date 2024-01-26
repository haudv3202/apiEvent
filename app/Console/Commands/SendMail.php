<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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
                if($email->event->attendances->count() > 0 && $email->event->count() > 0) {
                    foreach ($email->event->attendances as $userSend) {
                        if($userSend->user->count() > 0 ){
                            Mail::to($userSend->user->email)->send(new EmailApi($data));
                        }else {
                            Log::info('Truy xuất người dùng không tồn tại');
                        }

                    }
                    $notificationsToUpdate[] = $email->id;
                }else {
                    Log::info('Không có người tham gia sự kiện hoặc sự kiện không tồn tại');
                }
            }
            notification::whereIn('id', $notificationsToUpdate)->update(['sent_at' => now()]);
        }else {
            Log::info("Không có email nào cần gửi vào lúc " . $currentDateTime);
        }

        return 0;
    }
}
