<template>
    <div class="audio-player bg-white rounded-lg shadow-md p-4">
        <div class="flex items-center space-x-4">
            <button
                @click="togglePlay"
                class="p-3 rounded-full bg-indigo-600 text-white hover:bg-indigo-700 transition"
                :disabled="loading"
            >
                <svg v-if="!isPlaying" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                </svg>
                <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z" />
                </svg>
            </button>

            <div class="flex-1">
                <div class="relative h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                        class="absolute h-full bg-indigo-600 transition-all"
                        :style="{ width: progress + '%' }"
                    ></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>{{ formatTime(currentTime) }}</span>
                    <span>{{ formatTime(duration) }}</span>
                </div>
            </div>

            <button
                v-if="showDownload"
                @click="download"
                class="p-2 text-gray-600 hover:text-indigo-600 transition"
                title="Download"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
            </button>
        </div>

        <audio
            ref="audioElement"
            :src="src"
            @loadedmetadata="onLoadedMetadata"
            @timeupdate="onTimeUpdate"
            @ended="onEnded"
            @error="onError"
        ></audio>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    src: {
        type: String,
        required: true,
    },
    autoplay: {
        type: Boolean,
        default: false,
    },
    showDownload: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['ended', 'error']);

const audioElement = ref(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const loading = ref(true);

const progress = computed(() => {
    return duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0;
});

const togglePlay = () => {
    if (isPlaying.value) {
        audioElement.value.pause();
    } else {
        audioElement.value.play();
    }
    isPlaying.value = !isPlaying.value;
};

const onLoadedMetadata = () => {
    duration.value = audioElement.value.duration;
    loading.value = false;

    if (props.autoplay) {
        audioElement.value.play();
        isPlaying.value = true;
    }
};

const onTimeUpdate = () => {
    currentTime.value = audioElement.value.currentTime;
};

const onEnded = () => {
    isPlaying.value = false;
    currentTime.value = 0;
    emit('ended');
};

const onError = (error) => {
    loading.value = false;
    emit('error', error);
};

const formatTime = (seconds) => {
    if (!seconds || isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const download = () => {
    const a = document.createElement('a');
    a.href = props.src;
    a.download = 'audio.mp3';
    a.click();
};

onUnmounted(() => {
    if (audioElement.value) {
        audioElement.value.pause();
    }
});
</script>
