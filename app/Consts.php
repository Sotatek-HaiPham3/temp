<?php

namespace App;

class Consts
{
    const VERIFY_CODE_LENGTH = 6;
    const VERIFY_CODE_STRING = '0123456789';
    const VERIFY_CODE_TIME_LIVE = 1; // check token expired - 1day
    const TRUE = 1;
    const FALSE = 0;

    const PASSWORD_SOCIAL_LENGTH = 20;

    const EXCEPTION_REPORT_EMAIL = 'server-error-alert@gamelancer.pagerduty.com';

    const RC_PROCESS_SESSION_RUNNING = 'process_session_running';
    const RC_SESSION_CHECK_READY = 'session_check_ready';
    const RC_CHECK_BOUNTY_EXPIRED_TIME = 'check_bounty_expired_time';
    const RC_CHECK_SESSION_BOOK_NOW = 'check_session_response_invitation';
    const RC_CHECK_SESSION_SCHEDULE_EXPIRED_TIME = 'check_session_schedule_expried_time';
    const RC_CHECK_VOICE_OUTDATED = 'check_voice_outdated';
    const RC_USER_CHANNELS = 'user_channels';
    const RC_USER_FORUMS = 'user_forums';
    const RC_USER_VIDEOS = 'user_videos';

    const DEFAULT_BOUNTY_TYPES = 1;

    const CHAR_COMMA = ',';
    const CHAR_COLON = ':';
    const CHAR_SPACE = ' ';
    const CHAR_UNDERSCORE = '_';
    const STRING_EMPTY = '';
    const CHAR_HYPHEN = '-';

    const ADDRESS_UNKNOWN = 'Unknown';

    const CURRENCY_USD  = 'usd';
    const CURRENCY_EUR  = 'eur';
    const CURRENCY_COIN = 'coin';
    const CURRENCY_BAR  = 'bar';
    const APP_CURRENCY  = 'usd';

    const CURRENCY_EXP  = 'exp';

    const USER_ACTIVE = 'active';
    const USER_INACTIVE = 'inactive';

    const DEFAULT_LOCALE = 'en';

    const DEFAULT_PER_PAGE = 10;

    const AUTH_ROUTE_RESET_PASSWORD = '/restore-password?token=';
    const AUTH_ROUTE_CONFIRM_EMAIL = '/login?code=';
    const AUTH_ROUTE_VERIFY_EMAIL = '/verify-account/';
    const AUTH_ROUTE_CLAIMED_BOUNTY = '?token=';
    const AUTH_ROUTE_VERIFY_CHANGE_EMAIL = '/verify-change-email?code=';
    const AUTH_ROUTE_VERIFY_CHANGE_USERNAME = '/verify-change-username?code=';
    const AUTH_ROUTE_VERIFY_CHANGE_PHONE = '/verify-change-phone?code=';
    const AUTH_ROUTE_VERIFY_ACCOUNT_CHECKING = '/verify-checking?email=%s&code=%s&vip=%s';

    const PAYMENT_DEPOSIT_MESSAGE = 'Gamelancer deposit';

    const PAYMENT_TRANSACTION_STATUS = [
        'PENDING' => 'PENDING',
        'SUCCEEDED' => 'SUCCEEDED',
    ];
    const PAYPAL_CHECKOUT_TYPE = 'sale';

    const PAYPAL_TRANSACTION_EXPIRED_TIME = 10 * 60 * 1000; // 10 minutes

    const STRIPE_TRANSACTION_STATUS_SUCCESS = 'succeeded';

    const PAYMENT_SERVICE_TYPE_STRIPE   = 'stripe';
    const PAYMENT_SERVICE_TYPE_PAYPAL   = 'paypal';
    const PAYMENT_SERVICE_TYPE_INTERNAL = 'internal';
    const PAYMENT_SERVICE_TYPE_CONVERT = 'convert currency';
    const PAYMENT_SERVICE_TYPE_GLBAR = 'glbar';
    const PAYMENT_SERVICE_TYPE_IAP = 'IAP Purchase';

    const PAYMENT_ORDER_FAILED = 'failed';

    const PAYMENT_SALE_COMPLETED = 'PAYMENT.SALE.COMPLETED';
    const PAYMENT_SALE_DENIED    = 'PAYMENT.SALE.DENIED';
    const PAYMENT_SALE_PENDING   = 'PAYMENT.SALE.PENDING';

    const PAYMENT_PAYOUTSBATCH_SUCCESS = 'PAYMENT.PAYOUTSBATCH.SUCCESS';
    const PAYMENT_PAYOUTSBATCH_PROCESSING = 'PAYMENT.PAYOUTSBATCH.PROCESSING';
    const PAYMENT_PAYOUTSBATCH_DENIED = 'PAYMENT.PAYOUTSBATCH.DENIED';

    const PAYMENT_MAX_DEPOSIT_STRIPE = 1000000;
    const PAYMENT_MAX_DEPOSIT_PAYPAL = 10000000;
    const CARD_ZIP_MAX_LENGTH = 5;
    const CARD_NUMBER_MAX_LENGTH = 20;
    const CARD_CVC_MAX_LENGTH = 3;
    const MAX_MONTH = 12;
    const MIN_YEAR = 19;

