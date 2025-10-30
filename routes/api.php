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
Route::get('/healthcheck', function () {
    return 'Ok';
});

Route::prefix('v2.1')->group(function () {
    Route::get('/user-info', 'API\V21\UserAPIController@getUserInfoByUsername');
    Route::get('/user-info/{username?}', 'API\V1\UserAPIController@getUserInfoByUsername');
    Route::group(['namespace' => 'API\V21'], function () {
        Route::group(['middleware' => 'auth:api'], function () {
            Route::group(['prefix' => '/voice-room'], function () {
                Route::post('/create', 'VoiceAPIController@createVoiceChatRoom');
                Route::post('/force-create', 'VoiceAPIController@forceCreateRoom');
            });

            Route::group(['prefix' => '/community'], function () {
                Route::get('/members/online', 'CommunityAPIController@getCommunityMembersOnline');
            });
        });
    });
});

Route::prefix('v2')->group(function () {
    Route::get('/user-info', 'API\V21\UserAPIController@getUserInfoByUsername');
    Route::get('/user-info/{username?}', 'API\V1\UserAPIController@getUserInfoByUsername'); // HOT FIX
    Route::group(['namespace' => 'API\V2'], function () {

        Route::group(['prefix' => '/register'], function () {
            Route::post('/send-email-code', 'RegisterAPIController@sendEmailCode');
            Route::post('/confirm-email-code', 'RegisterAPIController@confirmEmailCode');
            Route::post('/send-phone-code', 'RegisterAPIController@sendRegisterCode');
            Route::post('/confirm-phone-code', 'RegisterAPIController@confirmRegisterCode');
        });

        Route::group(['prefix' => '/voice-room'], function () {
            Route::get('/list', 'VoiceAPIController@listVoiceChatRoom');
        });

        Route::group(['prefix' => '/community'], function () {
            Route::get('/list', 'CommunityAPIController@getCommunities');
            Route::get('/detail', 'CommunityAPIController@getCommunityDetail');
            Route::post('/community-existed', 'CommunityAPIController@checkCommunityExisted');
        });

        Route::group(['middleware' => 'auth:api'], function () {
            Route::group(['prefix' => '/user'], function () {
                Route::get('/profile', 'UserAPIController@getUserProfile');
            });

            Route::group(['prefix' => '/voice-room'], function () {
                Route::post('/create', 'VoiceAPIController@createVoiceChatRoom');
                Route::post('/force-create', 'VoiceAPIController@forceCreateRoom');
                Route::get('/get-invite-list', 'VoiceAPIController@getInviteList');
                Route::post('/join', 'VoiceAPIController@joinVoiceChatRoom');
                Route::post('/force-join', 'VoiceAPIController@forceJoinRoom');
                Route::post('/check-next-room', 'VoiceAPIController@checkNextRoom');
                Route::post('/check-random-room', 'VoiceAPIController@checkRandomRoom');
                Route::put('/make-host', 'VoiceAPIController@makeRoomHost');
                Route::put('/make-moderator', 'VoiceAPIController@makeRoomModerator');
                Route::get('/questions', 'VoiceAPIController@getQuestions');
                Route::get('/queue-questions', 'VoiceAPIController@getQueuedQuestions');
                Route::get('/asked-questions', 'VoiceAPIController@getAskedQuestions');
                Route::post('/question/cancel', 'VoiceAPIController@cancelQuestion');
                Route::post('/question/accept', 'VoiceAPIController@acceptQuestion');
                Route::post('/question/answer', 'VoiceAPIController@answerQuestion');
                Route::get('/count-questions', 'VoiceAPIController@countQuestions');
            });

            Route::group(['prefix' => '/community'], function () {
                Route::get('/my-community', 'CommunityAPIController@getMyCommunities');
                Route::get('/members', 'CommunityAPIController@getCommunityMembers');
                Route::post('/create', 'CommunityAPIController@store');
                Route::post('/update', 'CommunityAPIController@update');
                Route::delete('/delete', 'CommunityAPIController@destroy');
                Route::post('/deactivate', 'CommunityAPIController@deactivate');
                Route::post('/reactivate', 'CommunityAPIController@reactivate');
                Route::put('/make-leader', 'CommunityAPIController@makeLeader');
                Route::put('/remove-leader', 'CommunityAPIController@removeLeader');
                Route::put('/kick', 'CommunityAPIController@kickUser');
                Route::post('/report', 'CommunityAPIController@reportCommunity');
                Route::post('/join', 'CommunityAPIController@joinCommunity');
                Route::put('/exit', 'CommunityAPIController@exitCommunity');
                Route::get('/invite/list', 'CommunityAPIController@getInviteList');
                Route::post('/invite/create', 'CommunityAPIController@inviteUser');
                Route::post('/invite/accept', 'CommunityAPIController@acceptInvite');
                Route::get('/request/list', 'CommunityAPIController@getRequests');
                Route::post('/request/accept', 'CommunityAPIController@acceptRequestToJoin');
                Route::post('/request/reject', 'CommunityAPIController@rejectRequestToJoin');
                Route::post('/request/cancel', 'CommunityAPIController@cancelRequestToJoin');
                Route::get('/posts-for-channel', 'CommunityAPIController@getPostsForChannel');
                Route::put('/posts/{id}', 'CommunityAPIController@updatePost');
                Route::put('/posts/{id}/patch', 'CommunityAPIController@patchPost');
                Route::post('/post', 'CommunityAPIController@createPost');
                Route::get('/post', 'CommunityAPIController@getPost');
                Route::post('/post/pin', 'CommunityAPIController@pinPost');
                Route::post('/post/unpin', 'CommunityAPIController@unpinPost');
                Route::post('/post/unpin-all', 'CommunityAPIController@unpinAllPosts');
                Route::get('/post/pinned', 'CommunityAPIController@getPinnedPosts');
                Route::delete('/post/delete', 'CommunityAPIController@deletePost');
                Route::post('/post/reaction', 'CommunityAPIController@reactionPost');
                Route::delete('/post/reaction', 'CommunityAPIController@deleteReactionPost');
                Route::get('/post/reaction', 'CommunityAPIController@getReactionPost');
                Route::post('/post/report', 'CommunityAPIController@reportPost');
                Route::post('/check-random-room', 'CommunityAPIController@checkRandomRoom');
                Route::get('/my-role', 'CommunityAPIController@getMyRole');
                Route::get('/name-change/list', 'CommunityAPIController@getNameChangeRequest');
                Route::post('/name-change/create', 'CommunityAPIController@nameChangeRequest');
                Route::post('/name-change/cancel', 'CommunityAPIController@cancelNameChangeRequest');
                Route::get('/get-room-statistic', 'CommunityAPIController@getRoomStatistic');
                Route::get('/check-user-join-request', 'CommunityAPIController@checkUserJoinRequest');
                Route::post('/report-user', 'CommunityAPIController@reportUser');
                Route::get('/check-user-report-existed', 'CommunityAPIController@checkUserReportExisted');
                Route::get('/check-community-report-existed', 'CommunityAPIController@checkCommunityReportExisted');
            });
            Route::group(['prefix' => 'security'], function() {
                Route::post('send-email-otp-code', 'UserAPIController@sendEmailOtpCode');
                Route::post('send-phone-otp-code', 'UserAPIController@sendPhoneOtpCode');
                Route::post('confirm-email-otp-code', 'UserAPIController@confirmEmailOtpCode');
                Route::post('confirm-phone-otp-code', 'UserAPIController@confirmPhoneOtpCode');
            });
        });
    });
});
// ======================== Public API ========================

