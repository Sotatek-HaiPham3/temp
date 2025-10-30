export default {
  methods: {
    resetError () {
      this.errors.clear()
    },

    convertRemoteErrors(error) {
      const errors = error.response.data.errors || {}
      for (const field in errors) {
        for (const error in errors[field]) {
          this.errors.add({ field: field, msg: errors[field][error] })
        }
      }

      if (!this.errors.any()) {
        if (error.response.data.message) {
          this.showError(error.response.data.message);
          return
        }
        this.errors.add({ field: 'error', msg:'Some error occurred. Please try again later' })
      }
    }
  },
}
