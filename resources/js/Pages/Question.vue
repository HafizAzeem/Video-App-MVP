<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import SpeechRecorder from '@/Components/SpeechRecorder.vue';

const props = defineProps({
    questions: Array,
    currentQuestionIndex: {
        type: Number,
        default: 0,
    },
});

const STORAGE_KEY = 'las_question_answers';

const currentIndex = ref(props.currentQuestionIndex);
const answers = ref({});

const currentQuestion = computed(() => props.questions[currentIndex.value]);
const currentAnswer = computed({
    get: () => answers.value[currentQuestion.value?.id] || '',
    set: (value) => {
        if (currentQuestion.value) {
            answers.value[currentQuestion.value.id] = value;
            saveToLocalStorage();
        }
    }
});

const isLastQuestion = computed(() => currentIndex.value === props.questions.length - 1);

// Character configuration for each question
const characters = [
    { name: '루비', nameEn: 'Ruby', image: '/images/KakaoTalk_20251106_111302420.png', color: 'bg-orange-500' },
    { name: '엘리', nameEn: 'Ellie', image: '/images/KakaoTalk_20251106_111302790.png', color: 'bg-teal-600' },
    { name: '피피', nameEn: 'Pipi', image: '/images/KakaoTalk_20251106_111303519.png', color: 'bg-teal-600' },
    { name: '올리', nameEn: 'Ollie', image: '/images/KakaoTalk_20251106_111301981.png', color: 'bg-blue-600' },
];

const currentCharacter = computed(() => characters[currentIndex.value] || characters[0]);

// Load answers from localStorage on mount
onMounted(() => {
    loadFromLocalStorage();
});

// Save to localStorage
const saveToLocalStorage = () => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(answers.value));
    } catch (error) {
        console.error('Failed to save to localStorage:', error);
    }
};

// Load from localStorage
const loadFromLocalStorage = () => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            answers.value = JSON.parse(saved);
        }
    } catch (error) {
        console.error('Failed to load from localStorage:', error);
        answers.value = {};
    }
};

const nextQuestion = () => {
    if (!currentAnswer.value.trim()) {
        alert('Please answer the question before moving to the next one.');
        return;
    }
    
    if (currentIndex.value < props.questions.length - 1) {
        currentIndex.value++;
    } else {
        // Last question - go to review
        router.visit(route('review.index'));
    }
};

const previousQuestion = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
};

const handleTranscribed = (text, shouldAppend = false) => {
    // If shouldAppend is true and text is not empty, append to existing answer
    // If shouldAppend is false or text is empty, replace the answer
    if (shouldAppend && text) {
        // Append with a space if there's existing text
        if (currentAnswer.value.trim()) {
            currentAnswer.value = currentAnswer.value.trim() + ' ' + text;
        } else {
            currentAnswer.value = text;
        }
    } else {
        // Replace (used for clearing)
        currentAnswer.value = text;
    }
};
</script>

<template>
    <Head title="Answer Questions" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 py-12">
            <div class="max-w-3xl mx-auto px-4">
                <!-- Character Image -->
                <div class="text-center mb-6">
                    <img 
                        :src="currentCharacter.image" 
                        :alt="currentCharacter.nameEn" 
                        class="w-48 h-48 mx-auto object-contain drop-shadow-xl animate-bounce-slow"
                    />
                    <!-- Character Name Badge -->
                    <div class="mt-4">
                        <span 
                            :class="[currentCharacter.color, 'inline-block px-6 py-2 text-white font-bold text-lg rounded-full shadow-lg']"
                        >
                            {{ currentCharacter.name }}
                        </span>
                    </div>
                </div>

                <!-- Question Card -->
                <div class="bg-white rounded-2xl shadow-xl p-8 space-y-6">
                    <!-- Question Text -->
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">
                            Q.{{ currentIndex + 1 }} {{ currentQuestion?.text }}
                        </h2>
                    </div>

                    <!-- Answer Input Area -->
                    <div class="space-y-4">
                        <textarea
                            v-model="currentAnswer"
                            rows="8"
                            class="w-full px-4 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            placeholder="Type or use voice to answer..."
                        ></textarea>
                    </div>

                    <!-- Speech Recorder -->
                    <!-- Auto-stops after 4 seconds of silence -->
                    <div class="flex justify-center">
                        <SpeechRecorder
                            :max-duration="120"
                            :silence-timeout="4000"
                            language="en-US"
                            @transcribed="handleTranscribed"
                            class="inline-block"
                        />
                    </div>

                    <!-- Progress Dots -->
                    <div class="flex justify-center space-x-3 pt-4">
                        <div
                            v-for="index in questions.length"
                            :key="index"
                            :class="[
                                'w-3 h-3 rounded-full transition-all',
                                index - 1 === currentIndex ? 'bg-blue-600' : 'bg-gray-300'
                            ]"
                        ></div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between mt-6">
                    <button
                        v-if="currentIndex > 0"
                        @click="previousQuestion"
                        class="px-12 py-4 bg-gray-400 hover:bg-gray-500 text-white text-xl font-semibold rounded-full shadow-xl hover:shadow-2xl transition-all"
                    >
                        Back
                    </button>
                    <div v-else></div>
                    
                    <button
                        @click="nextQuestion"
                        :disabled="!currentAnswer"
                        class="px-12 py-4 bg-blue-600 hover:bg-blue-700 text-white text-xl font-semibold rounded-full shadow-xl hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        {{ isLastQuestion ? 'Review' : 'Next' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes bounce-slow {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-bounce-slow {
    animation: bounce-slow 2s ease-in-out infinite;
}
</style>
