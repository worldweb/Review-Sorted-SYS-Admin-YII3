<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<int, App\Domain\SocialConnection\Api> $apis
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Google Projects');
?>

<div class="rs-container">
    <?= $this->render('../partials/_header', [
        'title' => 'Google Projects',
        'subtitle' => 'Manage connected Google Projects.'
    ]) ?>

    <?php if ($flash->has('success')): ?>
        <div class="rs-alert rs-alert--success rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('success')) ?>
        </div>
    <?php endif; ?>
    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <div class="rs-section">
        <?php if (!empty($apis)): ?>
            <div class="rs-card rs-card--shadow">
                <?= $this->render('../partials/_card_header', [
                    'title' => 'Connected Projects',
                    'subtitle' => 'Manage your connected Google Projects',
                    'action' => '<a href="/sys-yii3/settings/google-api/create" class="rs-btn rs-btn--primary rs-btn--sm rs-w-100 md:rs-w-auto">Add Project</a>'
                ]) ?>
                <div class="rs-table-wrapper">
                    <table class="rs-table rs-table--hover rs-w-100">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Created At</th>
                                <th class="rs-text-right" style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apis as $project): ?>
                                <tr>
                                    <td class="rs-font-semibold">
                                        <a href="/sys-yii3/settings/google-api/view?id=<?= $project->getId() ?>" style="color: var(--rs-primary); text-decoration: none;">
                                            <?= Html::encode($project->getLabel()) ?>
                                        </a>
                                    </td>
                                    <td class="rs-text-muted"><?= Html::encode($project->getCreatedAtStr()) ?></td>
                                    <td class="rs-text-right">
                                        <div class="rs-d-flex rs-align-items-center rs-justify-content-end rs-gap-1">
                                            <a href="/sys-yii3/settings/google-api/view?id=<?= $project->getId() ?>" class="rs-btn rs-btn--ghost rs-btn--sm rs-px-2" title="View Details">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            </a>
                                            <a href="/sys-yii3/settings/google-api/update?id=<?= $project->getId() ?>" class="rs-btn rs-btn--ghost rs-btn--sm rs-px-2" title="Edit Project">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                            </a>
                                            <a href="/sys-yii3/settings/google-api/delete?id=<?= $project->getId() ?>" class="rs-btn rs-btn--ghost rs-btn--sm rs-px-2 rs-text-danger" title="Delete Project" onclick="return confirm('Are you sure you want to delete this Google Project?');">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <?= $this->render('../partials/_empty_state', [
                'message' => 'No Google Projects configured yet.',
                'actionUrl' => '/sys-yii3/settings/google-api/create',
                'actionLabel' => 'Add Project'
            ]) ?>
        <?php endif; ?>
    </div>
</div>
