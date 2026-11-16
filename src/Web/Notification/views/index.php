<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Dedicated Standalone Notification History View (/notifications).
 *
 * @var array<\App\Domain\Notification\Notification> $notifications
 * @var int $total
 * @var int $page
 * @var int $pageSize
 * @var int $totalPages
 * @var int $unreadCount
 * @var string $currentStatus
 * @var string $currentType
 * @var \App\Domain\User\User $currentUser
 * @var string|null $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Notifications');

$baseUrl = \App\Environment::url('/notifications');
$markAllReadUrl = \App\Environment::url('/notification/mark-all-read');
$markReadUrl = \App\Environment::url('/notification/mark-read');

// Header action: Mark All as Read button if unread exists
$actionButtons = [];
if ($unreadCount > 0) {
    $actionButtons[] = '<button type="button" class="rs-btn rs-btn--secondary rs-d-flex rs-align-items-center rs-gap-2" id="rs-history-mark-all-btn">' .
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' .
        ' Mark All as Read</button>';
}

$title = 'Notification History';
$description = 'Review all activity, new customer reviews, publishing alerts, and platform events.';
$actions = $actionButtons;
include __DIR__ . '/../../Shared/Components/_page_header.php';

// Calculate pagination range
$startItem = $total > 0 ? (($page - 1) * $pageSize) + 1 : 0;
$endItem = min($total, $page * $pageSize);

// Helper for type icons
$getTypeIcon = static function (string $type): array {
    return match ($type) {
        'review', 'new_review' => [
            'bg' => 'rgba(245, 158, 11, 0.12)',
            'color' => '#d97706',
            'svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
            'label' => 'Review',
        ],
        'social_post' => [
            'bg' => 'rgba(16, 185, 129, 0.12)',
            'color' => '#059669',
            'svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>',
            'label' => 'Social Post',
        ],
        'social_post_failed' => [
            'bg' => 'rgba(239, 68, 68, 0.12)',
            'color' => '#dc2626',
            'svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
            'label' => 'Publishing Failed',
        ],
        'connection' => [
            'bg' => 'rgba(59, 130, 246, 0.12)',
            'color' => '#2563eb',
            'svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>',
            'label' => 'Connection',
        ],
        default => [
            'bg' => 'rgba(35, 65, 55, 0.08)',
            'color' => 'var(--rs-primary)',
            'svg' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
            'label' => 'Notification',
        ],
    };
};

// Relative time helper
$formatTimeAgo = static function (int $timestamp): string {
    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $mins = (int) floor($diff / 60);
        return $mins . 'm ago';
    }
    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . 'h ago';
    }
    if ($diff < 604800) {
        $days = (int) floor($diff / 86400);
        return $days . 'd ago';
    }
    return date('M j, Y', $timestamp);
};
?>

