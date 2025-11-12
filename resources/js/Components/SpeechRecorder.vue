<template>
    <div class="speech-recorder">
        <div class="text-center">
            <!-- Recording Button -->
            <button
                @click="toggleRecording"
                :class="[
                    'relative inline-flex items-center justify-center w-20 h-20 rounded-full transition-all',
                    isRecording
                        ? 'bg-red-600 hover:bg-red-700 animate-pulse'
                        : 'bg-indigo-600 hover:bg-indigo-700',
                    isProcessing ? 'opacity-50 cursor-not-allowed' : ''
                ]"
                :disabled="isProcessing || !isSupported"
            >
                <svg v-if="!isRecording" class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7 4a3 3 0 016 0v6a3 3 0 11-6 0V4z" />
                    <path d="M5.5 9.643a.75.75 0 00-1.5 0V10c0 3.06 2.29 5.585 5.25 5.954V17.5h-1.5a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-1.5v-1.546A6.001 6.001 0 0016 10v-.357a.75.75 0 00-1.5 0V10a4.5 4.5 0 01-9 0v-.357z" />
                </svg>
                <svg v-else class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5.25 3A2.25 2.25 0 003 5.25v9.5A2.25 2.25 0 005.25 17h9.5A2.25 2.25 0 0017 14.75v-9.5A2.25 2.25 0 0014.75 3h-9.5z" />
                </svg>
            </button>

            <!-- Status Text -->
            <p class="mt-4 text-sm text-gray-600">
                <span v-if="!isSupported" class="text-red-600 font-medium">
                    Speech recognition not supported in this browser
                </span>
                <span v-else-if="isRecording" class="text-red-600 font-medium">
                    🎤 Listening... Speak now
                </span>
                <span v-else-if="isProcessing">
                    Processing...
                </span>
                <span v-else>
                    Click to start recording
                </span>
            </p>

            <!-- Live Transcript Preview -->
            <div v-if="interimTranscript" class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-gray-600">
                    <span class="text-blue-600 italic">{{ interimTranscript }}</span>
                </p>
            </div>

            <!-- Recording Time -->
            <div v-if="isRecording" class="mt-2 text-lg font-medium text-red-600">
                {{ formatTime(recordingTime) }}
            </div>

            <!-- Clear Text Button (only when there's text and not recording) -->
            <div v-if="hasRecordedText && !isRecording" class="mt-4">
                <button
                    @click="clearText"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                    Clear Text
                </button>
            </div>

            <!-- Browser Support Info -->
            <div v-if="!isSupported" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <strong>Note:</strong> Speech recognition requires Chrome, Edge, or Safari.
                    Please use a supported browser or type your answer manually.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    maxDuration: {
        type: Number,
        default: 120, // 2 minutes
    },
    language: {
        type: String,
        default: 'en-US',
    },
    continuous: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['transcribed']);

// State
const recognition = ref(null);
const isRecording = ref(false);
const isProcessing = ref(false);
const isSupported = ref(false);
const interimTranscript = ref('');
const recordingTime = ref(0);
const recordingInterval = ref(null);
const hasRecordedText = ref(false);

// Check browser support
onMounted(() => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    
    if (SpeechRecognition) {
        isSupported.value = true;
        initRecognition(SpeechRecognition);
    } else {
        console.warn('Speech recognition not supported in this browser');
    }
});

const initRecognition = (SpeechRecognition) => {
    recognition.value = new SpeechRecognition();
    
    // Configuration
    recognition.value.continuous = props.continuous;
    recognition.value.interimResults = true;
    recognition.value.lang = props.language;
    recognition.value.maxAlternatives = 1;

    // Event handlers
    recognition.value.onstart = () => {
        console.log('Speech recognition started');
        isRecording.value = true;
        recordingTime.value = 0;
        
        // Start timer
        recordingInterval.value = setInterval(() => {
            recordingTime.value++;
            
            // Auto-stop after max duration
            if (recordingTime.value >= props.maxDuration) {
                stopRecording();
            }
        }, 1000);
    };

    recognition.value.onresult = (event) => {
        let interim = '';
        let currentFinal = '';

        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = event.results[i][0].transcript;
            
            if (event.results[i].isFinal) {
                currentFinal += transcript + ' ';
            } else {
                interim += transcript;
            }
        }

        // Emit final results immediately to append to textarea
        if (currentFinal) {
            hasRecordedText.value = true;
            emit('transcribed', currentFinal.trim(), true); // true means append
        }
        
        // Show interim results in preview only
        interimTranscript.value = interim;
    };

    recognition.value.onerror = (event) => {
        console.error('Speech recognition error:', event.error);
        
        let errorMessage = 'Speech recognition error: ';
        
        switch (event.error) {
            case 'no-speech':
                errorMessage += 'No speech detected. Please try again.';
                break;
            case 'audio-capture':
                errorMessage += 'No microphone found. Please check your device.';
                break;
            case 'not-allowed':
                errorMessage += 'Microphone permission denied. Please enable it in settings.';
                break;
            case 'network':
                errorMessage += 'Network error. Please check your connection.';
                break;
            default:
                errorMessage += event.error;
        }
        
        alert(errorMessage);
        stopRecording();
    };

    recognition.value.onend = () => {
        console.log('Speech recognition ended');
        isRecording.value = false;
        
        if (recordingInterval.value) {
            clearInterval(recordingInterval.value);
        }
        
        // Clear interim text when recording stops
        interimTranscript.value = '';
    };
};

const toggleRecording = () => {
    if (!isSupported.value) {
        alert('Speech recognition is not supported in your browser. Please use Chrome, Edge, or Safari.');
        return;
    }

    if (isRecording.value) {
        stopRecording();
    } else {
        startRecording();
    }
};

const startRecording = () => {
    if (!recognition.value) return;

    try {
        // Don't reset - we want to append text
        interimTranscript.value = '';
        
        recognition.value.start();
    } catch (error) {
        console.error('Error starting recognition:', error);
        alert('Failed to start recording. Please try again.');
    }
};

const stopRecording = () => {
    if (recognition.value && isRecording.value) {
        recognition.value.stop();
    }
};

const clearText = () => {
    hasRecordedText.value = false;
    interimTranscript.value = '';
    recordingTime.value = 0;
    // Emit empty string to clear the textarea
    emit('transcribed', '', false);
};

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

// Cleanup
onUnmounted(() => {
    if (recordingInterval.value) {
        clearInterval(recordingInterval.value);
    }
    if (isRecording.value && recognition.value) {
        recognition.value.stop();
    }
});
</script>

<style scoped>
.animate-bounce-slow {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(-5%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
    50% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
}
</style>
