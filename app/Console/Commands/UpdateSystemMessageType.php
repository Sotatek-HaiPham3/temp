<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SessionSystemMessage;
use App\Consts;

class UpdateSystemMessageType extends Command
{
    const MESSAGE_SESSION_BOOK_NOW = 'system_message.session.book_now';
    const MESSAGE_SESSION_BOOK = 'system_message.session.book';
    const MESSAGE_SESSION_ACCEPT = 'system_message.session.accept';
    const MESSAGE_SESSION_REJECT = 'system_message.session.reject';
    const MESSAGE_SESSION_SYSTEM_REJECT = 'system_message.session.system_reject';
    const MESSAGE_SESSION_REQUEST_NEXT_GAME = 'system_message.session.request.next_game';
    const MESSAGE_SESSION_CONFIRM_NEXT_GAME = 'system_message.session.confirm.next_game';
    const MESSAGE_SESSION_REQUEST_NEXT_FINAL_GAME = 'system_message.session.request.next_final_game';
    const MESSAGE_SESSION_CONFIRM_NEXT_FINAL_GAME = 'system_message.session.confirm.next_final_game';
    const MESSAGE_SESSION_ADD_REQUEST = 'system_message.session.add_request';
    const MESSAGE_SESSION_ADD_REQUEST_REJECT = 'system_message.session.add_request.reject';
    const MESSAGE_SESSION_ADD_REQUEST_ACCEPT = 'system_message.session.add_request.accept';
    const MESSAGE_SESSION_COMPLETE = 'system_message.session.complete';
    const MESSAGE_SESSION_GAMELANCER_STOP = 'system_message.session.gamelancer.stop';
    const MESSAGE_SESSION_USER_STOP = 'system_message.session.user.stop';
    const MESSAGE_SESSION_GAMELANCER_REVIEW = 'system_message.session.gamelancer.review';
    const MESSAGE_SESSION_USER_REVIEW = 'system_message.session.user.review';
    const MESSAGE_SESSION_OUTDATED = 'system_message.session.outdate';
    const MESSAGE_SESSION_STARTING = 'system_message.session.starting';
    const MESSAGE_SESSION_CANCEL = 'system_message.session.cancel';
    const MESSAGE_SESSION_RESTART = 'system_message.session.restart';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session_system_message:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update session system message type';

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
        $messages = SessionSystemMessage::whereNull('message_type')->get();
        foreach ($messages as $message) {
            switch ($message->message_key) {
                case self::MESSAGE_SESSION_RESTART:
                case self::MESSAGE_SESSION_BOOK_NOW:
                case self::MESSAGE_SESSION_BOOK:
                    $type = Consts::MESSAGE_TYPE_BOOK_SESSION;
                    break;
                case self::MESSAGE_SESSION_ACCEPT:
                    $type = Consts::MESSAGE_TYPE_ACCEPT_BOOK_NOW;
                    break;
                case self::MESSAGE_SESSION_REJECT:
                case self::MESSAGE_SESSION_SYSTEM_REJECT:
                    $type = Consts::MESSAGE_TYPE_REJECT_BOOK;
                    break;
                case self::MESSAGE_SESSION_ADD_REQUEST:
                    $type = Consts::MESSAGE_TYPE_ADD_TIME;
                    break;
                case self::MESSAGE_SESSION_ADD_REQUEST_REJECT:
                case self::MESSAGE_SESSION_ADD_REQUEST_ACCEPT:
                    $type = Consts::MESSAGE_TYPE_RESPONSE_ADD_TIME;
                    break;
                case self::MESSAGE_SESSION_COMPLETE:
                    $type = Consts::MESSAGE_TYPE_COMPLETE_SESSION;
                    break;
                case self::MESSAGE_SESSION_GAMELANCER_STOP:
                case self::MESSAGE_SESSION_USER_STOP:
                    $type = Consts::MESSAGE_TYPE_STOP_SESSION;
                    break;
                case self::MESSAGE_SESSION_GAMELANCER_REVIEW:
                case self::MESSAGE_SESSION_USER_REVIEW:
                    $type = Consts::MESSAGE_TYPE_REVIEW_SESSION;
                    break;
                case self::MESSAGE_SESSION_OUTDATED:
                    $type = Consts::MESSAGE_TYPE_OUTDATED_SESSION;
                    break;
                case self::MESSAGE_SESSION_STARTING:
                    $type = Consts::MESSAGE_TYPE_START_SESSION;
                    break;
                case self::MESSAGE_SESSION_CANCEL:
                    $type = Consts::MESSAGE_TYPE_CANCEL_BOOK;
                    break;
                default:
                    $type = Consts::MESSAGE_TYPE_TEXT_MESSAGE;
                    break;
            }

            $message->message_type = $type;
            $message->save();
        }
    }
}