Route::prefix('v1')->group(function () {
    Route::get('/user-info', 'API\V21\UserAPIController@getUserInfoByUsername');
    Route::group(['namespace' => 'API\V1'], function () {

        Route::post('echo-hook', 'EchoHookAPIController@handleHook');

        Route::get('/masterdata', 'MasterdataAPIController@getAll');

        Route::get('/settings', 'MasterdataAPIController@getSettings');

        Route::group(['prefix' => 'stripe'], function () {
            Route::post('/web-hook/deposit', 'WebHookStripeAPIController@depositWebHook');
            Route::post('/web-hook/withdraw', 'WebHookStripeAPIController@withdrawWebHook');
        });

        Route::group(['prefix' => '/register'], function () {
            Route::post('/', 'RegisterAPIController@register');
            Route::post('/send-code', 'RegisterAPIController@sendRegisterCode');
            Route::post('/confirm-code', 'RegisterAPIController@confirmRegisterCode');
        });

        Route::group(['prefix' => '/login'], function () {
            Route::post('/', 'LoginAPIController@loginViaApi')->middleware('throttle:6000|6000,1');
            Route::post('/send-code', 'LoginAPIController@sendLoginCode');
            Route::post('/confirm-code', 'LoginAPIController@confirmLoginCode');
        });

        Route::group(['prefix' => '/reset-password'], function () {
            Route::post('/send-code', 'ResetPasswordController@sendResetPasswordCode');
            Route::post('/confirm-code', 'ResetPasswordController@confirmResetPwCode');
            Route::post('/execute', 'ResetPasswordController@executeResetPassword');
        });

        Route::get('/email-exists', 'UserAPIController@checkEmailExists');
        Route::get('/username-exists', 'UserAPIController@checkUsernameExists');
        Route::get('/username-valid', 'UserAPIController@checkUsernameValid');
        Route::get('/phonenumber-valid', 'UserAPIController@checkPhonenumberValid');
        Route::get('/phonenumber-exists', 'UserAPIController@checkPhoneNumberExists');
        Route::get('/username-verified-account', 'UserAPIController@checkUsernameVerifiedAccount');

        Route::post('/oauth/token', 'LoginAPIController@login')->middleware('throttle:6000|6000,1');

        Route::post('/refresh-token', 'LoginAPIController@refreshTokenViaApi')->middleware('throttle:6000|6000,1');
        Route::post('/oauth/refresh-token', 'LoginAPIController@refreshToken')->middleware('throttle:6000|6000,1');

        Route::get('/user-info/{username?}', 'UserAPIController@getUserInfoByUsername');
        Route::get('/user/reviews', 'UserAPIController@getUserReviews');

        Route::group(['prefix' => '/social'], function () {
            Route::post('/auth-token', 'SocialUserAPIController@authToken');
            Route::post('/register', 'SocialUserAPIController@register');
            Route::post('/social-checking', 'SocialUserAPIController@handleAppleCallback');

            Route::group(['prefix' => 'authorization'], function () {
                Route::get('/users', 'SocialUserAPIController@getAuthorizationUsers');
                Route::post('/send-email-code', 'SocialUserAPIController@sendEmailAuthorizationCode');
                Route::post('/send-phone-number-code', 'SocialUserAPIController@sendPhoneAuthorizationCode');
                Route::post('/confirm-code', 'SocialUserAPIController@confirmAuthorizationCode');
            });
        });

        Route::get('/banners', 'BannerAPIController@getBanners');

        Route::get('/bounties', 'BountyAPIController@getAllBounties');
        Route::get('/bounty/detail', 'BountyAPIController@getBountyDetail');

        Route::get('/game-profiles', 'GameProfileAPIController@getAllGameProfiles');
        Route::get('/game-profile/detail', 'GameProfileAPIController@getGameProfileDetail');
        Route::get('/game-profile/reviews', 'GameProfileAPIController@getGameProfileReviews');
        Route::get('/game-profile/collection', 'GameProfileAPIController@getGameProfileCollection');
        Route::get('/matching', 'GameProfileAPIController@quickMatching');

        Route::get('/featured-gamelancers', 'GameProfileAPIController@getFeaturedGamelancers');

        Route::get('/offers', 'TransactionAPIController@getOffers');

        Route::post('/firebase/push', 'FirebaseAPIController@pushNotification');

        Route::group(['prefix' => 'payment'], function () {
            Route::post('/deposit-without-logged/paypal', 'TransactionAPIController@depositPaypalWithoutLogged');
            Route::post('/deposit-without-logged/stripe', 'TransactionAPIController@depositStripeWithoutLogged');
        });

        Route::get('/search', 'SearchingAPIController@search');

        Route::get('/invitation-code', 'UserAPIController@getInvitationCode');

        Route::get('/user/get-interests-games', 'UserAPIController@getInterestsGames');

        Route::get('/game-statistics', 'GameProfileAPIController@getGameStatistics');

        Route::group(['prefix' => 'medias'], function () {

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

        Route::get('/user/photo', 'UserAPIController@getUserPhotos');

        Route::group(['prefix' => '/user'], function () {
            Route::get('/followings', 'UserAPIController@getMyFollows'); // following other guys
            Route::get('/followers', 'UserAPIController@getUserFollowers'); // other guys following myself
            Route::get('/recent-room-games', 'UserAPIController@getRecentRoomGames');
        });

        Route::group(['prefix' => '/voice-room'], function () {
            Route::get('/detail', 'VoiceAPIController@getRoomDetail');
            Route::get('/room-users', 'VoiceAPIController@getRoomUsers');
            Route::get('/list-category', 'VoiceAPIController@listVoiceCategory');
            Route::get('/list', 'VoiceAPIController@listVoiceChatRoom');
            Route::post('/category-existed', 'VoiceAPIController@checkCategoryExisted');
        });


        // ======================== Private API ========================

        Route::group(['middleware' => 'auth:api'], function () {

            Route::group(['middleware' => 'mobile_app.requests'], function () {
                Route::post('/users-existed', 'UserAPIController@getUsersExisted');
            });

            Route::post('/reset-ranking', 'UserAPIController@resetUserRanking');

            Route::post('/s3/pre-signed', 'FileUploadAPIController@generatePreSignedS3Form');
            Route::post('/video/verify-upload', 'FileUploadAPIController@verifyUploadS3');

            Route::post('/upload-file', 'FileUploadAPIController@uploadFile');
            Route::put('/remove-upload-file', 'FileUploadAPIController@removeFileUpload');

            Route::put('/logout', 'LoginAPIController@logoutViaApi');

            Route::post('broadcasting/auth', ['uses' => '\Illuminate\Broadcasting\BroadcastController@authenticate']);

            Route::post('become-gamelancer', 'UserAPIController@createGamelancerInfo');

            Route::get('invitation-code/is-valid', 'UserAPIController@validateInvitationCode');

            Route::post('/save-phone-for-user', 'UserAPIController@savePhoneForUser');

            Route::post('/check-password-valid', 'UserAPIController@checkPasswordValid');

            Route::post('/send-otp-code', 'UserAPIController@sendOtpCode');

            Route::get('/get-unlock-security-type', 'UserAPIController@getUnlockSecurityType');

            Route::post('/confirm-otp-code', 'UserAPIController@confirmOtpCode');

            Route::group(['prefix' => 'security'], function() {
                Route::post('verify-email', 'UserAPIController@verifyEmail');
                Route::post('verify-phone', 'UserAPIController@verifyPhone');
                Route::post('change-email', 'UserAPIController@changeEmail');
                Route::post('change-phone', 'UserAPIController@changePhone');
                Route::post('send-email-verification-code', 'UserAPIController@sendEmailVerificationCode');
                Route::post('send-phone-verification-code', 'UserAPIController@sendPhoneVerificationCode');
                Route::post('cancel-changing-email', 'UserAPIController@cancelChangingEmail');
                Route::post('cancel-changing-phone', 'UserAPIController@cancelChangingPhone');
                Route::put('change-password', 'UserAPIController@changePassword');
                Route::put('change-username', 'UserAPIController@changeUsername');
            });

            Route::group(['prefix' => '/notifications'], function () {
                Route::get('/', 'SystemNotificationAPIController@getNotifications');
                Route::get('/total-unview', 'SystemNotificationAPIController@totalUnview');
                Route::put('/mark-as-read', 'SystemNotificationAPIController@markAsRead');
                Route::put('/mark-as-view', 'SystemNotificationAPIController@markAsView');
                Route::delete('/clear-all', 'SystemNotificationAPIController@deleteNotify');
            });

            Route::group(['prefix' => '/user'], function () {
                Route::get('/gamelancer-info', 'UserAPIController@getGamelancerInfo');
                Route::get('/profile', 'UserAPIController@getUserProfile');
                Route::put('/profile/update', 'UserAPIController@updateProfile');
                Route::get('/my-reviews', 'UserAPIController@getMyReviews');
                Route::get('/balance', 'UserAPIController@getUserBalance');
                Route::post('/tip', 'TransactionAPIController@tip')->middleware('auth.account.verified');
                Route::put('/follow', 'UserAPIController@addOrRemoveFollow');
                Route::get('/block-list', 'UserAPIController@getMyBlockList');
                Route::put('/block', 'UserAPIController@addOrRemoveBlock');

                Route::put('/settings/update', 'UserAPIController@updateSettings');
                Route::get('/settings', 'UserAPIController@getUserSettings');

                Route::get('/get-invitation-code', 'UserAPIController@getInvitationCodeForVip');

                Route::get('/scheduler', 'UserAPIController@getUserScheduler');

                Route::post('/report', 'UserAPIController@report');

                Route::group(['prefix' => '/social-network'], function () {
                    Route::post('/create', 'UserAPIController@createSocialNetwork');
                    Route::post('/update', 'UserAPIController@updateSocialNetwork');
                    Route::delete('/delete', 'UserAPIController@deleteSocialNetwork');
                });

                Route::group(['prefix' => '/photo'], function () {
                    Route::post('/create', 'UserAPIController@createUserPhoto');
                    Route::delete('/delete', 'UserAPIController@deleteUserPhoto');
                });

                Route::group(['prefix' => '/available-times', 'middleware' => 'auth.gamelancer'], function () {
                    Route::get('/', 'UserAPIController@getAvailableTimes');
                    Route::post('/add', 'UserAPIController@addAvailableTime');
                    Route::delete('/delete', 'UserAPIController@deleteAvailableTime');
                });

                Route::group(['prefix' => '/bounty', 'middleware' => 'auth.account.verified'], function () {
                    Route::post('/create', 'BountyAPIController@createBounty');
                    Route::put('/update', 'BountyAPIController@updateBounty');
                    Route::delete('/delete', 'BountyAPIController@deleteBounty');

                    Route::post('/claim', 'BountyAPIController@claim')->middleware('auth.gamelancer');
                    Route::post('/cancel-claim', 'BountyAPIController@cancelClaim')->middleware('auth.gamelancer');
                    Route::post('/approve', 'BountyAPIController@approve');
                    Route::post('/reject', 'BountyAPIController@reject');

                    Route::post('/complete', 'BountyAPIController@completeBounty');
                    Route::post('/mark-complete', 'BountyAPIController@markCompleteBounty')->middleware('auth.gamelancer');
                    Route::post('/dispute', 'BountyAPIController@disputeBounty');
                    Route::post('/cancel-bounty', 'BountyAPIController@cancelBountyFromGamelancer')->middleware('auth.gamelancer');
                    Route::post('/review', 'BountyAPIController@reviewBounty');

                    Route::get('/for-user', 'BountyAPIController@getBountyClaimForUser');
                    Route::get('/for-gamelancer', 'BountyAPIController@getBountyClaimForGamelancer');
                });

                Route::get('/bounties', 'BountyAPIController@getMyBounties');

                Route::group(['prefix' => '/game-profile', 'middleware' => 'auth.account.verified'], function () {
                    Route::get('/existed', 'GameProfileAPIController@getExistedGameProfile');
                    Route::post('/create', 'GameProfileAPIController@createGameProfile')->middleware('auth.gamelancer');
                    Route::put('/update', 'GameProfileAPIController@updateGameProfile')->middleware('auth.gamelancer');
                    Route::delete('/delete', 'GameProfileAPIController@deleteGameProfile')->middleware('auth.gamelancer');
                    Route::post('/create-media', 'GameProfileAPIController@createGameProfileMedia')->middleware('auth.gamelancer');
                    Route::delete('/delete-media', 'GameProfileAPIController@deleteGameProfileMedia')->middleware('auth.gamelancer');
                });

                Route::get('/game-profiles', 'GameProfileAPIController@getMyGameProfiles');

                Route::group(['prefix' => '/session', 'middleware' => 'auth.account.verified'], function () {
                    Route::post('/book', 'SessionAPIController@bookGameProfile');
                    Route::post('/check-book-another-gamelancer', 'SessionAPIController@checkBookingAnotherGamelancer');
                    Route::get('/check-gamelancer-offline', 'SessionAPIController@checkGamelancerOffline');
                    Route::put('/accept-booking', 'SessionAPIController@acceptBookingGameProfile')->middleware('auth.gamelancer');
                    Route::put('/reject-booking', 'SessionAPIController@rejectBookingGameProfile')->middleware('auth.gamelancer');
                    Route::put('/ready', 'SessionAPIController@readySession');

                    // request add/accept/reject the time/game
                    Route::post('/add-request', 'SessionAPIController@addSessionRequest');
                    Route::put('/accept-request', 'SessionAPIController@acceptAddingRequest')->middleware('auth.gamelancer');
                    Route::put('/reject-request', 'SessionAPIController@rejectAddingRequest')->middleware('auth.gamelancer');

                    Route::put('/stop', 'SessionAPIController@stopSession');
                    Route::put('/complete', 'SessionAPIController@completeSession');
                    Route::post('/restart', 'SessionAPIController@restartSession');
                    Route::get('/booked-slots', 'SessionAPIController@getSessionBookedSlots');
                    Route::get('/booked-slots-as-user', 'SessionAPIController@getSessionBookedSlotsAsUser');
                    Route::get('/playing-session', 'SessionAPIController@getPlayingSessionPairUser');
                    Route::get('/data-bubble', 'SessionAPIController@getDataBubbleChat');
                    Route::post('/review', 'SessionAPIController@reviewSession');
                    Route::put('/cancel-booking', 'SessionAPIController@cancelBooking');
                    Route::put('/mark-as-complete', 'SessionAPIController@markAsComplete');
                    Route::put('/reject-mark-complete', 'SessionAPIController@rejectMarkComplete');
                    Route::put('/continue-session', 'SessionAPIController@continueSession');
                });

                Route::post('/create-interests-games', 'UserAPIController@createInterestsGames');
                Route::get('/get-existed-interest-game', 'UserAPIController@getExistedInterestGame');
                Route::put('/update-interests-games', 'UserAPIController@updateInterestGame');
                Route::delete('/delete-interests-game', 'UserAPIController@deleteInterestsGame');

                Route::get('/taskings', 'UserAPIController@getUserTasks');
                Route::post('/intro-tasks/collect', 'UserAPIController@collectStepIntroTask');
                Route::post('/taskings/collect', 'UserAPIController@collectUserTasking');
                Route::post('/taskings/claim', 'UserAPIController@claimTasking');
                Route::post('/daily-checkin/collect', 'UserAPIController@collectDailyCheckin');

                Route::get('/list-friend', 'UserAPIController@getListFriend');
                Route::get('/playing-friends', 'UserAPIController@getPlayingFriends');
                Route::get('/suggest-friends', 'UserAPIController@getSuggestFriends');
                Route::post('/delete-account', 'UserAPIController@deleteUser');
            });

            Route::group(['prefix' => 'fcm-devices'], function () {
                Route::post('register', 'FirebaseAPIController@registerDevice');
                Route::delete('delete', 'FirebaseAPIController@deleteDevice');
            });

            Route::group(['prefix' => 'iap-items/'], function () {
                Route::get('/ios', 'IapAPIController@getListItemIos');
                Route::post('/ios', 'IapAPIController@purchaseItemIos');

                Route::get('/android', 'IapAPIController@getListItemAndroid');
                Route::post('/android', 'IapAPIController@purchaseItemAndroid');
            });

            Route::group(['prefix' => 'payment', 'middleware' => 'auth.account.verified'], function () {
                Route::post('/deposit/paypal', 'TransactionAPIController@depositPaypal');
                Route::post('/deposit/stripe', 'TransactionAPIController@depositStripe');
                Route::post('/deposit/handle-payment-intent', 'TransactionAPIController@handlePaymentIntent');
                Route::post('/withdraw/paypal', 'TransactionAPIController@withdraw');
                Route::get('/transaction-detail', 'TransactionAPIController@getTransactionDetail');
                Route::post('/convert-balance', 'TransactionAPIController@convertBalances');
                Route::get('/transactions/history', 'TransactionAPIController@getHistory');
            });

            Route::group(['prefix' => 'chat'], function () {
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

            Route::group(['prefix' => 'voice'], function () {
                Route::post('/create-channel', 'VoiceAPIController@createChannel');
                Route::post('/join-channel', 'VoiceAPIController@joinChannel');
                Route::put('/decline-call', 'VoiceAPIController@declineCall');
                Route::put('/ending-call', 'VoiceAPIController@endCall');
                Route::put('/pairing-call', 'VoiceAPIController@pairCall');
                Route::get('/check-incoming-voice-call', 'VoiceAPIController@checkIncomingVoiceCall');
            });

            Route::group(['prefix' => '/voice-room'], function () {
                Route::post('/create', 'VoiceAPIController@createVoiceChatRoom');
                Route::put('/update', 'VoiceAPIController@updateVoiceChatRoom');
                Route::post('/check-room-available', 'VoiceAPIController@checkRoomAvailable');
                Route::post('/check-user-can-join-room', 'VoiceAPIController@checkUserCanJoinRoom');
                Route::post('/check-user-can-create-room', 'VoiceAPIController@checkUserCanCreateRoom');
                Route::post('/check-next-room', 'VoiceAPIController@checkNextRoom');
                Route::post('/check-random-room', 'VoiceAPIController@checkRandomRoom');
                Route::post('/join', 'VoiceAPIController@joinVoiceChatRoom');
                Route::post('/force-join', 'VoiceAPIController@forceJoinRoom');
                Route::post('/force-create', 'VoiceAPIController@forceCreateRoom');
                Route::put('/kick', 'VoiceAPIController@kickUserOutRoom');
                Route::put('/make-host', 'VoiceAPIController@makeRoomHost');
                Route::put('/make-moderator', 'VoiceAPIController@makeRoomModerator');
                Route::put('/remove-moderator', 'VoiceAPIController@removeRoomModerator');
                Route::put('/make-speaker', 'VoiceAPIController@makeRoomSpeaker');
                Route::put('/remove-speaker', 'VoiceAPIController@removeRoomSpeaker');
                Route::post('/invite', 'VoiceAPIController@inviteUserIntoRoom');
                Route::put('/leave', 'VoiceAPIController@leaveVoiceChatRoom');
                Route::put('/leave-anyroom', 'VoiceAPIController@leaveAnyRoom');
                Route::put('/close', 'VoiceAPIController@closeRoom');
                Route::get('/get-invite-list', 'VoiceAPIController@getInviteList');
                Route::put('/update-username', 'VoiceAPIController@updateUserUsername');
                Route::post('/raise-hand', 'VoiceAPIController@raiseHand');
                Route::get('/list-raise-hand', 'VoiceAPIController@listRaiseHand');
                Route::post('/report-room', 'VoiceAPIController@reportRoom');
                Route::post('/report-user', 'VoiceAPIController@reportUser');
                Route::get('/check-room-report-existed', 'VoiceAPIController@checkRoomReportExisted');
                Route::get('/check-user-report-existed', 'VoiceAPIController@checkUserReportExisted');
                Route::get('/get-user-current-room', 'VoiceAPIController@getCurrentRoom');
                Route::post('/ask-question', 'VoiceAPIController@askQuestion');
                Route::get('/questions', 'VoiceAPIController@getRoomQuestions');
                Route::post('/reject-question', 'VoiceAPIController@rejectQuestion');
                Route::post('/accept-question', 'VoiceAPIController@acceptQuestion');
                Route::post('/switch-allow-question', 'VoiceAPIController@switchAllowQuestion');
                Route::post('/share-video', 'VoiceAPIController@shareVideo');
                Route::delete('/clear-share-video', 'VoiceAPIController@clearShareVideo');
            });
        });
    });
});
