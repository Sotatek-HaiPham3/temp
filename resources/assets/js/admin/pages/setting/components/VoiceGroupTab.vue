<template>
  <div id="voice-room-setting">
    <div class="filter_container clearfix">
      <button class="btn btn_creat_new" @click="openFormCreate()">
        <span class="icon-plus"></span> Create New Category
      </button>
      <div v-if="isCreateNew" class="create-new-form row">
        <div class="col-xs-4">
          <select
            class="form-control"
            v-model="params.game_id"
            v-validate="'required'"
            name="game_id"
            data-vv-as="game">
            <option value="" disabled selected>Select Category</option>
            <option v-for="item in games" :value="item.id">{{ item.title }}</option>
          </select>
        </div>

        <div class="col-xs-4">
          <select class="form-control" v-model="params.type" v-validate="'required'" name="type">
            <option v-for="item in roomType" :value="item.value">{{ item.title }}</option>
          </select>
        </div>

        <div class="col-xs-4">
          <input class="form-control"
            type="text"
            name="size_range"
            placeholder="Room size. Ex: 5,10,15,20"
            v-validate="'required'"
            data-vv-as="size"
            v-model.trim="params.size_range">
        </div>

        <div class="clearfix"></div>

        <div class="col-xs-4">
          <span v-show="errors.has('game_id')" class="error has-error">
            {{ errors.first('game_id') }}
          </span>
        </div>

        <div class="col-xs-4">
          <span v-show="errors.has('type')" class="error has-error">
            {{ errors.first('type') }}
          </span>
        </div>

        <div class="col-xs-4">
          <span v-show="errors.has('size_range')" class="error has-error">
            {{ errors.first('size_range') }}
          </span>
        </div>

        <div class="clearfix"></div>

        <div class="col-xs-4">
          <input class="form-control"
            type="text"
            name="label"
            v-validate="'required'"
            placeholder="Category label"
            v-model="params.label" />
        </div>

        <div class="col-xs-4">
          <img v-if="imgPath || params.image" class="image" width="200px" :src="imgPath || params.image">
          <label class="custom-file-label create-label" for="image">
            Choose File
          </label>
          <input type="file"
            id="image"
            accept="image/x-png,image/jpg,image/jpeg"
            @change="onChangeImage"
            @focus="resetError"
            class="input-file">
        </div>

        <div class="col-xs-4">
          <textarea class="form-control"
            type="text"
            name="description"
            v-validate="'required'"
            placeholder="Category description"
            v-model="params.description" />
        </div>

        <div class="col-xs-4">
          <label class="auto-order-checkbox">Pinned
            <input type="checkbox" v-model="params.pinned">
            <span class="checkmark"></span>
          </label>
        </div>

        <div class="col-xs-4">
          <button class="btn" @click.stop="cancelCreateNew()">
            <i class="fa fa-times"></i>
          </button>
          <button class="btn" @click.stop="createCategory()">
            <i class="fa fa-floppy-o"></i>
          </button>
        </div>

        <div class="clearfix"></div>

        <div class="col-xs-4">
          <span v-show="errors.has('label')" class="error has-error">
            {{ errors.first('label') }}
          </span>
        </div>

        <div class="col-xs-4">
          <span v-show="errors.has('image')" class="error has-error">
            {{ errors.first('image') }}
          </span>
        </div>

        <div class="col-xs-4">
          <span v-show="errors.has('description')" class="error has-error">
            {{ errors.first('description') }}
          </span>
        </div>

      </div>
    </div>

    <div class="datatable">
      <data-table :getData="getRoomCategories"
                  :limit="limit"
                  :widthTable="'100%'"
                  :column="column"
                  ref="datatable"
                  @DataTable:finish="onDatatableFinish">
        <th class="col1 text-left">No.</th>
        <th class="col2 text-center">Category</th>
        <th class="col3 text-center">Type</th>
        <th class="col4 text-center">Size</th>
        <th class="col5 text-center">Label</th>
        <th class="col6 text-center">Image</th>
        <th class="col7 text-center">Description</th>
        <th class="col7 text-center">Pinned</th>
        <th class="col8 text-right">{{ $t('game.action') }}</th>

        <template slot="body" slot-scope="props">
          <tr v-if="!rows[props.index].editable">
            <td class="col1 text-left">
              {{ props.realIndex }}
            </td>
            <td class="col2 text-center">
              {{ getCategoryTitle(props.item.game_id) }}
            </td>
            <td class="col3 text-center">
              {{ props.item.type }}
            </td>
            <td class="col4 text-center">
              {{ props.item.game_id !== CHATTING_ROOM_ID ? props.item.size_range : '' }}
            </td>
            <td class="col5 text-center">
              {{ props.item.label }}
            </td>
            <td class="col6 text-center">
              <img :src="props.item.image" width="250px">
            </td>
            <td class="col7 text-center">
              {{ props.item.description }}
            </td>
            <td class="col7 text-center">
              {{ props.item.pinned }}
            </td>
            <td class="col8 text-right">
              <button type="button" class="btn btn_save_user" @click="editCategory(props.index)">
                <i class="fa fa-pencil"></i>
              </button>
              <button v-if="isGameCategory(props.item.game_id)" class="btn" @click.stop="deleteCategory(props.item)">
                <i class="fa fa-trash-o"></i>
              </button>
            </td>
          </tr>

          <tr v-else>
            <td class="col1 text-left">
              {{ props.realIndex }}
            </td>
            <td class="col2 text-center">
              {{ getCategoryTitle(props.item.game_id) }}
            </td>
            <td class="col3 text-center">
              <template v-if="props.item.game_id === CHATTING_ROOM_ID">
                {{ props.item.type }}
              </template>
              <template v-else>
                <select class="form-control" v-model="params.type" v-validate="'required'" name="type">
                  <option v-for="item in roomType" :value="item.value">{{ item.title }}</option>
                </select>
                <span v-show="errors.has('type')" class="error has-error">
                  {{ errors.first('type') }}
                </span>
              </template>
            </td>
            <td class="col4 text-center">
              <template v-if="props.item.game_id !== CHATTING_ROOM_ID">
                <input class="form-control"
                  type="text"
                  name="size"
                  placeholder="Room size. Ex: 5,10,15,20"
                  v-validate="'required'"
                  v-model.trim="params.size_range">
                <span v-show="errors.has('size')" class="error has-error">
                  {{ errors.first('size') }}
                </span>
              </template>
            </td>
            <td class="col6 text-center">
              <template>
                <input class="form-control"
                  type="text"
                  name="label"
                  v-validate="'required'"
                  placeholder="Category label"
                  v-model="params.label" />
                <span v-show="errors.has('label')" class="error has-error">
                  {{ errors.first('label') }}
                </span>
              </template>
            </td>
            <td class="col6 text-center">
              <img class="image" width="200px" :src="imgPath || params.image">
              <label class="custom-file-label" for="image">
                Choose File
              </label>
              <input type="file"
                id="image"
                accept="image/x-png,image/jpg,image/jpeg"
                @change="onChangeImage"
                @focus="resetError"
                class="input-file">
              <span v-show="errors.has('image')" class="error has-error">
                {{ errors.first('image') }}
              </span>
            </td>
            <td class="col7 text-center">
              <textarea class="form-control"
                type="text"
                name="description"
                v-validate="'required'"
                v-model="params.description" />
              <span v-show="errors.has('description')" class="error has-error">
                {{ errors.first('description') }}
              </span>
            </td>
            <td class="col7 text-center">
              <select class="form-control" v-model="params.pinned" v-validate="'required'" name="pinned">
                <option v-for="item in pinnedOption" :value="item.value">{{ item.title }}</option>
              </select>
              <span v-show="errors.has('pinned')" class="error has-error">
                  {{ errors.first('pinned') }}
                </span>
            </td>
            <td class="col8 text-right">
              <button class="btn" @click.stop="cancelEdit()">
                <i class="fa fa-times"></i>
              </button>
              <button class="btn" @click.stop="createCategory()">
                <i class="fa fa-floppy-o"></i>
              </button>
            </td>
          </tr>
        </template>
      </data-table>
    </div>
  </div>
