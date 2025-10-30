<template>
  <div class="boxCore" id="gamelancer_information">
    <section class="clearfix">
      <div class="datatable">
        <data-table :getData="getData" ref="datatable" :limit="10" :column="3" class="scroll">
          <th class="cl1 text-left">Code</th>
          <th class="cl1 text-left">Create Date</th>
          <th class="cl3 text-left"></th>
          <template slot="body" slot-scope="props">
            <tr>
              <td class="cl1 text-left">
                {{ props.item.code }}
              </td>
              <td class="cl1 text-left">
                {{ props.item.created_at }}
              </td>
              <td class="cl6 text-right">
              </td>
            </tr>
          </template>
        </data-table>
      </div>

      <loading :isLoading="isLoading"/>
    </section>
  </div>
</template>

<script>
  import rf from '../../lib/RequestFactory';
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin';

  export default {
    mixins: [RemoveErrorsMixin],
    data() {
      return {
        titlePage: 'Invitation Code',
        params: {},
        isLoading: false
      }
    },
    methods: {
      getData(params) {
        return rf.getRequest('AdminRequest').getInvitationCodes(params);
      },

      requestHandler(promise) {
        this.startSubmit();
        promise.then(res => {
          this.endSubmit();
          this.refresh();
          this.isLoading = false;
          this.showSuccess(this.$t('common.update_sucessful'));
        })
        .catch(error => {
          this.endSubmit();
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
        });
      },

      refresh() {
        this.$refs.datatable.refresh();
      },
    },

    mounted() {
      this.$emit('EVENT_PAGE_CHANGE', this);
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/variables";

  .box_select_level {
    width: 40px;
  }
  .box_select_user {
    width: 85px;
  }
  .cl1{
    max-width: 300px;
  }
  .cl3 {
    max-width: 150px;
    input {
      width: 40px;
      padding: 0px 0px 0px 15px;
      height: 30px;
    }
  }
  .cl5 {
    max-width: 150px;
    select{
      background: $color_white;
    }
  }
  .cl6 {
    max-width: 200px;
  }
  .text-left, .text-right{
    &:after {
      content: none;
    }
  }
  .switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
    left: 40px;
    top: -5px;
  }

  .modal-title {
    padding-bottom: 10px;
  }

  .error {
    color: $red;
  }
  .row-item {
    margin-bottom: 15px;
    .required {
      color: red;
    }

  }
  .radio-group {
      display: inline;
      padding-left: 5px;
    }
  .submit {
    text-align: center;
    margin-top: 30px;
    margin-bottom: 10px;
    cursor: pointer;
    a {
      border: 3px solid $color_green_vogue;
      border-radius: 50px;
      color: $color_white;
      background: $color_green_vogue;
      margin: 0px 15px 0px 10px;
      cursor: pointer;
      &:hover, active, focus {
        color: $deep_sapphire;
        background: $color_white;
        text-decoration: none;
      }
      width: 60%;
      height: 40px;
      padding: 10px 50px;
    }
  }

  #gamelancer_information {
    width: 700px;
    .edit-input-number {
      input {
        width: 80px;
        padding-left: 5px;
        background: transparent;
        box-shadow: inset 0 0px 0px rgba(0, 0, 0, 0);
        border-radius: 0px;
        border: 1px solid $color_alto;
      }
    }
    .filter_container {
      margin: 12px 0px;
      .title_item {
        color: $color_mine_shaft;
        font-size: $font_big_20;
        font-weight: 500;
        line-height: 28px;
        float: left;
        .btn-create-user {
          border: 1px solid $color_eden;
          line-height: 20px;
          padding: 3px 12px;
          font-size: $font_root;
          font-weight: bold;
          border-radius: 22px;
          text-align: center;
          color: $color_eden;
          transition: 0.5s;
          min-width: 86px;
          cursor: pointer;
          text-transform: uppercase;
          &:focus,
          &:active,
          &:hover {
            background-color: $color_eden;
            border-color: $color_eden;
            color: $color_white;
            transition: 0.5s;
          }
          .icon-plus {
            font-size: $font_mini_mini;
            float: left;
            margin-right: 5px;
            line-height: 20px;
          }
        }
      }
      .search_box {
        display: inline-block;
        float: right;
        width: 215px;
        max-width: 100%;
        .search_input {
          background-color: transparent;
          height: 28px;
          border: 1px solid $color_alto;
          padding: 4px 15px;
          line-height: 20px;
          width: 100%;
          overflow: hidden;
          white-space: nowrap;
          text-overflow: ellipsis;
          font-size: $font-small;
        }
      }
    }
    .select_user {
      box-shadow: inset 0 0px 0px rgba(0, 0, 0, 0);
      border-radius: 0px;
      width: 80px;
      height: 27px;
      border: 1px solid $color_alto;
      line-height: 20px;
      padding: 3px 5px;
      background-color: transparent;
    }
    .item_email_user {
      display: inline-block;
      float: left;
      position: relative;
      .txt_email_user {
        display: block;
        max-width: 95px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
      }
      .tooltip_email_user {
        position: absolute;
        top: 0px;
        left: 0px;
        line-height: 20px;
        padding: 5px 20px;
        left: 100%;
        background-color: $color_white;
        white-space: nowrap;
        width: auto;
        z-index: 10;
        font-size: $font_root;
        font-weight: 500;
        color: $color_mine_shaft;
        transition: 0.5s;
        display: none;
        box-shadow: 1px 1px 15px rgba(0, 0, 0, 0.4);
        &:after {
          right: 100%;
          top: 50%;
          border: solid transparent;
          content: " ";
          height: 0;
          width: 0;
          position: absolute;
          pointer-events: none;
          border-color: rgba(136, 183, 213, 0);
          border-right-color: $color_white;
          border-width: 5px;
          margin-top: -5px;
        }
      }
      &:hover {
        .tooltip_email_user {
          display: block;
          transition: 0.5s;
        }
      }
    }
    table {
      thead {
      }
      td {
        word-break: break-all;
        img {
          height: 100px;
        }
      }
      tbody {
        tr:hover {
          .btn_update_user, .btn_save_user {
            background-color: $color_champagne;
          }
        }
        tr {
          .btn_update_user:active,.btn_update_user:hover, 
          .btn_save_user:active, .btn_save_user:hover {
            background-color: $color_eden;
            color: $color_white;
          }
        }
      }
    }
  }

  @media only screen and (min-width: 1399px) {
   .userInformation .item_email_user .txt_email_user {
      max-width: 250px;
   }
  }
</style>
