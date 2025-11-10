<template>
  <div id="games_setting" class="boxCore">
    <div class="games_edit">
      <div class="row form-game-container">
        <div id="game">

          <div class="form-group col-xs-4">
            <label class="game-form-label">Name <sup>*</sup>:</label>
            <div class="clearfix"></div>
            <input
                  v-model.trim="game.title"
                  @focus="resetError"
                  :placeholder="$t('input.name')"
                  name="title"
                  data-vv-validate-on="none"
                  maxlength="50"
                  v-validate="'required'"
                  type="text"
                  class="form-control">
            <div class="clearfix"></div>
            <div class="error-box">
              <span v-show="errors.has('title')" class="f-left is-error">{{ errors.first('title') }}</span>
            </div>
          </div>

          <div class="form-group col-xs-4">
            <label class="game-form-label">Slug <sup>*</sup>:</label>
            <div class="clearfix"></div>
            <input
                  v-model.trim="game.slug"
                  @focus="resetError"
                  placeholder="slug"
                  :class="{ error: errors.has('slug') }"
                  name="slug"
                  data-vv-validate-on="none"
                  maxlength="50"
                  v-validate="'required'"
                  type="text"
                  class="form-control">
            <div class="clearfix"></div>
            <div class="error-box">
              <span v-show="errors.has('slug')" class="f-left is-error">{{ errors.first('slug') }}</span>
            </div>
          </div>

          <div class="form-group col-xs-4">
            <label class="game-form-label">Status:</label>
            <div class="clearfix"></div>
            <select class="form-control" v-model="game.is_active">
              <option value="1">Publish</option>
              <option value="0">Draft</option>
            </select>
          </div>

          <div class="clearfix"></div>

          <div class="group-file">
            <div class="form-group col-xs-4">
              <label class="game-form-label">Logo <sup>*</sup>:</label>
              <div class="clearfix"></div>
              <div class="custom-file">
                <label class="custom-file-label" for="logo">
                  Choose File
                </label>
                <div class="clearfix"></div>
                <div class="error-box">
                  <span v-show="errors.has('logo')" class="f-left is-error">{{ errors.first('logo') }}</span>
                </div>
                <input type="file"
                  id="logo"
                  ref="inputLogo"
                  accept="image/x-png,image/jpg,image/jpeg"
                  @change="onChangeLogo"
                  @focus="resetError"
                  class="input-file">
              </div>
              <div class="clearfix"></div>
              <div class="review_image">
                <div class="group-thumbnail" v-if="logo.path || game.logo">
                  <img :src="logo.path ? logo.path : game.logo">
                </div>
              </div>
            </div>

            <div class="form-group col-xs-4">
              <label class="game-form-label">Thumbnail <sup>*</sup>:</label>
              <div class="clearfix"></div>
              <div class="custom-file">
                <label class="custom-file-label" for="thumbnail">
                  Choose File
                </label>
                <div class="clearfix"></div>
                <div class="error-box">
                  <span v-show="errors.has('thumbnail')" class="f-left is-error">{{ errors.first('thumbnail') }}</span>
                </div>
                <input type="file"
                  id="thumbnail"
                  ref="inputThumbnail"
                  accept="image/x-png,image/jpg,image/jpeg"
                  @change="onChangeThumbnail"
                  @focus="resetError"
                  class="input-file">
              </div>
              <div class="clearfix"></div>
              <div class="review_image">
                <div class="group-thumbnail" v-if="thumbnail.path || game.thumbnail">
                  <img :src="thumbnail.path ? thumbnail.path : game.thumbnail">
                </div>
              </div>
            </div>

            <div class="form-group col-xs-4">
              <label class="game-form-label">Thumbnail hover <sup>*</sup>:</label>
              <div class="clearfix"></div>
              <div class="custom-file">
                <label class="custom-file-label" for="thumbnail-hover">
                  Choose File
                </label>
                <div class="clearfix"></div>
                <div class="error-box">
                  <span v-show="errors.has('thumbnailHover')" class="f-left is-error">{{ errors.first('thumbnailHover') }}</span>
                </div>
                <input type="file"
                  id="thumbnail-hover"
                  ref="inputThumbnailHover"
                  accept="image/x-png,image/jpg,image/jpeg"
                  @change="onChangeThumbnailHover"
                  @focus="resetError"
                  class="input-file">
              </div>
              <div class="clearfix"></div>
              <div class="review_image">
                <div class="group-thumbnail" v-if="thumbnailHover.path || game.thumbnail_hover">
                  <img :src="thumbnailHover.path ? thumbnailHover.path : game.thumbnail_hover">
                </div>
              </div>
            </div>
          </div>

          <div class="group-file">
            <div class="form-group col-xs-4">
              <label class="game-form-label">Thumbnail active <sup>*</sup>:</label>
              <div class="clearfix"></div>
              <div class="custom-file">
                <label class="custom-file-label" for="thumbnail-active">
                  Choose File
                </label>
                <div class="clearfix"></div>
                <div class="error-box">
                  <span v-show="errors.has('thumbnailActive')" class="f-left is-error">{{ errors.first('thumbnailActive') }}</span>
                </div>
                <input type="file"
                  id="thumbnail-active"
                  ref="inputThumbnailActive"
                  accept="image/x-png,image/jpg,image/jpeg"
                  @change="onChangeThumbnailActive"
                  @focus="resetError"
                  class="input-file">
              </div>
              <div class="clearfix"></div>
              <div class="review_image">
                <div class="group-thumbnail" v-if="thumbnailActive.path || game.thumbnail_active">
                  <img :src="thumbnailActive.path ? thumbnailActive.path : game.thumbnail_active">
                </div>
              </div>
            </div>

            <div class="form-group col-xs-4">
              <label class="game-form-label">Banner <sup>*</sup>:</label>
              <div class="clearfix"></div>
              <div class="custom-file">
                <label class="custom-file-label" for="banner">
                  Choose File
                </label>
                <div class="clearfix"></div>
                <div class="error-box">
                  <span v-show="errors.has('banner')" class="f-left is-error">{{ errors.first('banner') }}</span>
                </div>
                <input type="file"
                  id="banner"
                  ref="inputBanner"
                  accept="image/x-png,image/jpg,image/jpeg"
                  @change="onChangeBanner"
                  @focus="resetError"
                  class="input-file">
              </div>
              <div class="clearfix"></div>
              <div class="review_image">
                <div class="group-thumbnail" v-if="banner.path || game.banner">
                  <img :src="banner.path ? banner.path : game.banner">
                </div>
              </div>
            </div>

            <div class="form-group col-xs-4">
              <label class="game-form-label">Portrait <sup>*</sup>:</label>
              <div class="clearfix"></div>
              <div class="custom-file">
                <label class="custom-file-label" for="portrait">
                  Choose File
                </label>
                <div class="clearfix"></div>
                <div class="error-box">
                  <span v-show="errors.has('portrait')" class="f-left is-error">{{ errors.first('portrait') }}</span>
                </div>
                <input type="file"
                  id="portrait"
                  ref="inputPortrait"
                  accept="image/x-png,image/jpg,image/jpeg"
                  @change="onChangePortrait"
                  @focus="resetError"
                  class="input-file">
              </div>
              <div class="clearfix"></div>
              <div class="review_image">
                <div class="group-thumbnail" v-if="portrait.path || game.portrait">
                  <img :src="portrait.path ? portrait.path : game.portrait">
                </div>
              </div>
            </div>
          </div>

          <div class="clearfix"></div>

          <div class="form-group col-xs-4">
            <label class="game-form-label">Cover <sup>*</sup>:</label>
            <div class="clearfix"></div>
            <div class="custom-file">
              <label class="custom-file-label" for="cover">
                Choose File
              </label>
              <div class="clearfix"></div>
              <div class="error-box">
                <span v-show="errors.has('cover')" class="f-left is-error">{{ errors.first('cover') }}</span>
              </div>
              <input type="file"
                id="cover"
                ref="inputCover"
                accept="image/x-png,image/jpg,image/jpeg"
                @change="onChangeCover"
                @focus="resetError"
                class="input-file">
            </div>
            <div class="clearfix"></div>
            <div class="review_image">
              <div class="group-thumbnail" v-if="cover.path || game.cover">
                <img :src="cover.path ? cover.path : game.cover">
              </div>
            </div>
          </div>

          <div class="form-group col-xs-4">
            <label class="game-form-label">Servers:</label>
            <input
                  v-model.trim="newServer"
                  placeholder="Add Game Server"
                  maxlength="50"
                  @keypress.enter="addServer()"
                  class="form-control">
            <div class="error-box">
              <span v-show="errors.has('server')" class="f-left is-error">{{ errors.first('server') }}</span>
            </div>
            <button class="btn add-btn" @click="addServer()">Add</button>
            <ul class="list-new-item">
              <li class="new-item" v-for="server in game.servers">
                <span class="item-label">{{ server.name }}</span>
              </li>
              <li class="new-item" v-for="(server, index) in servers">
                <span class="item-label">{{ server }}</span>
                <span class="item-close" @click="removeServer(index)"></span>
              </li>
            </ul>
            <div class="clearfix"></div>
          </div>

          <div class="form-group col-xs-4">
            <label class="game-form-label">Ranks:</label>
            <input
                  v-model.trim="newRank"
                  placeholder="Add Game Rank"
                  maxlength="50"
                  @keypress.enter="addRank()"
                  class="form-control">
            <div class="error-box">
              <span v-show="errors.has('rank')" class="f-left is-error">{{ errors.first('rank') }}</span>
            </div>
            <button class="btn add-btn" @click="addRank()">Add</button>
            <ul class="list-new-item">
              <li class="new-item" v-for="rank in game.ranks">
                <span class="item-label">{{ rank.name }}</span>
              </li>
              <li class="new-item" v-for="(rank, index) in ranks">
                <span class="item-label">{{ rank }}</span>
                <span class="item-close" @click="removeRank(index)"></span>
              </li>
            </ul>
            <div class="clearfix"></div>
          </div>

          <div class="clearfix"></div>

          <div class="form-group col-xs-4">
            <label class="game-form-label">Platform <sup>*</sup>:</label>
            <multiselect  v-model="newPlatforms"
                          :options="platforms"
                          label="name"
                          multiple
                          taggable
                          @tag="addPlatform"
                          @focus="resetError"
                          :hide-selected="true"
                          track-by="name"/>
            <div class="error-box">
              <span v-show="errors.has('platform')" class="f-left is-error">{{ errors.first('platform') }}</span>
            </div>
            <ul class="list-platform" v-if="game.platforms.length">
              <li v-for="item in game.platforms">{{ item.name }}</li>
            </ul>
            <div class="clearfix"></div>
          </div>

          <div class="form-group col-xs-4">
            <label class="game-form-label">Order <sup>*</sup>:</label>
            <div class="clearfix"></div>
            <input-only-number
                  v-model.trim="game.order"
                  @focus="resetError"
                  placeholder="Enter order number"
                  name="order"
                  data-vv-validate-on="none"
                  maxlength="10"
                  v-validate="`required|min_value:${MIN_VALUE_ORDER}`"
                  type="text"
                  without-decimals
                  class="form-control"/>
            <div class="clearfix"></div>
            <div class="error-box">
              <span v-show="errors.has('order')" class="f-left is-error">{{ errors.first('order') }}</span>
            </div>
          </div>

          <div class="form-group col-xs-4">
            <label class="game-form-label">Auto Sorting:</label>
            <div class="clearfix"></div>
            <label class="auto-order-checkbox" name="auto-order">This game will automatically sorting.
              <input type="checkbox" v-model="game.auto_order">
              <span class="checkmark"></span>
            </label>
            <div class="clearfix"></div>
            <div class="error-box">
              <span v-show="errors.has('auto-order')" class="f-left is-error">{{ errors.first('auto-order') }}</span>
            </div>
          </div>

          <div class="col-xs-12 form-group">
            <button class="btn_button_done mr-10" @click.stop="onClickedSubmit()">{{$t('common.done')}}</button>
          </div>
        </div>
        <loading :isLoading="isLoading"/>
      </div>
    </div>
  </div>