    const TRANSACTION_TYPE_DEPOSIT = 'deposit';
    const TRANSACTION_TYPE_WITHDRAW = 'withdraw';
    const TRANSACTION_TYPE_CONVERT = 'convert';

    const TRANSACTION_STATUS_SUCCESS    = 'success';
    const TRANSACTION_STATUS_PENDING    = 'pending';
    const TRANSACTION_STATUS_REJECTED   = 'rejected';
    const TRANSACTION_STATUS_FAILED     = 'failed';
    const TRANSACTION_STATUS_CREATING   = 'creating';
    const TRANSACTION_STATUS_CREATED    = 'created';
    const TRANSACTION_STATUS_EXECUTING  = 'executing';
    const TRANSACTION_STATUS_DENIED     = 'denied';
    const TRANSACTION_STATUS_CANCEL     = 'cancel';

    const TRANSACTION_MEMO_DEPOSIT_SUCCESS  = 'Deposit Successful';
    const TRANSACTION_MEMO_WITHDRAW_WAITING = 'Waiting For Confirmation';
    const TRANSACTION_MEMO_WITHDRAW_REJECT  = 'Admin Rejected';
    const TRANSACTION_MEMO_WITHDRAW_EXECUTING = 'Withdrawal Executing';
    const TRANSACTION_MEMO_WITHDRAW_FAILED  = 'Withdrawal Failed';
    const TRANSACTION_MEMO_WITHDRAW_SUCCESS = 'Withdrawal Successful';
    const TRANSATION_MEMO_DEPOSIT           = '%s Coins Bundle';
    const TRANSATION_MEMO_WITHDRAW          = 'Cash out';
    const TRANSATION_MEMO_CONVERT           = 'Exchange';
    const TRANSATION_MEMO_CLAIMED_BOUNTY    = 'Claimed Bounty';
    const TRANSATION_MEMO_RECEIVED_BOUNTY   = 'Received Bounty';
    const TRANSATION_MEMO_SERVICE_FEE       = 'Service Fees';
    const TRANSATION_MEMO_TIP               = 'Tip';

    const MASTERDATA_TABLES = [
        'games',
        'platforms',
        'banners',
        'languages',
        'settings',
        'coin_price_settings',
        'user_levels_meta',
        'offers',
        'exchange_offers',
        'social_networks_link',
        'reasons',
        'review_tags',
        'video_tags',
        'rankings',
        'sms_whitelists'
    ];

    const SESSION_STATUS_BOOKED          = 'booked';
    const SESSION_STATUS_ACCEPTED        = 'accepted';
    const SESSION_STATUS_REJECTED        = 'rejected';
    const SESSION_STATUS_CANCELED        = 'canceled';
    const SESSION_STATUS_STARTING        = 'starting';
    const SESSION_STATUS_RUNNING         = 'running';
    const SESSION_STATUS_STOPPED         = 'stopped';
    const SESSION_STATUS_COMPLETED       = 'completed';
    const SESSION_STATUS_OUTDATED        = 'outdated';
    const SESSION_STATUS_MARK_COMPLETED  = 'mark_completed';

    const CLAIM_BOUNTY_FEE                  = 0.3; // Fee 30% for each transaction;
    const CLAIM_BOUNTY_ESCROW_RATIO         = 1; // Escrow ratio % value of bounty when claimed bounty;
    const INDEMNIFY_BOUNTY_CREATOR_RATIO    = 1; // Indemnify ratio when claimer is breack a contract.

    const SOCKET_CHANNEL_USER = 'App.User.';

    const QUEUE_SOCKET = 'socket';
    const CONNECTION_SOCKET = 'sync';

    const QUEUE_EXCEPTION_EMAIL = 'pager_duty';

    const ROLE_SUPER_ADMIN  = 'Super Admin';
    const ROLE_ADMIN        = 'Admin';

    const CHAR_PASSWORD_HIDDEN = '$2y$10$AZwQOZgRySHQRcjTKrxOqenIBOEk.dEAhEbjuhMPx/r.KYdBsMj9S';//'Password'

    const BOUNTY_TIME_INCREMENT = 10; // 10 minutes

    const MAIL_PROCESSING = 'processing'; // mail processing
    const MAIL_FAIL = 'fail'; // send mail status is fail
    const MAIL_SUCCESS = 'success'; //send mail status is success

    const REPORT_TYPE = 'report';
    const REPORT_TYPE_RATE = 'rating';

    const PAYMENT_TYPE_PAYPAL           = 'paypal';
    const PAYMENT_TYPE_CREDIT_CARD      = 'credit card';
    const PAYMENT_TYPE_IBAN             = 'iban';

