<?php

declare(strict_types=1);

/**
 * @var string $activeTab
 */
$activeTab = $activeTab ?? 'facebook';
?>
<div class="list-group mb-4 shadow-sm">
    <a href="/sys-yii3/settings/facebook" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?= $activeTab === 'facebook' ? 'active bg-primary text-white' : '' ?>">
        <span><i class="bi bi-facebook me-2 text-primary <?= $activeTab === 'facebook' ? 'text-white' : '' ?>"></i> Facebook &amp; Instagram</span>
        <i class="bi bi-chevron-right small opacity-75"></i>
    </a>
    <a href="/sys-yii3/settings/google" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?= $activeTab === 'google' ? 'active bg-primary text-white' : '' ?>">
        <span><i class="bi bi-google me-2 text-danger <?= $activeTab === 'google' ? 'text-white' : '' ?>"></i> Google Business Profiles</span>
        <i class="bi bi-chevron-right small opacity-75"></i>
    </a>
    <a href="/sys-yii3/location-group" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
        <span><i class="bi bi-diagram-3 me-2 text-info"></i> Location Groups</span>
        <i class="bi bi-chevron-right small opacity-75"></i>
    </a>
</div>
