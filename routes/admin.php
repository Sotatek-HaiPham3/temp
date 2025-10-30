<?php

Route::get('/login', 'Admin\LoginController@showLoginForm')->middleware('admin.guest');

Route::post('/logout', 'Admin\LoginController@logout');

Route::post('/login', 'Admin\LoginController@login')->name("adminLogin");

Route::group(['middleware' => 'auth.admin'], function () {

    Route::get('/', 'Admin\AdminController@index');

    Route::get('/transactions/usd-withdraw/export', 'Admin\AdminController@exportUsdTransactionsToExcel');

    Route::group(['prefix' => 'api'], function () {
        Route::get('/masterdata', 'API\V1\MasterdataAPIController@getAll');

        Route::group(['prefix' => 'administrators'], function () {
            Route::get('/', 'Admin\AdminController@getAdministrators');
            Route::get('/{id}', 'Admin\AdminController@getAdministratorById');
            Route::post('/create', 'Admin\AdminController@createNewOrUpdateAdministrator');
            Route::post('/update', 'Admin\AdminController@createNewOrUpdateAdministrator');
            Route::delete('delete', 'Admin\AdminController@deleteAdministrator');
        });

        // Route::post('/clear-cache', 'ToolController@clearCache');
        Route::post('/clear-cache', 'Admin\AdminController@clearCache');

        Route::get('/user', 'Admin\AdminController@getCurrentAdmin');

        Route::get('/users', 'API\V1\UserAPIController@users');

        Route::get('/users2', 'Admin\AdminController@getUsers');

        Route::get('/usernames', 'Admin\AdminController@getUsernames');

        Route::get('/gamelancer-forms', 'Admin\AdminController@getGamelancerForms');

        Route::put('/gamelancer-forms/approveGamelancer', 'Admin\AdminController@approveGamelancer');

        Route::put('/gamelancer-forms/approveFreeGamelancer', 'Admin\AdminController@approveFreeGamelancer');

        Route::put('/gamelancer-forms/disapproveGamelancer', 'Admin\AdminController@disapproveGamelancer');

        Route::get('/gamelancer-forms/detail', 'Admin\AdminController@getGamelancerInfoDetail');

        Route::get('/invitation-codes', 'Admin\AdminController@getInvitationCodes');

        Route::get('/user-transactions', 'Admin\AdminController@getUserTransactions');

        Route::post('/user-transactions/excute-transaction', 'Admin\AdminController@updateExcuteTransaction');

        Route::get('/user-balances', 'Admin\AdminController@getUserBalances');

        Route::post('/user-balances/update', 'Admin\AdminController@updateUserBalance');

        Route::get('/transactions', 'Admin\AdminController@getTransactions');

        Route::post('/user/update', 'Admin\AdminController@updateUser');

        Route::group(['prefix' => 'socical-networks', 'namespace' => 'Admin'], function () {
            Route::get('/', 'SiteSettingController@getSocialNetworks');
            Route::post('/', 'SiteSettingController@addSocialNetwork');
            Route::post('/update', 'SiteSettingController@updateSocialNetWork');
            Route::delete('/{id}', 'SiteSettingController@removeSocialNetwork');
        });

        Route::get('user/{userId}/devices', 'API\V1\UserAPIController@getDeviceRegister');

        Route::delete('user/{userId}/device/{id}', 'API\V1\UserAPIController@deleteDevice');

        Route::group(['prefix' => 'setting', 'namespace' => 'Admin'], function () {

            Route::group(['prefix' => 'platform'], function() {
                Route::get('/' , 'SettingController@getPlatforms');
                Route::post('/create' , 'SettingController@createNewPlatform');
                Route::post('/update', 'SettingController@updatePlatform');
                Route::post('/remove' , 'SettingController@removePlatform');
            });

            Route::group(['prefix' => 'site'], function() {
                Route::get('/' , 'SettingController@getSiteSettings');
                Route::put('/update', 'SettingController@updateSiteSettings');
            });

            Route::group(['prefix' => 'banner'], function() {
                Route::get('/' , 'SettingController@getBanners');
                Route::post('/create', 'SettingController@createBanner');
                Route::post('/update', 'SettingController@updateBanner');
                Route::delete('/delete', 'SettingController@deleteBanner');
            });

            Route::group(['prefix' => 'ranking'], function() {
                Route::get('/', 'SettingController@getRankings');
                Route::post('/create', 'SettingController@createRanking');
                Route::post('/update', 'SettingController@updateRanking');
                Route::delete('/delete', 'SettingController@deleteRanking');
            });

            Route::group(['prefix' => 'reward'], function() {
                Route::get('/', 'SettingController@getRewards');
                Route::post('/create', 'SettingController@createReward');
                Route::post('/update', 'SettingController@updateReward');
                Route::delete('/delete', 'SettingController@deleteReward');
            });

            Route::group(['prefix' => 'tasking'], function() {
                Route::get('/', 'SettingController@getTaskings');
                Route::post('/create', 'SettingController@createTasking');
                Route::post('/update', 'SettingController@updateTasking');
                Route::delete('/delete', 'SettingController@deleteTasking');
            });

            Route::group(['prefix' => 'daily-checkin'], function() {
                Route::get('/', 'SettingController@getDailyCheckinPoints');
                Route::post('/update', 'SettingController@updateDailyCheckinPoint');
                Route::post('/update/multiple', 'SettingController@updateMultipleDailyCheckinPoints');
                Route::group(['prefix' => 'period'], function() {
                    Route::get('/', 'SettingController@getDailyCheckinPeriod');
                    Route::post('/update', 'SettingController@updateDailyCheckinPeriod');
                });
            });

            Route::group(['prefix' => 'room'], function() {
                Route::get('/categories', 'SettingController@getRoomCategories');
                Route::post('/category/create', 'SettingController@createRoomCategory');
                Route::post('/category/update', 'SettingController@updateRoomCategory');
                Route::post('/category/delete', 'SettingController@deleteRoomCategory');
                Route::get('/manager/role', 'SettingController@getRoomUserRole');
                Route::post('/manager/make-role', 'SettingController@makeRoomUserRole');
                Route::delete('/manager/remove-role', 'SettingController@removeRoomUserRole');
            });

            Route::group(['prefix' => 'sms'], function() {
                Route::get('/', 'SettingController@getSmsSetting');
                Route::put('/update', 'SettingController@updateSmsSetting');
            });
        });

        Route::group(['prefix' => 'game', 'namespace' => 'Admin'], function() {
            Route::get('/', 'GameController@getGames');
            Route::get('/{id}/edit', 'GameController@editGame');
            Route::post('/create', 'GameController@createGame');
            Route::post('/update', 'GameController@updateGame');
            Route::post('/{id}/delete', 'GameController@deleteGame');
            Route::put('/order', 'GameController@orderGames');
        });

        Route::group(['prefix' => 'bounty', 'namespace' => 'Admin'], function() {
            Route::get('/', 'BountyController@getBounties');
            Route::get('/request', 'BountyController@getBountyClaimRequest');
        });

        Route::group(['prefix' => 'session', 'namespace' => 'Admin'], function() {
            Route::get('/', 'SessionController@getSessions');
            Route::get('/detail', 'SessionController@getSessionDetail');
        });

        Route::group(['prefix' => 'reviews', 'namespace' => 'Admin'], function () {
            Route::get('/', 'AdminController@getReviews');
            Route::delete('/delete', 'AdminController@deleteReview');
        });

        Route::group(['prefix' => 'user/restrict-pricings', 'namespace' => 'Admin'], function() {
            Route::get('/', 'AdminController@getUserRestrictPricings');
            Route::post('/create', 'AdminController@createUserRestrictPricing');
            Route::put('/update', 'AdminController@updateUserRestrictPricing');
            Route::delete('/delete', 'AdminController@deleteUserRestrictPricing');
        });

        Route::group(['prefix' => 'community', 'namespace' => 'Admin'], function () {
            Route::get('/get-list-request-name-change', 'CommunityController@getListRequestNameChange');
            Route::post('/approve-request-name-change', 'CommunityController@approveRequestNameChange');
            Route::post('/reject-request-name-change', 'CommunityController@rejectRequestNameChange');
            Route::get('/gallery', 'CommunityController@getListGallery');
            Route::post('/gallery/create', 'CommunityController@createGallery');
            Route::post('/gallery/update', 'CommunityController@updateGallery');
            Route::delete('/gallery/delete', 'CommunityController@deleteGallery');
        });
    });

    Route::get('/{view?}', 'Admin\AdminController@index')->where('view', '(.*)');
});
