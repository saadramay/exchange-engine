<script setup lang="ts">
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';

type Symbol = 'BTC' | 'ETH';
type FilterSide = 'ALL' | 'BUY' | 'SELL';
type FilterStatus = 'ALL' | 'OPEN' | 'MATCHED' | 'CANCELLED';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const page = usePage();
const userId = computed(() => (page.props as any)?.auth?.user?.id as number | undefined);

const symbol = ref<Symbol>('BTC');
const loading = ref(false);
const error = ref<string | null>(null);

// profile state
const usdBalance = ref<string>('0');
const assets = ref<Record<string, { amount: string; locked_amount: string }>>({});

// orderbook + my orders
const orderbook = ref<any[]>([]);
const myOrders = ref<any[]>([]);

const recentTrades = ref<any[]>([]);
const myTrades = ref<any[]>([]);

// bonus filters
const filterSide = ref<FilterSide>('ALL');
const filterStatus = ref<FilterStatus>('ALL');

// cancel state
const cancellingId = ref<number | null>(null);

function assetRow(sym: string) {
    return assets.value[sym] ?? { amount: '0', locked_amount: '0' };
}

function formatDate(value: string) {
    const d = new Date(value);
    return isNaN(d.getTime()) ? value : d.toLocaleString();
}

function badgeClassForStatus(status: string) {
    if (status === 'OPEN') return 'border-blue-300 text-blue-700';
    if (status === 'MATCHED') return 'border-green-300 text-green-700';
    if (status === 'CANCELLED') return 'border-zinc-300 text-zinc-700';
    return 'border-zinc-300 text-zinc-700';
}

function badgeClassForSide(side: string) {
    if (side === 'BUY') return 'border-green-300 text-green-700';
    if (side === 'SELL') return 'border-red-300 text-red-700';
    return 'border-zinc-300 text-zinc-700';
}

async function fetchProfile() {
    const res = await axios.get('/api/profile');
    usdBalance.value = res.data?.usd?.balance ?? '0';

    const map: Record<string, { amount: string; locked_amount: string }> = {};
    for (const a of (res.data?.assets ?? [])) {
        map[a.symbol] = { amount: a.amount, locked_amount: a.locked_amount };
    }
    assets.value = map;
}

async function fetchOrderbook() {
    const res = await axios.get('/api/orders', { params: { symbol: symbol.value } });
    orderbook.value = res.data?.orders ?? [];
}

async function fetchMyOrders() {
    const res = await axios.get('/api/my/orders');
    myOrders.value = res.data?.orders ?? [];
}

async function fetchRecentTrades() {
    const res = await axios.get('/api/trades', { params: { symbol: symbol.value, limit: 50 } });
    recentTrades.value = res.data?.trades ?? [];
}

async function fetchMyTrades() {
    const res = await axios.get('/api/my/trades', { params: { symbol: symbol.value, limit: 50 } });
    myTrades.value = res.data?.trades ?? [];
}

function tradeSide(t: any) {
    // compute from current user id
    const id = userId.value;
    if (!id) return '—';
    if (t.buyer_id === id) return 'BUY';
    if (t.seller_id === id) return 'SELL';
    return '—';
}


async function refreshAll() {
    error.value = null;
    loading.value = true;

    try {
        await Promise.all([fetchProfile(), fetchOrderbook(), fetchMyOrders(), fetchRecentTrades(), fetchMyTrades()]);
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? 'Failed to load overview data.';
    } finally {
        loading.value = false;
    }
}

const buyOrders = computed(() => orderbook.value.filter((o) => o.side === 'BUY'));
const sellOrders = computed(() => orderbook.value.filter((o) => o.side === 'SELL'));

const filteredMyOrders = computed(() => {
    return myOrders.value.filter((o) => {
        // optional: keep list aligned with symbol dropdown (looks cleaner)
        if (o.symbol !== symbol.value) return false;

        if (filterSide.value !== 'ALL' && o.side !== filterSide.value) return false;
        if (filterStatus.value !== 'ALL' && o.status !== filterStatus.value) return false;

        return true;
    });
});

