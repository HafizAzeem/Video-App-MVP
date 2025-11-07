<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Home - Learn And Share" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50">
            <div class="max-w-5xl mx-auto px-4 py-16">
                <!-- Hero Section -->
                <div class="text-center space-y-8">
                    <!-- Adventure Team Image -->
                    <div class="py-8">
                        <img 
                            src="/images/first-image.png" 
                            alt="LAS Adventure Team" 
                            class="w-96 h-96 mx-auto object-contain drop-shadow-2xl animate-float"
                        />
                    </div>

                    <!-- Welcome Message -->
                    <div class="animate-fade-in">
                        <h1 class="text-5xl font-bold text-gray-900 mb-4">
                            Welcome LAS Adventure Team!!
                        </h1>
                    </div>

                    <!-- Start Button -->
                    <div class="pt-8">
                        <Link
                            :href="route('questions.index')"
                            class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-2xl font-semibold px-16 py-5 rounded-full shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105"
                        >
                            Let's Start!
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.8s ease-out;
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}
</style>
