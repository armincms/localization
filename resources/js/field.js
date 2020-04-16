Nova.booting((Vue, router, store) => {
    Vue.component('index-language-toolbar', require('./components/IndexField'))
    Vue.component('detail-language-toolbar', require('./components/FormField'))
    Vue.component('form-language-toolbar', require('./components/FormField'))
})
