const galleryImg = {
    data(){
            return {
                params: '',
                countColumns: '',
                allItems: '',
                itemsColumns: [],
                searchQuery: '',
                sortBy: 'date',
                loading: false,
                hasMore: 'true',
                currentPage: 1,
                isLoadingMore: false,
                observer: null
            }
        },
        created() {
            this.params = vueImgData.params,
            this.allItems = vueImgData.items,
            this.updateColumnCount()
            this.initColumns();
        },
        methods:{
            initColumns() {
                this.itemsColumns = Array.from({ length: this.countColumns }, () => []);
                this.distributeItems(this.allItems, this.itemsColumns, this.countColumns);
            },
            loadNextPage(){
                currentPage = this.params.currentPage++
                BX.ajax.runComponentAction(this.params.ajaxComponent, "load", {
                    mode: "class",
                    signedParameters: this.params.signedParams,
                    data: {
                        page: this.params.currentPage
                    }
                }).then((response)=>{
                    this.distributeItems(response.data.html, this.itemsColumns , this.countColumns)
                    this.hasMore = response.data.hasMore
                }).catch(function(err){
                    console.error('AJAX error', err);
                });
            },
            distributeItems(items, itemsCol, countColumns) {
                items.forEach((item, index) => {
                    const col = index % countColumns;
                    itemsCol[col].push(item);
                });
            },
            initScrollObserver() {
                const triggerElement = this.$refs.scrollTrigger;
                if (!triggerElement) return;

                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !this.loading && this.hasMore) {
                            this.loadNextPage()
                        }
                    });
                }, { rootMargin: '50px' });

                this.observer.observe(triggerElement);
            },
            getColumnCountByScreen() {
                const width = window.innerWidth;
                if (width < 1024) return 2;
                if (width < 1440) return 3;
                return 4;
            },
            updateColumnCount() {
                this.countColumns = this.getColumnCountByScreen();
            }
        },
        computed: {},
        mounted() {
            this.$nextTick(() => {
                this.initScrollObserver();
            });
        },
        beforeUnmount() {
            if (this.observer) {
                this.observer.disconnect();
            }
        },
        components: {
            'img-item':imgItem,
        },
        template:`
                <div class="gallery-grid gallery_masonry_wrapper" id="gallery-container">
                        <div :id="'column_' + idx" class="column_gallery" v-for="(columns, idx) in this.itemsColumns">
                                <div class="gallery-item"
                                     :data-id=item_idx
                                     v-for="(item, item_idx) in columns"
                                      >
                                      <img-item :image_data='item'></img-item>
                                </div>
                        </div>
                </div>
                <!-- После колонок -->
                <div v-if="isLoadingMore">Загрузка...</div>
                <div ref="scrollTrigger" style="height: 1px;"></div>
                <!--<div v-if="!hasMore">Все загружено</div>-->
        `

    };