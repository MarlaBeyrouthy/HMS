<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class BroadcastController extends Controller
{
    public function authenticate(Request $request)
    {
        Log::info('Pusher Key:', [env('PUSHER_APP_KEY')]);
        Log::info('Pusher Secret:', [env('PUSHER_APP_SECRET')]);
        Log::info('Pusher App ID:', [env('PUSHER_APP_ID')]);
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => true,
            ]
        );

        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        return response($pusher->socket_auth($channelName, $socketId));
    }
}
