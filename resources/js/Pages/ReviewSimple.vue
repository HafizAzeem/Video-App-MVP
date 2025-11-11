<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    questions: {
        type: Array,
        required: true,
    },
});

const STORAGE_KEY = 'las_question_answers';

const videoPrompt = ref(null);
const isGenerating = ref(false);
const answers = ref({});

onMounted(() => {
    loadAnswersAndGeneratePrompt();
});

const loadAnswersAndGeneratePrompt = async () => {
    // Load answers from localStorage
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            answers.value = JSON.parse(saved);
        }
    } catch (error) {
        console.error('Failed to load from localStorage:', error);
    }

    // Check if all questions are answered
    const allAnswered = props.questions.every(q => answers.value[q.id]?.trim());
    
    if (!allAnswered) {
        alert('Please answer all questions before reviewing.');
        router.visit(route('questions.index'));
        return;
    }

    // Generate video prompt
    await generateVideoPrompt();
};

const generateVideoPrompt = async () => {
    isGenerating.value = true;

    try {
        // Format answers for backend
        const formattedAnswers = props.questions.map(q => ({
            question: q.text,
            answer: answers.value[q.id] || '',
        }));

        const response = await axios.post(route('review.generate-prompt'), {
            answers: formattedAnswers,
        });

        if (response.data.success) {
            videoPrompt.value = response.data.videoPrompt;
        } else {
            throw new Error(response.data.error || 'Failed to generate video prompt');
        }
    } catch (error) {
        console.error('Failed to generate video prompt:', error);
        alert('Failed to generate video prompt. Please try again.');
    } finally {
        isGenerating.value = false;
    }
};

const goBack = () => {
    router.visit(route('questions.index'));
};

const confirmAndProceed = () => {
    if (!videoPrompt.value) {
        alert('Please wait for the video prompt to be generated.');
        return;
    }
    
    router.visit(route('production.index'));
};
</script>

<template>
    <Head title="Review" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 py-12">
            <div class="max-w-4xl mx-auto px-4">
                <!-- Header Message -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        You had this experience through this book!!
                    </h1>
                    <p class="text-xl text-gray-700">
                        Shall we make a fun video with this content?
                    </p>
                </div>

                <!-- Video Prompt Display -->
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 min-h-[400px] flex items-center justify-center">
                    <div v-if="isGenerating" class="text-center">
                        <div class="inline-flex items-center space-x-2 text-blue-600 mb-4">
                            <svg class="animate-spin h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <p class="text-lg text-gray-600">Creating your amazing video concept...</p>
                        <p class="text-sm text-gray-500 mt-2">This may take a moment</p>
                    </div>
                    <div v-else-if="videoPrompt" class="w-full">
                        <p class="text-gray-800 text-lg leading-relaxed whitespace-pre-line">
                            {{ videoPrompt }}
                        </p>
                    </div>
                    <div v-else class="text-center text-gray-500">
                        <p class="text-lg">Failed to generate video prompt</p>
                        <p class="text-sm mt-2">Please go back and try again</p>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-center space-x-6">
                    <button
                        @click="goBack"
                        :disabled="isGenerating"
                        class="px-12 py-4 bg-gray-400 hover:bg-gray-500 text-white text-xl font-semibold rounded-full shadow-xl hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        Back
                    </button>
                    <button
                        @click="confirmAndProceed"
                        :disabled="!videoPrompt || isGenerating"
                        class="px-12 py-4 bg-blue-600 hover:bg-blue-700 text-white text-xl font-semibold rounded-full shadow-xl hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        {{ isGenerating ? 'Generating...' : 'Next' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any custom styles here */
</style>
