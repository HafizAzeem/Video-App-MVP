<template>
    <div class="progress-bar">
        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
            <div
                class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 transition-all duration-300 flex items-center justify-center text-xs font-medium text-white"
                :style="{ width: progress + '%' }"
            >
                <span v-if="showPercentage && progress > 10">{{ Math.round(progress) }}%</span>
            </div>
        </div>

        <p v-if="status" class="mt-2 text-sm text-center" :class="statusClass">
            {{ status }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    progress: {
        type: Number,
        required: true,
        validator: (value) => value >= 0 && value <= 100,
    },
    status: {
        type: String,
        default: '',
    },
    statusType: {
        type: String,
        default: 'info', // info, success, error, warning
        validator: (value) => ['info', 'success', 'error', 'warning'].includes(value),
    },
    showPercentage: {
        type: Boolean,
        default: true,
    },
});

const statusClass = computed(() => {
    const classes = {
        info: 'text-gray-600',
        success: 'text-green-600',
        error: 'text-red-600',
        warning: 'text-yellow-600',
    };

    return classes[props.statusType] || classes.info;
});
</script>