    const STRIPE_WEBHOOK_EVENT_PAYOUT_FAILED    = 'payout.failed';
    const STRIPE_WEBHOOK_EVENT_PAYOUT_PAID      = 'payout.paid';
    const STRIPE_WEBHOOK_EVENT_CHARGE_SUCCEEDED = 'charge.succeeded';
    const STRIPE_WEBHOOK_EVENT_CHARGE_FAILED    = 'charge.failed';
    const STRIPE_WEBHOOK_EVENT_PAYMENT_INTENT_CREATED = 'payment_intent.created';
    const STRIPE_WEBHOOK_EVENT_PAYMENT_INTENT_SUCCEEDED = 'payment_intent.succeeded';

    const STRIPE_DESCRIPTION_DEPOSIT_WITHOUT_LOGGED = 'This customer using for deposit without logged!';

    const REGEX_REMOVE_SPECIAL_CHAR    = '/^[\pZ\p{Cc}\x{feff}]+|[\pZ\p{Cc}\x{feff}]+$/ux';

    const GAMELANCER_INFO_STATUS_PENDING  = 'pending';
    const GAMELANCER_INFO_STATUS_APPROVED = 'approved';
    const GAMELANCER_INFO_STATUS_APPROVED_FREEGAMELANCER = 'freegamelancer';
    const GAMELANCER_INFO_STATUS_REJECTED = 'rejected';

    const SESSION_ADDING_REQUEST_STATUS_PENDING  = 'pending';
    const SESSION_ADDING_REQUEST_STATUS_APPROVED = 'approved';
    const SESSION_ADDING_REQUEST_STATUS_REJECTED = 'rejected';

    const GAME_TYPE_HOUR        = 'hour';
    const GAME_TYPE_PER_GAME    = 'per_game';

    const GAME_PROFILE_MEDIA_STORAGE    = 'game_profile_media';
    const GAMELANCER_AUDIO_STORAGE      = 'gamelancer_audio';

    const GAME_PROFILE_MEDIA_TYPE_IMAGE = 'image';
    const GAME_PROFILE_MEDIA_TYPE_VIDEO = 'video';

    const USER_MEDIA_PHOTO = 'image';
    const USER_MEDIA_VIDEO = 'video';
    const USER_PHOTO = 'user_photo';
    const GAME_PROFILE_MEDIA = 'game_profile_media';

    const OBJECT_TYPE_SESSION = 'session';
    const OBJECT_TYPE_BOUNTY = 'bounty';
    const OBJECT_TYPE_VIDEO = 'video';
    const OBJECT_TYPE_TIP = 'tip';

    const TRANSACTION_TYPE_TIP  = 'tip';

    const REASON_TYPE_CANCEL    = 'cancel';
    const REASON_TYPE_STOP      = 'stop';
    const REASON_TYPE_DECLINE      = 'decline';
    const REASON_CONTENT_OUTDATED = 'outdated';
    const REASON_CONTENT_BOUNTY_COMPLETED = 'Bounty has completed by another gamelancer.';

    const TIP_MEMO_SESSION      = 'Tip For Playing Session';
    const TIP_MEMO_BOUNTY       = 'Tip For Playing Bounty';
    const TIP_MEMO_VIDEO        = 'Tip For Video';
    const TIP_MEMO_FREE         = 'Tip For No Reason';

    const USER_DEFAULT_TIMEZONE = 'UTC';
    const AUDIO_LIMIT_SIZE_FILE = 2048;// 2MB;

    const IAP_PLATFORM_ANDROID  = 'android';
    const IAP_PLATFORM_IOS      = 'ios';

    const CLAIM_BOUNTY_REQUEST_STATUS_PENDING = 'pending';
    const CLAIM_BOUNTY_REQUEST_STATUS_APPROVED = 'approved';
    const CLAIM_BOUNTY_REQUEST_STATUS_REJECTED = 'rejected';
    const CLAIM_BOUNTY_REQUEST_STATUS_CANCELED = 'canceled';

    const BOUNTY_STATUS_COMPLETED = 'completed';
    const BOUNTY_STATUS_DISPUTED = 'disputed';
    const BOUNTY_STATUS_STOPPED = 'stopped';
    const BOUNTY_STATUS_STARTED = 'started';
    const BOUNTY_STATUS_CREATED = 'created';

    const INVITATION_CODE_MAXIMUM = 1000;
    const BOUNTY_EXPIRED_TIME = 6 * 60 * 60; //6h

    const GAMEPROFILE_BOOK_NOW_AUTO_CANCEL = 15 * 60; // 15 minutes
    const GAMEPROFILE_BOOK_NOW_USER_CAN_CANCEL = 5 * 60; // 5 minutes
    const GAMEPROFILE_BOOK_ACCEPT_USER_CANCEL = 24 * 60 * 60; // 24 hours

    const SOCIAL_LIST = [
        'google',
        'facebook',
        'discord',
        'apple'
    ];

    const SOCIAL_NETWORKS_LINK = [
        'discord'   => 'https://discord.com/users/%s',
        'facebook'  => 'https://facebook.com/%s',
        'instagram' => 'https://instagram.com/%s',
        'tiktok'    => 'https://tiktok.com/@%s',
        'twitch'    => 'https://twitch.tv/%s',
        'twitter'   => 'https://twitter.com/%s',
        'youtube'   => 'https://youtube.com/channel/%s'
    ];

