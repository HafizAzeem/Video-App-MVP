<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AudioPlayer from '@/Components/AudioPlayer.vue';
import MicRecorder from '@/Components/MicRecorder.vue';

const props = defineProps({
    questions: Array,
    userAnswers: Array,
    currentQuestionIndex: {
        type: Number,
        default: 0,
    },
});

const currentIndex = ref(props.currentQuestionIndex);

const currentQuestion = computed(() => props.questions[currentIndex.value]);
const progress = computed(() => ((currentIndex.value + 1) / props.questions.length) * 100);
const isLastQuestion = computed(() => currentIndex.value === props.questions.length - 1);

// Character configuration for each question
const characters = [
    { name: '루비', nameEn: 'Ruby', image: '/images/KakaoTalk_20251106_111302420.png', color: 'bg-orange-500' }, // Fox
    { name: '엘리', nameEn: 'Ellie', image: '/images/KakaoTalk_20251106_111302790.png', color: 'bg-teal-600' }, // Elephant
    { name: '피피', nameEn: 'Pipi', image: '/images/KakaoTalk_20251106_111303519.png', color: 'bg-teal-600' }, // Bird
    { name: '올리', nameEn: 'Ollie', image: '/images/KakaoTalk_20251106_111301981.png', color: 'bg-blue-600' }, // Owl
];

const currentCharacter = computed(() => characters[currentIndex.value] || characters[0]);

const form = useForm({
    question_id: null,
    text: '',
    audio: null,
});

const answerMode = ref('voice'); // 'voice' or 'text'

const nextQuestion = () => {
    if (currentIndex.value < props.questions.length - 1) {
        currentIndex.value++;
        resetForm();
    }
};

const previousQuestion = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
        resetForm();
    }
};

const resetForm = () => {
    form.reset();
    answerMode.value = 'voice';
};

const handleRecordingSaved = (audioBlob) => {
    form.question_id = currentQuestion.value.id;
    form.audio = new File([audioBlob], 'answer.webm', { type: 'audio/webm' });

    form.post(route('questions.answer'), {
        preserveScroll: true,
        onSuccess: () => {
            if (!isLastQuestion.value) {
                nextQuestion();
            } else {
                router.visit(route('review.index'));
            }
        },
    });
};

const submitTextAnswer = () => {
    form.question_id = currentQuestion.value.id;

    form.post(route('questions.answer'), {
        preserveScroll: true,
        onSuccess: () => {
            if (!isLastQuestion.value) {
                nextQuestion();
            } else {
                router.visit(route('review.index'));
            }
        },
    });
};

const getUserAnswer = (questionId) => {
    return props.userAnswers.find(a => a.question_id === questionId);
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
                            v-model="form.text"
                            rows="8"
                            class="w-full px-4 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            placeholder="Type or use voice to answer..."
                        ></textarea>
                    </div>

                    <!-- Mic Button -->
                    <div class="flex justify-center">
                        <MicRecorder
                            :max-duration="120"
                            @saved="handleRecordingSaved"
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

                <!-- Next Button -->
                <div class="flex justify-end mt-6">
                    <button
                        @click="submitTextAnswer"
                        :disabled="!form.text || form.processing"
                        class="px-12 py-4 bg-blue-600 hover:bg-blue-700 text-white text-xl font-semibold rounded-full shadow-xl hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        {{ form.processing ? 'Saving...' : 'Next' }}
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
