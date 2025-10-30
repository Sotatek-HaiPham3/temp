<template>
  <div class="boxCore" id="leveling-setting">
    <div class="tab">
      <button class="tablinks"
        :class="{ active: tab === TABS.RANKING }"
        @click="tab = TABS.RANKING"
      >Ranking</button>

      <button class="tablinks"
        :class="{ active: tab === TABS.REWARD }"
        @click="tab = TABS.REWARD"
      >Reward</button>

      <button class="tablinks"
        :class="{ active: tab === TABS.TASKING }"
        @click="tab = TABS.TASKING"
      >Tasking</button>
      <button class="tablinks"
        :class="{ active: tab === TABS.DAILY_CHECKIN }"
        @click="tab = TABS.DAILY_CHECKIN"
      >Daily Check-in</button>
    </div>
    <section class="clearfix" v-if="tab === TABS.RANKING">
      <ranking-tab></ranking-tab>
    </section>
    <section class="clearfix" v-if="tab === TABS.REWARD">
      <reward-tab></reward-tab>
    </section>
    <section class="clearfix" v-if="tab === TABS.TASKING">
      <tasking-tab></tasking-tab>
    </section>
    <section class="clearfix" v-if="tab === TABS.DAILY_CHECKIN">
      <daily-checkin-tab></daily-checkin-tab>
    </section>
  </div>
</template>

<script>
import RankingTab from './components/RankingTab'
import RewardTab from './components/RewardTab'
import TaskingTab from './components/TaskingTab'
import DailyCheckinTab from './components/DailyCheckinTab'

  const TABS = {
    RANKING: 'ranking',
    REWARD: 'reward',
    TASKING: 'tasking',
    DAILY_CHECKIN: 'daily-checkin'
  }

  export default {
    components: {
      RankingTab,
      RewardTab,
      TaskingTab,
      DailyCheckinTab
    },

    data () {
      return {
        titlePage: 'ranking',
        TABS,
        tab: TABS.RANKING
      }
    },

    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this)
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/common";

  #leveling-setting {
    .tab {
      overflow: hidden;
      background-color: #f1f1f1;
      border-bottom: 1px solid #CFCFCF;
      button {
        padding: 0 20px;
        height: 40px;
        box-sizing: border-box;
        line-height: 40px;
        display: inline-block;
        list-style: none;
        font-size: 14px;
        font-weight: 500;
        background: transparent;
        border: none;
        &:hover {
          color: inherit;
        }
      }
      .active {
        border-bottom: 2px solid #12575f;
      }
    }
  }
</style>
