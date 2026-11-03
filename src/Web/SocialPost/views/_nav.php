<?php

declare(strict_types=1);

/**
 * @var string $activeTab
 */
$activeTab = $activeTab ?? 'index';
?>
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'index' ? 'active' : '' ?>" href="/sys-yii3/social-post">
            <i class="bi bi-collection me-1"></i> All Posts
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'scheduled' ? 'active' : '' ?>" href="/sys-yii3/social-post?type=scheduled">
            <i class="bi bi-clock-history me-1"></i> Scheduled
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'history' ? 'active' : '' ?>" href="/sys-yii3/social-post/history">
            <i class="bi bi-journal-check me-1"></i> History
        </a>
    </li>
    <li class="nav-item ms-auto">
        <a class="btn btn-success" href="/sys-yii3/social-post/create">
            <i class="bi bi-pencil-square me-1"></i> Compose Post
        </a>
    </li>
</ul>