<!-- Filter & Search Toolbar -->
<div class="rs-card rs-mb-4" style="padding: 1rem 1.25rem;">
    <form method="get" action="<?= Html::encode($baseUrl) ?>" class="rs-d-flex rs-flex-column rs-flex-md-row rs-align-items-md-center rs-justify-content-between rs-gap-3" id="rs-notification-filter-form">
        <!-- Status Tabs / Filter Pills -->
        <div class="rs-d-flex rs-align-items-center rs-gap-2" style="flex-wrap: wrap;">
            <a href="<?= Html::encode($baseUrl . '?status=all&type=' . urlencode($currentType)) ?>"
               class="rs-btn rs-btn--sm <?= $currentStatus === 'all' ? 'rs-btn--primary' : 'rs-btn--ghost' ?>"
               style="border-radius: 999px; padding: 0.35rem 0.85rem; font-size: 0.8125rem;">
               All
            </a>
            <a href="<?= Html::encode($baseUrl . '?status=unread&type=' . urlencode($currentType)) ?>"
               class="rs-btn rs-btn--sm <?= $currentStatus === 'unread' ? 'rs-btn--primary' : 'rs-btn--ghost' ?>"
               style="border-radius: 999px; padding: 0.35rem 0.85rem; font-size: 0.8125rem;">
               Unread
               <?php if ($unreadCount > 0): ?>
                   <span class="rs-badge" style="background: <?= $currentStatus === 'unread' ? '#fff' : 'var(--rs-primary)' ?>; color: <?= $currentStatus === 'unread' ? 'var(--rs-primary)' : '#fff' ?>; margin-left: 4px; padding: 1px 6px; font-size: 0.7rem; border-radius: 999px;"><?= $unreadCount ?></span>
               <?php endif; ?>
            </a>
            <a href="<?= Html::encode($baseUrl . '?status=read&type=' . urlencode($currentType)) ?>"
               class="rs-btn rs-btn--sm <?= $currentStatus === 'read' ? 'rs-btn--primary' : 'rs-btn--ghost' ?>"
               style="border-radius: 999px; padding: 0.35rem 0.85rem; font-size: 0.8125rem;">
               Read
            </a>
        </div>

        <!-- Type Filter & Meta Info -->
        <div class="rs-d-flex rs-align-items-center rs-gap-3">
            <input type="hidden" name="status" value="<?= Html::encode($currentStatus) ?>">
            <div class="rs-d-flex rs-align-items-center rs-gap-2">
                <label for="filter-type" class="rs-text-muted rs-font-size-sm rs-m-0" style="white-space: nowrap;">Type:</label>
                <select id="filter-type" name="type" class="rs-form-control rs-form-control--sm" style="width: auto; min-width: 150px; font-size: 0.8125rem; border-radius: 6px;" onchange="this.form.submit();">
                    <option value="all" <?= $currentType === 'all' ? 'selected' : '' ?>>All Activity</option>
                    <option value="review" <?= in_array($currentType, ['review', 'new_review'], true) ? 'selected' : '' ?>>Reviews</option>
                    <option value="social_post" <?= $currentType === 'social_post' ? 'selected' : '' ?>>Published Posts</option>
                    <option value="social_post_failed" <?= $currentType === 'social_post_failed' ? 'selected' : '' ?>>Publishing Errors</option>
                    <option value="connection" <?= $currentType === 'connection' ? 'selected' : '' ?>>Connections</option>
                </select>
            </div>

            <?php if ($currentStatus !== 'all' || $currentType !== 'all'): ?>
                <a href="<?= Html::encode($baseUrl) ?>" class="rs-btn rs-btn--ghost rs-btn--sm rs-text-muted" style="font-size: 0.75rem;" title="Reset all filters">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Notification Feed Card -->
