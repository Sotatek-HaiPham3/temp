<template>
  <div id="session-detail" class="boxCore">
    <h3>User Info</h3>
      <div class="item">
        <p class="title">Username:</p>
        <p class="value">{{ data.user.username }}</p>
      </div>
      <div class="clearfix"></div>
      <div class="item">
        <p class="title">Email:</p>
        <p class="value">{{ data.user.email }}</p>
      </div>
      <div class="clearfix"></div>
      <div class="item">
        <p class="title">Date of birth:</p>
        <p class="value">{{ data.user.dob }}</p>
      </div>
      <div class="clearfix"></div>
      <div class="item">
        <p class="title">Gender:</p>
        <p class="value" v-if="data.user.sex === 1">Male</p>
        <p class="value" v-else-if="data.user.sex === 2">Non-binary</p>
        <p class="value" v-else>Female</p>
      </div>
      <div class="clearfix"></div>
      <div class="item">
        <p class="title">Available time:</p>
        <p class="value">{{ data.total_hours }}</p>
      </div>
      <div class="clearfix"></div>
      <div class="item">
        <p class="title">Social account:</p>
        <p class="value">
          <a class="three-dots" href="javascript:void(0)" @click.stop="gotoSocialLink(data)">{{ data.social_link ? data.social_link.url : '' }}</a>
        </p>
      </div>
      <div class="clearfix"></div>
      <div class="item">
        <p class="title">Status:</p>
        <p class="value capitalize">{{ $t(`gamelancer_page.status.${data.status}`) }}</p>
      </div>
      <div class="clearfix"></div>
    <h3>Session Info</h3>
    <div class="item">
      <p class="title">Title:</p>
      <p class="value">{{ data.game_profile.title }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Game:</p>
      <p class="value">{{ data.game_profile.game.title }}</p>
    </div>
    <!-- <div class="clearfix"></div>
    <div class="item">
      <p class="title">Rank:</p>
      <p class="value">{{ rankForGame(data.rank_id, data.game_id) }}</p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Server:</p>
      <p class="value">
        <span class="value-item" v-for="server in data.match_servers">{{ serverForGame(server.game_server_id, data.game_id) }}</span>
      </p>
    </div> -->
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Platform:</p>
      <p class="value">
        <span class="value-item" v-for="platform in data.game_profile.platforms">{{ platformGame(platform.platform_id, data.game_profile.game_id) }}</span>
      </p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Audio:</p>
      <p class="value">
        <audio v-if="data.game_profile.audio" controls ref="audio">
          <source :src="data.game_profile.audio"></source>
        </audio>
      </p>
    </div>
    <div class="clearfix"></div>
    <div class="item">
      <p class="title">Price:</p>
      <p class="value" v-for="i in data.game_profile.game_offers">{{ i.price | formatCurrencyAmount('0', 2) }} coins /
        <template v-if="i.type === 'hour'">hr</template>
        <template v-else-if="i.type === 'per_game'">g</template>
      </p>
    </div>
    <div class="clearfix"></div>
    <div class="item" v-if="data.game_profile.medias">
      <p>Media</p>
      <div class="media" v-for="item in data.game_profile.medias">
        <template v-if="isVideo(item.url)">
          <video
            class="video"
            autoplay
            loop
            preload
            muted
            playsinline
          >
            <source :src="item.url">
          </video>
        </template>
        <template v-else>
          <img :src="item.url">
        </template>
      </div>
    </div>
    <div class="clearfix"></div>
    <div class="action-group" v-if="data.status === 'pending'">
      <button class="btn action-btn" @click.stop="approve()">Approve</button>
      <button class="btn action-btn" @click.stop="approveAsFree()">Approve as Free Gamelancer</button>
      <button class="btn action-btn disapprove" @click.stop="disapprove()">Disapprove</button>
    </div>
  </div>
</template>

<script>
  import rf from '../../lib/RequestFactory'
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin'
  import { mapState } from 'vuex'

  const UNRANKED = 'Unranked'
  const COMMON_PLATFORM = 'Common'
  const COMMON_SERVER = 'Common'

  export default {
    mixins: [RemoveErrorsMixin],
    data() {
      return {
        data: {
          game_profile: {
            game: {},
            medias: [],
            game_offers: []
          },
          social_link: {},
          user: {}
        },
        titlePage: 'Gamelancer Form Detail',
      }
    },

    computed: {
      ...mapState(['masterdata'])
    },

    methods: {
      approve () {
        return this.requestHandler(rf.getRequest('AdminRequest').approveGamelancer({ id: this.data.id }))
      },

      approveAsFree () {
        return this.requestHandler(rf.getRequest('AdminRequest').approveFreeGamelancer({ id: this.data.id }))
      },

      disapprove () {
        return this.requestHandler(rf.getRequest('AdminRequest').disapproveGamelancer({ id: this.data.id }))
      },

      requestHandler(promise) {
        if (this.isSubmitting || this.isLoading) {
          return
        }

        this.isLoading = true
        this.startSubmit()
        promise.then(res => {
          this.getGamelancerInfoDetail()
          this.showSuccess(this.$t('common.update_sucessful'))
        })
        .catch(error => {
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"))
            return
          }
          this.convertRemoteErrors(error)
          if (this.errors.has('error')) {
            this.showError(error.response.data.message)
          }
        })
        .finally(() => {
          this.isLoading = false
          this.endSubmit()
        })
      },

      getGamelancerInfoDetail() {
        return rf.getRequest('AdminRequest').getGamelancerInfoDetail({id: this.$route.params.id})
          .then(res => {
            this.data = res.data
            this.playAudio()
          })
      },

      playAudio () {
        this.$nextTick(() => {
          if (this.$refs.audio) {
            this.$refs.audio.load()
          }
        })
      },

      rankForGame(rankId, gameId) {
        if (!rankId || !gameId) {
          return UNRANKED
        }
        if (!this.masterdata.games) {
          return UNRANKED
        }
        const game = this.masterdata.games.find(i => i.id === gameId)
        if (!game || !game.ranks) {
          return UNRANKED
        }
        const rank = game.ranks.find(i => i.id === rankId)
        return rank ? rank.name : UNRANKED
      },

      serverForGame(serverId, gameId) {
        if (!this.masterdata.games) {
          return COMMON_SERVER
        }
        if (!serverId || !gameId) {
          return COMMON_SERVER
        }
        const game = this.masterdata.games.find(i => i.id === gameId)
        if (!game || !game.servers) {
          return COMMON_SERVER
        }
        const server = game.servers.find(i => i.id === serverId)
        return server ? server.name : COMMON_SERVER
      },

      platformGame(platformId) {
        if (!this.masterdata.platforms) {
          return COMMON_PLATFORM
        }
        const platform = this.masterdata.platforms.find(i => i.id === platformId)
        if (!platform || !platform.name) {
          return COMMON_PLATFORM
        }
        return platform.name
      },

      isVideo (url) {
        return !url.match(/\.(jpeg|jpg|gif|png|bmp|svg)$/)
      },

      gotoSocialLink(item) {
        if (!item.social_link) {
          return
        }

        let url = item.social_link.url;
        url = url.match(/^https?:/) ? url : '//' + url;
        window.open(url, '_blank');
      }
    },

    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this);
      this.getGamelancerInfoDetail()
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
        word-break: break-word;
        max-width: calc(100% - 200px);
        .value-item {
          padding: 5px;
          background-color: #55d184;
          color: white;
          margin-right: 10px;
        }
        audio {
          outline: none;
          &::-webkit-media-controls-panel {
            background-color: #cfcfcf;
          }
        }
      }
      .capitalize {
        text-transform: capitalize;
      }
      .media {
        float: left;
        img, video {
          max-width: 300px;
          margin-right: 10px;
        }
      }
    }
    .action-group {
      margin-top: 15px;
      .action-btn {
        background-color: #12575f;
        color: white;
        &.disapprove {
          background-color: red;
        }
      }
    }
  }
</style>
