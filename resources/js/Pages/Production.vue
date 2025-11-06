<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import VideoPlayer from '@/Components/VideoPlayer.vue';

const props = defineProps({
    video: {
        type: Object,
        default: null,
    },
    videoPrompt: {
        type: String,
        default: null,
    },
});

const isGenerating = ref(false);
const progress = ref(0);
const statusMessage = ref('');
const pollInterval = ref(null);

const videoStatus = computed(() => props.video?.status || 'pending');
const videoUrl = computed(() => props.video?.video_url || null);
const isCompleted = computed(() => videoStatus.value === 'completed');
const hasFailed = computed(() => videoStatus.value === 'failed');
const isProcessing = computed(() => ['pending', 'processing'].includes(videoStatus.value));

onMounted(() => {
    if (props.video && isProcessing.value) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});

const startVideoGeneration = () => {
    isGenerating.value = true;
    statusMessage.value = 'Initializing Google Veo video generation...';
    progress.value = 5;

    router.post(route('production.generate'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            startPolling();
        },
        onFinish: () => {
            isGenerating.value = false;
        },
    });
};

const startPolling = () => {
    if (pollInterval.value) return;

    pollInterval.value = setInterval(() => {
        checkVideoStatus();
    }, 5000); // Poll every 5 seconds
};

const stopPolling = () => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
        pollInterval.value = null;
    }
};

const checkVideoStatus = () => {
    if (!props.video) return;

    router.get(
        route('production.status', { video: props.video.id }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['video'],
            onSuccess: (page) => {
                const video = page.props.video;
                
                if (video.status === 'completed') {
                    progress.value = 100;
                    statusMessage.value = 'Video generation completed!';
                    stopPolling();
                } else if (video.status === 'failed') {
                    statusMessage.value = 'Video generation failed. Please try again.';
                    stopPolling();
                } else if (video.status === 'processing') {
                    // Update progress based on elapsed time (simulated)
                    const currentProgress = Math.min(progress.value + 5, 95);
                    progress.value = currentProgress;
                    statusMessage.value = 'Generating your video with Google Veo...';
                }
            },
        }
    );
};

const retryGeneration = () => {
    progress.value = 0;
    startVideoGeneration();
};

const goToGallery = () => {
    router.visit(route('gallery.index'));
};

const goBack = () => {
    router.visit(route('review.index'));
};
</script>

<template>
    <Head title="Video Production" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 py-12">
            <div class="max-w-4xl mx-auto px-4">
                <!-- Header -->
                <div class="text-center mb-12">
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">
                        Video Production
                    </h1>
                    <p class="text-gray-600">
                        Creating your AI-powered video story with Google Veo
                    </p>
                </div>

                <!-- Video Prompt Display -->
                <div v-if="videoPrompt" class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-indigo-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        Video Prompt
                    </h2>
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6">
                        <p class="text-gray-800 leading-relaxed">
                            {{ videoPrompt }}
                        </p>
                    </div>
                </div>

                <!-- Video Generation Status -->
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <!-- Not Started State -->
                    <div v-if="!video" class="text-center py-12">
                        <div class="w-24 h-24 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            Ready to Generate Your Video
                        </h2>
                        <p class="text-gray-600 mb-8">
                            Click the button below to start creating your AI-powered video story
                        </p>
                        <button
                            @click="startVideoGeneration"
                            :disabled="isGenerating"
                            class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-10 py-4 rounded-full font-semibold text-lg shadow-lg hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isGenerating" class="flex items-center">
                                <svg class="animate-spin h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Starting...
                            </span>
                            <span v-else class="flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Generate Video with Google Veo
                            </span>
                        </button>
                    </div>

                    <!-- Processing State -->
                    <div v-else-if="isProcessing" class="py-12">
                        <div class="text-center mb-8">
                            <div class="relative w-24 h-24 mx-auto mb-6">
                                <div class="absolute inset-0 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full animate-pulse"></div>
                                <div class="relative w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-12 h-12 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                Generating Your Video
                            </h2>
                            <p class="text-gray-600 mb-8">
                                {{ statusMessage || 'Please wait while Google Veo creates your video...' }}
                            </p>
                        </div>

                        <ProgressBar :progress="progress" />

                        <div class="mt-8 text-center">
                            <p class="text-sm text-gray-500">
                                This may take 1-2 minutes. Please don't close this window.
                            </p>
                        </div>
                    </div>

                    <!-- Completed State -->
                    <div v-else-if="isCompleted" class="py-8">
                        <div class="text-center mb-8">
                            <div class="w-24 h-24 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                Video Ready!
                            </h2>
                            <p class="text-gray-600 mb-8">
                                Your AI-powered video story has been generated successfully
                            </p>
                        </div>

                        <div v-if="videoUrl" class="mb-8">
                            <VideoPlayer :src="videoUrl" />
                        </div>

                        <div class="flex justify-center space-x-4">
                            <button
                                @click="retryGeneration"
                                class="px-6 py-3 border-2 border-indigo-600 text-indigo-600 rounded-full font-semibold hover:bg-indigo-50 transition-all duration-300"
                            >
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Regenerate
                                </span>
                            </button>
                            <button
                                @click="goToGallery"
                                class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300"
                            >
                                <span class="flex items-center">
                                    View in Gallery
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Failed State -->
                    <div v-else-if="hasFailed" class="text-center py-12">
                        <div class="w-24 h-24 bg-gradient-to-br from-red-400 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            Video Generation Failed
                        </h2>
                        <p class="text-gray-600 mb-8">
                            {{ video.error_message || 'Something went wrong. Please try again.' }}
                        </p>
                        <button
                            @click="retryGeneration"
                            class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300"
                        >
                            Try Again
                        </button>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex justify-center">
                    <button
                        @click="goBack"
                        class="text-indigo-600 hover:text-indigo-700 font-medium flex items-center space-x-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Back to Review</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
