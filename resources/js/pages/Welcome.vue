<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header class="mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>

                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <div class="flex w-full items-center justify-center lg:grow">
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row"
            >
                <!-- Left content -->
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-10 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-14 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <div class="mb-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        VirgoSoft Assessment
                    </div>

                    <h1 class="text-lg font-semibold lg:text-xl">
                        Exchange Engine
                    </h1>

                    <p class="mt-2 text-[#706f6c] dark:text-[#A1A09A]">
                        A minimal limit-order exchange demo built with Laravel and Vue (Inertia).
                        Includes orderbook, wallet balances, full-match engine, and realtime updates via Pusher.
                    </p>

                    <div class="mt-5">
                        <div class="text-sm font-medium">What you can test</div>
                        <ul class="mt-2 space-y-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                            <li class="flex items-start gap-2">
                                <span class="mt-[6px] h-1.5 w-1.5 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                Place BUY or SELL limit orders for BTC or ETH
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-[6px] h-1.5 w-1.5 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                Matching rules full match only first valid counter order
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-[6px] h-1.5 w-1.5 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                Commission is 1 point 5 percent of matched USD paid by buyer
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-[6px] h-1.5 w-1.5 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                Realtime updates on match using private user channels
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6 rounded-md border border-[#19140035] p-4 dark:border-[#3E3E3A]">
                        <div class="text-sm font-medium">Demo credentials</div>
                        <div class="mt-2 grid gap-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            <div>
                                Buyer account: buyer test com
                            </div>
                            <div>
                                Seller account: seller test com
                            </div>
                            <div>
                                Password: password
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            href="/dashboard"
                            class="inline-flex items-center rounded-sm border border-black bg-[#1b1b18] px-5 py-2 text-sm text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                        >
                            Go to Dashboard
                        </Link>

                        <Link
                            v-if="$page.props.auth.user"
                            href="/exchange/order"
                            class="inline-flex items-center rounded-sm border border-[#19140035] px-5 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                        >
                            Place Order
                        </Link>

                        <template v-if="!$page.props.auth.user">
                            <Link
                                :href="login()"
                                class="inline-flex items-center rounded-sm border border-black bg-[#1b1b18] px-5 py-2 text-sm text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                            >
                                Log in
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="register()"
                                class="inline-flex items-center rounded-sm border border-[#19140035] px-5 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                            >
                                Register
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- Right panel (simple visual, keep clean) -->
                <div
                    class="relative -mb-px flex w-full shrink-0 items-center justify-center overflow-hidden rounded-t-lg bg-[#fff2f2] p-10 lg:mb-0 lg:-ml-px lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:bg-[#1D0002]"
                >
                    <div class="w-full max-w-sm space-y-4">
                        <div class="rounded-lg bg-white/70 p-4 text-xs shadow-sm dark:bg-white/10 dark:text-[#EDEDEC]">
                            <div class="font-semibold">Quick flow</div>
                            <ol class="mt-2 list-decimal space-y-1 pl-4">
                                <li>Login as seller and place a SELL order</li>
                                <li>Login as buyer and place a BUY order at same or higher price</li>
                                <li>Dashboard updates instantly on match</li>
                            </ol>
                        </div>

                        <div class="rounded-lg bg-white/70 p-4 text-xs shadow-sm dark:bg-white/10 dark:text-[#EDEDEC]">
                            <div class="font-semibold">Endpoints used</div>
                            <div class="mt-2 space-y-1">
                                <div>GET api profile</div>
                                <div>GET api orders symbol BTC</div>
                                <div>POST api orders</div>
                                <div>POST api orders id cancel</div>
                                <div>GET api trades and my trades</div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-t-none lg:rounded-r-lg dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    />
                </div>
            </main>
        </div>

        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
