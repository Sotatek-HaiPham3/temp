<template>
  <div class="boxCore" id="offer_setting">
    <section class="clearfix">
      <div class="filter_container clearfix">
        <button type="button" class="btn btn-create-offer" @click.stop="onClickCreateOffer()">
          <span class="icon-plus"></span> {{ $t('game.create_new_offer') }}
        </button>
        <span class="search_box">
          <input type="text" placeholder="Search" 
                 v-on:keyup.enter="search" 
                 class="form-control search_input" 
                 name="searchKey" 
                 v-model="searchKey"/>
        </span>
      </div>

      <!-- <div class="clearfix"></div> -->

      <div class="datatable">
        <data-table :getData="getData"
                    :limit="limit"
                    :widthTable="'100%'"
                    :column="column"
                    ref="datatable"
                    @DataTable:finish="onDatatableFinish">
          <th class="col1 text-left">ID</th>
          <th class="col2 text-left" data-sort-field="coin">Coin</th>
          <th class="col3 text-left" data-sort-field="cover">Cover</th>
          <th class="col4 text-left" data-sort-field="price">Price</th>
          <th class="col5 text-left" data-sort-field="bonus">Bonus</th>
          <th class="col6 text-left"
              data-sort-field="always_bonus"
              v-tooltip="{
                html: true,
                content: $t('setting.offers_acivate_bonus'),
                classes: 'tooltip-activate-bonus',
                show: false,
                autoHide: true,
                trigger: 'hover',
              }">
            Activate Bonus
          </th>
          <th class="col7 text-right">Actions</th>
          
          <template slot="body" slot-scope="props">
            <template v-if="rows[ props.index ].editable === false">
              <tr>
                <td class="col1 text-left">
                  {{ props.realIndex }}
                </td>
                <td class="col2 text-left">
                  {{ rows[ props.index ].coin }}
                </td>
                <td class="col3 text-left">
                  <img :src="rows[ props.index ].cover">
                </td>
                <td class="col4 text-left">
                  {{ rows[ props.index ].price | number }}
                </td>
                <td class="col5 text-left">
                  {{ rows[ props.index ].bonus | number }}
                </td>
                <td class="col6 text-left">
                  {{ rows[ props.index ].always_bonus | formatStatus }}
                </td>
                <td class="col7 text-right">
                  <button type="button" class="btn btn_edit_user" @click.stop="onClickRemove(rows[ props.index ].id)">
                    <i class="fa fa-trash-o"></i>
                  </button>
                  <button type="button" class="btn btn_save_user" @click.stop="onClickEdit(props.index)">
                    <i class="fa fa-pencil"></i>
                  </button>
                </td>
              </tr>
            </template>
            <template v-else>
              <tr>
                <td class="col1"></td>
                <td class="col2 text-left">
                  <input class="form-control" 
                         type="number"
                         name="coin"
                         data-vv-as="coin"
                         v-validate="'required|numeric|min_value:1|max_value:10000'"
                         data-vv-validate-on="none"
                         @focus="resetError"
                         v-model="params.coin" />
                  <span v-show="errors.has('coin')" class="error has-error">
                    {{ errors.first('coin') }}
                  </span>
                </td>
                <td class="col3-edit text-left">
                  <template v-if="!isUpdate">
                    <input type="file" 
                         accept="image/x-png,image/gif,image/jpeg"
                         @change="onChangeProfilePicture"
                         v-validate="'image|size:2048|required'"
                         name="cover" 
                         data-vv-validate-on="none"
                         @focus="resetError"
                         class="choose-file form-control"
                         ref="fileInput"
                         id="customFile">
                  </template>
                  <template v-else>
                    <input type="file" 
                         accept="image/x-png,image/gif,image/jpeg"
                         @change="onChangeProfilePicture"
                         v-validate="'image|size:2048'"
                         name="cover" 
                         data-vv-validate-on="none"
                         @focus="resetError"
                         class="choose-file form-control"
                         ref="fileInput"
                         id="customFile">
                  </template>
                  <label class="custom-file-label" for="customFile">{{ inputImage ? inputImage.name : 'Choose File' }}</label>
                  <span v-show="errors.has('cover')" class="error has-error" id="cover-fixed">
                    {{ errors.first('cover') }}
                  </span>
                  <div class="preview-image">
                    <img :src="imgPath ? imgPath : params.cover" v-if="isUpdate || imgPath">
                  </div>
                </td>
                <td class="col4 text-left">
                  <input class="form-control" 
                         type="number"
                         name="price"
                         data-vv-as="price"
                         v-validate="'required|decimal:2|min_value:1|max_value:10000'"
                         data-vv-validate-on="none"
                         @focus="resetError"
                         v-model="params.price" />
                  <span v-show="errors.has('price')" class="error has-error">
                    {{ errors.first('price') }}
                  </span>
                </td>
                <td class="col5 text-left">
                  <input class="form-control" 
                         type="number"
                         name="bonus"
                         placeholder="Ex: 20% = 0.2" 
                         data-vv-as="bonus"
                         v-validate="'required|decimal:5|min_value:0|max_value:1'"
                         data-vv-validate-on="none"
                         @focus="resetError"
                         v-model="params.bonus" />
                  <span v-show="errors.has('bonus')" class="error has-error">
                    {{ errors.first('bonus') }}
                  </span>
                </td>
                <td class="col6 text-left">
                  <label class="switch">
                    <input type="checkbox"
                           v-model="params.always_bonus"
                           :checked="params.always_bonus === 1 ? true : false">
                    <span class="slider round"></span>
                  </label>
                  <span class="bonus-status">
                    {{ status === 1 ? 'ON' : 'OFF' }}
                  </span>
                </td>
                <td class="col7 text-right">
                  <button type="button" class="btn btn_edit_user" @click.stop="onClickCancel()">
                    <i class="icon-close"></i>
                  </button>
                  <button type="button" class="btn btn_save_user" @click.stop="onClickSubmit()">
                    <i class="icon-save"></i>
                  </button>
                </td>
              </tr>
            </template>
          </template>
        </data-table>
      </div>
    </section>
  </div>
