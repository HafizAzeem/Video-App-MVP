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
        <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 py-12">
            <div class="max-w-3xl mx-auto px-4">
                <!-- Progress Bar -->
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700">
                            Question {{ currentIndex + 1 }} of {{ questions.length }}
                        </span>
                        <span class="text-sm text-gray-600">{{ Math.round(progress) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div
                            class="h-full bg-gradient-to-r from-indigo-600 to-purple-600 transition-all duration-500"
                            :style="{ width: progress + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Question Card -->
                <div class="bg-white rounded-2xl shadow-xl p-8 space-y-6">
                    <!-- Question Text -->
                    <div class="text-center space-y-4">
                        <h2 class="text-3xl font-bold text-gray-900">
                            {{ currentQuestion?.text }}
                        </h2>

                        <!-- Question Audio (if available) -->
                        <div v-if="currentQuestion?.audio_path" class="max-w-md mx-auto">
                            <AudioPlayer
                                :src="`/storage/${currentQuestion.audio_path}`"
                                :autoplay="true"
                            />
                        </div>
                    </div>

                    <!-- Answer Mode Toggle -->
                    <div class="flex justify-center space-x-4">
                        <button
                            @click="answerMode = 'voice'"
                            :class="[
                                'px-6 py-3 rounded-lg font-medium transition-all',
                                answerMode === 'voice'
                                    ? 'bg-indigo-600 text-white shadow-lg'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            🎤 Voice Answer
                        </button>
                        <button
                            @click="answerMode = 'text'"
                            :class="[
                                'px-6 py-3 rounded-lg font-medium transition-all',
                                answerMode === 'text'
                                    ? 'bg-indigo-600 text-white shadow-lg'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            ✍️ Text Answer
                        </button>
                    </div>

                    <!-- Voice Recording Mode -->
                    <div v-if="answerMode === 'voice'" class="py-8">
                        <MicRecorder
                            :max-duration="120"
                            @saved="handleRecordingSaved"
                        />
                    </div>

                    <!-- Text Input Mode -->
                    <div v-else class="space-y-4">
                        <textarea
                            v-model="form.text"
                            rows="6"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Type your answer here..."
                        ></textarea>

                        <button
                            @click="submitTextAnswer"
                            :disabled="!form.text || form.processing"
                            class="w-full py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-lg hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                        >
                            {{ form.processing ? 'Saving...' : (isLastQuestion ? 'Complete & Review' : 'Next Question →') }}
                        </button>
                    </div>

                    <!-- Previous Answer Display -->
                    <div v-if="getUserAnswer(currentQuestion?.id)" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm font-medium text-green-800 mb-1">✓ Previously answered:</p>
                        <p class="text-sm text-green-700">{{ getUserAnswer(currentQuestion?.id).text }}</p>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between mt-6">
                    <button
                        @click="previousQuestion"
                        :disabled="currentIndex === 0"
                        class="px-6 py-3 bg-white text-gray-700 font-medium rounded-lg shadow-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        ← Previous
                    </button>

                    <button
                        @click="nextQuestion"
                        :disabled="currentIndex === questions.length - 1"
                        class="px-6 py-3 bg-white text-gray-700 font-medium rounded-lg shadow-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        Skip →
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
