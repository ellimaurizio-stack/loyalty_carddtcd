<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pwaSettings: Object,
    errors: Object,
});

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('pwa.login.post'));
};

const bg = computed(() => props.pwaSettings?.background_color || '#f3f4f6');
const bgImage = computed(() => props.pwaSettings?.background_image_path ? `url(/storage/${props.pwaSettings.background_image_path})` : 'none');
const primary = computed(() => props.pwaSettings?.primary_color || '#4f46e5');
const text = computed(() => props.pwaSettings?.text_color || '#111827');
const logo = computed(() => props.pwaSettings?.logo_path ? `/storage/${props.pwaSettings.logo_path}` : null);
const name = computed(() => props.pwaSettings?.app_name || 'Loyalty App');
</script>

<template>
    <Head title="Accedi" />
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-cover bg-center bg-no-repeat" :style="{ backgroundColor: bg, backgroundImage: bgImage, color: text }">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <img v-if="logo" :src="logo" alt="Logo" class="mx-auto h-24 w-auto rounded-full object-cover shadow-lg mb-4" />
            <div v-else class="mx-auto h-24 w-24 rounded-full bg-gray-200 shadow-lg mb-4 flex items-center justify-center">
                <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold">{{ name }}</h2>
            <p class="mt-2 text-sm opacity-70">
                Accedi per vedere i tuoi punti e i premi
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
            <div class="bg-white/80 backdrop-blur py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-gray-100">
                <form class="space-y-6" @submit.prevent="submit">
                    <div>
                        <label for="email" class="block text-sm font-medium" :style="{ color: text }"> Indirizzo Email </label>
                        <div class="mt-1">
                            <input v-model="form.email" id="email" name="email" type="email" required class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black" placeholder="mario.rossi@email.com">
                        </div>
                        <p v-if="errors.email" class="mt-2 text-sm text-red-600">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium" :style="{ color: text }"> Password </label>
                        <div class="mt-1">
                            <input v-model="form.password" id="password" name="password" type="password" required class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black">
                        </div>
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-transform active:scale-95" :style="{ backgroundColor: primary }">
                            Accedi
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm opacity-80" :style="{ color: text }">
                        Non hai un account?
                        <Link :href="route('pwa.register')" class="font-bold hover:underline" :style="{ color: primary }">
                            Registrati
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
