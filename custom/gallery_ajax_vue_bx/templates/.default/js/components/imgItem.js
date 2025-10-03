const imgItem = {
    data()
    {
        return {
            count: 0
        }
    },
    props:['image_data'],
    template:`
                                    <a :href="image_data.SRC" data-fancybox="gallery" >
                                        <img
                                                :src="image_data.RESIZE_PREVIEW.src"
                                                class="element-item persent-size lazyload fade-img"
                                                :width="image_data.RESIZE_PREVIEW.width"
                                                :height="image_data.RESIZE_PREVIEW.height"
                                                :alt="image_data.NAME"
                                                :title="image_data.NAME"
                                        />
                                    </a>
        `
};