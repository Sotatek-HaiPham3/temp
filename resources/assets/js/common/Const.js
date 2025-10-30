export default {
  KEYS_GLOBAL: {
    // 'APP_NAME'      : APP_NAME,
    // 'COIN_HOLDING'  : 'MGC'
  },

  NO_NAME: 'No Name',
  USER_UNKNOWN: 'Unknown',
  ADDRESS_UNKNOWN: 'Unknown',
  DEFAULT_AVATAR: '/images/user-default.svg',
  EPIC_LOGO: '/images/epic.png',

  ACCOUNT_TAB_CONFIG : {
    INFO          : 'info',
    DASHBOARD     : 'wallets',
    BOUNTIES      : 'bounties',
    REVIEWS       : 'reviews',
    FAVORITES     : 'favorites',
    SOCIALNETWORK : 'socialnetworks',
    VERIFY_EMAIL  : 'verify email',
    SETTINGS      : 'settings',
  },
  PAYMENT_TYPE : {
    WITHDRAW : 'withdraw',
    DEPOSIT : 'deposit',
    LINK_BANK : 'link_bank',
    CONVERT : 'convert',
  },

  LIST_BANK: {
    GLBARS: {
      TYPE: 'bar',
      NAME: 'Gamelancer Bars',
    },
    PAYPAL: {
      TYPE: 'paypal',
      NAME: 'Paypal',
    },
    CREDIT_CARD: {
      TYPE: 'credit card',
      NAME: 'Credit Card',
    },
  },

  ADD_GAME_BOUNTY         : 'AddGameBounty',

  BOUNTY_CLAIMED          : 'claimed',
  BOUNTY_ACCEPTED         : 'accepted',
  BOUNTY_STARTING         : 'starting', // Gamelancer make request start play.
  BOUNTY_STARTED          : 'started', // Bounty is playing.
  BOUNTY_COMPLETED        : 'completed',
  BOUNTY_CANCELED         : 'canceled',
  BOUNTY_REJECTED         : 'rejected',
  BOUNTY_STOPPING         : 'stopping',
  BOUNTY_STOPPED          : 'stopped',

  BOUNTY_TRIGGER_STARTING   : 'trigger_starting',
  BOUNTY_TRIGGER_STARTED    : 'trigger_started',
  BOUNTY_TRIGGER_COMPLETED  : 'trigger_completed',
  BOUNTY_TRIGGER_REJECTED   : 'trigger_rejected',
  BOUNTY_TRIGGER_REQUESTED_ADD_TIME : 'trigger_requested_add_time',
  BOUNTY_TRIGGER_CONFIRMED_ADD_TIME : 'trigger_confirmed_add_time',
  BOUNTY_TRIGGER_REQUESTED_STOP     : 'trigger_requested_stop',
  BOUNTY_TRIGGER_CONFIRMED_STOP     : 'trigger_confirmed_stop',
  BOUNTY_TRIGGER_WAITING            : 'trigger_waiting',
  BOUNTY_TRIGGER_CLAIMED            : 'trigger_claimed',
  BOUNTY_TRIGGER_CANCEL             : 'trigger_cancel',
  BOUNTY_TRIGGER_ACCEPTED           : 'trigger_accepted',

  NOTIFICATION_TYPE_CLAIMED_BOUNTY : 'Claimed Bounty',

  BROADCAST_RATING_MODAL            : 'RatingModal',
  BROADCAST_BOUNTY_COUNTER_MODAL    : 'BountyCounterModal',

  TIMES   : [15, 30, 60],
  DONATES : [5, 10, 20],

  CREDIT_CARD_TYPE : {
    CREDIT_CARD : 'credit card',
    PAYPAL : 'paypal',
    IBAN : 'iban',
  },

  RATE_USD_COIN: 'usd_to_coin',
  RATE_COIN_BAR: 'coin_to_bar',
  RATE_BAR_USD: 'bar_to_usd',
  REGION_DEFAULT: 'default',

  CURRENCY_USD: 'usd',

  CONVERT_CURRENCIES: {
    1: {id: 1, fromCurrency: 'coin', toCurrency: 'bar', name: 'Coin to Bar'},
    2: {id: 2, fromCurrency: 'bar', toCurrency: 'coin', name: 'Bar to Coin'},
  },

  WEEKDAYS: [
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday'
  ],

  SCHEDULES: {
    'monday': [],
    'tuesday': [],
    'wednesday': [],
    'thursday': [],
    'friday': [],
    'saturday': [],
    'sunday': [],
  },

  MEDIA_IMAGE: 'image',
  MEDIA_VIDEO: 'video',

  PAYPAL_DEPOSIT_EXECUTING: 'executing',
  PAYPAL_DEPOSIT_FAILED: 'failed',

  REASON_TYPE_OTHERS: 'others',
  REASON_TYPE_STOP_BOUNTY: 'stop-bounty',
  REASON_TYPE_CANCEL_BOUNTY: 'cancel-bounty',

  NOTIFICATION_TYPE_NOTICE: 'notice',
  NOTIFICATION_TYPE_MESSAGE: 'message',

  TRANSACTION_INTERNAL: 'internal',
  TRANSACTION_DEPOSIT: 'deposit',
  TRANSACTION_WITHDRAW: 'withdraw',

  TRANSACTION_STATUS_FAILED: 'failed',
  TRANSACTION_STATUS_SUCCESS: 'success',
  TRANSATION_MEMO_TIP: 'Tip',
}
