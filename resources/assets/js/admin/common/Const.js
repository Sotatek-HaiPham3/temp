export default {
  LIST_NETWORKS: [
    'Facebook',
    'Twitter',
    'Telegram',
    'Linkerdin',
    'Youtube',
    'WhatsApp',
    'Reddit',
    'Instagram',
    'Google Plus'
  ],
  ICON_CLASSES: [
    'icon-facebook',
    'icon-twitter',
    'icon-telegram',
    'icon-linkedin',
    'icon-youtube',
    'icon-whatsapp',
    'icon-reddit',
    'icon-instagram',
    'icon-google-plus'
  ],
  MARKETS: ['mgc', 'eth', 'btc'],
  MENU: [
    {
      type: 'tree',
      icon: 'icon-setting',
      name: 'Site Setting',
      isHasPermission: false,
      router: {
        name: 'site_setting'
      },
    },
    {
      type: 'tree',
      icon: 'icon-avatar',
      name: 'Users',
      isHasPermission: false,
      items: [
        {
          type: 'item',
          icon: '',
          name: 'User list',
          router: {
            name: 'users'
          }
        },
        // {
        //   type: 'item',
        //   icon: '',
        //   name: 'Kyc',
        //   router: {
        //     name: 'kyc',
        //     subRoutes: '/kyc/detail',
        //   }
        // },
        // {
        //   type: 'item',
        //   icon: '',
        //   name: 'User setting',
        //   router: {
        //     name: 'user_setting'
        //   }
        // },
        // {
        //   type: 'item',
        //   icon: '',
        //   name: 'Device Management',
        //   router: {
        //     name: 'device_management'
        //   }
        // },
      ]
    },
    // {
    //   type: 'item',
    //   icon: 'icon-shield',
    //   name: 'Permission',
    //   isHasPermission: false,
    //   router: {
    //     name: 'Permissions',
    //     subRoutes: '/permissions/edit',
    //   }
    // },
  ],
  ROLE_SUPER_ADMIN: 'Super Admin',
  ROLE_ADMIN: 'Admin',

  SESSION_STATUS: [
    'booked',
    'accepted',
    'rejected',
    'canceled',
    'starting',
    'running',
    'stopped',
    'completed',
    'outdated'
  ],

  TRANSACTION_TYPES: {
    DEPOSIT: 'deposit',
    CONVERT: 'convert',
    WITHDRAW: 'withdraw'
  },

  TRANSACTION_PAYMENT_STATUS: {
    SUCCESS: 'success',
    PENDING: 'pending',
    REJECTED: 'rejected',
    FAILED: 'failed',
    CREATED: 'created',
    EXECUTING: 'executing',
    DENIED: 'denied',
    CANCEL: 'cancel'
  }
}
