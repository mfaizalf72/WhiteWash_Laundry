<?php
use Faizal\LaundryLms\Helpers\Security;

$flashConfig = [
    'success' => [
        'bg' => 'bg-emerald-50 dark:bg-emerald-950/40',
        'border' => 'border-emerald-200 dark:border-emerald-800/60',
        'text' => 'text-emerald-800 dark:text-emerald-300',
        'iconColor' => 'text-emerald-600 dark:text-emerald-400',
        'btnColor' => 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-200 hover:bg-emerald-100/70 dark:hover:bg-emerald-900/60 focus:ring-emerald-500',
        'role' => 'status',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'
    ],
    'error' => [
        'bg' => 'bg-rose-50 dark:bg-rose-950/40',
        'border' => 'border-rose-200 dark:border-rose-800/60',
        'text' => 'text-rose-800 dark:text-rose-300',
        'iconColor' => 'text-rose-600 dark:text-rose-400',
        'btnColor' => 'text-rose-600 dark:text-rose-400 hover:text-rose-900 dark:hover:text-rose-200 hover:bg-rose-100/70 dark:hover:bg-rose-900/60 focus:ring-rose-500',
        'role' => 'alert',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
    ],
    'warning' => [
        'bg' => 'bg-amber-50 dark:bg-amber-950/40',
        'border' => 'border-amber-200 dark:border-amber-800/60',
        'text' => 'text-amber-800 dark:text-amber-300',
        'iconColor' => 'text-amber-600 dark:text-amber-400',
        'btnColor' => 'text-amber-600 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-200 hover:bg-amber-100/70 dark:hover:bg-amber-900/60 focus:ring-amber-500',
        'role' => 'alert',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />'
    ],
    'info' => [
        'bg' => 'bg-blue-50 dark:bg-blue-950/40',
        'border' => 'border-blue-200 dark:border-blue-800/60',
        'text' => 'text-blue-800 dark:text-blue-300',
        'iconColor' => 'text-blue-600 dark:text-blue-400',
        'btnColor' => 'text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-200 hover:bg-blue-100/70 dark:hover:bg-blue-900/60 focus:ring-blue-500',
        'role' => 'status',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
    ]
];

foreach ($flashConfig as $flashKey => $cfg):
    if (!empty($_SESSION[$flashKey])):
        $flashMsg = (string)$_SESSION[$flashKey];
        unset($_SESSION[$flashKey]);
?>
    <div data-flash-alert class="flash-alert mb-6 p-4 rounded-xl border <?= $cfg['bg'] ?> <?= $cfg['border'] ?> <?= $cfg['text'] ?> text-sm font-medium flex items-start justify-between gap-3 shadow-sm transition-all duration-200" role="<?= $cfg['role'] ?>">
        <div class="flex items-start gap-3 min-w-0">
            <svg class="w-5 h-5 <?= $cfg['iconColor'] ?> shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <?= $cfg['icon'] ?>
            </svg>
            <div class="leading-5 break-words"><?= Security::e($flashMsg) ?></div>
        </div>
        <button type="button" data-flash-dismiss aria-label="Dismiss alert" onclick="this.closest('[data-flash-alert]').remove()" class="shrink-0 p-1 -mr-1 -mt-1 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 <?= $cfg['btnColor'] ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
<?php
    endif;
endforeach;
?>
