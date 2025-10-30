<template>
  <div id="session-detail" class="boxCore">
    <div class="item">
      <p class="title">Gamelancer name:</p>
      <p class="value">{{ data.gamelancer_info.username }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Claimer name:</p>
      <p class="value">{{ data.claimer_info.username }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Game:</p>
      <p class="value">{{ data.game_profile.game.title }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Price:</p>
      <p class="value">{{ data.game_offer.price | formatGameOffer(data.game_offer.type) }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Quantity:</p>
      <p class="value">{{ data.quantity | formatCurrencyAmount('0', 2) }}{{ gameType }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Quantity Played:</p>
      <p class="value">{{ data.quantity_played | formatCurrencyAmount('---', 2) }}{{ gameType }}</p>
    </div>
    <template v-if="isStoppedSession">
      <div class="clearfix"></div>
      <div class="item">
        <p class="title">Paid Amount:</p>
        <p class="value">{{ paidAmount | formatCurrencyAmount('0', 2) }} coins</p>
      </div>
      <div class="clearfix"></div>
      <div class="item">
        <p class="title">Refund Amount:</p>
        <p class="value">{{ refundAmount | formatCurrencyAmount('0', 2) }} coins</p>
      </div>
    </template>
    <template v-else>
      <div class="clearfix"></div>
      <div class="item">
        <p class="title">Escrow Balance:</p>
        <p class="value">{{ data.escrow_balance | formatCurrencyAmount('0', 2) }} coins</p>
      </div>
    </template>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Status:</p>
      <p class="value">{{ data.status | upperFirst }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Schedule At:</p>
      <p class="value">{{ data.schedule_at | timestampToDate }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Start At:</p>
      <p class="value">{{ data.start_at | timestampToDate }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">End At:</p>
      <p class="value">{{ data.end_at | timestampToDate }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item" v-if="data.reason">
      <p class="title">Stopped by:</p>
      <p class="value">{{ data.claimer_stop ? data.claimer_info.username : data.gamelancer_info.username }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item" v-if="data.reason">
      <p class="title">Reason:</p>
      <p class="value">{{ data.reason.content }}</p>
    </div>
  </div>
</template>

<script>
  import rf from '../../lib/RequestFactory';
  import { mapState } from 'vuex';
  import BigNumber from 'bignumber.js'

  const UNRANKED = 'Unranked'
  const COMMON_PLATFORM = 'Common'
  const COMMON_SERVER = 'Common'
  export default {
    data() {
      return {
        data: {
          gamelancer_info: {},
          claimer_info: {},
          game_offer: {},
          game_profile: {
            game: {}
          }
        },
        titlePage: 'Session Detail',
      }
    },

    computed: {
      ...mapState(['masterdata']),

      isStoppedSession () {
        return this.data.status === 'stopped'
      },

      gameType () {
        if (this.data.game_offer.type === 'per_game') {
          return 'g'
        }

        return 'h'
      },

      escrowBalance () {
        if (this.data.escrow_balance) {
          return this.data.escrow_balance
        }

        const offerPrice = parseFloat(this.data.game_offer.price)
        const offerQuantity = parseFloat(this.data.game_offer.quantity)
        const quantity = parseFloat(this.data.quantity)
        return new BigNumber(offerPrice).div(offerQuantity).mul(quantity).toFixed(2)
      },

      paidAmount () {
        if (!this.isStoppedSession) {
          return this.data.escrow_balance
        }

        if (this.data.claimer_stop) {
          return this.data.escrow_balance
        }

        const offerPrice = parseFloat(this.data.game_offer.price)
        const offerQuantity = parseFloat(this.data.game_offer.quantity)
        const quantityPlayed = parseFloat(this.data.quantity_played)
        return new BigNumber(offerPrice).div(offerQuantity).mul(quantityPlayed).toFixed(2)
      },

      refundAmount () {
        if (!this.isStoppedSession) {
          return 0
        }

        if (this.data.claimer_stop) {
          return 0
        }

        const escrowBalance = parseFloat(this.escrowBalance)
        const paidAmount = parseFloat(this.paidAmount)
        return new BigNumber(escrowBalance).minus(paidAmount).toFixed(2)
      }
    },

    methods: {
      getGameProfileDetail() {
        return rf.getRequest('SessionRequest').getSessionDetail({id: this.$route.params.id})
          .then(res => {
            this.data = res.data
            this.titlePage = this.data.game_profile.title
            this.$emit('EVENT_PAGE_CHANGE', this)
          })
      }
    },

    mounted() {
      this.getGameProfileDetail()
    }
  }
</script>

<style lang="scss" scoped>
  #session-detail {
    .item {
      float: left;
      .title {
        width: 200px;
        float: left;
      }
      .value {
        float: left;
        .value-item {
          padding: 5px;
          background-color: #55d184;
          color: white;
          margin-right: 10px;
        }
      }
      .media {
        img {
          max-width: 300px;
          margin-right: 10px;
        }
      }
    }
  }
</style>
