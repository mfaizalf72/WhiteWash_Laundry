<?php
declare(strict_types=1);

use Faizal\LaundryLms\Helpers\Security;
use Faizal\LaundryLms\Helpers\Csrf;

/** @var array $notifications */
/** @var int $unreadCount */
/** @var array $customer */
?>

<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 font-display">Notifications</h1>
                <?php if ($unreadCount > 0): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                        <?= (int)$unreadCount ?> unread
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Updates on your pickup requests, inspections, delivery status, and payments.
            </p>
        </div>

        <?php if ($unreadCount > 0): ?>
            <div>
                <form action="/laundry-lms/customer/notifications/read-all" method="POST" class="inline-block">
                    <?= Csrf::field() ?>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 rounded-lg border border-slate-300 shadow-sm transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Mark All as Read
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Notification List -->
    <?php if (empty($notifications)): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">No notifications yet</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                When you request a pickup or when your order changes status, you'll receive real-time updates here.
            </p>
            <div class="mt-4">
                <a href="/laundry-lms/customer/orders/create" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition">
                    + Request Pickup
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notifications as $n): 
                $isRead = (int)($n['is_read'] ?? 0) === 1;
                $cardClasses = $isRead 
                    ? "bg-white border-slate-200 opacity-90 hover:opacity-100" 
                    : "bg-blue-50/50 border-blue-200 shadow-sm";
            ?>
                <div class="rounded-xl border p-4 sm:p-5 transition <?= $cardClasses ?> flex items-start gap-3 sm:gap-4">
                    <!-- Type Icon -->
                    <div class="p-2 sm:p-2.5 rounded-lg shrink-0 mt-0.5 <?= $isRead ? 'bg-slate-100 text-slate-500' : 'bg-blue-100 text-blue-700' ?>">
                        <?php if (str_contains($n['type'], 'payment')): ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        <?php elseif (str_contains($n['type'], 'refund')): ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        <?php else: ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-800 truncate">
                                    <?= Security::e($n['title']) ?>
                                </h3>
                                <?php if (!$isRead): ?>
                                    <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0" title="Unread"></span>
                                <?php endif; ?>
                            </div>
                            <time class="text-[11px] text-slate-600 sm:text-right shrink-0">
                                <?= date('M d, Y \a\t h:i A', strtotime($n['created_at'])) ?>
                            </time>
                        </div>

                        <p class="text-xs sm:text-sm text-slate-700 leading-relaxed mb-3">
                            <?= Security::e($n['message']) ?>
                        </p>

                        <!-- Actions row -->
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs">
                            <?php if (!empty($n['order_id'])): ?>
                                <a href="/laundry-lms/customer/orders/show?id=<?= (int)$n['order_id'] ?>" class="font-semibold text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                    <span>View Order <?= !empty($n['job_id']) ? '#' . Security::e($n['job_id']) : '' ?></span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            <?php endif; ?>

                            <?php if (!$isRead): ?>
                                <?php if (!empty($n['order_id'])): ?>
                                    <span class="text-slate-300">&bull;</span>
                                <?php endif; ?>
                                <form action="/laundry-lms/customer/notifications/read" method="POST" class="inline-block">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                    <button type="submit" class="text-slate-500 hover:text-slate-800 font-medium transition underline-offset-2 hover:underline">
                                        Mark as read
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
