<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pwaSettings: Object,
    errors: Object,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('pwa.register.post'));
};

const bg = computed(() => props.pwaSettings?.background_color || '#f3f4f6');
const primary = computed(() => props.pwaSettings?.primary_color || '#4f46e5');
const text = computed(() => props.pwaSettings?.text_color || '#111827');
const logo = computed(() => props.pwaSettings?.logo_path ? `/storage/${props.pwaSettings.logo_path}` : null);
const name = computed(() => props.pwaSettings?.app_name || 'Loyalty App');
</script>

<template>
    <Head title="Registrati" />
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8" :style="{ backgroundColor: bg, color: text }">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <img v-if="logo" :src="logo" alt="Logo" class="mx-auto h-24 w-auto rounded-full object-cover shadow-lg mb-4" />
            <h2 class="mt-6 text-3xl font-extrabold">{{ name }}</h2>
            <p class="mt-2 text-sm opacity-70">
                Crea il tuo profilo fedeltà o collega la tua carta esistente
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
            <div class="bg-white/80 backdrop-blur py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-gray-100">
                <form class="space-y-6" @submit.prevent="submit">
                    
                    <div>
                        <label for="name" class="block text-sm font-medium" :style="{ color: text }"> Nome e Cognome </label>
                        <div class="mt-1">
                            <input v-model="form.name" id="name" type="text" required class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black" placeholder="Mario Rossi">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium" :style="{ color: text }"> Indirizzo Email </label>
                        <div class="mt-1">
                            <input v-model="form.email" id="email" type="email" required class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black" placeholder="mario.rossi@email.com">
                        </div>
                        <p class="text-xs mt-1 opacity-70">Usa la stessa email fornita in negozio se hai già una carta.</p>
                        <p v-if="errors.email" class="mt-2 text-sm text-red-600">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium" :style="{ color: text }"> Password </label>
                        <div class="mt-1">
                            <input v-model="form.password" id="password" type="password" required class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black">
                        </div>
                        <p v-if="errors.password" class="mt-2 text-sm text-red-600">{{ errors.password }}</p>
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium" :style="{ color: text }"> Conferma Password </label>
                        <div class="mt-1">
                            <input v-model="form.password_confirmation" id="password_confirmation" type="password" required class="appearance-none block w-full px-3 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-black">
                        </div>
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-transform active:scale-95" :style="{ backgroundColor: primary }">
                            Registrati
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm opacity-80" :style="{ color: text }">
                        Hai già un account?
                        <Link :href="route('pwa.login')" class="font-bold hover:underline" :style="{ color: primary }">
                            Accedi
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template
