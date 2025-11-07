<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    videoPrompt: {
        type: String,
        default: null,
    },
});

const form = useForm({
    confirmed: false,
});

const hasPrompt = computed(() => props.videoPrompt !== null);

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
                    <div v-if="hasPrompt" class="w-full">
                        <p class="text-gray-800 text-lg leading-relaxed whitespace-pre-line">
                            {{ videoPrompt }}
                        </p>
                    </div>
                    <div v-else class="text-center text-gray-500">
                        <p class="text-lg">[Generated Video Prompt]</p>
                        <p class="text-sm mt-2">Loading your video concept...</p>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-center space-x-6">
                    <button
                        @click="goBack"
                        class="px-12 py-4 bg-blue-600 hover:bg-blue-700 text-white text-xl font-semibold rounded-full shadow-xl hover:shadow-2xl transition-all"
                    >
                        Back
                    </button>
                    <button
                        @click="confirmAndProceed"
                        :disabled="!hasPrompt || form.processing"
                        class="px-12 py-4 bg-blue-600 hover:bg-blue-700 text-white text-xl font-semibold rounded-full shadow-xl hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        {{ form.processing ? 'Processing...' : 'Next' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any custom styles here */
</style>
