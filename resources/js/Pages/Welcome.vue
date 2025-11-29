<script setup>
import { Head, Link } from '@inertiajs/vue3';

const features = [
  {
    title: 'How It Works',
    description:
      'Enter 4 questions about any part of your book. Our AI will generate a stunning video visualization based on your answers.',
    image: '/images/logo.png',
  },
  {
    title: 'AI Video Generation',
    description:
      'Harness the power of AI to turn your book ideas into engaging videos. Perfect for authors, educators, and creators.',
    image: '/images/logo.png',
  },
  {
    title: 'Visualization Magic',
    description:
      'Transform your written ideas into captivating visual stories. Lasbook’s visualization engine brings your book’s concepts to life with dynamic, AI-powered animations and scenes.',
    image: '/images/logo.png',
  },
];
</script>

<template>
  <Head title="Welcome to Lasbook" />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 flex flex-col items-center justify-center">
    <div class="w-full max-w-4xl mx-auto px-4 py-12">
      <div class="text-center mb-10">
        <img src="/images/logo.png" alt="Lasbook Logo" class="mx-auto w-32 h-32 mb-4 drop-shadow-lg" />
        <h1 class="text-4xl font-extrabold text-gray-900 mb-2 tracking-tight">Welcome to Lasbook</h1>
        <p class="text-lg text-gray-700 mb-4">AI-powered video generation from your book ideas</p>
        <div class="flex justify-center gap-4 mt-4">
          <Link :href="route('questions.index')" class="px-8 py-3 bg-indigo-600 text-white rounded-full font-semibold shadow-lg hover:bg-indigo-700 transition">Get Started</Link>
          <Link :href="route('login')" class="px-8 py-3 bg-white text-indigo-600 border border-indigo-600 rounded-full font-semibold shadow-lg hover:bg-indigo-50 transition">Login</Link>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        <div v-for="feature in features" :key="feature.title" class="bg-white rounded-2xl shadow-xl p-8 flex flex-col items-center">
          <img :src="feature.image" :alt="feature.title" class="w-24 h-24 mb-4" />
          <h2 class="text-xl font-bold text-indigo-700 mb-2">{{ feature.title }}</h2>
          <p class="text-gray-600 text-center">{{ feature.description }}</p>
        </div>
      </div>
      <div class="text-center mt-8">
        <h3 class="text-2xl font-semibold text-gray-900 mb-2">Why Lasbook?</h3>
        <p class="text-gray-700 mb-4">Lasbook makes it easy to visualize your stories, lessons, or ideas. Just answer a few questions and let our AI do the rest!</p>
        <img src="/images/logo.png" alt="Lasbook Showcase" class="mx-auto w-full max-w-md rounded-xl shadow-lg" />
      </div>
      <footer class="mt-12 text-center text-sm text-gray-500">
        &copy; {{ new Date().getFullYear() }} Lasbook. All rights reserved.
      </footer>
    </div>
  </div>
</template>

<style scoped>
body {
  font-family: 'Figtree', sans-serif;
}
</style>
