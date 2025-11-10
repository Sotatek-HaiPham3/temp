<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ======================== Public API ========================

Route::get('/healthcheck', function () {
    return 'Ok';
});

Route::get('/masterdata', 'API\MasterdataAPIController@getAll');

Route::group(['prefix' => 'stripe'], function () {
    Route::post('/web-hook/deposit', 'API\WebHookStripeAPIController@depositWebHook');
    Route::post('/web-hook/withdraw', 'API\WebHookStripeAPIController@withdrawWebHook');
});

Route::post('/create-account', 'API\Auth\RegisterAPIController@register');
Route::post('/verify-email', 'API\Auth\RegisterAPIController@verifyEmail');
Route::post('/verify-phone-number', 'API\Auth\RegisterAPIController@verifyPhoneNumber');
Route::post('/verify-change-email', 'API\UserAPIController@verifyChangeEmail');
Route::post('/verify-change-username', 'API\UserAPIController@verifyChangeUsername');
Route::post('/verify-change-phone-number', 'API\UserAPIController@verifyChangePhoneNumber');
Route::post('/send-verification-code-for-email', 'API\Auth\RegisterAPIController@sendVerificationCodeForEmail');
Route::post('/send-verification-code-for-phone-number', 'API\Auth\RegisterAPIController@sendVerificationCodeForPhoneNumber');
Route::post('/reset-password', 'Auth\ForgotPasswordController@sendResetLinkEmailViaApi');
Route::post('/execute-reset-password', 'Auth\ResetPasswordController@resetViaApi');
Route::get('/is-valid-token', 'Auth\ResetPasswordController@checkValidToken');
Route::get('/email-exists', 'API\UserAPIController@checkEmailExists');
Route::get('/username-exists', 'API\UserAPIController@checkUsernameExists');
Route::get('/username-valid', 'API\UserAPIController@checkUsernameValid');
Route::get('/phonenumber-exists', 'API\UserAPIController@checkPhoneNumberExists');
Route::get('/username-verified-account', 'API\UserAPIController@checkUsernameVerifiedAccount');

Route::post('/login', [ 'uses' => 'API\Auth\LoginAPIController@loginViaApi', 'middleware' => 'throttle:6000|6000,1' ]);
Route::post('/oauth/token', [ 'uses' => 'API\Auth\LoginAPIController@login', 'middleware' => 'throttle:6000|6000,1' ]);

Route::post('/refresh-token', [ 'uses' => 'API\Auth\LoginAPIController@refreshTokenViaApi', 'middleware' => 'throttle:6000|6000,1' ]);
Route::post('/oauth/refresh-token', [ 'uses' => 'API\Auth\LoginAPIController@refreshToken', 'middleware' => 'throttle:6000|6000,1' ]);

Route::get('/user-info/{username?}', 'API\UserAPIController@getUserInfoByUsername');
Route::get('/user/reviews', 'API\UserAPIController@getUserReviews');

Route::group(['prefix' => '/social', 'namespace' => 'API\Auth'], function () {
    Route::post('/auth-token', 'SocialUserAPIController@authToken');
    Route::post('/register', 'SocialUserAPIController@register');
});

Route::get('/banners', 'API\BannerAPIController@getBanners');

Route::get('/bounties', 'API\BountyAPIController@getAllBounties');
Route::get('/bounty/detail', 'API\BountyAPIController@getBountyDetail');

Route::get('/game-profiles', 'API\GameProfileAPIController@getAllGameProfiles');
Route::get('/game-profile/detail', 'API\GameProfileAPIController@getGameProfileDetail');
Route::get('/game-profile/reviews', 'API\GameProfileAPIController@getGameProfileReviews');
Route::get('/game-profile/collection', 'API\GameProfileAPIController@getGameProfileCollection');
Route::get('/matching', 'API\GameProfileAPIController@quickMatching');

Route::get('/featured-gamelancers', 'API\GameProfileAPIController@getFeaturedGamelancers');

Route::get('/offers', 'API\TransactionAPIController@getOffers');

Route::post('/firebase/push', 'API\FirebaseAPIController@pushNotifcation');