<div class="rs-card" style="padding: 0; overflow: hidden;">
    <?php if (empty($notifications)): ?>
        <!-- Empty State -->
        <div class="rs-p-6 rs-text-center" style="padding: 4rem 1.5rem;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(35, 65, 55, 0.08); color: var(--rs-primary); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            </div>
            <h3 class="rs-font-bold rs-text-main rs-mb-1" style="font-size: 1.125rem;">No notifications found</h3>
            <p class="rs-text-muted rs-font-size-sm rs-mb-4" style="max-width: 360px; margin-left: auto; margin-right: auto;">
                <?php if ($currentStatus !== 'all' || $currentType !== 'all'): ?>
                    There are no notifications matching your selected filter criteria.
                <?php else: ?>
                    You are all caught up! When updates, reviews, or alerts occur, they will appear here.
                <?php endif; ?>
            </p>
            <?php if ($currentStatus !== 'all' || $currentType !== 'all'): ?>
                <a href="<?= Html::encode($baseUrl) ?>" class="rs-btn rs-btn--secondary rs-btn--sm">View All Notifications</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Notification Items List -->
        <div class="rs-notification-feed-list" id="rs-notification-feed">
            <?php foreach ($notifications as $notification): ?>
                <?php
                    $isUnread = !$notification->isRead();
                    $iconData = $getTypeIcon($notification->getType());
                    $timeAgo = $formatTimeAgo($notification->getCreatedAt());
                    $fullTime = date('M j, Y g:i A', $notification->getCreatedAt());
                    $actionUrl = $notification->getActionUrl();
                    $notifId = $notification->getId();
                ?>
                <div class="rs-notification-item rs-d-flex rs-align-items-start rs-p-4 <?= $isUnread ? 'rs-notification-item--unread' : '' ?>"
                     id="notif-row-<?= $notifId ?>"
                     style="border-bottom: 1px solid var(--rs-border); transition: background-color 0.15s ease; position: relative; <?= $isUnread ? 'background-color: rgba(35, 65, 55, 0.03); border-left: 3px solid var(--rs-primary);' : 'border-left: 3px solid transparent;' ?>">
                    
                    <!-- Icon badge -->
                    <div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 8px; background: <?= $iconData['bg'] ?>; color: <?= $iconData['color'] ?>; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                        <?= $iconData['svg'] ?>
                    </div>

                    <!-- Content body -->
                    <div style="flex-grow: 1; min-width: 0;">
                        <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-gap-2 rs-mb-1" style="flex-wrap: wrap;">
                            <div class="rs-d-flex rs-align-items-center rs-gap-2" style="flex-wrap: wrap;">
                                <span class="rs-badge" style="font-size: 0.7rem; padding: 2px 6px; background: rgba(0, 0, 0, 0.05); color: var(--rs-text-secondary); border-radius: 4px;">
                                    <?= Html::encode($iconData['label']) ?>
                                </span>
                                <?php if ($notification->getAccountName() !== null && $currentUser->isAdmin()): ?>
                                    <span class="rs-badge" style="font-size: 0.7rem; padding: 2px 6px; background: rgba(35, 65, 55, 0.08); color: var(--rs-primary); border-radius: 4px; font-weight: 600;">
                                        <?= Html::encode($notification->getAccountName()) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="rs-font-bold rs-text-main" style="font-size: 0.9375rem;">
                                    <?= Html::encode($notification->getTitle()) ?>
                                </span>
                                <?php if ($isUnread): ?>
                                    <span class="rs-unread-dot" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: var(--rs-primary);" title="Unread"></span>
                                <?php endif; ?>
                            </div>
                            <span class="rs-text-muted" style="font-size: 0.75rem; white-space: nowrap;" title="<?= Html::encode($fullTime) ?>">
                                <?= Html::encode($timeAgo) ?>
                            </span>
                        </div>

                        <?php if ($notification->getMessage() !== null && $notification->getMessage() !== ''): ?>
                            <p class="rs-text-secondary rs-font-size-sm rs-m-0 rs-mb-2" style="line-height: 1.5; color: #4b5563;">
                                <?= nl2br(Html::encode($notification->getMessage())) ?>
                            </p>
                        <?php endif; ?>

                        <!-- Row Actions -->
                        <div class="rs-d-flex rs-align-items-center rs-gap-3 rs-mt-2">
                            <?php if ($actionUrl !== null && $actionUrl !== '' && $actionUrl !== '#'): ?>
                                <a href="<?= Html::encode($actionUrl) ?>"
                                   class="rs-btn rs-btn--ghost rs-btn--sm rs-notification-action-link"
                                   data-id="<?= $notifId ?>"
                                   style="font-size: 0.8125rem; padding: 3px 10px; color: var(--rs-primary); border: 1px solid rgba(35, 65, 55, 0.2);">
                                   View Details &rarr;
                                </a>
                            <?php endif; ?>

                            <?php if ($isUnread): ?>
                                <button type="button"
                                        class="rs-btn rs-btn--ghost rs-btn--sm rs-mark-read-btn"
                                        data-id="<?= $notifId ?>"
                                        style="font-size: 0.75rem; padding: 3px 8px; color: var(--rs-text-muted);">
                                    Mark as read
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination & Summary Footer -->
        <div class="rs-card-footer rs-d-flex rs-flex-column rs-flex-md-row rs-align-items-md-center rs-justify-content-between rs-p-3" style="border-top: 1px solid var(--rs-border); background-color: #fafafa; font-size: 0.8125rem;">
            <div class="rs-text-muted rs-mb-2 rs-mb-md-0">
                Showing <strong><?= $startItem ?></strong> to <strong><?= $endItem ?></strong> of <strong><?= $total ?></strong> notifications
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="rs-d-flex rs-align-items-center rs-gap-1">
                    <!-- Previous -->
                    <?php if ($page > 1): ?>
                        <a href="<?= Html::encode($baseUrl . '?status=' . urlencode($currentStatus) . '&type=' . urlencode($currentType) . '&page=' . ($page - 1)) ?>"
                           class="rs-btn rs-btn--ghost rs-btn--sm" style="padding: 4px 10px;">&laquo; Prev</a>
                    <?php else: ?>
                        <span class="rs-btn rs-btn--ghost rs-btn--sm" style="padding: 4px 10px; opacity: 0.4; cursor: not-allowed;">&laquo; Prev</span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                        <a href="<?= Html::encode($baseUrl . '?status=' . urlencode($currentStatus) . '&type=' . urlencode($currentType) . '&page=' . $p) ?>"
                           class="rs-btn rs-btn--sm <?= $p === $page ? 'rs-btn--primary' : 'rs-btn--ghost' ?>"
                           style="min-width: 32px; padding: 4px 8px; text-align: center; border-radius: 4px;">
                           <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Next -->
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= Html::encode($baseUrl . '?status=' . urlencode($currentStatus) . '&type=' . urlencode($currentType) . '&page=' . ($page + 1)) ?>"
                           class="rs-btn rs-btn--ghost rs-btn--sm" style="padding: 4px 10px;">Next &raquo;</a>
                    <?php else: ?>
                        <span class="rs-btn rs-btn--ghost rs-btn--sm" style="padding: 4px 10px; opacity: 0.4; cursor: not-allowed;">Next &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Inline JavaScript for History Page Actions -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const markReadUrl = <?= json_encode($markReadUrl) ?>;
    const markAllReadUrl = <?= json_encode($markAllReadUrl) ?>;
    const csrfToken = <?= json_encode($csrfToken ?? '') ?>;

    function getCsrfParam() {
        if (window.yii && typeof window.yii.getCsrfParam === 'function') {
            return window.yii.getCsrfParam();
        }
        return '_csrf';
    }

    function markNotificationRead(id, callback) {
        if (!markReadUrl || !id) {
            if (callback) callback();
            return;
        }

        const data = {};
        data[getCsrfParam()] = csrfToken;

        const sep = markReadUrl.indexOf('?') !== -1 ? '&' : '?';
        const url = markReadUrl + sep + 'id=' + encodeURIComponent(id);

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(data)
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res && res.success) {
                // Visual update on row
                const row = document.getElementById('notif-row-' + id);
                if (row) {
                    row.style.backgroundColor = 'transparent';
                    row.style.borderLeft = '3px solid transparent';
                    const dot = row.querySelector('.rs-unread-dot');
                    if (dot) dot.remove();
                    const btn = row.querySelector('.rs-mark-read-btn');
                    if (btn) btn.remove();
                }

                // Update navbar bell badge via NotificationManager or directly
                if (res.unread_count !== undefined) {
                    const count = parseInt(res.unread_count, 10) || 0;
                    if (window.NotificationManager && typeof window.NotificationManager.updateBadge === 'function') {
                        window.NotificationManager.cachedUnreadCount = count;
                        window.NotificationManager.updateBadge(count);
                    } else {
                        const badge = document.getElementById('rs-notification-badge');
                        if (badge) {
                            if (count > 0) {
                                badge.textContent = count > 99 ? '99+' : count;
                                badge.style.display = 'inline-block';
                                badge.classList.remove('rs-d-none');
                            } else {
                                badge.textContent = '0';
                                badge.style.display = 'none';
                                badge.classList.add('rs-d-none');
                            }
                        }
                    }
                }
            }
            if (callback) callback();
        })
        .catch(function() {
            if (callback) callback();
        });
    }

    // Bind individual mark-as-read buttons
    document.querySelectorAll('.rs-mark-read-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            this.disabled = true;
            this.textContent = 'Updating...';
            markNotificationRead(id);
        });
    });

    // Bind action links to mark read on click before navigation
    document.querySelectorAll('.rs-notification-action-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            const id = this.getAttribute('data-id');
            const href = this.getAttribute('href');
            if (id && href && href !== '#') {
                e.preventDefault();
                markNotificationRead(id, function() {
                    window.location.href = href;
                });
            }
        });
    });

    // Bind Mark All as Read button
    const markAllBtn = document.getElementById('rs-history-mark-all-btn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            this.disabled = true;
            this.textContent = 'Marking all read...';

            const data = {};
            data[getCsrfParam()] = csrfToken;

            fetch(markAllReadUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(data)
            })
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (res && res.success) {
                    // Update visible notification rows immediately
                    document.querySelectorAll('.rs-notification-item--unread').forEach(function(row) {
                        row.classList.remove('rs-notification-item--unread');
                        row.style.backgroundColor = 'transparent';
                        row.style.borderLeft = '3px solid transparent';
                        const dot = row.querySelector('.rs-unread-dot');
                        if (dot) dot.remove();
                        const btn = row.querySelector('.rs-mark-read-btn');
                        if (btn) btn.remove();
                    });
                    if (window.NotificationManager && typeof window.NotificationManager.updateBadge === 'function') {
                        window.NotificationManager.cachedUnreadCount = 0;
                        window.NotificationManager.updateBadge(0);
                    } else {
                        const badge = document.getElementById('rs-notification-badge');
                        if (badge) {
                            badge.textContent = '0';
                            badge.style.display = 'none';
                            badge.classList.add('rs-d-none');
                        }
                    }
                    markAllBtn.remove();
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('status') === 'unread') {
                        window.location.reload();
                    }
                } else {
                    markAllBtn.disabled = false;
                    markAllBtn.textContent = 'Mark All as Read';
                }
            })
            .catch(function() {
                window.location.reload();
            });
        });
    }
});
</script>
