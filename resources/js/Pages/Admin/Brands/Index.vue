<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

defineProps({
    brands: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    name: '',
    slug: '',
});

const submit = () => {
    form.post(route('brands.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Brands" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Brands (Insegne)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Create Brand Form -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Create New Brand</h2>
                        <p class="mt-1 text-sm text-gray-600">Add a new brand to the system.</p>
                    </header>

                    <form @submit.prevent="submit" class="mt-6 space-y-6 max-w-xl">
                        <div>
                            <InputLabel for="name" value="Brand Name" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="slug" value="Slug (URL friendly)" />
                            <TextInput
                                id="slug"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.slug"
                                required
                            />
                        </div>
                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Save Brand</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- List Brands -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <header class="mb-4">
                        <h2 class="text-lg font-medium text-gray-900">All Brands</h2>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3">ID</th>
                                    <th scope="col" class="px-6 py-3">Name</th>
                                    <th scope="col" class="px-6 py-3">Slug</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="brand in brands" :key="brand.id" class="border-b bg-white">
                                    <td class="px-6 py-4">{{ brand.id }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ brand.name }}</td>
                                    <td class="px-6 py-4">{{ brand.slug }}</td>
                                </tr>
                                <tr v-if="brands.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center">No brands found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
