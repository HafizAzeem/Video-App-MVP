<template>
    <div class="video-player bg-black rounded-lg overflow-hidden shadow-xl">
        <video
            ref="videoElement"
            :src="src"
            :poster="poster"
            controls
            :autoplay="autoplay"
            :loop="loop"
            class="w-full aspect-video"
            @loadedmetadata="onLoadedMetadata"
            @error="onError"
        >
            Your browser does not support the video tag.
        </video>

        <div v-if="showInfo" class="p-4 bg-gray-900 text-white">
            <h3 v-if="title" class="text-lg font-semibold">{{ title }}</h3>
            <p v-if="description" class="text-sm text-gray-300 mt-1">{{ description }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    src: {
        type: String,
        required: true,
    },
    poster: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
    autoplay: {
        type: Boolean,
        default: false,
    },
    loop: {
        type: Boolean,
        default: false,
    },
    showInfo: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['loaded', 'error']);

const videoElement = ref(null);

const onLoadedMetadata = () => {
    emit('loaded', {
        duration: videoElement.value.duration,
        width: videoElement.value.videoWidth,
        height: videoElement.value.videoHeight,
    });
};

const onError = (error) => {
    emit('error', error);
};
</script>