Route::group(['prefix' => 'payment', 'namespace' => 'API'], function () {
    Route::post('/deposit-without-logged/paypal', 'TransactionAPIController@depositPaypalWithoutLogged');
    Route::post('/deposit-without-logged/stripe', 'TransactionAPIController@depositStripeWithoutLogged');
});

Route::get('/search', 'API\SearchingAPIController@search');

Route::get('/invitation-code', 'API\UserAPIController@getInvitationCode');

Route::get('/user/get-interests-games', 'API\UserAPIController@getInterestsGames');

Route::get('/game-statistics', 'API\GameProfileAPIController@getGameStatistics');

Route::group(['prefix' => 'medias', 'namespace' => 'API'], function () {

    Route::group(['prefix' => 'videos'], function () {
        Route::get('/', 'MediaAPIController@getVideos');
        Route::get('/featured', 'MediaAPIController@getFeaturedVideos');
        Route::get('/info', 'MediaAPIController@getVideoInfoById');
        Route::get('/topic-info', 'MediaAPIController@getTopicForVideoByVideoId');
        Route::get('/recently-added', 'MediaAPIController@getRecentlyAddedVideos');
        Route::get('/suggest', 'MediaAPIController@getSuggestionVideos');
    });

    Route::get('/user/videos', 'MediaAPIController@getUserVideos');
    Route::post('/webhook', 'MediaAPIController@listenVideoTranscodingWebhook');
});

Route::get('/user/photo', 'API\UserAPIController@getUserPhotos');

Route::group(['prefix' => 'forums', 'namespace' => 'API'], function () {
    Route::get('/topics-for-user-without-login', 'ForumAPIController@getTopicsForUser');
    Route::get('/posts-for-topic-without-login', 'ForumAPIController@getPostsForTopic');
    Route::get('/sub-posts-for-post-without-login', 'ForumAPIController@getSubPostsForPost');
    Route::put('/posts-detail-without-login', 'ForumAPIController@getPostsDetail');
});

Route::group(['prefix' => '/user'], function () {
    Route::get('/followings', 'API\UserAPIController@getMyFollows'); // following other guys
    Route::get('/followers', 'API\UserAPIController@getUserFollowers'); // other guys following myself
});


// ======================== Private API ========================

