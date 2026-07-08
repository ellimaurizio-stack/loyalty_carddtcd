<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import moment from 'moment';

defineProps({
    customers: Object,
});

const formatDate = (dateString) => {
    return moment(dateString).format('DD MMM YYYY, HH:mm');
};

const obscureCard = (cardId) => {
    if (!cardId) return '-';
    return '•••• •••• •••• ' + cardId.slice(-4);
};
</script>

<template>
    <Head title="Customers" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Customers List</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Card ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchases</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined At</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="customer in customers.data" :key="customer.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">
                                        {{ obscureCard(customer.card_identifier) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ customer.name }} {{ customer.surname }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ customer.phone }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ customer.purchases_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(customer.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('customers.show', customer.id)" class="text-indigo-600 hover:text-indigo-900">View</Link>
                                    </td>
                                </tr>
                                <tr v-if="customers.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No customers found.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div class="mt-4 flex justify-between items-center" v-if="customers.links">
                            <div class="flex flex-wrap gap-1">
                                <template v-for="(link, key) in customers.links" :key="key">
                                    <Link v-if="link.url" :href="link.url" v-html="link.label" 
                                        class="px-4 py-2 border rounded text-sm"
                                        :class="link.active ? 'bg-indigo-50 text-indigo-600 border-indigo-200' : 'hover:bg-gray-50'" />
                                    <span v-else v-html="link.label" class="px-4 py-2 border rounded text-sm text-gray-400 bg-gray-50"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