</template>
<script>
  import rf from '../../lib/RequestFactory'
  import Const from '../../common/Const'
  import RemoveErrorsMixin from '../../common/RemoveErrorsMixin'
  import InputOnlyNumber from '../../common/InputOnlyNumber';
  import moment from 'moment'
  import { size } from 'lodash'

  const MAXIMUM_FILESIZE = 10 * 1024 * 1024
  const ALLOW_FILETYPE = ['image/jpg', 'image/jpeg', 'image/png']
  const MIN_VALUE_ORDER = 1

  export default {
    mixins: [RemoveErrorsMixin],

    components: {
      InputOnlyNumber
    },

    data() {
      return {
        titlePage: this.$t('add_game'),
        currentGame: {},
        platforms: [],
        servers: [],
        ranks: [],
        newPlatforms: [],
        newServer: null,
        newRank: null,
        isLoading: false,
        game: {
          id: '',
          title: '',
          slug: '',
          is_active: 1,
          platforms: [],
          auto_order: 1
        },
        thumbnail: {
          path: null,
          input: null,
          name: null
        },
        thumbnailHover: {
          path: null,
          input: null,
          name: null
        },
        thumbnailActive: {
          path: null,
          input: null,
          name: null
        },
        banner: {
          path: null,
          input: null,
          name: null
        },
        logo: {
          path: null,
          input: null,
          name: null
        },
        portrait: {
          path: null,
          input: null,
          name: null
        },
        cover: {
          path: null,
          input: null,
          name: null
        },
        MIN_VALUE_ORDER
      }
    },

    watch: {
      'game.order' (newVal, oldVal) {
        if (oldVal && this.isUpdateGame()) {
          this.game.auto_order = 0
        }
      }
    },

    methods: {
      isUpdateGame() { 
        return this.$route.params.id
      },

      initGame() {
        if (this.isUpdateGame()) {
          this.logo.path = this.game.logo
          this.thumbnail.path = this.game.thumbnail
          this.thumbnailHover.path = this.game.thumbnail_hover
          this.thumbnailActive.path = this.game.thumbnail_active
          this.banner.path = this.game.banner
          this.portrait.path = this.game.portrait
          this.cover.path = this.game.cover
        }
      },

      async onClickedSubmit() {
        if (this.isSubmitting) {
          return
        }

        this.resetError()
        await this.$validator.validateAll()
        if (!this.logo.input && !this.logo.path) {
          this.errors.add({
            field: 'logo',
            msg: 'The logo file is required.'
          })
        }
        if (!this.thumbnail.input && !this.thumbnail.path) {
          this.errors.add({
            field: 'thumbnail',
            msg: 'The thumbnail file is required.'
          })
        }
        if (!this.thumbnailHover.input && !this.thumbnailHover.path) {
          this.errors.add({
            field: 'thumbnailHover',
            msg: 'The thumbnail hover file is required.'
          })
        }
        if (!this.thumbnailActive.input && !this.thumbnailActive.path) {
          this.errors.add({
            field: 'thumbnailActive',
            msg: 'The thumbnail active file is required.'
          })
        }
        if (!this.banner.input && !this.banner.path) {
          this.errors.add({
            field: 'banner',
            msg: 'The banner file is required.'
          })
        }
        if (!this.portrait.input && !this.portrait.path) {
          this.errors.add({
            field: 'portrait',
            msg: 'The portrait file is required.'
          })
        }
        if (!this.cover.input && !this.cover.path) {
          this.errors.add({
            field: 'cover',
            msg: 'The cover file is required.'
          })
        }
        if (size(this.newPlatforms) <= 0 && size(this.game.platforms) <= 0) {
          this.errors.add({
            field: 'platform',
            msg: 'The platform field is required.'
          })
        }

        if (this.errors.any()) {
          return
        }

        if(this.isUpdateGame()) {
          return this.requestHandler(rf.getRequest('GameRequest').updateGame(this.createFormData()))
        }
        return this.requestHandler(rf.getRequest('GameRequest').createGame(this.createFormData()))
      },

      createFormData() {
        let formData = new FormData()
        formData.append('id', this.game.id)
        formData.append('title', this.game.title)
        formData.append('slug', this.game.slug)
        formData.append('is_active', this.game.is_active)
        formData.append('order', parseInt(this.game.order))
        formData.append('auto_order', this.game.auto_order ? 1 : 0)
        formData.append('logo', this.logo.input ? this.logo.input : this.logo.path)
        formData.append('thumbnail', this.thumbnail.input ? this.thumbnail.input : this.thumbnail.path)
        formData.append('thumbnail_hover', this.thumbnailHover.input ? this.thumbnailHover.input : this.thumbnailHover.path)
        formData.append('thumbnail_active', this.thumbnailActive.input ? this.thumbnailActive.input : this.thumbnailActive.path)
        formData.append('banner', this.banner.input ? this.banner.input : this.banner.path)
        formData.append('portrait', this.portrait.input ? this.portrait.input : this.portrait.path)
        formData.append('platforms', JSON.stringify(this.newPlatforms))
        formData.append('cover', this.cover.input ? this.cover.input : this.cover.path)
        formData.append('servers', JSON.stringify(this.servers))
        formData.append('ranks', JSON.stringify(this.ranks))
        return formData
      },

      goToGamesList() {
        this.$router.push({ name: 'Games Setting' })
      },

      requestHandler(promise) {
        this.startSubmit()
        this.isLoading = true
        promise.then(res => {
          this.showSuccess('Successfull!')
          this.goToGamesList()
        })
        .catch(error => {
          this.endSubmit()
          this.isLoading = false
          if (!error.response) {
            this.showError(window.i18n.t("common.message.network_error"));
            return;
          }
          this.convertRemoteErrors(error)
          if (this.errors.has('error')) {
            this.showError(error.response.data.message);
          }
        })
      },

      getPlatforms() {
        rf.getRequest('SettingRequest').getPlatforms().then(res => {
          this.platforms = res.data

          this.setGamePlatforms()
          this.setPlatformsList()
          this.currentGame = window._.cloneDeep(this.game);
        })
      },

      setPlatformsList () {
        this.platforms = window._.difference(this.platforms, this.game.platforms)
      },

      setGamePlatforms () {
        this.game.platforms = window._.map(this.game.platforms, item => {
          return window._.find(this.platforms, i => {
            return item.platform_id === i.id
          })
        })
      },

      addPlatform (platformName, id) {
        const existedOldPlatform = window._.find(this.game.platforms, item => {
          return item.name === platformName
        })

        const existedPlatform = window._.find(this.platforms, item => {
          return item.name === platformName
        })

        if (existedPlatform || existedOldPlatform) {
          alert('Platform existed')
          return
        }
        const newItem = {
          id: null,
          name: platformName
        }
        this.platforms.push(newItem)
        this.newPlatforms.push(newItem)
      },

      addServer () {
        if (!this.newServer) {
          return
        }

        const existedServer = window._.find(this.game.servers, item => {
          return item.name === this.newServer
        })

        if (this.servers.includes(this.newServer) || existedServer) {
          this.errors.add({
            field: 'server',
            msg: 'The server name is existed.'
          })
          return
        }
        this.servers.push(this.newServer)
        this.newServer = null
      },

      removeServer (index) {
        this.servers.splice(index, 1)
      },

      addRank () {
        if (!this.newRank) {
          return
        }

        const existedRank = window._.find(this.game.ranks, item => {
          return item.name === this.newRank
        })

        if (this.ranks.includes(this.newRank) || existedRank) {
          this.errors.add({
            field: 'rank',
            msg: 'The rank name is existed.'
          })
          return
        }
        this.ranks.push(this.newRank)
        this.newRank = null
      },

      removeRank (index) {
        this.ranks.splice(index, 1)
      },

      onUpdateSelected(newSelected) {
        this.game.platforms = newSelected;
      },

      onChangeLogo (e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        if (!this.isValidFileType(files[0])) {
          this.$refs.inputLogo.value = ''
          this.errors.add({
            field: 'logo',
            msg: 'The logo file type is Invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'logo',
            msg: 'The logo file size can not exceed 10MB!'
          })
          return
        }

        this.logo.path = URL.createObjectURL(e.target.files[0])
        this.logo.input = files[0]
        this.logo.name = files[0].name
      },

      onChangeThumbnail (e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        if (!this.isValidFileType(files[0])) {
          this.$refs.inputThumbnail.value = ''
          this.errors.add({
            field: 'thumbnail',
            msg: 'The thumbnail file type is invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'thumbnail',
            msg: 'The thumbnail file size can not exceed 10MB!'
          })
          return
        }

        this.thumbnail.path = URL.createObjectURL(e.target.files[0])
        this.thumbnail.input = files[0]
        this.thumbnail.name = files[0].name
      },

      onChangeThumbnailHover (e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        if (!this.isValidFileType(files[0])) {
          this.$refs.inputThumbnailHover.value = ''
          this.errors.add({
            field: 'thumbnailHover',
            msg: 'The thumbnail hover file type is invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'thumbnailHover',
            msg: 'The thumbnail hover file size can not exceed 10MB!'
          })
          return
        }

        this.thumbnailHover.path = URL.createObjectURL(e.target.files[0])
        this.thumbnailHover.input = files[0]
        this.thumbnailHover.name = files[0].name
      },

      onChangeThumbnailActive (e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        if (!this.isValidFileType(files[0])) {
          this.$refs.inputThumbnailActive.value = ''
          this.errors.add({
            field: 'thumbnailActive',
            msg: 'The thumbnail active file type is invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'thumbnailActive',
            msg: 'The thumbnail active file size can not exceed 10MB!'
          })
          return
        }

        this.thumbnailActive.path = URL.createObjectURL(e.target.files[0])
        this.thumbnailActive.input = files[0]
        this.thumbnailActive.name = files[0].name
      },

      onChangeBanner (e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        if (!this.isValidFileType(files[0])) {
          this.$refs.inputBanner.value = ''
          this.errors.add({
            field: 'banner',
            msg: 'The banner file type is invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'banner',
            msg: 'The banner file size can not exceed 10MB!'
          })
          return
        }

        this.banner.path = URL.createObjectURL(e.target.files[0])
        this.banner.input = files[0]
        this.banner.name = files[0].name
      },

      onChangePortrait (e) {
        this.resetError()

        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }

        if (!this.isValidFileType(files[0])) {
          this.$refs.inputPortrait.value = ''
          this.errors.add({
            field: 'portrait',
            msg: 'The portrait file type is invalid.'
          })
          return
        }

        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'portrait',
            msg: 'The portrait file size can not exceed 10MB!'
          })
          return
        }

        this.portrait.path = URL.createObjectURL(e.target.files[0])
        this.portrait.input = files[0]
        this.portrait.name = files[0].name
      },

      onChangeCover (e) {
        this.resetError()
        let files = e.target.files || e.dataTransfer.files
        if (!files.length) {
          return
        }
        if (!this.isValidFileType(files[0])) {
          this.$refs.inputCover.value = ''
          this.errors.add({
            field: 'cover',
            msg: 'The cover file type is invalid.'
          })
          return
        }
        if (files[0].size > MAXIMUM_FILESIZE) {
          this.errors.add({
            field: 'cover',
            msg: 'The cover file size can not exceed 10MB!'
          })
          return
        }
        this.cover.path = URL.createObjectURL(e.target.files[0])
        this.cover.input = files[0]
        this.cover.name = files[0].name
      },

      isValidFileType (file) {
        let fileType = file.type
        return ALLOW_FILETYPE.includes(fileType)
      }
    },

    mounted() {
      if(this.$route.params.id !== undefined ) {
        this.titlePage = this.$t('edit_game')
        rf.getRequest('GameRequest').detailGame(this.$route.params.id).then((res) => {
          this.game = res.data || {}
          this.initGame()
          this.getPlatforms()
        })
      } else {
        this.getPlatforms()
      }
      this.$emit('EVENT_PAGE_CHANGE', this);
    },
  }
