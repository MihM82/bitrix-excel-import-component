const BitrixVue = BX.Vue3.BitrixVue;


const app = BitrixVue.createApp({
    // хранения данных
    data() {
        return{
            test: 'test'
        }
    },
    // свои функции
    methods: { },
    // вычисляемые свойства
    computed: { },
    // асинхронные действия
    watch: { },
    // повторяющийся код
    mixins: [ ],
    components: {
        'gallery-img': galleryImg
    },
    template:`
                                      <gallery-img></gallery-img>
        `
});
// контейнер
app.mount('#app');