<template>
    <div class="mic-recorder">
        <div class="text-center">
            <button
                @click="toggleRecording"
                :class="[
                    'relative inline-flex items-center justify-center w-20 h-20 rounded-full transition-all',
                    isRecording
                        ? 'bg-red-600 hover:bg-red-700 animate-pulse'
                        : 'bg-indigo-600 hover:bg-indigo-700'
                ]"
                :disabled="isProcessing"
            >
                <svg v-if="!isRecording" class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7 4a3 3 0 016 0v6a3 3 0 11-6 0V4z" />
                    <path d="M5.5 9.643a.75.75 0 00-1.5 0V10c0 3.06 2.29 5.585 5.25 5.954V17.5h-1.5a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-1.5v-1.546A6.001 6.001 0 0016 10v-.357a.75.75 0 00-1.5 0V10a4.5 4.5 0 01-9 0v-.357z" />
                </svg>
                <svg v-else class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5.25 3A2.25 2.25 0 003 5.25v9.5A2.25 2.25 0 005.25 17h9.5A2.25 2.25 0 0017 14.75v-9.5A2.25 2.25 0 0014.75 3h-9.5z" />
                </svg>
            </button>

            <p class="mt-4 text-sm text-gray-600">
                {{ isRecording ? 'Recording...' : 'Click to start recording' }}
            </p>

            <div v-if="isRecording" class="mt-2 text-lg font-medium text-red-600">
                {{ formatTime(recordingTime) }}
            </div>

            <div v-if="audioBlob && !isRecording" class="mt-6 space-y-4">
                <AudioPlayer :src="audioUrl" :show-download="false" />

                <div class="flex justify-center space-x-3">
                    <button
                        @click="retryRecording"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Re-record
                    </button>
                    <button
                        @click="saveRecording"
                        :disabled="isProcessing"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ isProcessing ? 'Saving...' : 'Save Recording' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import AudioPlayer from './AudioPlayer.vue';

const props = defineProps({
    maxDuration: {
        type: Number,
        default: 120, // 2 minutes
    },
});

const emit = defineEmits(['recorded', 'saved']);

const mediaRecorder = ref(null);
const audioChunks = ref([]);
const audioBlob = ref(null);
const isRecording = ref(false);
const isProcessing = ref(false);
const recordingTime = ref(0);
const recordingInterval = ref(null);

const audioUrl = computed(() => {
    return audioBlob.value ? URL.createObjectURL(audioBlob.value) : null;
});

const toggleRecording = async () => {
    if (isRecording.value) {
        stopRecording();
    } else {
        await startRecording();
    }
};

const startRecording = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

        mediaRecorder.value = new MediaRecorder(stream);
        audioChunks.value = [];

        mediaRecorder.value.ondataavailable = (event) => {
            audioChunks.value.push(event.data);
        };

        mediaRecorder.value.onstop = () => {
            audioBlob.value = new Blob(audioChunks.value, { type: 'audio/webm' });
            emit('recorded', audioBlob.value);

            // Stop all tracks
            stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.value.start();
        isRecording.value = true;
        recordingTime.value = 0;

        // Start timer
        recordingInterval.value = setInterval(() => {
            recordingTime.value++;

            if (recordingTime.value >= props.maxDuration) {
                stopRecording();
            }
        }, 1000);

    } catch (error) {
        console.error('Error accessing microphone:', error);
        alert('Unable to access microphone. Please check permissions.');
    }
};

const stopRecording = () => {
    if (mediaRecorder.value && isRecording.value) {
        mediaRecorder.value.stop();
        isRecording.value = false;

        if (recordingInterval.value) {
            clearInterval(recordingInterval.value);
        }
    }
};

const retryRecording = () => {
    audioBlob.value = null;
    audioChunks.value = [];
    recordingTime.value = 0;
};

const saveRecording = () => {
    isProcessing.value = true;
    emit('saved', audioBlob.value);

    // Reset after emit (parent component handles the actual save)
    setTimeout(() => {
        isProcessing.value = false;
    }, 500);
};

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

onUnmounted(() => {
    if (recordingInterval.value) {
        clearInterval(recordingInterval.value);
    }
    if (isRecording.value) {
        stopRecording();
    }
});
</script>
