import Vue from 'vue';
import VueRouter from 'vue-router';
import NotFound from './pages/NotFound';
import WrapPage from './pages/WrapPage';

import Users from './pages/users/Users';
import GamelancerInfo from './pages/users/GamelancerInfo';
import GamelancerInfoDetail from './pages/users/GamelancerInfoDetail';
import InvitationCode from './pages/users/InvitationCode';
import UserBalance from './pages/users/UserBalance';

import Deposit from './pages/transactions/Deposit';
import Withdrawals from './pages/transactions/Withdraw';

import GameSetting from './pages/games/GamesList';
import EditGame from './pages/games/EditGame';

import SiteSetting from './pages/setting/SiteSetting';
import BannerSetting from './pages/setting/BannerSetting';
import PlatformSetting from './pages/setting/PlatformSetting';
import OfferSetting from './pages/setting/OfferSetting';
import LevelingSetting from './pages/setting/LevelingSetting';

import BountyList from './pages/bounties/BountyList';
import SessionList from './pages/sessions/SessionList';
import SessionDetail from './pages/sessions/SessionDetail';

import ReviewList from './pages/reviews/ReviewList';
import UserRestrictPricing from './pages/users/UserRestrictPricing';

// import ReviewList from './pages/reviews/ReviewList';

Vue.use(VueRouter);

export default new VueRouter({
  mode: 'history',
  base: '/admin',
  routes: [
    {
      path: '/',
      redirect: {name: 'Users'},
      alias: '/home',
      meta: {}
    },
    {
      path: '/users',
      component: WrapPage,
      meta: {
        type: 'treeview',
        icon: 'icon-avatar',
        routerNameDisp: 'Account',
        hasPermission: true,
      },
      children: [
        {
          path: '/',
          fullPath: '/users',
          name: 'Users',
          component: Users,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Users List',
            hasPermission: true,
          }
        },
        {
          path: 'gamelancer-info',
          fullPath: '/users/gamelancer-info',
          name: 'Gamelancer Info',
          component: GamelancerInfo,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Gamelancer Forms',
            hasPermission: true,
            sub: ['Session Detail']
          }
        },
        {
          path: 'gamelancer-info/:id/detail',
          fullPath: '/users/gamelancer-info/:id/detail',
          name: 'Gamelancer Info Detail',
          component: GamelancerInfoDetail,
          meta: {
            hasPermission: true,
          }
        },
        {
          path: 'invitation-code',
          fullPath: '/users/invitation-code',
          name: 'Invitation Code',
          component: InvitationCode,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Invitation Codes',
            hasPermission: true,
          }
        },
        {
          path: 'balance',
          fullPath: '/users/balance',
          component: UserBalance,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Balances',
            hasPermission: true,
          }
        }
      ]
    },
    {
      path: '/setting',
      component: WrapPage,
      meta: {
        type: 'treeview',
        icon: 'icon-setting',
        routerNameDisp: 'Setting',
        hasPermission: true,
      },
      children: [
        {
          path: 'general',
          fullPath: '/setting/general',
          name: 'Site Setting',
          component: SiteSetting,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'General',
            hasPermission: true,
          }
        },
        {
          path: 'banners',
          fullPath: '/setting/banners',
          name: 'Banners Setting',
          component: BannerSetting,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Banners',
            hasPermission: true,
          }
        },
        {
          path: 'games',
          fullPath: '/setting/games',
          name: 'Games Setting',
          component: GameSetting,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Games',
            hasPermission: true,
            sub: ['Add Game', 'Edit Game']
          }
        },
        {
          path: 'games/add',
          fullPath: '/setting/games/add',
          name: 'Add Game',
          component: EditGame,
          meta: {
            hasPermission: true,
          }
        },
        {
          path: 'games/:id/edit',
          fullPath: '/setting/games/:id/edit',
          name: 'Edit Game',
          component: EditGame,
          meta: {
            prop: true,
            hasPermission: true,
          }
        },
        {
          path: 'restrict-pricing',
          fullPath: '/setting/restrict-pricing',
          name: 'User Restrict Pricing',
          component: UserRestrictPricing,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'User Restrict Pricings',
            hasPermission: true
          }
        },
        {
          path: 'leveling',
          fullPath: '/setting/leveling',
          name: 'Leveling Setting',
          component: LevelingSetting,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Leveling',
            hasPermission: true,
          }
        }
      ]
    },
    {
      path: '/transactions',
      component: WrapPage,
      meta: {
        type: 'treeview',
        icon: 'icon-fund',
        routerNameDisp: 'Transactions',
        hasPermission: true,
      },
      children: [
        {
          path: 'deposit',
          fullPath: '/transactions/deposit',
          name: 'Deposit',
          component: Deposit,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Deposit',
            hasPermission: true,
          }
        },
        {
          path: 'withdrawals',
          fullPath: '/transactions/withdrawals',
          name: 'withdrawals',
          component: Withdrawals,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Withdrawals',
            hasPermission: true,
          }
        },
      ]
    },
    {
      path: '/statistics',
      component: WrapPage,
      meta: {
        type: 'treeview',
        icon: 'fa fa-database',
        routerNameDisp: 'Statistics',
        hasPermission: true,
      },
      children: [
        {
          path: 'bounties',
          fullPath: '/statistics/bounties',
          name: 'Bounties',
          component: BountyList,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Bounties',
            hasPermission: true
          }
        },
        {
          path: 'sessions',
          fullPath: '/statistics/sessions',
          name: 'Sessions',
          component: SessionList,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Sessions',
            hasPermission: true,
            sub: ['Session Detail']
          }
        },
        {
          path: 'session/:id',
          fullPath: 'statistics/session/:id',
          name: 'Session Detail',
          component: SessionDetail,
          meta: {
            prop: true,
            hasPermission: true,
          }
        },
        {
          path: 'reviews',
          fullPath: '/statistics/reviews',
          name: 'Reviews',
          component: ReviewList,
          meta: {
            type: 'item',
            icon: '',
            routerNameDisp: 'Reviews',
            hasPermission: true
          }
        }
      ]
    },
    { path: '*', name: 'Not Found', component: NotFound, meta: {} }
  ]
})
