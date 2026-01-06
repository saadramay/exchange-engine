<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Symbol = 'BTC' | 'ETH';
type Side = 'BUY' | 'SELL';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Place Order', href: '/exchange/order' },
];

const symbol = ref<Symbol>('BTC');
const side = ref<Side>('BUY');
const price = ref<string>('');
const amount = ref<string>('');

const isSubmitting = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);

// balances (from /api/profile)
const usdBalance = ref<string>('0');
const assets = ref<Record<string, { amount: string; locked_amount: string }>>({});

function assetRow(sym: string) {
  return assets.value[sym] ?? { amount: '0', locked_amount: '0' };
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

function toNumber(v: string) {
  const n = Number(v);
  return Number.isFinite(n) ? n : NaN;
}

function round8(n: number) {
  return Math.round(n * 1e8) / 1e8;
}

const priceNum = computed(() => toNumber(price.value));
const amountNum = computed(() => toNumber(amount.value));

const isValidNumbers = computed(() => {
  if (!Number.isFinite(priceNum.value) || !Number.isFinite(amountNum.value)) return false;
  if (priceNum.value <= 0 || amountNum.value <= 0) return false;
  return true;
});

const subtotalUsd = computed(() => {
  if (!isValidNumbers.value) return 0;
  return round8(priceNum.value * amountNum.value);
});

const feeUsd = computed(() => {
  // buyer pays 1.5% in USD
  if (!isValidNumbers.value) return 0;
  return round8(subtotalUsd.value * 0.015);
});

const totalUsd = computed(() => {
  if (!isValidNumbers.value) return 0;
  return round8(subtotalUsd.value + feeUsd.value);
});

const usdBalanceNum = computed(() => toNumber(usdBalance.value));
const assetBalanceNum = computed(() => toNumber(assetRow(symbol.value).amount));
const assetLockedNum = computed(() => toNumber(assetRow(symbol.value).locked_amount));

const canAffordBuy = computed(() => {
  if (side.value !== 'BUY') return true;
  if (!Number.isFinite(usdBalanceNum.value)) return true; // if not loaded, don't block UI
  return usdBalanceNum.value >= totalUsd.value;
});

const canAffordSell = computed(() => {
  if (side.value !== 'SELL') return true;
  if (!Number.isFinite(assetBalanceNum.value)) return true;
  if (!Number.isFinite(amountNum.value)) return true;
  return assetBalanceNum.value >= amountNum.value;
});

const primaryHint = computed(() => {
  if (!isValidNumbers.value) return null;

  if (side.value === 'BUY') {
    if (!canAffordBuy.value) return `Insufficient USD. Need ${totalUsd.value} USD (incl. fee).`;
    return `Total lock ≈ ${totalUsd.value} USD (incl. 1.5% fee).`;
  }

  // SELL
  if (!canAffordSell.value) return `Insufficient ${symbol.value}. Need ${amountNum.value}.`;
  return `You will receive ≈ ${subtotalUsd.value} USD (fee paid by buyer).`;
});

async function placeOrder() {
  error.value = null;
  success.value = null;

  if (!isValidNumbers.value) {
    error.value = 'Price and amount must be valid numbers greater than 0.';
    return;
  }

  if (side.value === 'BUY' && !canAffordBuy.value) {
    error.value = `Insufficient USD balance. Need about ${totalUsd.value} USD including fee.`;
    return;
  }

  if (side.value === 'SELL' && !canAffordSell.value) {
    error.value = `Insufficient ${symbol.value} balance. Need at least ${amountNum.value}.`;
    return;
  }

  isSubmitting.value = true;

  try {
    const res = await axios.post('/api/orders', {
      symbol: symbol.value,
      side: side.value,
      price: price.value,
      amount: amount.value,
    });

    success.value = res.data?.matched ? 'Order placed and matched!' : 'Order placed successfully.';

    // reset fields only
    price.value = '';
    amount.value = '';

    // refresh balances after placing order (locks might change)
    await fetchProfile();
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to place order.';
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(async () => {
  await fetchProfile();
});

// when symbol changes, balances panel updates automatically via computed
watch(symbol, async () => {
  // optional: refresh to ensure asset exists / latest
  await fetchProfile();
});
</script>

<template>
  <Head title="Place Order" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <div class="text-xl font-semibold">Limit Order Form</div>
          <div class="text-sm text-muted-foreground">
            Buyer pays 1.5% fee in USD. Full-match only.
          </div>
        </div>

        <a href="/dashboard" class="h-10 inline-flex items-center rounded-md border px-3 text-sm">
          Back to Overview
        </a>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <!-- Form -->
        <div class="rounded-xl border border-sidebar-border/70 p-4 lg:col-span-2 dark:border-sidebar-border">
          <div class="mb-3 flex items-center justify-between">
            <div class="text-sm font-semibold">Order Details</div>

            <span v-if="primaryHint" class="text-xs text-muted-foreground">
              {{ primaryHint }}
            </span>
          </div>

          <div v-if="error" class="mb-3 rounded-md border border-red-300 p-3 text-sm text-red-700">
            {{ error }}
          </div>

          <div v-if="success" class="mb-3 rounded-md border border-green-300 p-3 text-sm text-green-700">
            {{ success }}
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div class="grid gap-2">
              <Label>Symbol</Label>
              <select v-model="symbol" class="h-10 rounded-md border px-3">
                <option value="BTC">BTC</option>
                <option value="ETH">ETH</option>
              </select>
            </div>

            <div class="grid gap-2">
              <Label>Side</Label>
              <select v-model="side" class="h-10 rounded-md border px-3">
                <option value="BUY">Buy</option>
                <option value="SELL">Sell</option>
              </select>
            </div>

            <div class="grid gap-2">
              <Label>Price (USD)</Label>
              <Input v-model="price" inputmode="decimal" placeholder="e.g. 100" />
            </div>

            <div class="grid gap-2">
              <Label>Amount</Label>
              <Input v-model="amount" inputmode="decimal" placeholder="e.g. 1.25" />
            </div>
          </div>

          <!-- Preview box -->
          <div class="mt-4 rounded-lg border p-4 text-sm">
            <div class="flex items-center justify-between">
              <div class="font-medium">Preview</div>
              <div class="text-xs text-muted-foreground">
                {{ isValidNumbers ? 'Calculated from inputs' : 'Enter price + amount' }}
              </div>
            </div>

            <div class="mt-3 grid gap-2">
              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">Subtotal</span>
                <span>{{ subtotalUsd }}</span>
              </div>

              <div v-if="side === 'BUY'" class="flex items-center justify-between">
                <span class="text-muted-foreground">Fee (1.5%)</span>
                <span>{{ feeUsd }}</span>
              </div>

              <div v-if="side === 'BUY'" class="flex items-center justify-between font-medium">
                <span>Total lock (USD)</span>
                <span>{{ totalUsd }}</span>
              </div>

              <div v-else class="flex items-center justify-between font-medium">
                <span>Expected receive (USD)</span>
                <span>{{ subtotalUsd }}</span>
              </div>

              <div v-if="side === 'BUY' && isValidNumbers" class="text-xs text-muted-foreground">
                Your BUY order locks USD using your limit price + fee buffer. Any leftover gets refunded after match.
              </div>

              <div v-if="side === 'SELL' && isValidNumbers" class="text-xs text-muted-foreground">
                Your SELL order locks the asset amount until matched or cancelled.
              </div>
            </div>
          </div>

          <Button
            class="mt-4 w-full"
            :disabled="isSubmitting || !isValidNumbers || (side === 'BUY' && !canAffordBuy) || (side === 'SELL' && !canAffordSell)"
            @click="placeOrder"
          >
            {{ isSubmitting ? 'Placing...' : 'Place Order' }}
          </Button>
        </div>

        <!-- Right sidebar -->
        <div class="space-y-4">
          <!-- Balances -->
          <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div class="mb-3 text-sm font-semibold">Your Balances</div>

            <div class="space-y-2 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">USD</span>
                <span class="font-medium">{{ usdBalance }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">{{ symbol }} (free)</span>
                <span class="font-medium">{{ assetRow(symbol).amount }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-muted-foreground">{{ symbol }} (locked)</span>
                <span class="font-medium">{{ assetRow(symbol).locked_amount }}</span>
              </div>
            </div>

            <div v-if="side === 'BUY' && isValidNumbers && !canAffordBuy" class="mt-3 rounded-md border border-red-300 p-3 text-xs text-red-700">
              Not enough USD to place this BUY order (needs ~{{ totalUsd }}).
            </div>

            <div v-if="side === 'SELL' && isValidNumbers && !canAffordSell" class="mt-3 rounded-md border border-red-300 p-3 text-xs text-red-700">
              Not enough {{ symbol }} to place this SELL order.
            </div>
          </div>

          <!-- Tips -->
          <div class="rounded-xl border border-sidebar-border/70 p-4 text-sm text-muted-foreground dark:border-sidebar-border">
            <div class="mb-2 font-semibold text-foreground">Tips</div>
            <ul class="list-disc space-y-2 pl-5">
              <li>Orders are full-match only (exact amount must exist on the other side).</li>
              <li>Executed price is the maker (existing order) price.</li>
              <li>Buyer pays 1.5% fee in USD.</li>
              <li>SELL locks asset amount; BUY locks USD + fee buffer.</li>
              <li>If no matching order exists, your order stays OPEN until matched or cancelled.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