    const MATTERMOST_TEAM_ID_KEY = 'mattermost_team_id';
    const NODEBB_CATEGORY_POST_ID_KEY = 'nodebb_category_post_id';
    const NODEBB_CATEGORY_VIDEO_ID_KEY = 'nodebb_category_video_id';

    const BOUNTY_FEE_KEY = 'bounty_fee';
    const SESSION_FEE_KEY = 'session_fee';
    const VISIBLE_BOUNTY_KEY = 'visible_bounty_feature';

    const VIP_SETTING = 'vip_link_approve';
    const BACKGROUND_VIDEO_URL = 'background_video_url';

    const DAILY_TASK_TIMEZONE_KEY = 'daily_task_timezone';
    const DAILY_CHECKIN_PERIOD_KEY = 'daily_checkin_period';

    const SETTINGS = [
        self::BOUNTY_FEE_KEY        => 0,
        self::SESSION_FEE_KEY       => 0,
        self::VIP_SETTING           => 0,
        self::BACKGROUND_VIDEO_URL  => '/v2/hero.mov',
    ];
    const KEY_SETTINGS_ADMIN = [
        self::VIP_SETTING,
        self::BACKGROUND_VIDEO_URL,
        self::VISIBLE_BOUNTY_KEY
    ];

    const MESSAGE_BOUNTY_CLAIM = 'system_message.bounty.claim';
    const MESSAGE_BOUNTY_ACCEPT = 'system_message.bounty.accept';
    const MESSAGE_BOUNTY_REJECT = 'system_message.bounty.reject';
    const MESSAGE_BOUNTY_COMPLETE = 'system_message.bounty.complete';
    const MESSAGE_BOUNTY_MARK_COMPLETE = 'system_message.bounty.mark_complete';
    const MESSAGE_BOUNTY_CANCEL_CLAIM = 'system_message.bounty.cancel_claim';
    const MESSAGE_BOUNTY_DISPUTED = 'system_message.bounty.dispute';
    const MESSAGE_BOUNTY_GAMELANCER_REVIEW = 'system_message.bounty.gamelancer.review';
    const MESSAGE_BOUNTY_USER_REVIEW = 'system_message.bounty.user.review';

    // session message key v2
    const MESSAGE_SESSION_BOOK_FREE = 'message.session.book.free';
    const MESSAGE_SESSION_BOOK_PAID = 'message.session.book.paid';
    const MESSAGE_SESSION_CANCEL_FREE = 'message.session.cancel.free';
    const MESSAGE_SESSION_CANCEL_PAID = 'message.session.cancel.paid';
    const MESSAGE_SESSION_REJECT_FREE = 'message.session.reject.free';
    const MESSAGE_SESSION_REJECT_PAID = 'message.session.reject.paid';
    const MESSAGE_SESSION_REJECT_AUTO_FREE = 'message.session.reject.auto.free';
    const MESSAGE_SESSION_REJECT_AUTO_PAID = 'message.session.reject.auto.paid';
    const MESSAGE_SESSION_ACCEPT_FREE = 'message.session.accept.free';
    const MESSAGE_SESSION_ACCEPT_PAID_NOW = 'message.session.accept.paid.now';
    const MESSAGE_SESSION_ACCEPT_PAID_SCHEDULE = 'message.session.accept.paid.schedule';
    const MESSAGE_SESSION_STARTED = 'message.session.started';
    const MESSAGE_SESSION_GAMELANCER_STOP = 'message.session.gamelancer.stop';
    const MESSAGE_SESSION_USER_STOP = 'message.session.user.stop';
    const MESSAGE_SESSION_MARK_COMPLETE = 'message.session.mark.complete';
    const MESSAGE_SESSION_REJECT_COMPLETE = 'message.session.reject.complete';
    const MESSAGE_SESSION_CONTINUE = 'message.session.continue';
    const MESSAGE_SESSION_COMPLETE_FREE = 'message.session.complete.free';
    const MESSAGE_SESSION_COMPLETE_PAID = 'message.session.complete.paid';
    const MESSAGE_SESSION_COMPLETE_PAID_TIP = 'message.session.complete.paid.tip';
    const MESSAGE_SESSION_STARTING = 'message.session.starting';
    const MESSAGE_SESSION_GAMELANCER_READY = 'message.session.gamelancer.ready';
    const MESSAGE_SESSION_USER_READY = 'message.session.user.ready';
    const MESSAGE_SESSION_USER_REVIEW = 'message.session.user.review';
    const MESSAGE_SESSION_USER_REVIEW_WITH_TIP = 'message.session.user.review.with_tip';
    const MESSAGE_SESSION_ADD_TIME = 'message.session.add.time';
    const MESSAGE_SESSION_REJECT_ADD_TIME = 'message.session.reject.add.time';
    const MESSAGE_SESSION_ACCEPT_ADD_TIME = 'message.session.accept.add.time';
    const MESSAGE_SESSION_OUTDATED = 'message.session.outdate';
    const MESSAGE_SESSION_GAMELANCER_OUTDATED = 'message.session.gamelancer.outdate';
    const MESSAGE_SESSION_USER_OUTDATED = 'message.session.user.outdate';
    const MESSAGE_SESSION_TIP = 'message.session.tip';

