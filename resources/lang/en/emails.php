<?php

return [
    'welcome' => 'Welcome to :APP_NAME',
    'team' => 'Team :APP_NAME',
    'team_slogan' => '----',
    'team_address' => '(CO) :APP_NAME - street xxx',
    'team_service_center' => 'Service center email. <a href=\'mailto: :email \' target=\'_blank\'> :email </a> / tel. :phone',
    'team_inc' => 'Gamelancer‌ ‌Inc.‌ ‌™‌',

    'confirmation_email_content' => [
        'subject' => 'Activate Your Account',
        'title' => 'Activate Your Account',
        'sub_text' => 'Copy and paste the following code to complete your verification, click on the button below or copy & paste the URL at the bottom.',
        'team_inform' => 'will inform you',
        'text1' => 'Thanks for signing up for a :APP_NAME account!.',
        'text2' => ' Please fill code to verify mail',
        'action' => 'Activate Your Account',
        'expired' => 'This link will expire after 24 hours.'
    ],
    'confirmation_change_email_content' => [
        'subject' => 'Verify your email address change on :APP_NAME',
        'title' => 'Verify Your Email Address',
        'sub_text' => 'Please click the button to verify email.',
        'action' => 'Verify'
    ],
    'confirmation_change_phone_content' => [
        'subject' => 'Verify your phone number change on :APP_NAME',
        'title' => 'Verify Your Phone Number',
        'sub_text' => 'Copy and paste the following code to verify phone number: :code'
    ],
    'confirmation_change_username_content' => [
        'subject' => '【:APP_NAME】Verify your change username on :APP_NAME',
        'title' => 'Verify Your Username',
        'sub_text' => 'Please click the button to verify username:',
        'action' => 'Verify'
    ],
    'otp_email_content' => [
        'subject' => 'Confirm Your Email Address',
        'title' => 'Hello',
        'sub_text' => 'Please type the following code to complete your confirmation',
        'action' => 'Verify'
    ],
    'authorization_code_email_content' => [
        'subject' => 'Authorise your social account attach on :APP_NAME',
        'title' => 'Authorise Your Social Account',
        'sub_text' => 'Please type the following code to complete your confirmation: :code'
    ],
    'change_user_status_email' => [
        'subject' => 'Your account has been changed',
        'team_inform' =>  'will inform you',
        'notification_user_status' => ':APP_NAME sent a notification to you to inform that your account is now',
        'offer_feedback' => 'If you have any questions, you can send your feedback to:'
    ],
    'code_of_claim_bounty_email_content' => [
        'subject' => 'Your code for bounty claim',
        'team_inform' => 'will inform you',
        'your_code' => 'This is your code to verify your bounty claim:'
    ],
    'marketing_email' => [
        'team_inform' => 'will inform you'
    ],
    'reset_password_email' => [
        'subject' => ':APP_NAME - Password Reset - :date',
        'title' => 'Reset Your Password',
        'forget_password_1' => 'We have received your password reset request.',
        'forget_password' => 'Click the link below to create your new password:'
    ],
    'reset_password_code' => [
        'subject' => ':APP_NAME - Password Reset',
        'title' => 'Reset Your Password',
        'forget_password_1' => 'We have received your password reset request.',
        'forget_password' => 'Enter this code below to create your new password:'
    ],
    'claimed_bounty_email' => [
        'subject' => 'Your bounty has been claimed!',
        'team_inform' => 'will inform you',
        'bounty_claimed' => 'Your bounty <strong>:title</strong> has been claimed by <strong>:gamename</strong>.',
    ],
    'rejected_bounty_email' => [
        'subject' => 'Your request claim bounty has been rejected.',
        'team_inform' => 'will inform you',
        'bounty_rejected' => 'The bounty <strong>:title</strong> has been rejected by <strong>:username</strong>.',
    ],
    'approved_bounty_email' => [
        'subject' => 'Your request claim bounty has been approved.',
        'team_inform' => 'will inform you',
        'bounty_approved' => 'The bounty <strong>:title</strong> has been approved by <strong>:username</strong>.',
    ],
    'stopped_bounty_email' => [
        'subject' => 'Your bounty has been marked as completed.',
        'team_inform' => 'will inform you',
        'bounty_stopped' => 'The bounty <strong>:title</strong> has been marked as completed by <strong>:username</strong>.',
    ],
    'canceled_bounty_email' => [
        'subject' => 'Your bounty has been canceled.',
        'team_inform' => 'will inform you',
        'bounty_canceled' => 'The bounty <strong>:title</strong> has been canceled by <strong>:gamename</strong>.',
    ],
    'disputed_bounty_email' => [
        'subject' => 'Your bounty has been disputed.',
        'team_inform' => 'will inform you',
        'bounty_disputed' => 'The bounty <strong>:title</strong> has been disputed by <strong>:username</strong>.',
    ],
    'session_booked_email' => [
        'subject' => ':APP_NAME - Your session has been booked!',
        'team_inform' => 'will inform you',
        'gameprofile_booked' => 'Your session <strong>:title</strong> has been booked for <strong>:type</strong> by <strong>:username</strong>!'
    ],
    'session_rejected_email' => [
        'subject' => ':APP_NAME - Your session has been rejected.',
        'team_inform' => 'will inform you',
        'gameprofile_rejected' => 'Your session <strong>:gamelancername</strong>\'s game profile <strong>:title</strong> has been rejected.'
    ],
    'session_accepted_email' => [
        'subject' => ':APP_NAME - Your session has been accepted!',
        'team_inform' => 'will inform you',
        'gameprofile_accepted' => 'Your session <strong>:gamelancername</strong>\'s game profile <strong>:title</strong> has been accepted!'
    ],
    'session_starting_email' => [
        'subject' => ':APP_NAME - Your session is starting soon!',
        'team_inform' => 'will inform you',
        'session_starting' => 'Your <strong>:gameTitle</strong> session with <strong>:username</strong> is going to start in <strong>:minutes</strong> minutes.'
    ],
    'booking_session_gamelancer_offline_email' => [
        'subject' => ':APP_NAME - Your session have a booking!',
        'team_inform' => 'will inform you',
        'content' => 'While you were away, <strong>:username</strong> tried to book a session with you! You can try messaging them to schedule a session.'
    ],
    'approved_withdraw_email' => [
        'subject' => ':APP_NAME - Your withdrawal request has been approved!',
        'headline' => 'Thank You',
        'hi' => 'Hi :username!',
        'text_1' => 'Thank you for your cash out!',
        'title_1' => 'Your Order information:',
        'title_1_subtitle_1' => 'Order ID:',
        'title_1_subtitle_2' => 'Bill To:',
        'title_1_subtitle_3' => 'Order Date:',
        'title_1_subtitle_4' => 'Source:',
        'source' => 'Gamelancer\'s Cash out',
        'title_2' => 'Here\'s What you cash out:',
        'title_2_subtitle_1' => 'Description',
        'title_2_subtitle_2' => 'Payer',
        'title_2_subtitle_3' => 'Price',
        'title_2_subtitle_4' => 'TOTAL [USD]:',
        'seller_name' => 'Gamelancer‌',
        'memo' => 'Cash out‌',
        'title_3' => 'PAYMENT DETAILS:',
        'title_4' => 'PAID FROM: ',
        'payment_type' => ':paymentType [USD]:',
        'action' => 'Login',
        'amount' => '$:amount',
        'withdraw' => 'You ordered cash out $:releaseAmount for :receiveAmount rewards.'
    ],
    'rejected_withdraw_email' => [
        'subject' => ':APP_NAME - Your withdrawal request has been rejected.',
        'team_inform' => 'will inform you',
        'withdraw_rejected' => 'Your request to withdraw <strong>$:amount</strong> to <strong>:email</strong> has been rejected by <strong>Admin</strong>.',
        'contact_admin' => 'Please contact Admin for details.',
    ],
    'failed_withdraw_email' => [
        'subject' => ':APP_NAME - Your withdrawal request has failed.',
        'team_inform' => 'will inform you',
        'withdraw_failed' => 'Your request to withdraw <strong>$:amount</strong> to <strong>:email</strong> has failed.',
        'contact_admin' => 'Please contact Admin for details.',
    ],
    'become_gamelancer_rejected_email' => [
        'subject' => ':APP_NAME - Your application has been declined.',
        'team_inform' => 'will inform you',
        'become_gamelancer_rejected' => 'Your request to become a Gamelancer has been declined.'
    ],
    'become_gamelancer_approved_email' => [
        'subject' => ':APP_NAME - Your application has been approved!',
        'title' => 'Are you ready, Gamelancer Partner‌?',
        'become_gamelancer_approved' => 'Congrats‌ ‌on‌ ‌becoming‌ ‌a‌ ‌Gamelancer Partner‌.‌ ‌You\'re‌ ‌just‌ ‌a‌ ‌few‌ steps‌ ‌away‌ ‌from‌ ‌booking‌ ‌sessions‌ ‌with‌ ‌gamers‌ and‌ ‌getting‌ ‌paid! Finish setting up your profile and set your schedule to get started.‌',
        'approved_from_freegamelancer' => 'Check and setup the price of your free sessions if any exists.',
        'action' => 'Update Profile'
    ],
    'become_gamelancer_approved_as_free_email' => [
        'subject' => 'Your play session is now live!',
        'title' => 'Welcome Gamelancer!',
        'approved_text_1' => 'Share your play session link online for other gamers to book you to game together. Play sessions, upload videos and stay active in the community to become a Gamelancer Partner. This will allow you to unlock the ability to earn money from your sessions!',
        'action' => 'Get Started'
    ],
    'game_profile_online_email' => [
        'subject' => ':APP_NAME - Your session is online now.',
        'team_inform' => 'will inform you',
        'game_profile_online' => 'Your session on <strong>:gameTitle</strong> is online now.'
    ],
    'bounty_online_email' => [
        'subject' => ':APP_NAME - Your bounty is online now.',
        'team_inform' => 'will inform you',
        'bounty_online' => 'Your bounty on <strong>:gameTitle</strong> is online now.'
    ],
    'change_password_email' => [
        'subject' => ':APP_NAME - Your password has been changed.',
        'action' => 'Login',
        'change_password' => 'Your password has been changed.'
    ],
    'exchange_coins_email' => [
        'subject' => ':APP_NAME - Exchange coins success.',
        'team_inform' => 'will inform you',
        'exchange_coins' => 'You exchanged <strong>:rewards</strong> rewards for <strong>:coins</strong> coins.'
    ],
    'deposit_payment_email' => [
        'subject' => ':APP_NAME - Deposit success.',
        'headline' => 'Thank You',
        'hi' => 'Hi :username!',
        'text_1' => 'Thank you for your purchase!',
        'title_1' => 'Your Order information:',
        'title_1_subtitle_1' => 'Order ID:',
        'title_1_subtitle_2' => 'Bill To:',
        'title_1_subtitle_3' => 'Order Date:',
        'title_1_subtitle_4' => 'Source:',
        'source' => 'Gamelancer\'s Offers',
        'title_2' => 'Here\'s What you ordered:',
        'title_2_subtitle_1' => 'Description',
        'title_2_subtitle_2' => 'Seller',
        'title_2_subtitle_3' => 'Price',
        'title_2_subtitle_4' => 'TOTAL [USD]:',
        'seller_name' => 'Gamelancer‌',
        'title_3' => 'PAYMENT DETAILS:',
        'title_4' => 'PAID FROM: ',
        'payment_type' => ':paymentType [USD]:',
        'action' => 'Login',
        'amount' => '$:amount',
        'deposit' => 'You ordered :receiveAmount coins for $:releaseAmount.'
    ],
    'welcome_email' => [
        'subject' => ':APP_NAME - Welcome to gamelancer',
        'pre_header' => 'Make real connections, get wins, and have fun.',
        'headline' => 'This is where your game gets better.',
        'subhead_1' => 'Thanks‌ ‌for‌ ‌joining‌ ‌Gamelancer!‌',
        'subhead_2' => 'Whether‌ ‌you\'re‌ ‌looking‌ ‌to‌ ‌improve‌ ‌your‌ ‌skills,‌ ‌build‌ ‌the‌ ‌perfect‌ team,‌ ‌or‌ ‌just‌ ‌have‌ ‌fun‌ ‌playing‌ ‌with‌ ‌people‌ ‌as‌ ‌passionate‌ ‌about‌ gaming‌ ‌as‌ ‌you,‌ ‌you\'re‌ ‌just‌ ‌a‌ ‌few‌ ‌steps‌ ‌away‌ ‌from‌ ‌booking‌ ‌a‌ session‌ ‌and‌ ‌taking‌ ‌your‌ ‌game‌ ‌to‌ ‌the‌ ‌next‌ ‌level.‌',
        'module_1_title' => 'Find a Gamelancer.',
        'module_1_subtitle' => 'Search‌ ‌available‌ ‌Gamelancers‌ ‌by‌ ‌name‌ ‌or‌ ‌the‌ ‌games‌ ‌they‌ ‌play.‌ Book‌ ‌a‌ ‌time‌ ‌to‌ ‌play‌ ‌and‌ ‌start‌ ‌gaming!‌',
        'module_1_action' => 'Browse gamelancers',
        'module_2_title' => 'Hit‌ ‌refresh‌ ‌on‌ ‌how‌ ‌it‌ ‌works‌',
        'module_2_subtitle' => 'Check‌ ‌out‌ ‌our‌ ‌quickstart‌ ‌guide‌ ‌for‌ ‌Gamelancer,‌ ‌including‌ ‌our‌ Code‌ ‌of‌ ‌Conduct‌ ‌to‌ ‌make‌ ‌our‌ ‌community‌ ‌welcoming‌ ‌to‌ everyone.',
        'module_2_action' => 'Learn more',
        'module_3_title' => 'Think‌ ‌you\'re‌ ‌Gamelancer‌ ‌material?‌',
        'module_3_subtitle' => 'Are‌ ‌other‌ ‌gamers‌ ‌willing‌ ‌to‌ ‌pay‌ ‌for‌ ‌your‌ ‌expertise?‌ ‌Sign‌ ‌up‌ ‌today‌ to‌ ‌become‌ ‌a‌ ‌Gamelancer‌ ‌and‌ ‌start‌ ‌making‌ ‌money‌ ‌with‌ ‌your‌ gaming‌ ‌skills.‌',
        'module_3_action' => 'Become‌ ‌a‌ ‌Gamelancer‌'
    ],
    'new_game_profile_email' => [
        'subject' => ':APP_NAME - New Session Online',
        'title' => 'New Session',
        'new_game_profile' => '<strong>:username</strong> just published new session for <strong>:gameTitle.</strong>'
    ],
    'new_bounty_email' => [
        'subject' => ':APP_NAME - New Bounty Online',
        'title' => 'New Bounty',
        'new_bounty' => '<strong>:username</strong> just published new bounty - <strong>:title.</strong>'
    ],
    'session_review_email' => [
        'subject' => ':APP_NAME - Session Review',
        'team_inform' => 'will inform you',
        'session_review' => '<strong>:reviewerName</strong> give you a <strong>:rate</strong> star review for the <strong>:gameTitle</strong> session.'
    ],
    'bounty_review_email' => [
        'subject' => ':APP_NAME - Bounty Review',
        'team_inform' => 'will inform you',
        'bounty_review' => '<strong>:reviewerName gave you a <strong>:rate</strong> star review for the <strong>:bountyTitle</strong> bounty.'
    ],
    'intro_task_completion' => [
        'subject' => ':APP_NAME - Intro Tasks Completed!',
        'text1' => "Congrats on completing the intro tasks! Go to Task Center to claim :coin coins."
    ],
    'intro_task_reminder' => [
        'subject' => ':APP_NAME - Intro Task Reminder!',
        'text1' => "Let's walk you through the platform! Complete the simple intro tasks to receive rewards and experience!"
    ]
];
