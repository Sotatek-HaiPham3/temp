<template>
  <div class="boxCore" id="leveling-setting">
    <div class="tab">
      <button class="tablinks"
              :class="{ active: tab === TABS.VOICE_GROUP }"
              @click="tab = TABS.VOICE_GROUP"
      >Voice Group</button>

      <button class="tablinks"
              :class="{ active: tab === TABS.VOICE_GROUP_ROLE }"
              @click="tab = TABS.VOICE_GROUP_ROLE"
      >Voice Group Role</button>
    </div>
    <section class="clearfix" v-if="tab === TABS.VOICE_GROUP">
      <voice-group></voice-group>
    </section>
    <section class="clearfix" v-if="tab === TABS.VOICE_GROUP_ROLE">
      <voice-group-role></voice-group-role>
    </section>
  </div>
</template>

<script>
  import VoiceGroup from './components/VoiceGroupTab'
  import VoiceGroupRole from './components/VoiceGroupRoleTab'
  const TABS = {
    VOICE_GROUP: 'voice-group',
    VOICE_GROUP_ROLE: 'voice-group-role'
  }
  export default {
    components: {
      VoiceGroup,
      VoiceGroupRole
    },
    data () {
      return {
        titlePage: 'voice group',
        TABS,
        tab: TABS.VOICE_GROUP
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