    // session message type v2
    const MESSAGE_TYPE_BOOK_SESSION = 'book_session_message';
    const MESSAGE_TYPE_REJECT_BOOK = 'reject_book_message';
    const MESSAGE_TYPE_CANCEL_BOOK = 'cancel_book_message';
    const MESSAGE_TYPE_ACCEPT_BOOK_SCHEDULE = 'accept_book_schedule_message';
    const MESSAGE_TYPE_ACCEPT_BOOK_NOW = 'accept_book_now_message';
    const MESSAGE_TYPE_ADD_TIME = 'add_time_message';
    const MESSAGE_TYPE_RESPONSE_ADD_TIME = 'response_add_time_message';
    const MESSAGE_TYPE_MARK_COMPLETE = 'mark_complete_message';
    const MESSAGE_TYPE_CONTINUE = 'continue_session_message';
    const MESSAGE_TYPE_REJECT_COMPLETE = 'reject_complete_message';
    const MESSAGE_TYPE_STOP_SESSION = 'stop_session_message';
    const MESSAGE_TYPE_COMPLETE_SESSION = 'complete_session_message';
    const MESSAGE_TYPE_REVIEW_SESSION = 'review_session_message';
    const MESSAGE_TYPE_START_SESSION = 'start_session_message';
    const MESSAGE_TYPE_OUTDATED_SESSION = 'outdated_session_message';
    const MESSAGE_TYPE_READY = 'ready_message';
    const MESSAGE_TYPE_TIP = 'tip_message';
    const MESSAGE_TYPE_TEXT_MESSAGE = 'text_message';

    // session action v2
    const SESSION_ACTION_BOOK_FREE = 'book_free_session';
    const SESSION_ACTION_BOOK_PAID = 'book_paid_session';
    const SESSION_ACTION_CANCEL_FREE = 'cancel_free_session';
    const SESSION_ACTION_CANCEL_PAID = 'cancel_paid_session';
    const SESSION_ACTION_REJECT_FREE = 'reject_free_session';
    const SESSION_ACTION_REJECT_PAID = 'reject_paid_session';
    const SESSION_ACTION_ACCEPT_FREE = 'accept_free_session';
    const SESSION_ACTION_ACCEPT_PAID = 'accept_paid_session';
    const SESSION_ACTION_READY = 'ready_session';
    const SESSION_ACTION_ADDTIME = 'add_time_session';
    const SESSION_ACTION_REJECT_ADDTIME = 'reject_add_time_session';
    const SESSION_ACTION_ACCEPT_ADDTIME = 'accept_add_time_session';
    const SESSION_ACTION_MARK_COMPLETE = 'mark_complete';
    const SESSION_ACTION_REJECT_COMPLETE = 'reject_complete';
    const SESSION_ACTION_CONTINUE = 'continue_session';
    const SESSION_ACTION_STOP = 'stop_session';
    const SESSION_ACTION_COMPLETE_FREE = 'complete_free_session';
    const SESSION_ACTION_COMPLETE_PAID = 'complete_paid_session';
    const SESSION_ACTION_NOTIFY_SCHEDULE = 'notify_schedule_session';
    const SESSION_ACTION_STARTING_SCHEDULE = 'starting_schedule_session';
    const SESSION_ACTION_OUTDATED = 'outdated_session';
    const SESSION_ACTION_REVIEW = 'review_session';

    // transaction message key
    const MESSAGE_TRANSACTION_BOUNTY_WITHDRAW = 'transaction.bounty.withdraw';
    const MESSAGE_TRANSACTION_BOUNTY_DEPOSIT = 'transaction.bounty.deposit';
    const MESSAGE_TRANSACTION_SESSION_WITHDRAW = 'transaction.session.withdraw';
    const MESSAGE_TRANSACTION_SESSION_DEPOSIT = 'transaction.session.deposit';
    const MESSAGE_TRANSACTION_WITHDRAW = 'transaction.message.withdraw';
    const MESSAGE_TRANSACTION_DEPOSIT = 'transaction.message.deposit';
    const MESSAGE_TRANSACTION_CONVERT = 'transaction.message.convert';
    const MESSAGE_TRANSACTION_TIP_WITHDRAW = 'transaction.tip.withdraw';
    const MESSAGE_TRANSACTION_TIP_VIDEO_WITHDRAW = 'transaction.tip.video.withdraw';
    const MESSAGE_TRANSACTION_TIP_DEPOSIT = 'transaction.tip.deposit';
    const MESSAGE_TRANSACTION_TIP_VIDEO_DEPOSIT = 'transaction.tip.video.deposit';