</template>

<script>
  import { mapGetters } from 'vuex'
  import rf from '../../../lib/RequestFactory'
  import RemoveErrorsMixin from '../../../common/RemoveErrorsMixin'
  import Const from '../../../common/Const'

  const MAXIMUM_FILESIZE = 10 * 1024 * 1024
  const ALLOW_FILETYPE = ['image/jpg', 'image/jpeg', 'image/png']
  const CHATTING_ROOM_ID = -1
  const LABEL_DEFAULT = {
    username: 'Username',
    code: 'Game Code'
  }

  export default {
    mixins: [RemoveErrorsMixin],

    data () {
      return {
        titlePage: 'Room Categories',
        limit: 10,
        params: {},
        column: 6,
        rows: [],
        imgPath: null,
        inputImage: null,
        imgName: null,
        isCreateNew: false,
        isSubmitting: false,
        CHATTING_ROOM_ID,
        label_default: _.cloneDeep(LABEL_DEFAULT)
      }
    },

    computed: {
      ...mapGetters([
        'masterdata'
      ]),

      roomType () {
        return Object.keys(Const.ROOM_TYPES).map(key => {
          return {
            value: Const.ROOM_TYPES[key],
            title: key
          }
        })
      },

      pinnedOption () {
        return Object.keys(Const.PINNED_OPTION).map(key => {
          return {
            value: Const.PINNED_OPTION[key],
            title: key
          }
        })
      },

      games () {
        return _.filter(this.masterdata.games, game => game.is_active) || []
      }
    },

    mounted () {
      this.$emit('EVENT_PAGE_CHANGE', this)
    },

    watch: {
      'params.type' (val) {
        this.params.label = this.label_default[val]
      }
    },

    methods: {
      getRoomCategories (params) {
        return rf.getRequest('RoomRequest').getRoomCategories(params)
      },

      onDatatableFinish () {
        this.rows = this.$refs.datatable.rows
        _.each(this.rows, item => {
          this.$set(item, 'editable', false)
        })
      },

      refresh () {
        this.$refs.datatable.refresh()
      },

      getCategoryTitle (gameId) {
        if (gameId === CHATTING_ROOM_ID) {
          return 'Just Hangout'
        }

        const game = _.find(this.games, game => game.id === gameId) || {}
        return game.title
      },

      isGameCategory (gameId) {
        return gameId !== CHATTING_ROOM_ID
      },

      openFormCreate () {
        this.label_default = _.cloneDeep(LABEL_DEFAULT)
        this.resetImage()
        this.isCreateNew = true
        this.params = {
          game_id: '',
          type: Const.ROOM_TYPES.USERNAME,
          size_range: '',
          label: this.label_default[Const.ROOM_TYPES.USERNAME],
          description: '',
          pinned: 0
        }
      },

      editCategory(index) {
        this.isCreateNew = false
        this.rows[index].editable = true
        for(let i = 0; i < this.rows.length; i++) {
          if(i !== index) {
            this.rows[i].editable = false
          }
        }
        this.label_default[this.rows[index].type] = this.rows[index].label
        this.params = JSON.parse(JSON.stringify(this.rows[index]))
      },

      cancelCreateNew () {
        this.isCreateNew = false
        this.params = {}
        this.resetImage()
      },

      cancelEdit () {
        for(let i = 0; i < this.rows.length; i++) {
          this.rows[i].editable = false
        }
        this.params = {}
        this.resetImage()
      },

      async createCategory () {
        if (this.isSubmitting) {
          return
        }

        await this.validateForm()
        if (this.errors.any()) {
          return
        }

        let formData = new FormData()
        formData.append('id', this.params.id || null)
        formData.append('game_id', this.params.game_id || '')
        formData.append('type', this.params.type || '')
        formData.append('size_range', this.params.size_range || '')
        formData.append('label', this.params.label)
        formData.append('image', this.inputImage || this.params.image)
        formData.append('description', this.params.description || '')
        formData.append('pinned', this.params.pinned ? 1 : 0)

        if (this.params.id) {
          return this.requestHandler(rf.getRequest('RoomRequest').updateRoomCategory(formData))
        }
        return this.requestHandler(rf.getRequest('RoomRequest').createRoomCategory(formData))
      },

      deleteCategory (category) {
        if (!this.isGameCategory(category.game_id)) {
          return
        }

        return this.requestHandler(rf.getRequest('RoomRequest').deleteRoomCategory({ id: category.id }))
      },

      requestHandler(promise) {
        promise.then(res => {
          this.cancelCreateNew()
          this.cancelEdit()
          this.refresh()
          this.showSuccess('Successful')
        })
        .catch(error => {
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
          this.convertRemoteErrors(error);
        })
        .finally(() => {
          this.isSubmitting = false
        })
      },

      async validateForm () {
        await this.$validator.validateAll()

        if (!this.inputImage && window._.isEmpty(this.params.image)) {
          this.errors.add({
            field: 'image',
            msg: 'The image file is required.'
          })
        }

        const size = _.uniq(this.params.size_range.split(','))
        _.forEach(size, num => {
          if (!_.isInteger(parseInt(num))) {
            this.errors.add({
              field: 'size_range',
              msg: 'The size room is invalid.'
            })
            return false
          }
        })
      },

      onChangeImage (e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        let fileType = files[0].type
        if (!ALLOW_FILETYPE.includes(fileType)) {
          this.errors.add({
            field: 'image',
            msg: 'The image file type is invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'image',
            msg: 'The image file size can not exceed 10MB!'
          })
          return
        }

        this.imgPath = URL.createObjectURL(e.target.files[0])
        this.inputImage = files[0]
        this.imgName = files[0].name
      },

      resetImage () {
        this.resetError()
        this.inputImage = null
        this.imgPath = null
        this.imgName = null
      }
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../../../sass/common";
  #voice-room-setting {
    label {
      font-weight: normal;
    }
    .custom-file-label {
      @include custom-file-input();
      max-width: 300px;
      padding: 0 12px;
      top: 1px;
      line-height: 34px;
      vertical-align: middle;
      &:after {
        padding: 0 12px;
        line-height: 34px;
      }
      &.create-label {
        max-width: unset;
      }
    }
    input[type='file'] {
      display: none;
    }
    .error {
      &.has-error {
        color: #e2221e;
      }
    }
    .filter_container {
      margin: 0px 0px 15px;
      .btn_creat_new {
        margin: 12px 0 20px;
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
      .create-new-form {
        background-color: $color_white;
        margin: 10px 0px;
        .col-xs-4 {
          margin-top: 10px;
        }
      }
    }
    .datatable {
      .col1 {
        width: 50px;
      }
      .col3 {
        width: 150px;
      }
      .col4, .col5 {
        width: 150px;
      }
      .col6 {
        width: 270px;
      }
      .col7 {
        width: 250px;
      }
    }
    .auto-order-checkbox {
      display: block;
      position: relative;
      padding-left: 25px;
      margin-bottom: 12px;
      margin-top: 12px;
      cursor: pointer;
      font-size: $font_root;
      font-weight: 500;
      line-height: 17px;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
        &:checked {
          &~ .checkmark {
            background-color: #19575f;
            border: transparent;
            &:after {
              display: block;
            }
          }
        }
      }
    }
    .checkmark {
      position: absolute;
      top: 0;
      left: 0;
      height: 15px;
      width: 15px;
      background-color: transparent;
      border: 1px #cfcfcf solid;
      border-radius: 2px;
      &:after {
        content: "";
        position: absolute;
        display: none;

        left: 5px;
        top: 2px;
        width: 5px;
        height: 9px;
        border: solid white;
        border-width: 0 2px 2px 0;
        -webkit-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
        transform: rotate(45deg);
      }
    }
  }
</style>