async function cancelOrder(id: number) {
    cancellingId.value = id;
    error.value = null;

    try {
        await axios.post(`/api/orders/${id}/cancel`);
        await refreshAll();
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? 'Failed to cancel order.';
    } finally {
        cancellingId.value = null;
    }
}

function setupRealtime() {
    const id = userId.value;
    if (!id || !window.Echo) return;

    window.Echo.private(`user.${id}`).listen('.OrderMatched', async () => {
        await refreshAll();
    });
}

function teardownRealtime() {
    const id = userId.value;
    if (!id || !window.Echo) return;

    window.Echo.leave(`user.${id}`);
}

onMounted(async () => {
    await refreshAll();
    setupRealtime();
});

watch(symbol, async () => {
    await Promise.all([fetchOrderbook(), fetchRecentTrades(), fetchMyTrades()]);
});


onBeforeUnmount(() => teardownRealtime());
</script>

<template>

    <Head title="Exchange Overview" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Top bar -->
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-xl font-semibold">Orders & Wallet Overview</div>
                    <div class="text-sm text-muted-foreground">
                        Realtime overview statistics
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <select v-model="symbol" class="h-10 rounded-md border px-3">
                        <option value="BTC">BTC</option>
                        <option value="ETH">ETH</option>
                    </select>

                    <Button :disabled="loading" @click="refreshAll">
                        {{ loading ? 'Refreshing...' : 'Refresh' }}
                    </Button>

                    <a href="/exchange/order" class="h-10 inline-flex items-center rounded-md border px-3 text-sm">
                        Place Order
                    </a>
                </div>
            </div>

            <div v-if="error" class="rounded-md border border-red-300 p-3 text-sm text-red-700">
                {{ error }}
            </div>

            <!-- Wallet cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="text-sm text-muted-foreground">USD Balance</div>
                    <div class="mt-2 text-2xl font-semibold">{{ usdBalance }}</div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="text-sm text-muted-foreground">{{ symbol }} Balance</div>
                    <div class="mt-2 text-2xl font-semibold">{{ assetRow(symbol).amount }}</div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="text-sm text-muted-foreground">{{ symbol }} Locked</div>
                    <div class="mt-2 text-2xl font-semibold">{{ assetRow(symbol).locked_amount }}</div>
                </div>
            </div>

            <!-- Orderbook -->
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-sm font-semibold">BUY Orderbook (OPEN)</div>
                        <div class="text-xs text-muted-foreground">{{ buyOrders.length }} orders</div>
                    </div>

                    <div v-if="buyOrders.length === 0" class="text-sm text-muted-foreground">
                        No open BUY orders.
                    </div>

                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-muted-foreground">
                                <th class="py-2">Price</th>
                                <th class="py-2">Amount</th>
                                <th class="py-2">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="o in buyOrders" :key="o.id" class="border-t">
                                <td class="py-2 font-medium">{{ o.price }}</td>
                                <td class="py-2">{{ o.amount }}</td>
                                <td class="py-2 text-xs text-muted-foreground">{{ formatDate(o.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-sm font-semibold">SELL Orderbook (OPEN)</div>
                        <div class="text-xs text-muted-foreground">{{ sellOrders.length }} orders</div>
                    </div>

                    <div v-if="sellOrders.length === 0" class="text-sm text-muted-foreground">
                        No open SELL orders.
                    </div>

                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-muted-foreground">
                                <th class="py-2">Price</th>
                                <th class="py-2">Amount</th>
                                <th class="py-2">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="o in sellOrders" :key="o.id" class="border-t">
                                <td class="py-2 font-medium">{{ o.price }}</td>
                                <td class="py-2">{{ o.amount }}</td>
                                <td class="py-2 text-xs text-muted-foreground">{{ formatDate(o.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Trades -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Recent Trades -->
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-sm font-semibold">Recent Trades ({{ symbol }})</div>
                        <div class="text-xs text-muted-foreground">{{ recentTrades.length }} latest</div>
                    </div>

                    <div v-if="recentTrades.length === 0" class="text-sm text-muted-foreground">
                        No trades yet.
                    </div>

                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-muted-foreground">
                                <th class="py-2">Price</th>
                                <th class="py-2">Amount</th>
                                <th class="py-2">USD</th>
                                <th class="py-2">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in recentTrades" :key="t.id" class="border-t">
                                <td class="py-2 font-medium">{{ t.price }}</td>
                                <td class="py-2">{{ t.amount }}</td>
                                <td class="py-2">{{ t.matched_usd }}</td>
                                <td class="py-2 text-xs text-muted-foreground">{{ formatDate(t.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- My Trades -->
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-sm font-semibold">My Trades ({{ symbol }})</div>
                        <div class="text-xs text-muted-foreground">{{ myTrades.length }} latest</div>
                    </div>

                    <div v-if="myTrades.length === 0" class="text-sm text-muted-foreground">
                        No trades for you yet.
                    </div>

                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-muted-foreground">
                                <th class="py-2">Side</th>
                                <th class="py-2">Price</th>
                                <th class="py-2">Amount</th>
                                <th class="py-2">Fee</th>
                                <th class="py-2">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in myTrades" :key="t.id" class="border-t">
                                <td class="py-2">
                                    <span class="rounded-md border px-2 py-1 text-xs"
                                        :class="badgeClassForSide(tradeSide(t))">
                                        {{ tradeSide(t) }}
                                    </span>
                                </td>
                                <td class="py-2 font-medium">{{ t.price }}</td>
                                <td class="py-2">{{ t.amount }}</td>
                                <td class="py-2">{{ t.fee_usd }}</td>
                                <td class="py-2 text-xs text-muted-foreground">{{ formatDate(t.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-2 text-xs text-muted-foreground">
                        Fee is paid by buyer (USD).
                    </div>
                </div>
            </div>

            <!-- My Orders -->
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <div class="text-sm font-semibold">My Orders</div>
                        <div class="text-xs text-muted-foreground">
                            Showing {{ filteredMyOrders.length }} orders for {{ symbol }} (filters applied)
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <select v-model="filterSide" class="h-9 rounded-md border px-2 text-sm">
                            <option value="ALL">All Sides</option>
                            <option value="BUY">Buy</option>
                            <option value="SELL">Sell</option>
                        </select>

                        <select v-model="filterStatus" class="h-9 rounded-md border px-2 text-sm">
                            <option value="ALL">All Status</option>
                            <option value="OPEN">Open</option>
                            <option value="MATCHED">Matched</option>
                            <option value="CANCELLED">Cancelled</option>
                        </select>

                        <Button variant="outline" size="sm" :disabled="loading" @click="
                            filterSide = 'ALL';
                        filterStatus = 'ALL';
                        ">
                            Reset filters
                        </Button>
                    </div>
                </div>

                <div v-if="filteredMyOrders.length === 0" class="text-sm text-muted-foreground">
                    No orders found for selected filters.
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted-foreground">
                            <th class="py-2">ID</th>
                            <th class="py-2">Symbol</th>
                            <th class="py-2">Side</th>
                            <th class="py-2">Price</th>
                            <th class="py-2">Amount</th>
                            <th class="py-2">Status</th>
                            <th class="py-2">Created</th>
                            <th class="py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="o in filteredMyOrders" :key="o.id" class="border-t">
                            <td class="py-2">{{ o.id }}</td>
                            <td class="py-2">{{ o.symbol }}</td>

                            <td class="py-2">
                                <span class="rounded-md border px-2 py-1 text-xs" :class="badgeClassForSide(o.side)">
                                    {{ o.side }}
                                </span>
                            </td>

                            <td class="py-2">{{ o.price }}</td>
                            <td class="py-2">{{ o.amount }}</td>

                            <td class="py-2">
                                <span class="rounded-md border px-2 py-1 text-xs"
                                    :class="badgeClassForStatus(o.status)">
                                    {{ o.status }}
                                </span>
                            </td>

                            <td class="py-2 text-xs text-muted-foreground">{{ formatDate(o.created_at) }}</td>

                            <td class="py-2">
                                <Button v-if="o.status === 'OPEN'" variant="outline" size="sm"
                                    :disabled="cancellingId === o.id" @click="cancelOrder(o.id)">
                                    {{ cancellingId === o.id ? 'Cancelling...' : 'Cancel' }}
                                </Button>

                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