    // notify content general
    const NOTIFY_NEW_MESSAGE = 'notification.message.new';
    const NOTIFY_NEW_GAME_PROFILE = 'notification.favourite.new_gameprofile';
    const NOTIFY_NEW_BOUNTY = 'notification.favourite.new_bounty';
    const NOTIFY_YOUR_NEW_GAME_PROFILE = 'notification.online.your_new_session';
    const NOTIFY_YOUR_NEW_BOUNTY = 'notification.online.your_new_bounty';
    const NOTIFY_NEW_FOLLOW = 'notification.follow.new';
    const NOTIFY_TIP = 'notification.tip';
    const NOTIFY_SEND_TIP = 'notification.send_tip';
    const OTHER_NOTIFY_APP = 'notification.other_nofify';

    // notify content bounty
    const NOTIFY_BOUNTY_COMPLETE = 'notification.bounty.complete';
    const NOTIFY_BOUNTY_COMPLETE_GAMELANCER = 'notification.bounty.complete.gamelancer';
    const NOTIFY_BOUNTY_MARK_COMPLETE = 'notification.bounty.mark_complete';
    const NOTIFY_BOUNTY_DISPUTED = 'notification.bounty.disputed';
    const NOTIFY_BOUNTY_CANCEL_CLAIM = 'notification.bounty.cancel_claim';
    const NOTIFY_BOUNTY_CLAIM = 'notification.bounty.claim';
    const NOTIFY_BOUNTY_ACCEPT = 'notification.bounty.accept';
    const NOTIFY_BOUNTY_REJECT = 'notification.bounty.reject';
    const NOTIFY_BOUNTY_REVIEW = 'notification.bounty.review';

    // notify content session
    const NOTIFY_SESSION_BOOK_FREE = 'notification.session.book.free';
    const NOTIFY_SESSION_CANCEL_FREE = 'notification.session.cancel.free';
    const NOTIFY_SESSION_REJECT_FREE = 'notification.session.reject.free';
    const NOTIFY_SESSION_REJECT_AUTO_FREE = 'notification.session.reject.auto.free';
    const NOTIFY_SESSION_ACCEPT_FREE = 'notification.session.accept.free';
    const NOTIFY_SESSION_COMPLETE_FREE = 'notification.session.complete.free';
    const NOTIFY_SESSION_BOOK_NOW = 'notification.session.book_now';
    const NOTIFY_SESSION_BOOK_NOW_GAMELANCER_OFFLINE = 'notification.session.book_now.gamelancer_offline';
    const NOTIFY_SESSION_BOOK = 'notification.session.book';
    const NOTIFY_SESSION_CANCEL_PAID = 'notification.session.cancel.paid';
    const NOTIFY_SESSION_CANCEL_GAMELANCER = 'notification.session.cancel.gamelancer';
    const NOTIFY_SESSION_CANCEL_USER = 'notification.session.cancel.user';
    const NOTIFY_SESSION_CANCEL_WITHOUT_REFUND_GAMELANCER = 'notification.session.cancel.without_refund.gamelancer';
    const NOTIFY_SESSION_CANCEL_WITHOUT_REFUND_USER = 'notification.session.cancel.without_refund.user';
    const NOTIFY_SESSION_ACCEPT = 'notification.session.accept';
    const NOTIFY_SESSION_REJECT = 'notification.session.reject';
    const NOTIFY_SESSION_SYSTEM_REJECT = 'notification.session.system_reject';
    const NOTIFY_SESSION_COMPLETE = 'notification.session.complete';
    const NOTIFY_SESSION_COMPLETE_GAMELANCER = 'notification.session.complete.gamelancer';
    const NOTIFY_SESSION_STOP = 'notification.session.stop';
    const NOTIFY_SESSION_STOP_GAMELANCER = 'notification.session.stop.gamelancer';
    const NOTIFY_SESSION_USER_OUTDATED_GAMELANCER = 'notification.session.user.outdated.gamelancer';
    const NOTIFY_SESSION_GAMELANCER_OUTDATED_GAMELANCER = 'notification.session.gamelancer.outdated.gamelancer';
    const NOTIFY_SESSION_USER_OUTDATED_USER = 'notification.session.user.outdated.user';
    const NOTIFY_SESSION_GAMELANCER_OUTDATED_USER = 'notification.session.gamelancer.outdated.user';
    const NOTIFY_SESSION_REVIEW = 'notification.session.review';
    const NOTIFY_SESSION_REVIEW_WITH_TIP = 'notification.session.review.with_tip';
    const NOTIFY_SESSION_STARTING = 'notification.session.starting';
    const NOTIFY_SESSION_START = 'notification.session.start';

    // notify content transaction
    const NOTIFY_WALLET_CASH_OUT = 'notification.wallet.cash_out';
    const NOTIFY_WALLET_CASH_OUT_APPROVED = 'notification.wallet.cash_out.approved';
    const NOTIFY_WALLET_CASH_OUT_REJECTED = 'notification.wallet.cash_out.rejected';
    const NOTIFY_WALLET_CASH_OUT_SUCCESS = 'notification.wallet.cash_out.success';
    const NOTIFY_WALLET_CASH_OUT_FAILED = 'notification.wallet.cash_out.failed';
    const NOTIFY_WALLET_EXCHANGE = 'notification.wallet.exchange';
    const NOTIFY_WALLET_PURCHASE = 'notification.wallet.purchase';
    const NOTIFY_WALLET_PURCHASE_WITHOUT_LOGGED = 'notification.wallet.purchase.without.logged';
    const NOTIFY_WALLET_PURCHASE_EXECUTING = 'notification.wallet.purchase.executing';

