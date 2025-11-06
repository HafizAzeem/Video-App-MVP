<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AudioPlayer from '@/Components/AudioPlayer.vue';

const props = defineProps({
    answers: Array,
    summary: {
        type: String,
        default: null,
    },
    audioUrl: {
        type: String,
        default: null,
    },
});

const isGeneratingSummary = ref(false);
const isGeneratingAudio = ref(false);

const form = useForm({
    confirmed: false,
});

const hasSummary = computed(() => props.summary !== null);
const hasAudio = computed(() => props.audioUrl !== null);

const generateSummary = () => {
    isGeneratingSummary.value = true;
    
    router.post(route('review.summary'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isGeneratingSummary.value = false;
        },
    });
};

const generateAudio = () => {
    isGeneratingAudio.value = true;
    
    router.post(route('review.tts'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isGeneratingAudio.value = false;
        },
    });
};

const confirmAndProceed = () => {
    form.post(route('review.confirm'), {
        preserveScroll: false,
        onSuccess: () => {
            router.visit(route('production.index'));
        },
    });
};

const goBack = () => {
    router.visit(route('questions.index'));
};
</script>

<template>
    <Head title="Review Your Answers" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 py-12">
            <div class="max-w-4xl mx-auto px-4">
                <!-- Header -->
                <div class="text-center mb-12">
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">
                        Review Your Story
                    </h1>
                    <p class="text-gray-600">
                        Check your answers and generate your video summary
                    </p>
                </div>

                <!-- Your Answers Section -->
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 text-indigo-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        Your Answers
                    </h2>

                    <div class="space-y-6">
                        <div
                            v-for="(answer, index) in answers"
                            :key="answer.id"
                            class="border-l-4 border-indigo-500 pl-6 py-4"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-semibold text-gray-900">
                                    {{ index + 1 }}. {{ answer.question.text }}
                                </h3>
                            </div>
                            
                            <div v-if="answer.transcription" class="bg-gray-50 rounded-lg p-4 mt-3">
                                <p class="text-gray-700 leading-relaxed">
                                    {{ answer.transcription }}
                                </p>
                            </div>

                            <div v-if="answer.audio_path" class="mt-3">
                                <AudioPlayer :src="answer.audio_url" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-center">
                        <button
                            @click="goBack"
                            class="text-indigo-600 hover:text-indigo-700 font-medium flex items-center space-x-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Edit Answers</span>
                        </button>
                    </div>
                </div>

                <!-- AI Summary Section -->
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 text-purple-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        AI-Generated Summary
                    </h2>

                    <div v-if="!hasSummary" class="text-center py-8">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-6">
                            Generate an AI-powered summary of your answers
                        </p>
                        <button
                            @click="generateSummary"
                            :disabled="isGeneratingSummary"
                            class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-8 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isGeneratingSummary" class="flex items-center">
                                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Generating Summary...
                            </span>
                            <span v-else>Generate Summary with Gemini AI</span>
                        </button>
                    </div>

                    <div v-else>
                        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-6 mb-6">
                            <p class="text-gray-800 leading-relaxed whitespace-pre-line">
                                {{ summary }}
                            </p>
                        </div>

                        <button
                            @click="generateSummary"
                            :disabled="isGeneratingSummary"
                            class="text-purple-600 hover:text-purple-700 font-medium text-sm flex items-center space-x-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Regenerate Summary</span>
                        </button>
                    </div>
                </div>

                <!-- Audio Narration Section -->
                <div v-if="hasSummary" class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="w-6 h-6 text-pink-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                        </svg>
                        Audio Narration
                    </h2>

                    <div v-if="!hasAudio" class="text-center py-8">
                        <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z" />
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-6">
                            Convert your summary into natural-sounding narration
                        </p>
                        <button
                            @click="generateAudio"
                            :disabled="isGeneratingAudio"
                            class="bg-gradient-to-r from-pink-600 to-purple-600 text-white px-8 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isGeneratingAudio" class="flex items-center">
                                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Generating Audio...
                            </span>
                            <span v-else>Generate Audio with Google TTS</span>
                        </button>
                    </div>

                    <div v-else>
                        <div class="bg-gradient-to-r from-pink-50 to-purple-50 rounded-xl p-6 mb-4">
                            <AudioPlayer :src="audioUrl" />
                        </div>

                        <button
                            @click="generateAudio"
                            :disabled="isGeneratingAudio"
                            class="text-pink-600 hover:text-pink-700 font-medium text-sm flex items-center space-x-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Regenerate Audio</span>
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div v-if="hasSummary && hasAudio" class="flex justify-center space-x-4">
                    <button
                        @click="goBack"
                        class="px-8 py-3 border-2 border-gray-300 text-gray-700 rounded-full font-semibold hover:border-gray-400 hover:bg-gray-50 transition-all duration-300"
                    >
                        Go Back
                    </button>
                    <button
                        @click="confirmAndProceed"
                        :disabled="form.processing"
                        class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full font-semibold shadow-lg hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing">Processing...</span>
                        <span v-else>Continue to Video Generation →</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.6s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
