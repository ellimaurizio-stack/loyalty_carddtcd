<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BrandSelector from '@/Components/BrandSelector.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    program: Object,
    customers: Array,
    brands: Array,
    currentBrandId: [Number, String],
});

const form = useForm({
    purchases_threshold: props.program.purchases_threshold,
    is_active: props.program.is_active,
});

const updateProgram = () => {
    form.put(route('program.update', props.program.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Loyalty Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <BrandSelector 
                    :brands="brands" 
                    :currentBrandId="currentBrandId" 
                />

                <!-- Program Settings -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <section class="max-w-xl">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Program Settings: {{ program.name }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Configure the threshold of repeated purchases before prompting the user.
                            </p>
                        </header>

                        <form @submit.prevent="updateProgram" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="purchases_threshold" value="Purchases Threshold" />
                                <TextInput
                                    id="purchases_threshold"
                                    type="number"
                                    class="mt-1 block w-full"
                                    v-model="form.purchases_threshold"
                                    required
                                    min="1"
                                />
                            </div>

                            <div class="flex items-center gap-4">
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Is Active</span>
                                </label>
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Save Settings</PrimaryButton>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Saved.</p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- Customers List -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Enrolled Customers
                            </h2>
                        </header>

                        <div class="mt-6">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Phone</th>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Purchases</th>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Points</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="customer in customers" :key="customer.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ customer.name || 'Anonymous' }} {{ customer.surname }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ customer.phone || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ customer.purchases_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ customer.loyalty_points }}
                                        </td>
                                    </tr>
                                    <tr v-if="customers.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No customers found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