    // notify content gamelancer
    const NOTIFY_GAMELANCER_APPROVE = 'notification.gamelancer.approve';
    const NOTIFY_GAMELANCER_APPROVE_FROM_FREE = 'notification.gamelancer.approve_from_free_gamelancer';
    const NOTIFY_GAMELANCER_APPROVE_FREEGAMELANCER = 'notification.gamelancer.approve_free_gamelancer';
    const NOTIFY_GAMELANCER_REJECT = 'notification.gamelancer.reject';

    // notify content video
    const NOTIFY_VIDEO_UPLOAD = 'notification.video.upload';
    const NOTIFY_VIDEO_VOTE_UP = 'notification.video.vote.up';
    const NOTIFY_VIDEO_VOTE_DOWN = 'notification.video.vote.down';
    const NOTIFY_VIDEO_COMMENT = 'notification.video.comment';
    const NOTIFY_VIDEO_FOLLOW = 'notification.video.follow';
    const NOTIFY_VIDEO_TIP = 'notification.video.tip';
    const NOTIFY_VIDEO_SEND_TIP = 'notification.video.send_tip';

    // For Tasking
    const MESSAGE_NOTIFY_TASKING_COMPLETED                  = 'notification.tasking.completed';
    const MESSAGE_NOTIFY_TASKING_LEVEL_UP                   = 'notification.tasking.levelup';
    const MESSAGE_NOTIFY_TASKING_RESET_DAILY_CHECKIN        = 'notification.tasking.reset_daily_checkin';

    // notify type
    const NOTIFY_TYPE_NEW_MESSAGE            = 'new_message';
    const NOTIFY_TYPE_MARKETING              = 'marketing';
    const NOTIFY_TYPE_FAVORITE               = 'favorite';
    const NOTIFY_TYPE_SESSION_ONLINE         = 'session_online';
    const NOTIFY_TYPE_CONFIRM_GAMELANCER     = 'confirm_gamelancer';
    const NOTIFY_TYPE_NEW_FOLLOWER           = 'new_follower';
    const NOTIFY_TYPE_TIP                    = 'tip';
    const NOTIFY_TYPE_SEND_TIP               = 'send_tip';
    const NOTIFY_TYPE_WALLET_COINS           = 'wallet_coins';
    const NOTIFY_TYPE_WALLET_USD             = 'wallet_usd';
    const NOTIFY_TYPE_BOUNTY                 = 'bounty';
    const NOTIFY_TYPE_BOUNTY_WALLET_COINS    = 'bounty_wallet_coins';
    const NOTIFY_TYPE_BOUNTY_WALLET_REWARDS  = 'bounty_wallet_rewards';
    const NOTIFY_TYPE_BOUNTY_REVIEW          = 'bounty_review';
    const NOTIFY_TYPE_SESSION                = 'session';
    const NOTIFY_TYPE_SESSION_WALLET_COINS   = 'session_wallet_coins';
    const NOTIFY_TYPE_SESSION_WALLET_REWARDS = 'session_wallet_rewards';
    const NOTIFY_TYPE_SESSION_REVIEW         = 'session_review';
    const NOTIFY_TYPE_VIDEO                  = 'video';
    const NOTIFY_TYPE_VIDEO_COMMENT          = 'video_comment';
    const NOTIFY_TYPE_VIDEO_TIP              = 'video_tip';
    const NOTIFY_TYPE_VIDEO_SEND_TIP         = 'video_send_tip';
    const NOTIFY_TYPE_VIDEO_VOTE             = 'video_vote';
    const NOTIFY_TYPE_VIDEO_ONLINE           = 'video_online';
    const NOTIFY_TYPE_TASKING                = 'tasking';
    const NOTIFY_TYPE_TASKING_COMPLETED      = 'tasking_completed';
    const NOTIFY_TYPE_TASKING_LEVEL_UP       = 'tasking_levelup';
    const NOTIFY_TYPE_TASKING_DAILY_CHECKIN  = 'tasking_daily_checkin';
    const NOTIFY_TYPE_OTHER                  = 'other';

    const MIN_COIN_TO_CHAT = 1;

    const SESSION_CHECK_READY_EXPIRED_TIME = 15 * 60; // 15 minutes
    const SESSION_CHECK_READY_STARTING_TIME = 30; // 30 minutes

    const SESSION_SCHEDULE_AT_DATETIME_FORMAT = 'Y-m-d H:i';

    const LIMIT_DAYS_GET_NOTIFY = 14;

    const SORT_BY_FOLLOWER = 'top';
    const SORT_BY_NEWEST = 'latest';
    const SORT_BY_REVIEW = 'review';
    const SORT_BY_PRICE = 'price';