</script>

<style lang="scss" scoped>
  @import "../../../../sass/common";
  .group-file {
    display: flex;
  }
  .custom-file {
    .error-box {
      line-height: 0px;
    }
  } 
  .custom-file-label {
    @include custom-file-input();
    max-width: 300px;
    height: 42px;
    padding: 10px 12px;
    top: 0;
    &:after {
      height: 42px;
      padding: 10px 12px;
    }
  }

  .custom-file {
    .error-box {
      line-height: 0px;
    }
  }

  input[type='file'] {
    visibility: hidden;
  }

  .error-box {
    .is-error {
      position: relative;
    }
  }

  .form-game-container {
    #game {
      margin-top: 10px;
      .add-btn {
        margin-top: 10px;
        background-color: #12575f;
        color: #fff;
      }
      .form-group {
        margin-bottom: 15px;
        .list-platform {
          margin-top: 20px;
        }
        .game-form-label {
          color: $color_dove_gray;
          font-size: $font_root;
          font-weight: 500;
          line-height: 17px;
          margin-bottom: 5px;
          sup {
            color: #e2221e;
            font-size: 20px;
            position: initial;
          }
        }
        select.form-control,
        input.form-control {
          border: 1px solid $color_alto;
          background-color: $color_white;
          height: 40px;
          line-height: 20px;
          padding: 7px 15px;
          color: $color_mine_shaft;
          font-size: $font_root;
          font-weight: 500;
        }
        .error-box {
          .is-error {
            color: #e2221e;
          }
        }
        .review_image {
          img {
            border-radius: 10px;
            width: 100%;
            max-width: 300px;
          }
          .group-thumbnail {
            width: 100%;
            max-width: 300px;
            position: relative;
            .thumbnail {
              width: 260px;
              height: auto;
            }
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

      .list-new-item {
        padding: 0;
        margin-top: 20px;
        list-style: none;
        max-height: 218px;
        overflow-y: auto;

        .new-item {
          position: relative;
          padding: 5px 15px;
          border-bottom: thin solid #cfcfcf;

          &:hover {
            background-color: #fff;
          }

          .item-close {
            position: absolute;
            right: 10px;
            cursor: pointer;

            &:after {
              content: "\D7";
              color: #000;
              font-size: 17px;
            }
          }
        }

      }
    }

    .btn_button_done {
      line-height: 20px;
      height: 35px;
      padding: 7px 35px;
      min-width: 90px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
      text-align: center;
      text-transform: uppercase;
      transition: 0.5s;
    }

    .btn_button_done {
      background-color: $color_eden;
      border-color: $color_eden;
      color: $color_white;
      &:hover {
        opacity: 0.6;
      }
    }
    .custom-checkbox {
      .custom-control-input:checked~.custom-control-label::before{
        background-color:black;
      }
    }
  }
</style>
