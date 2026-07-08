<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import moment from 'moment';

defineProps({
    customer: Object,
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
    <Head :title="'Customer: ' + obscureCard(customer.card_identifier)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Customer Details</h2>
                <Link :href="route('customers.index')" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to List</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Customer Info Card -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Card Identifier</p>
                            <p class="font-medium font-mono">{{ obscureCard(customer.card_identifier) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Phone Number</p>
                            <p class="font-medium">{{ customer.phone || 'Not provided' }}</p>
                        </div>
                        
                        <!-- Dynamic fields -->
                        <div v-if="customer.customer_data" v-for="(value, key) in customer.customer_data" :key="key">
                            <p class="text-sm text-gray-500 capitalize">{{ key.replace('_', ' ') }}</p>
                            <p class="font-medium">{{ value || 'Not provided' }}</p>
                        </div>
                        
                        <!-- Fallbacks if still using old data structure -->
                        <div v-if="!customer.customer_data && customer.name">
                            <p class="text-sm text-gray-500">Name</p>
                            <p class="font-medium">{{ customer.name }}</p>
                        </div>
                        <div v-if="!customer.customer_data && customer.surname">
                            <p class="text-sm text-gray-500">Surname</p>
                            <p class="font-medium">{{ customer.surname }}</p>
                        </div>
                        <div v-if="!customer.customer_data && customer.email">
                            <p class="text-sm text-gray-500">Email</p>
                            <p class="font-medium">{{ customer.email }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Loyalty Points</p>
                            <p class="font-medium">{{ customer.loyalty_points }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Cashback Balance</p>
                            <p class="font-medium">€{{ Number(customer.cashback_balance || 0).toFixed(2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Joined Date</p>
                            <p class="font-medium">{{ formatDate(customer.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Purchases List -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Purchase History</h3>
                    <div v-if="customer.purchases.length > 0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data e Ora</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prodotti</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Totale</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="purchase in customer.purchases" :key="purchase.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(purchase.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <ul v-if="purchase.products && purchase.products.length > 0" class="list-disc list-inside">
                                            <li v-for="(prod, i) in purchase.products" :key="i">
                                                {{ prod.name }} ({{ prod.ean_code }})
                                            </li>
                                        </ul>
                                        <span v-else class="text-gray-400">Nessun dettaglio</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        €{{ Number(purchase.amount).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-gray-500 text-center py-4">
                        No purchases found for this customer.
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