    const GAME_PROFILE_LIST_TYPE_TOP = 'top';
    const GAME_PROFILE_LIST_TYPE_TOP_RATED = 'top_rated';
    const GAME_PROFILE_LIST_TYPE_TOP_FOLLOWERS = 'top_followers';
    const GAME_PROFILE_LIST_TYPE_LAST = 'latest';
    const GAME_PROFILE_LIST_TYPE_FOLLOWING = 'following';

    const NOTIFY_FILTER_BALANCES = 'balances';
    const NOTIFY_FILTER_BOUNTY = 'bounty';
    const NOTIFY_FILTER_SESSION = 'session';
    const NOTIFY_FILTER_FAVOURITE = 'favorite';
    const NOTIFY_FILTER_OTHER = 'other';

    const NOTIFY_SMS_BOUNTY_RECEIVED    = 'bounty_received';
    const NOTIFY_SMS_BOUNTY_ACCEPTED    = 'bounty_accepted';
    const NOTIFY_SMS_SESSION_BOOKED     = 'session_booked';
    const NOTIFY_SMS_SESSION_ACCEPTED   = 'session_accepted';
    const NOTIFY_SMS_SESSION_STARTING   = 'session_starting';
    const NOTIFY_SMS_VERIFY_CODE        = 'verify_code';
    const NOTIFY_SMS_PHONE_CODE         = 'phone_code';
    const NOTIFY_SMS_PASSWORD_CODE      = 'password_code';
    const NOTIFY_SMS_USERNAME_CODE      = 'username_code';
    const NOTIFY_SMS_CONFIRMATION_CODE  = 'confirmation_code';

    const DEFAULT_GAME_SERVER = 'Main Server';
    const DEFAULT_GAME_RANK = 'Unranked';

    const KALVIYO_ACTION_ADD = 'add';
    const KALVIYO_ACTION_UPDATE = 'update';

    const USER_TYPE_USER = 0;
    const USER_TYPE_PREMIUM_GAMELANCER = 1;
    const USER_TYPE_FREE_GAMELANCER = 2;

    const BOUNTY_ALL_GAMELANCER = 1;
    const BOUNTY_PREMIUM_GAMELANCER = 2;

    const GAME_STATISTIC_CREATE_GAME_PROFILE = 'create_game_profile';
    const GAME_STATISTIC_SESSION_COMPLETED = 'session_completed';
    const GAME_STATISTIC_SESSION_STOPPED = 'session_stopped';

    const QUEUE_PUT_FIREHOSE                        = 'put_firehose';
    const CREATE_MATTERMOST_USER_ENDPOINT_QUEUE     = 'create_mattermost_user';
    const QUEUE_CALCULATE_STATISTIC                 = 'statistic';
    const CREATE_NODEBB_USER_ENDPOINT_QUEUE         = 'create_nodebb_user';
    const QUEUE_NOTIFICATION                        = 'notification';
    const RANKING_QUEUE                             = 'ranking';

    const MINUTES_OF_DAY = 24 * 60;
    const MINUTES_OF_WEEK = 24 * 60 * 7;

    const VIDEO_SORT_BY_NEWEST  = 'newest';
    const VIDEO_SORT_BY_VIEWS   = 'views';

    const TIP_VIA_REVIEW = 'tip_via_review';
    const TIP_VIA_SESSION = 'tip_via_session';
    const TIP_VIA_VIDEO = 'tip_via_video';
    const TIP_VIA_CHAT = 'tip_via_chat';

    const MESSAGE_DIRECTION_SENDER = 'out';
    const MESSAGE_DIRECTION_RECEIVER = 'in';
    const MESSAGE_DIRECTION_SYSTEM = 'center';

    // For voice call
    const VOICE_STATUS_CREATED      = 'created';
    const VOICE_STATUS_CALLING      = 'calling';
    const VOICE_STATUS_DECLINE      = 'declined';
    const VOICE_STATUS_PAIRED       = 'paired';
    const VOICE_STATUS_ENDED_CALL   = 'ended_call';

    const VOICE_CALLING_EXPIRED_TIME = 30; // 30 seconds

    const MESSAGE_PROPS_TYPE_VOICE = 'voice';

    const MESSAGE_TYPE_TEXT_VOICE_MESSAGE = 'text_voice_message';

    const VOICE_AUDIO_CALL     = 'voice.audio_call';
    const VOICE_MISSED_CALL    = 'voice.missed_call';

    // For Leveling System.
    const TASKING_TYPE_INTRO            = 'intro';
    const TASKING_TYPE_DAILY            = 'daily';
    const TASKING_TYPE_DAILY_CHECKIN    = 'daily-checkin';

    const TOTAL_INTRO_STEPS             = 5;

    const SESSION_TYPE_FREE = 'free';
    const SESSION_TYPE_NOW = 'now';
    const SESSION_TYPE_SCHEDULE = 'schedule';

    const SECURITY_UNLOCK_TYPE_PASSWORD = 'password';
    const SECURITY_UNLOCK_TYPE_EMAIL = 'email';
    const SECURITY_UNLOCK_TYPE_PHONE = 'phone';
}