Route::group(['middleware' => 'auth:api'], function () {

    Route::post('/reset-ranking', 'API\UserAPIController@resetUserRanking');

    Route::post('/s3/pre-signed', 'API\FileUploadAPIController@generatePreSignedS3Form');
    Route::post('/video/verify-upload', 'API\FileUploadAPIController@verifyUploadS3');

    Route::post('/upload-file', 'API\FileUploadAPIController@uploadFile');
    Route::put('/remove-upload-file', 'API\FileUploadAPIController@removeFileUpload');

    Route::put('/logout', 'API\Auth\LoginAPIController@logoutViaApi');

    Route::post('broadcasting/auth', ['uses' => '\Illuminate\Broadcasting\BroadcastController@authenticate']);

    Route::post('become-gamelancer', 'API\UserAPIController@createGamelancerInfo');

    Route::get('invitation-code/is-valid', 'API\UserAPIController@validateInvitationCode');

    Route::post('/save-phone-for-user', 'API\UserAPIController@savePhoneForUser');

    Route::post('/check-password-valid', 'API\UserAPIController@checkPasswordValid');

    Route::post('/send-otp-code', 'API\UserAPIController@sendOtpCode');

    Route::get('/get-unlock-security-type', 'API\UserAPIController@getUnlockSecurityType');

    Route::post('/confirm-otp-code', 'API\UserAPIController@confirmOtpCode');

    Route::group(['prefix' => '/notifications'], function () {
        Route::get('/', 'API\SystemNotificationAPIController@getNotifications');
        Route::get('/total-unview', 'API\SystemNotificationAPIController@totalUnview');
        Route::put('/mark-as-read', 'API\SystemNotificationAPIController@markAsRead');
        Route::put('/mark-as-view', 'API\SystemNotificationAPIController@markAsView');
        Route::delete('/clear-all', 'API\SystemNotificationAPIController@deleteNotify');
    });

    Route::group(['namespace' => 'API'], function () {
        Route::group(['prefix' => '/change-email'], function () {
            Route::post('/', 'UserAPIController@changeEmailFromSetting');
            Route::post('/without-verified-account', 'UserAPIController@changeEmailFromSettingWithoutVerifiedAccount');
            Route::post('/resend-link', 'UserAPIController@resendLinkChangeEmail');
            Route::delete('/cancel', 'UserAPIController@cancelChangeEmail');
        });

        Route::put('/change-password', 'UserAPIController@changePassword')->middleware('auth.account.verified');

        Route::group(['prefix' => 'change-username', 'middleware' => 'auth.account.verified'], function () {
            Route::post('/', 'UserAPIController@changeUsernameFromSetting');
            Route::post('/resend-link', 'UserAPIController@resendLinkChangeUsername');
            Route::delete('/cancel', 'UserAPIController@cancelChangeUsername');
        });

        Route::group(['prefix' => '/change-phone-number'], function () {
            Route::post('/', 'UserAPIController@changePhoneNumberFromSetting');
            Route::post('/without-verified-account', 'UserAPIController@changePhoneNumberFromSettingWithoutVerifiedAccount');
            Route::post('/resend-link', 'UserAPIController@resendCodeChangePhoneNumber');
            Route::delete('/cancel', 'UserAPIController@cancelChangePhoneNumber');
        });
    });

    Route::group(['prefix' => '/user'], function () {
        Route::get('/gamelancer-info', 'API\UserAPIController@getGamelancerInfo');
        Route::get('/profile', 'API\UserAPIController@getUserProfile');
        Route::put('/profile/update', 'API\UserAPIController@updateProfile');
        Route::get('/my-reviews', 'API\UserAPIController@getMyReviews');
        Route::get('/balance', 'API\UserAPIController@getUserBalance');
        Route::post('/tip', 'API\TransactionAPIController@tip')->middleware('auth.account.verified');
        Route::put('/follow', 'API\UserAPIController@addOrRemoveFollow');

        Route::put('/settings/update', 'API\UserAPIController@updateSettings');

        Route::get('/get-invitation-code', 'API\UserAPIController@getInvitationCodeForVip');

        Route::get('/scheduler', 'API\UserAPIController@getUserScheduler');

        Route::post('/report', 'API\UserAPIController@report');

        Route::group(['prefix' => '/social-network'], function () {
            Route::post('/create', 'API\UserAPIController@createSocialNetwork');
            Route::post('/update', 'API\UserAPIController@updateSocialNetwork');
            Route::delete('/delete', 'API\UserAPIController@deleteSocialNetwork');
        });

        Route::group(['prefix' => '/photo'], function () {
            Route::post('/create', 'API\UserAPIController@createUserPhoto');
            Route::delete('/delete', 'API\UserAPIController@deleteUserPhoto');
        });

        Route::group(['prefix' => '/available-times', 'middleware' => 'auth.gamelancer'], function () {
            Route::get('/', 'API\UserAPIController@getAvailableTimes');
            Route::post('/add', 'API\UserAPIController@addAvailableTime');
            Route::delete('/delete', 'API\UserAPIController@deleteAvailableTime');
        });

        Route::group(['prefix' => '/bounty', 'middleware' => 'auth.account.verified'], function () {
            Route::post('/create', 'API\BountyAPIController@createBounty');
            Route::put('/update', 'API\BountyAPIController@updateBounty');
            Route::delete('/delete', 'API\BountyAPIController@deleteBounty');

            Route::post('/claim', 'API\BountyAPIController@claim')->middleware('auth.gamelancer');
            Route::post('/cancel-claim', 'API\BountyAPIController@cancelClaim')->middleware('auth.gamelancer');
            Route::post('/approve', 'API\BountyAPIController@approve');
            Route::post('/reject', 'API\BountyAPIController@reject');

            Route::post('/complete', 'API\BountyAPIController@completeBounty');
            Route::post('/mark-complete', 'API\BountyAPIController@markCompleteBounty')->middleware('auth.gamelancer');
            Route::post('/dispute', 'API\BountyAPIController@disputeBounty');
            Route::post('/cancel-bounty', 'API\BountyAPIController@cancelBountyFromGamelancer')->middleware('auth.gamelancer');
            Route::post('/review', 'API\BountyAPIController@reviewBounty');

            Route::get('/for-user', 'API\BountyAPIController@getBountyClaimForUser');
            Route::get('/for-gamelancer', 'API\BountyAPIController@getBountyClaimForGamelancer');
        });

        Route::get('/bounties', 'API\BountyAPIController@getMyBounties');

        Route::group(['prefix' => '/game-profile', 'middleware' => 'auth.account.verified'], function () {
            Route::get('/existed', 'API\GameProfileAPIController@getExistedGameProfile');
            Route::post('/create', 'API\GameProfileAPIController@createGameProfile')->middleware('auth.gamelancer');
            Route::put('/update', 'API\GameProfileAPIController@updateGameProfile')->middleware('auth.gamelancer');
            Route::delete('/delete', 'API\GameProfileAPIController@deleteGameProfile')->middleware('auth.gamelancer');
            Route::post('/create-media', 'API\GameProfileAPIController@createGameProfileMedia')->middleware('auth.gamelancer');
            Route::delete('/delete-media', 'API\GameProfileAPIController@deleteGameProfileMedia')->middleware('auth.gamelancer');
        });

        Route::get('/game-profiles', 'API\GameProfileAPIController@getMyGameProfiles');

        Route::group(['prefix' => '/session', 'middleware' => 'auth.account.verified'], function () {
            Route::post('/book', 'API\SessionAPIController@bookGameProfile');
            Route::post('/check-book-another-gamelancer', 'API\SessionAPIController@checkBookingAnotherGamelancer');
            Route::get('/check-gamelancer-offline', 'API\SessionAPIController@checkGamelancerOffline');
            Route::put('/accept-booking', 'API\SessionAPIController@acceptBookingGameProfile')->middleware('auth.gamelancer');
            Route::put('/reject-booking', 'API\SessionAPIController@rejectBookingGameProfile')->middleware('auth.gamelancer');
            Route::put('/ready', 'API\SessionAPIController@readySession');

            // request add/accept/reject the time/game
            Route::post('/add-request', 'API\SessionAPIController@addSessionRequest');
            Route::put('/accept-request', 'API\SessionAPIController@acceptAddingRequest')->middleware('auth.gamelancer');
            Route::put('/reject-request', 'API\SessionAPIController@rejectAddingRequest')->middleware('auth.gamelancer');

            Route::put('/stop', 'API\SessionAPIController@stopSession');
            Route::put('/complete', 'API\SessionAPIController@completeSession');
            Route::post('/restart', 'API\SessionAPIController@restartSession');
            Route::get('/booked-slots', 'API\SessionAPIController@getSessionBookedSlots');
            Route::get('/booked-slots-as-user', 'API\SessionAPIController@getSessionBookedSlotsAsUser');
            Route::get('/playing-session', 'API\SessionAPIController@getPlayingSessionPairUser');
            Route::get('/data-bubble', 'API\SessionAPIController@getDataBubbleChat');
            Route::post('/review', 'API\SessionAPIController@reviewSession');
            Route::put('/cancel-booking', 'API\SessionAPIController@cancelBooking');
            Route::put('/mark-as-complete', 'API\SessionAPIController@markAsComplete');
            Route::put('/reject-mark-complete', 'API\SessionAPIController@rejectMarkComplete');
            Route::put('/continue-session', 'API\SessionAPIController@continueSession');
        });

        Route::post('/create-interests-games', 'API\UserAPIController@createInterestsGames');
        Route::get('/get-existed-interest-game', 'API\UserAPIController@getExistedInterestGame');
        Route::put('/update-interests-games', 'API\UserAPIController@updateInterestGame');
        Route::delete('/delete-interests-game', 'API\UserAPIController@deleteInterestsGame');

        Route::get('/taskings', 'API\UserAPIController@getUserTasks');
        Route::post('/intro-tasks/collect', 'API\UserAPIController@collectStepIntroTask');
        Route::post('/taskings/collect', 'API\UserAPIController@collectUserTasking');
        Route::post('/taskings/claim', 'API\UserAPIController@claimTasking');
        Route::post('/daily-checkin/collect', 'API\UserAPIController@collectDailyCheckin');
    });

    Route::group(['prefix' => 'fcm-devices'], function () {
        Route::post('register', 'API\FirebaseAPIController@registerDevice');
        Route::delete('delete', 'API\FirebaseAPIController@deleteDevice');
    });

    Route::group(['prefix' => 'iap-items/'], function () {
        Route::get('/ios', 'API\IapAPIController@getListItemIos');
        Route::post('/ios', 'API\IapAPIController@purchaseItemIos');

        Route::get('/android', 'API\IapAPIController@getListItemAndroid');
        Route::post('/android', 'API\IapAPIController@purchaseItemAndroid');
    });

    Route::group(['prefix' => 'payment', 'namespace' => 'API', 'middleware' => 'auth.account.verified'], function () {
        Route::post('/deposit/paypal', 'TransactionAPIController@depositPaypal');
        Route::post('/deposit/stripe', 'TransactionAPIController@depositStripe');
        Route::post('/deposit/handle-payment-intent', 'TransactionAPIController@handlePaymentIntent');
        Route::post('/withdraw/paypal', 'TransactionAPIController@withdraw');
        Route::get('/transaction-detail', 'TransactionAPIController@getTransactionDetail');
        Route::post('/convert-balance', 'TransactionAPIController@convertBalances');
        Route::get('/transactions/history', 'TransactionAPIController@getHistory');
    });

    Route::group(['prefix' => 'chat', 'namespace' => 'API'], function () {
        Route::post('/channels/direct', 'ChatAPIController@createDirectMessageChannel');
        Route::post('/create-post', 'ChatAPIController@createPost');
        Route::get('/posts-for-channel', 'ChatAPIController@getPostsForChannel');
        Route::get('/channels-for-user', 'ChatAPIController@getChannelsForUser');
        Route::post('/view-channel', 'ChatAPIController@viewChannel');
        Route::post('/search-channels', 'ChatAPIController@searchChannel');
        Route::get('/user-chat-session-list', 'ChatAPIController@getUserChatSessionList');
        Route::get('/unread-messages', 'ChatAPIController@getUnreadMessages');
        Route::get('/channel-detail', 'ChatAPIController@getChannelById');
        Route::get('/detail-by-username', 'ChatAPIController@getChannelByUsername');
        Route::put('/block-channel', 'ChatAPIController@blockChannel');
        Route::put('/mute-channel', 'ChatAPIController@muteChannel');
        Route::put('/unblock-channel', 'ChatAPIController@unblockChannel');
        Route::put('/unmute-channel', 'ChatAPIController@unmuteChannel');
        Route::post('/system-logs', 'ChatAPIController@getSystemLogs');
        Route::get('/token', 'ChatAPIController@getMattermostToken');
        Route::get('/total-channels-unread-message', 'ChatAPIController@getTotalChannelsUnreadMessage');
        Route::put('/mark-as-view', 'ChatAPIController@markAsView');
    });

    Route::group(['prefix' => 'forums', 'namespace' => 'API'], function () {
        Route::post('/create-topic', 'ForumAPIController@createTopic');
        Route::post('/create-comment', 'ForumAPIController@createComment');
        Route::post('/upvote', 'ForumAPIController@upvote');
        Route::post('/downvote', 'ForumAPIController@downvote');
        Route::delete('/unvote', 'ForumAPIController@unvote');
        Route::delete('/delete-topic', 'ForumAPIController@deleteTopic');
        Route::get('/topics-for-user', 'ForumAPIController@getTopicsForUser');
        Route::get('/posts-for-topic', 'ForumAPIController@getPostsForTopic');
        Route::get('/sub-posts-for-post', 'ForumAPIController@getSubPostsForPost');
        Route::put('/posts-detail', 'ForumAPIController@getPostsDetail');
    });

    Route::group(['prefix' => 'voice', 'namespace' => 'API'], function () {
        Route::post('/create-channel', 'VoiceAPIController@createChannel');
        Route::post('/join-channel', 'VoiceAPIController@joinChannel');
        Route::put('/decline-call', 'VoiceAPIController@declineCall');
        Route::put('/ending-call', 'VoiceAPIController@endCall');
        Route::put('/pairing-call', 'VoiceAPIController@pairCall');
        Route::get('/check-incoming-voice-call', 'VoiceAPIController@checkIncomingVoiceCall');
    });
});
