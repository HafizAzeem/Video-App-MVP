<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import VideoPlayer from '@/Components/VideoPlayer.vue';

const props = defineProps({
    videos: {
        type: Array,
        default: () => [],
    },
});

const selectedVideo = ref(null);
const showDeleteModal = ref(false);
const videoToDelete = ref(null);

const openVideo = (video) => {
    selectedVideo.value = video;
};

const closeVideo = () => {
    selectedVideo.value = null;
};

const confirmDelete = (video) => {
    videoToDelete.value = video;
    showDeleteModal.value = true;
};

const cancelDelete = () => {
    videoToDelete.value = null;
    showDeleteModal.value = false;
};

const deleteVideo = () => {
    if (!videoToDelete.value) return;

    router.delete(route('videos.destroy', { video: videoToDelete.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            videoToDelete.value = null;
            if (selectedVideo.value?.id === videoToDelete.value.id) {
                selectedVideo.value = null;
            }
        },
    });
};

const createNewVideo = () => {
    router.visit(route('questions.index'));
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        completed: 'bg-green-100 text-green-800',
        processing: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800',
        pending: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || classes.pending;
};
</script>

<template>
    <Head title="Video Gallery" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 py-12">
            <div class="max-w-7xl mx-auto px-4">
                <!-- Header -->
                <div class="flex justify-between items-center mb-12">
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 mb-2">
                            Your Video Gallery
                        </h1>
                        <p class="text-gray-600">
                            View and manage your AI-generated video stories
                        </p>
                    </div>
                    <button
                        @click="createNewVideo"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300"
                    >
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create New Video
                        </span>
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="videos.length === 0" class="text-center py-20">
                    <div class="w-32 h-32 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-16 h-16 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        No Videos Yet
                    </h2>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        Start your journey by answering a few questions and create your first AI-powered video story
                    </p>
                    <button
                        @click="createNewVideo"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-4 rounded-full font-semibold text-lg shadow-lg hover:shadow-xl transition-all duration-300"
                    >
                        Get Started
                    </button>
                </div>

                <!-- Video Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="video in videos"
                        :key="video.id"
                        class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 cursor-pointer group"
                    >
                        <!-- Video Thumbnail -->
                        <div
                            @click="openVideo(video)"
                            class="relative aspect-video bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center overflow-hidden"
                        >
                            <div v-if="video.status === 'completed' && video.video_url" class="absolute inset-0">
                                <video
                                    :src="video.video_url"
                                    class="w-full h-full object-cover"
                                    preload="metadata"
                                ></video>
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-300 flex items-center justify-center">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <div class="w-16 h-16 bg-white bg-opacity-90 rounded-full flex items-center justify-center">
                                            <svg class="w-8 h-8 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-white">
                                <svg v-if="video.status === 'processing'" class="w-12 h-12 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else-if="video.status === 'failed'" class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Video Info -->
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-3">
                                <h3 class="font-semibold text-gray-900 text-lg">
                                    {{ video.title || `Video #${video.id}` }}
                                </h3>
                                <span
                                    :class="getStatusBadgeClass(video.status)"
                                    class="px-2 py-1 rounded-full text-xs font-medium capitalize"
                                >
                                    {{ video.status }}
                                </span>
                            </div>

                            <p v-if="video.prompt" class="text-gray-600 text-sm mb-4 line-clamp-2">
                                {{ video.prompt }}
                            </p>

                            <div class="flex items-center text-xs text-gray-500 mb-4">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(video.created_at) }}
                            </div>

                            <div class="flex space-x-2">
                                <button
                                    v-if="video.status === 'completed'"
                                    @click="openVideo(video)"
                                    class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition-colors duration-300"
                                >
                                    Watch
                                </button>
                                <button
                                    v-else-if="video.status === 'processing'"
                                    disabled
                                    class="flex-1 bg-gray-100 text-gray-400 px-4 py-2 rounded-lg font-medium cursor-not-allowed"
                                >
                                    Processing...
                                </button>
                                <button
                                    v-else
                                    disabled
                                    class="flex-1 bg-gray-100 text-gray-400 px-4 py-2 rounded-lg font-medium cursor-not-allowed"
                                >
                                    Unavailable
                                </button>
                                <button
                                    @click="confirmDelete(video)"
                                    class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors duration-300"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Modal -->
        <Teleport to="body">
            <div
                v-if="selectedVideo"
                @click="closeVideo"
                class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4 animate-fade-in"
            >
                <div
                    @click.stop
                    class="relative max-w-5xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden animate-scale-in"
                >
                    <!-- Close Button -->
                    <button
                        @click="closeVideo"
                        class="absolute top-4 right-4 z-10 w-10 h-10 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full flex items-center justify-center text-white transition-all duration-300"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Video Player -->
                    <div class="aspect-video bg-black">
                        <VideoPlayer v-if="selectedVideo.video_url" :src="selectedVideo.video_url" autoplay />
                    </div>

                    <!-- Video Details -->
                    <div class="p-6 bg-white">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            {{ selectedVideo.title || `Video #${selectedVideo.id}` }}
                        </h2>
                        <p v-if="selectedVideo.prompt" class="text-gray-600 mb-4">
                            {{ selectedVideo.prompt }}
                        </p>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ formatDate(selectedVideo.created_at) }}
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal"
                @click="cancelDelete"
                class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 animate-fade-in"
            >
                <div
                    @click.stop
                    class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full animate-scale-in"
                >
                    <div class="text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">
                            Delete Video?
                        </h3>
                        <p class="text-gray-600 mb-6">
                            Are you sure you want to delete this video? This action cannot be undone.
                        </p>
                        <div class="flex space-x-4">
                            <button
                                @click="cancelDelete"
                                class="flex-1 px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-all duration-300"
                            >
                                Cancel
                            </button>
                            <button
                                @click="deleteVideo"
                                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition-all duration-300"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

.animate-scale-in {
    animation: scaleIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