</template>

<script>
  import rf from '../../lib/RequestFactory';
  import Modal from '../../components/Modal';
  import Numeral from '../../lib/numeral';
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin';

  export default {
    components: {
      Modal
    },
    mixins: [RemoveErrorsMixin],
    data() {
      return {
        limit: 10,
        column: 7,
        titlePage: this.$t('setting.offer'),
        searchKey: '',
        params: {},
        rows: [],
        inputImage: '',
        imgPath: null,
        isUpdate: false,
        status: 0,
      }
    },
    filters: {
      formatStatus: function (val) {
        if(val == 1)
          return 'ON';
        else return 'OFF';
      },
    },
    watch: {
      'params.always_bonus' () {
        if (this.params.always_bonus === true || this.params.always_bonus === 1) {
          this.status = 1;
        }
        else this.status = 0;
      },
    },
    methods: {
      onDatatableFinish() {
        this.rows = this.$refs.datatable.rows;
        window._.each(this.rows, item => {
          this.$set(item, 'editable', false);
        });
      },

      onClickCreateOffer() {
        this.inputImage = '';
        this.imgPath = null;
        this.isUpdate = false;
        for(let i = 0; i < this.rows.length; i++) {
          this.rows[i].editable = false;
        }
        this.params = {};
        if(this.rows[this.rows.length -1].id) {
          this.rows.push({ editable: true });
        }
        else {
          this.rows.splice(this.rows.length -1 , 1);
          this.rows.push({ editable: true });
        }
      },

      onClickEdit(index) {
        this.imgPath = null;
        this.rows[index].editable = true;
        this.inputImage = '';
        for(let i = 0; i < this.rows.length; i++) {
          if(i !== index) {
            this.rows[i].editable = false;
          }
          if( !this.rows[i].id) {
            this.rows.splice(i, 1);
          }
        }
        this.params = JSON.parse(JSON.stringify(this.rows[index]));
        this.params.price = this.formatCurrencyAmount(this.params.price);
        this.params.bonus = this.formatCurrencyAmount(this.params.bonus);
        this.isUpdate = true;
      },

      onClickCancel() {
        this.refresh();
      },

      onChangeProfilePicture(e) {
        this.imgPath = null;
        this.inputImage = '';
        let files = e.target.files || e.dataTransfer.files;
        if (!files.length)
          return;
        this.imgPath = URL.createObjectURL(e.target.files[0]);
        this.inputImage = files[0];
      },

      async onClickSubmit() {

        if (this.isSubmitting) {
          return;
        }

        this.resetError();

        await this.$validator.validate('coin');
        await this.$validator.validate('cover');
        await this.$validator.validate('price');
        await this.$validator.validate('bonus');
        if (this.errors.any()) {
          return;
        }
        this.createOrUpdateOffer();

      },

      createOrUpdateOffer() {
        let formData = new FormData();
        formData.append('id', this.params.id);
        formData.append('coin', this.params.coin);
        formData.append('price', this.params.price);
        formData.append('bonus', this.params.bonus);
        formData.append('always_bonus', this.status);
        if(this.inputImage){
          formData.append('cover', this.inputImage, this.inputImage.name);
        }
        if (!this.isUpdate) {
          return this.requestHandler(rf.getRequest('SettingRequest').createOffer(formData));
        }
        return this.requestHandler(rf.getRequest('SettingRequest').updateOffer(formData));
      },

      requestHandler(promise) {
        this.startSubmit();
        promise.then(res => {
          this.endSubmit();

          if (!this.isUpdate) {
            this.showSuccess(this.$t('common.create_sucessful'));
            this.refresh();
          } else {
            this.showSuccess(this.$t('common.update_sucessful'));
            this.refresh();
          }
        })
        .catch(error => {
          this.endSubmit();
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
          this.convertRemoteErrors(error);
        });
      },

      onClickRemove(params) {
        this.params = params;
        window.ConfirmationModal.show({
          type        : 'confirm',
          title       : '',
          content     : 'Do you want to remove this offer?',
          onConfirm   :  () => {
            this.onClickRemoveOffer();
          },
          onCancel    : () => {}
        });
      },

      onClickRemoveOffer() {
        this.startSubmit();
        rf.getRequest('SettingRequest').removeOffer({id: this.params}).then(res => {
          this.endSubmit();
          this.showSuccess(this.$t('common.remove_sucessful'));
          this.refresh();
        })
        .catch(error => {
          this.endSubmit();
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
          this.convertRemoteErrors(error);
          if (this.errors.has('error')) {
            this.showError(error.response.data.message);
          }
        });
      },
      formatCurrencyAmount(amount, zeroValue) {
        const numberOfDecimalDigits = 6;
        const format = numberOfDecimalDigits == 0 ?
        '0,0' :
        '0,0.[' + Array(numberOfDecimalDigits + 1).join('0') + ']';
        if (window._.isNil(zeroValue)) {
          zeroValue = '';
        }
        return (amount && parseFloat(amount) != 0) ? Numeral(amount).format(format) : zeroValue;
      },
      search() {
        this.$refs.datatable.$emit('DataTable:filter', Object.assign(this.params, {search_key: this.searchKey}));
      },

      getData(params) {
        return rf.getRequest('SettingRequest').getOffers(params);
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
  @import "../../../../sass/common";
  .custom-file-label {
    @include custom-file-input();
  }
  input[type='file'] {
    visibility: hidden;
  }

  #cover-fixed {
    position: relative;
    top: -45px;
  }

  input[type='number'] {
    -moz-appearance:textfield;
  }

  input::-webkit-outer-spin-button,
  input::-webkit-inner-spin-button {
    -webkit-appearance: none;
  }

  .text-left, .text-right {
    &:after {
      content: none;
    }
  }

  .switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 20px;
    vertical-align: sub;
  }

  .switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }

  .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    -webkit-transition: .4s;
    transition: .4s;
  }

  .slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
  }

  input:checked + .slider {
    background-color: #2196F3;
  }

  input:focus + .slider {
    box-shadow: 0 0 1px #2196F3;
  }

  input:checked + .slider:before {
    -webkit-transform: translateX(35px);
    -ms-transform: translateX(35px);
    transform: translateX(35px);
  }

  .slider.round {
    border-radius: 34px;
  }

  .slider.round:before {
    border-radius: 50%;
  }

  .bonus-status {
    vertical-align: -webkit-baseline-middle;
    margin-left: 10px;
    cursor: pointer;
  }

  .col3 {
    img {
      width: 35px;
      height: auto;
      max-height: 50px;
      overflow: hidden;
      vertical-align: sub;
    }
    .choose-file {
      width: 100%;
    }
  }

  .col3-edit {
    width: 200px;
    .preview-image {
      position: relative;
      top: -36px;
      margin-bottom: -36px;
      img {
        width: 35px;
        height: auto;
        max-height: 50px;
        overflow: hidden;
        margin-top: 5px;
      }
    }
  }

  .btn-create-offer {
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

  #offer_setting {
    max-width: 1400px;
    .filter_container {
      margin: 12px 0px;
      width: 100%;
      .title_item {
        color: $color_mine_shaft;
        font-size: $font_big_20;
        font-weight: 500;
        line-height: 28px;
        float: left;
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
    table {
      thead {
      }
      tbody {
        tr:hover {
          .btn_edit_user, .btn_save_user {
            background-color: $color_champagne;
          }
        }
        tr {
          .btn_edit_user:active,.btn_edit_user:hover, 
          .btn_save_user:active, .btn_save_user:hover {
            background-color: $color_eden;
            color: $color_white;
          }
        }
      }
      .col1 {
        width: 60px;
      }
      .col2 {
        width: 150px;
      }
      .col3 {
        width: 200px;
      }
      .col4 {
        width: 150px;
      }
      .col5 {
        width: 150px;
        .icon-close {
          font-size: 11px;
        }
        .icon-save {
          font-size: 12px;
        }
      }
      .col6 {
        width: 140px;
      }
      .col7 {
        width: 100px;
        .icon-close {
          font-size: 11px;
        }
        .icon-save {
          font-size: 12px;
        }
      }
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
    .submit {
      text-align: center;
      margin-top: 30px;
      margin-bottom: 10px;
      cursor: pointer;
      a {
        border: 3px solid #55d184;
        border-radius: 50px;
        color: $color_white;
        background: #55d184;
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

  }
</style>
