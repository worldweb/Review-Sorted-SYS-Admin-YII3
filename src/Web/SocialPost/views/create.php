<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Social Post Create View (Yii 3 Modern Theme)
 * 100% UI and JavaScript Parity with Yii2 Source of Truth.
 *
 * @var App\Domain\User\User $currentUser
 * @var array<int, App\Domain\LocationGroup\LocationGroup> $groups
 * @var array<int, string> $groupOptions
 * @var array<int, array<string, mixed>> $groupData
 * @var array<int, array<string, mixed>> $googleItems
 * @var array<int, array<string, mixed>> $facebookItems
 * @var array<int, array<string, mixed>> $instagramItems
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Create Social Post');

$totalDestinations = count($googleItems) + count($facebookItems) + count($instagramItems);
?>

<div class="rs-social-post-create">
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 class="rs-h1 rs-m-0" style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0;"><?= Html::encode($this->getTitle()) ?></h2>
        <div class="rs-d-flex rs-gap-2" style="display: flex; gap: 0.5rem;">
            <button type="button" id="btn-preview-post" class="rs-btn rs-btn--secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                Preview Post
            </button>
            <a href="<?= \App\Environment::url('/social-post') ?>" class="rs-btn rs-btn--secondary">Back to Publishing History</a>
        </div>
    </div>

    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert" style="padding: 12px 16px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; color: #991b1b; margin-bottom: 1.25rem;">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>
    <?php if ($flash->has('success')): ?>
        <div class="rs-alert rs-alert--success rs-mb-4" role="alert" style="padding: 12px 16px; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; color: #166534; margin-bottom: 1.25rem;">
            <?= Html::encode((string) $flash->get('success')) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= \App\Environment::url('/social-post/create') ?>" id="social-post-create-form" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">

        <!-- Card 1: Post Content & Media -->
        <div class="rs-card rs-mb-4" style="background: #fff; border: 1px solid var(--rs-border, #e2e8f0); border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
                <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Post Content &amp; Media</h3>
            </div>
            <div class="rs-card__body" style="padding: 20px;">
                <div class="rs-form-group rs-mb-3" style="margin-bottom: 1.25rem;">
                    <label class="rs-label" for="post-content-textarea" style="font-weight: 600; margin-bottom: 6px; display: block; color: #334155; font-size: 14px;">
                        Post Message <span class="rs-text-danger" style="color: #ef4444;">*</span>
                    </label>
                    <textarea id="post-content-textarea" name="content" class="rs-textarea rs-input" rows="5" placeholder="Write your social post content here..." style="width: 100%; border: 1px solid var(--rs-border, #cbd5e1); border-radius: 6px; padding: 12px; font-size: 14px; box-sizing: border-box; line-height: 1.5;" required></textarea>
                    
                    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mt-2" style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                        <span id="char-counter-display" class="rs-text-sm" style="font-weight: 600; font-size: 13px; color: #475569;">0 / 63,206 characters</span>
                        <span id="platform-limit-note" class="rs-text-sm rs-text-muted" style="font-size: 12px; color: #64748b;"></span>
                    </div>
                </div>

                <div class="rs-form-group rs-mb-0">
                    <label class="rs-label" for="social-media-file-input" style="font-weight: 600; margin-bottom: 6px; display: block; color: #334155; font-size: 14px;">Image or Video Attachment</label>
                    <input type="file" name="media" id="social-media-file-input" class="rs-input" accept="image/*,video/*" style="width: 100%; box-sizing: border-box; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                    <span class="rs-help-block" style="display: block; margin-top: 6px; font-size: 12px; color: #64748b;">Supported formats: JPG, PNG, MP4, MOV (Max 50MB). Instagram requires media.</span>
                    <div id="google-video-warning" class="rs-alert rs-alert--warning rs-mt-3" style="display:none; padding: 10px 14px; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 6px; font-size: 13px; margin-top: 10px;">
                        <strong>Note:</strong> Google Business Profile Local Posts currently support image attachments (JPG/PNG). If a video is attached, please uncheck Google Business Profile to proceed.
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Target Locations & Location Groups -->
        <div class="rs-card rs-mb-4" id="target-locations-card" style="background: #fff; border: 1px solid var(--rs-border, #e2e8f0); border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: visible !important;">
            <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
                <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Target Locations &amp; Groups</h3>
            </div>
            <div class="rs-card__body" style="padding: 20px; overflow: visible !important; position: relative;">
                
                <?php if (!empty($groupOptions)): ?>
                    <!-- Location Groups Multi-Select -->
                    <div class="rs-form-group rs-mb-4" style="margin-bottom: 1.25rem;">
                        <label class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px; color: #334155; font-size: 14px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2" style="vertical-align: -2px; margin-right: 4px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            Target Location Groups (Optional)
                        </label>
                        <select name="group_ids[]" id="target-groups-select2" class="rs-select2-target-groups rs-input" multiple data-placeholder="Search and select location groups..." style="width: 100%;">
                            <?php foreach ($groupOptions as $groupId => $groupLabel): ?>
                                <option value="<?= $groupId ?>"><?= Html::encode($groupLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="rs-help-block" style="display: block; margin-top: 6px; font-size: 12px; color: #64748b;">Selecting a group automatically targets all destinations configured in that group.</span>

                        <!-- Live preview badges for selected groups with remove action -->
                        <div id="selected-groups-badges-container" style="display: none; margin-top: 10px;">
                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 6px;">Selected Groups:</div>
                            <div id="selected-groups-badges" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
                        </div>

                        <!-- Live preview destinations breakdown included in selected groups -->
                        <div id="selected-groups-breakdown-card" style="display: none; margin-top: 14px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; background: #fff;">
                            <div style="padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                    <span id="breakdown-header-title" style="font-weight: 700; font-size: 13px; color: #1e293b;">Group Destinations Breakdown</span>
                                </div>
                                <button type="button" id="btn-toggle-breakdown" class="rs-btn-link" style="font-size: 12px; font-weight: 600; color: #1d4ed8; background: none; border: none; cursor: pointer;">Show List</button>
                            </div>
                            <div id="selected-groups-breakdown-body" style="display: none; max-height: 240px; overflow-y: auto; padding: 8px 14px;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid #e2e8f0; text-align: left; color: #64748b;">
                                            <th style="padding: 6px 4px; font-weight: 600;">Platform</th>
                                            <th style="padding: 6px 4px; font-weight: 600;">Destination Name</th>
                                            <th style="padding: 6px 4px; font-weight: 600;">Account</th>
                                            <th style="padding: 6px 4px; font-weight: 600;">Source Group</th>
                                        </tr>
                                    </thead>
                                    <tbody id="breakdown-table-rows"></tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <div class="rs-divider rs-my-4" style="border-top: 1px solid var(--rs-border, #e2e8f0); margin: 1.5rem 0;"></div>
                <?php endif; ?>

                <!-- Individual Locations & Destinations Multi-Select -->
                <div class="rs-form-group rs-mb-0" style="overflow: visible !important; position: relative;">
                    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-2" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 8px;">
                        <label class="rs-label rs-m-0" style="font-weight: 600; margin: 0; color: #334155; font-size: 14px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2" style="vertical-align: -2px; margin-right: 4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            Target Individual Locations (Optional)
                        </label>
                        <div class="rs-d-flex rs-align-items-center rs-gap-2" style="display: flex; align-items: center; gap: 8px;">
                            <select id="platform-filter-select" class="rs-select rs-select--sm" style="width: auto; min-width: 140px; padding: 4px 8px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;">
                                <option value="all">All Platforms (<?= $totalDestinations ?>)</option>
                                <option value="google">Google (<?= count($googleItems) ?>)</option>
                                <option value="facebook">Facebook (<?= count($facebookItems) ?>)</option>
                                <option value="instagram">Instagram (<?= count($instagramItems) ?>)</option>
                            </select>

                            <button type="button" id="btn-select-all-dest" class="rs-btn rs-btn--secondary rs-btn--sm" style="padding: 4px 10px; font-size: 12px; font-weight: 600;">Select All</button>
                            <button type="button" id="btn-deselect-all-dest" class="rs-btn rs-btn--secondary rs-btn--sm" style="padding: 4px 10px; font-size: 12px; font-weight: 600;">Deselect All</button>
                        </div>
                    </div>

                    <?php if ($totalDestinations === 0): ?>
                        <div class="rs-text-muted rs-p-3" style="background-color: var(--rs-bg-app, #f8fafc); border-radius: var(--rs-radius-md, 6px); padding: 14px; text-align: center; color: #64748b;">
                            No connected social destinations available.
                        </div>
                    <?php else: ?>
                        <!-- Destinations Multi-Select Component -->
                        <div class="rs-dest-multiselect" id="dest-multiselect">
                            <div class="rs-dest-trigger" id="dest-trigger" tabindex="0" role="combobox" aria-expanded="false">
                                <div class="rs-dest-trigger__content" id="dest-trigger-content">
                                    <span class="rs-dest-placeholder" id="dest-placeholder">Search and select individual social destinations...</span>
                                    <div class="rs-dest-chips-container" id="dest-chips-container" style="display: none;"></div>
                                </div>
                                <div class="rs-dest-trigger__actions">
                                    <button type="button" class="rs-dest-clear-btn" id="dest-clear-btn" title="Clear all selections" style="display: none;">&times;</button>
                                    <span class="rs-dest-caret">
                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    </span>
                                </div>
                            </div>

                            <!-- Multi-Select Floating Dropdown Panel -->
                            <div class="rs-dest-dropdown" id="dest-dropdown" style="display: none;">
                                <div class="rs-dest-dropdown__header">
                                    <div class="rs-dest-search-wrap">
                                        <svg class="rs-dest-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                        <input type="text" id="dest-search-input" class="rs-dest-search-input" placeholder="Search destinations by name or account..." autocomplete="off">
                                        <button type="button" id="dest-search-clear" class="rs-dest-search-clear" style="display: none;">&times;</button>
                                    </div>
                                    <div class="rs-dest-quick-actions">
                                        <button type="button" id="btn-dropdown-select-all" class="rs-btn-link">Select Visible</button>
                                        <span class="rs-text-muted" style="font-size: 11px;">•</span>
                                        <button type="button" id="btn-dropdown-deselect-all" class="rs-btn-link">Deselect Visible</button>
                                    </div>
                                </div>

                                <div class="rs-dest-dropdown__body" id="dest-dropdown-body">
                                    <!-- Google Platform Group -->
                                    <div class="rs-dest-platform-group" id="group-google" data-platform="google" style="<?= !empty($googleItems) ? '' : 'display:none;' ?>">
                                        <div class="rs-dest-platform-group__header">
                                            <div class="rs-d-flex rs-align-items-center rs-gap-2">
                                                <svg width="15" height="15" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/></svg>
                                                <span class="rs-dest-platform-title">GOOGLE (<span id="count-google"><?= count($googleItems) ?></span>)</span>
                                            </div>
                                            <button type="button" class="rs-btn-link btn-toggle-platform" data-platform="google">Toggle</button>
                                        </div>
                                        <div class="rs-dest-platform-group__items" id="list-google">
                                            <?php foreach ($googleItems as $item): ?>
                                                <div class="rs-dest-item" data-platform="google" data-key="<?= Html::encode($item['key']) ?>" data-label="<?= Html::encode($item['label']) ?>" data-search="<?= Html::encode(strtolower($item['label'] . ' google ' . $item['account_name'])) ?>">
                                                    <label class="rs-dest-item__label">
                                                        <input type="checkbox" name="destinations[]" value="<?= Html::encode($item['key']) ?>" class="rs-checkbox dest-checkbox dest-google" data-label="<?= Html::encode($item['label']) ?>" data-platform="google">
                                                        <span class="rs-dest-item__name"><?= Html::encode($item['label']) ?></span>
                                                        <?php if (!empty($item['account_name'])): ?>
                                                            <span class="rs-text-xs rs-text-muted" style="margin-left: 4px; font-size: 11px; color: #64748b;">(<?= Html::encode($item['account_name']) ?>)</span>
                                                        <?php endif; ?>
                                                        <span class="rs-dest-item__badge rs-dest-item__badge--google">Google</span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Facebook Platform Group -->
                                    <div class="rs-dest-platform-group" id="group-facebook" data-platform="facebook" style="<?= !empty($facebookItems) ? '' : 'display:none;' ?>">
                                        <div class="rs-dest-platform-group__header">
                                            <div class="rs-d-flex rs-align-items-center rs-gap-2">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                                <span class="rs-dest-platform-title">FACEBOOK (<span id="count-facebook"><?= count($facebookItems) ?></span>)</span>
                                            </div>
                                            <button type="button" class="rs-btn-link btn-toggle-platform" data-platform="facebook">Toggle</button>
                                        </div>
                                        <div class="rs-dest-platform-group__items" id="list-facebook">
                                            <?php foreach ($facebookItems as $item): ?>
                                                <div class="rs-dest-item" data-platform="facebook" data-key="<?= Html::encode($item['key']) ?>" data-label="<?= Html::encode($item['label']) ?>" data-search="<?= Html::encode(strtolower($item['label'] . ' facebook ' . $item['account_name'])) ?>">
                                                    <label class="rs-dest-item__label">
                                                        <input type="checkbox" name="destinations[]" value="<?= Html::encode($item['key']) ?>" class="rs-checkbox dest-checkbox dest-facebook" data-label="<?= Html::encode($item['label']) ?>" data-platform="facebook">
                                                        <span class="rs-dest-item__name"><?= Html::encode($item['label']) ?></span>
                                                        <?php if (!empty($item['account_name'])): ?>
                                                            <span class="rs-text-xs rs-text-muted" style="margin-left: 4px; font-size: 11px; color: #64748b;">(<?= Html::encode($item['account_name']) ?>)</span>
                                                        <?php endif; ?>
                                                        <span class="rs-dest-item__badge rs-dest-item__badge--facebook">Facebook</span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Instagram Platform Group -->
                                    <div class="rs-dest-platform-group" id="group-instagram" data-platform="instagram" style="<?= !empty($instagramItems) ? '' : 'display:none;' ?>">
                                        <div class="rs-dest-platform-group__header">
                                            <div class="rs-d-flex rs-align-items-center rs-gap-2">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="#E1306C"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                                <span class="rs-dest-platform-title">INSTAGRAM (<span id="count-instagram"><?= count($instagramItems) ?></span>)</span>
                                            </div>
                                            <button type="button" class="rs-btn-link btn-toggle-platform" data-platform="instagram">Toggle</button>
                                        </div>
                                        <div class="rs-dest-platform-group__items" id="list-instagram">
                                            <?php foreach ($instagramItems as $item): ?>
                                                <div class="rs-dest-item" data-platform="instagram" data-key="<?= Html::encode($item['key']) ?>" data-label="<?= Html::encode($item['label']) ?>" data-search="<?= Html::encode(strtolower($item['label'] . ' instagram ' . $item['account_name'])) ?>">
                                                    <label class="rs-dest-item__label">
                                                        <input type="checkbox" name="destinations[]" value="<?= Html::encode($item['key']) ?>" class="rs-checkbox dest-checkbox dest-instagram" data-label="<?= Html::encode($item['label']) ?>" data-platform="instagram">
                                                        <span class="rs-dest-item__name"><?= Html::encode($item['label']) ?></span>
                                                        <?php if (!empty($item['account_name'])): ?>
                                                            <span class="rs-text-xs rs-text-muted" style="margin-left: 4px; font-size: 11px; color: #64748b;">(<?= Html::encode($item['account_name']) ?>)</span>
                                                        <?php endif; ?>
                                                        <span class="rs-dest-item__badge rs-dest-item__badge--instagram">Instagram</span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div id="dest-no-search-results" class="rs-p-4 rs-text-center rs-text-muted" style="display: none; padding: 1.5rem; text-align: center; color: #64748b;">
                                        <p class="rs-text-sm rs-m-0" style="margin: 0; font-size: 13px;">No destinations match your search or filter.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Live Target Summary Display -->
                <div id="target-selection-live-summary" class="rs-p-3 rs-mt-4" style="background-color: var(--rs-bg-app, #f8fafc); border: 1px solid var(--rs-border, #e2e8f0); border-radius: 6px; font-size: 13px; padding: 12px; margin-top: 14px;">
                    <div class="rs-d-flex rs-align-items-center rs-gap-2" style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <span id="target-summary-text" style="font-weight: 600; color: #1e293b;">
                            No locations currently selected.
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Card 3: Target Social Platforms -->
        <div class="rs-card rs-mb-4" style="background: #fff; border: 1px solid var(--rs-border, #e2e8f0); border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
                <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Target Social Platforms</h3>
            </div>
            <div class="rs-card__body" style="padding: 20px;">
                <div class="rs-platform-checkbox-group">
                    <div class="rs-checkbox-wrap rs-mb-3 rs-p-2" style="margin-bottom: 0.75rem; padding: 0.5rem; border-radius: 6px; transition: background 0.15s ease;">
                        <label class="rs-checkbox-wrap" style="font-weight: 600; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center;">
                            <input type="checkbox" name="platforms[]" value="facebook" class="rs-checkbox platform-filter-chk" checked style="margin-right: 8px; transform: scale(1.15);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2" style="margin-right:8px;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Facebook Page
                        </label>
                    </div>
                    <div class="rs-checkbox-wrap rs-mb-3 rs-p-2" style="margin-bottom: 0.75rem; padding: 0.5rem; border-radius: 6px; transition: background 0.15s ease;">
                        <label class="rs-checkbox-wrap" style="font-weight: 600; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center;">
                            <input type="checkbox" name="platforms[]" value="instagram" class="rs-checkbox platform-filter-chk" checked style="margin-right: 8px; transform: scale(1.15);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="margin-right:8px;"><defs><linearGradient id="ig-grad-create" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" stop-color="#fdf497"/><stop offset="5%" stop-color="#fdf497"/><stop offset="45%" stop-color="#fd5949"/><stop offset="60%" stop-color="#d6249f"/><stop offset="90%" stop-color="#285AEB"/></linearGradient></defs><rect width="24" height="24" rx="6.5" fill="url(#ig-grad-create)"/><rect x="4.75" y="4.75" width="14.5" height="14.5" rx="3.75" stroke="#ffffff" stroke-width="1.6"/><circle cx="12" cy="12" r="3.25" stroke="#ffffff" stroke-width="1.6"/><circle cx="16.25" cy="7.75" r="1" fill="#ffffff"/></svg>
                            Instagram Business
                        </label>
                    </div>
                    <div class="rs-checkbox-wrap rs-mb-3 rs-p-2" style="margin-bottom: 0.75rem; padding: 0.5rem; border-radius: 6px; transition: background 0.15s ease;">
                        <label class="rs-checkbox-wrap" style="font-weight: 600; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center;">
                            <input type="checkbox" name="platforms[]" value="google" class="rs-checkbox platform-filter-chk" checked style="margin-right: 8px; transform: scale(1.15);">
                            <svg width="20" height="20" viewBox="0 0 24 24" style="margin-right:8px;"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/></svg>
                            Google Business Profile
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Publishing Mode & Modern Date/Time Picker -->
        <div class="rs-card rs-mb-5" style="background: #fff; border: 1px solid var(--rs-border, #e2e8f0); border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
                <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Publishing Mode &amp; Schedule</h3>
            </div>
            <div class="rs-card__body" style="padding: 20px;">
                <div class="rs-form-group rs-mb-4" style="margin-bottom: 1rem;">
                    <div class="rs-mb-2" style="margin-bottom: 0.5rem;">
                        <label class="rs-checkbox-wrap" style="font-weight: 600; cursor: pointer; display: inline-flex; align-items: center;">
                            <input type="radio" name="post_type" value="immediate" checked class="publishing-mode-radio" style="margin-right: 6px;">
                            Publish Immediately
                        </label>
                    </div>
                    <div class="rs-mb-2">
                        <label class="rs-checkbox-wrap" style="font-weight: 600; cursor: pointer; display: inline-flex; align-items: center;">
                            <input type="radio" name="post_type" value="scheduled" class="publishing-mode-radio" style="margin-right: 6px;">
                            Schedule for Later
                        </label>
                    </div>
                </div>

                <!-- Schedule Picker Trigger & Summary Container -->
                <div id="schedule-picker-container" class="rs-form-group rs-mb-0" style="display:none; margin-top: 1rem;">
                    <div class="rs-mb-3" style="margin-bottom: 0.75rem;">
                        <button type="button" id="btn-open-schedule-picker" class="rs-btn rs-btn--primary" style="display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span id="schedule-trigger-label">Select Date &amp; Time</span>
                        </button>
                    </div>

                    <input type="hidden" name="scheduled_at" id="socialpostform-scheduled_at" value="">

                    <div id="schedule-summary-card" class="rs-p-3" style="background-color: var(--rs-bg-app, #f8fafc); border: 1px solid var(--rs-border, #e2e8f0); border-radius: 6px; padding: 12px;">
                        <div class="rs-d-flex rs-align-items-center rs-gap-3" style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #eff6ff; color: #1d4ed8; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <div>
                                <div id="schedule-summary-headline" style="font-weight: 700; color: #1e293b; font-size: 14px;">
                                    No schedule selected
                                </div>
                                <div id="schedule-summary-details" class="rs-text-sm rs-text-muted" style="font-size: 12px; color: #64748b;">
                                    Timezone: Australia/Perth &bull; Window: Today + Next 7 Days
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-6" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <button type="button" class="rs-btn rs-btn--secondary btn-preview-post-footer" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                Preview Post
            </button>
            <button type="submit" class="rs-btn rs-btn--primary rs-btn--lg" id="btn-submit-social-post" style="padding: 10px 24px; font-size: 16px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                Submit Social Post
            </button>
        </div>
    </form>
</div>

<!-- Modern Date & Time Picker Modal -->
<div id="rs-schedule-modal" class="rs-modal-backdrop" aria-hidden="true" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1060; align-items: center; justify-content: center;">
    <div class="rs-modal" style="max-width: 520px; width: 100%; background: #fff; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden;" role="dialog" aria-modal="true">
        <div class="rs-modal__header" style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="schedule-modal-title" class="rs-modal__title" style="margin: 0; font-size: 16px; font-weight: 600;">Select Publishing Date &amp; Time</h3>
            <button type="button" class="rs-modal__close" id="btn-close-schedule-modal" aria-label="Close" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="rs-modal__body" style="padding: 20px;">
            <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-3" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <button type="button" class="rs-btn rs-btn--secondary rs-btn--sm" id="cal-prev-month" style="padding: 4px 10px; font-weight: bold;">&laquo;</button>
                <div id="cal-month-year-label" style="font-weight: 700; font-size: 16px; color: #1e293b;"></div>
                <button type="button" class="rs-btn rs-btn--secondary rs-btn--sm" id="cal-next-month" style="padding: 4px 10px; font-weight: bold;">&raquo;</button>
            </div>

            <div class="rs-calendar-grid rs-mb-3" style="display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: 600; font-size: 12px; color: #64748b; margin-bottom: 8px;">
                <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
            </div>

            <div id="calendar-days-container" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; margin-bottom: 20px;"></div>

            <div class="rs-p-3" style="background-color: var(--rs-bg-app, #f8fafc); border-radius: 6px; border: 1px solid var(--rs-border, #e2e8f0); padding: 12px;">
                <label class="rs-label" style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block; color: #334155;">Select Time (Hour : Minute AM/PM)</label>
                <div class="rs-d-flex rs-align-items-center rs-gap-2 rs-justify-content-center" style="display: flex; align-items: center; gap: 8px; justify-content: center;">
                    <select id="time-hour-select" class="rs-select" style="width: 85px; text-align: center; font-weight: 600; font-size: 16px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;"></select>
                    <span style="font-weight: 700; font-size: 18px;">:</span>
                    <select id="time-minute-select" class="rs-select" style="width: 85px; text-align: center; font-weight: 600; font-size: 16px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;"></select>
                    <select id="time-ampm-select" class="rs-select" style="width: 85px; text-align: center; font-weight: 600; font-size: 16px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; background: #fff;">
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>
                <div id="time-past-warning" style="display: none; margin-top: 8px; font-size: 12px; color: #dc2626; font-weight: 600; text-align: center;">
                    Selected time has already passed today. Please choose a future time.
                </div>
            </div>

            <div class="rs-mt-3 rs-text-sm rs-text-muted rs-text-center" style="margin-top: 12px; font-size: 12px; color: #64748b; text-align: center;">
                Timezone: Australia/Perth &bull; Allowed Window: Today + Next 7 Days
            </div>
        </div>
        <div class="rs-modal__footer" style="padding: 12px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="rs-btn rs-btn--secondary" id="btn-cancel-schedule">Cancel</button>
            <button type="button" class="rs-btn rs-btn--primary" id="btn-confirm-schedule">Confirm Schedule</button>
        </div>
    </div>
</div>

<!-- Social Post Preview Modal -->
<div id="rs-social-preview-modal" class="rs-modal-backdrop" aria-hidden="true" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1060; align-items: center; justify-content: center;">
    <div class="rs-modal rs-modal--lg" style="max-width: 620px; width: 100%; background: #fff; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15); overflow: hidden;" role="dialog" aria-modal="true">
        <div class="rs-modal__header" style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="preview-modal-title" class="rs-modal__title" style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Social Post Preview</h3>
            <button type="button" class="rs-modal__close" id="btn-close-preview-modal" aria-label="Close" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="rs-modal__body" style="padding: 20px;">
            <div id="preview-tabs" class="rs-tabs" style="display: flex; gap: 6px; border-bottom: 2px solid #e2e8f0; margin-bottom: 16px;"></div>
            <div id="preview-alert-banner" style="display:none; margin-bottom: 14px;"></div>
            <div id="preview-viewport" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; padding: 16px;"></div>
        </div>
        <div class="rs-modal__footer" style="padding: 12px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
            <button type="button" class="rs-btn rs-btn--secondary" id="btn-close-preview-modal-footer">Close Preview</button>
        </div>
    </div>
</div>

<style>
/* Destination Multi-Select & Floating Dropdown */
#target-locations-card { overflow: visible !important; }
#target-locations-card .rs-card__body { overflow: visible !important; position: relative !important; }
.rs-dest-multiselect { position: relative; width: 100%; }
.rs-dest-trigger {
    min-height: 44px; padding: 6px 12px; background-color: #fff;
    border: 1px solid var(--rs-border, #cbd5e1); border-radius: 6px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; cursor: pointer; user-select: none; box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.rs-dest-trigger:hover, .rs-dest-multiselect.is-open .rs-dest-trigger {
    border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.rs-dest-trigger__content { flex: 1; display: flex; align-items: center; flex-wrap: wrap; gap: 6px; min-width: 0; }
.rs-dest-placeholder { color: #94a3b8; font-size: 14px; }
.rs-dest-chips-container { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; max-height: 80px; overflow-y: auto; }
.rs-dest-chip {
    display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px;
    font-size: 12px; font-weight: 500; border-radius: 4px; background: #f8fafc;
    border: 1px solid #e2e8f0; color: #1e293b; line-height: 1.4;
}
.rs-dest-chip--google { border-left: 3px solid #4285F4; }
.rs-dest-chip--facebook { border-left: 3px solid #1877F2; }
.rs-dest-chip--instagram { border-left: 3px solid #E1306C; }
.rs-dest-chip__platform { font-weight: 700; font-size: 11px; text-transform: uppercase; color: #64748b; }
.rs-dest-chip__name { max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rs-dest-chip__remove {
    background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 14px;
    line-height: 1; padding: 0 0 0 2px; font-weight: 700;
}
.rs-dest-chip__remove:hover { color: #ef4444; }
.rs-dest-chip-more {
    display: inline-flex; align-items: center; padding: 2px 8px; font-size: 12px; font-weight: 600;
    border-radius: 4px; background-color: #eff6ff; color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2);
}
.rs-dest-trigger__actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.rs-dest-clear-btn { background: none; border: none; color: #94a3b8; font-size: 18px; line-height: 1; cursor: pointer; padding: 0 4px; }
.rs-dest-clear-btn:hover { color: #ef4444; }
.rs-dest-caret { color: #94a3b8; transition: transform 0.15s ease; display: flex; align-items: center; }
.rs-dest-multiselect.is-open .rs-dest-caret { transform: rotate(180deg); }
.rs-dest-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1px solid #cbd5e1; border-radius: 6px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2), 0 8px 10px -6px rgba(0,0,0,0.1);
    z-index: 1050; display: flex; flex-direction: column; max-height: 440px; box-sizing: border-box;
}
.rs-dest-dropdown__header {
    padding: 10px 14px; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-shrink: 0;
}
.rs-dest-search-wrap { position: relative; flex: 1; }
.rs-dest-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.rs-dest-search-input {
    width: 100%; height: 36px; padding: 6px 30px 6px 32px; font-size: 13px;
    background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; color: #1e293b; box-sizing: border-box;
}
.rs-dest-search-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15); }
.rs-dest-search-clear { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; font-size: 16px; cursor: pointer; line-height: 1; padding: 0 4px; }
.rs-dest-quick-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.rs-btn-link { background: none; border: none; padding: 0; font-size: 12px; font-weight: 600; color: #3b82f6; cursor: pointer; text-decoration: none; }
.rs-btn-link:hover { text-decoration: underline; }
.rs-dest-dropdown__body { overflow-y: auto; flex: 1; max-height: 360px; padding: 6px 0; }
.rs-dest-platform-group { margin-bottom: 6px; }
.rs-dest-platform-group__header {
    padding: 6px 14px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: space-between;
}
.rs-dest-platform-title { font-size: 12px; font-weight: 700; letter-spacing: 0.5px; color: #334155; }
.rs-dest-item { padding: 7px 14px; display: flex; align-items: center; transition: background-color 0.1s ease; cursor: pointer; }
.rs-dest-item:hover { background-color: #f1f5f9; }
.rs-dest-item--selected { background-color: #f0fdf4; }
.rs-dest-item__label { display: flex; align-items: center; gap: 10px; width: 100%; margin: 0; cursor: pointer; font-weight: 500; font-size: 13px; color: #1e293b; }
.rs-dest-item__name { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rs-dest-item__badge { font-size: 10px; font-weight: 600; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; flex-shrink: 0; }
.rs-dest-item__badge--google { background-color: #E8F0FE; color: #1A73E8; }
.rs-dest-item__badge--facebook { background-color: #E7F3FF; color: #1877F2; }
.rs-dest-item__badge--instagram { background-color: #FCE8F3; color: #E1306C; }

/* Group Badge Pill */
.rs-group-badge {
    display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px;
    background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 9999px;
    font-size: 12px; color: #1e40af; font-weight: 600;
}
.rs-group-badge__remove {
    background: none; border: none; font-size: 14px; line-height: 1;
    color: #3b82f6; cursor: pointer; padding: 0; font-weight: bold;
}
.rs-group-badge__remove:hover { color: #dc2626; }

/* Spin animation */
@keyframes rs-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.rs-spin { display: inline-block; animation: rs-spin 1s infinite linear; }
</style>

<script>
window.GROUP_MAP = <?= json_encode($groupData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

(function($) {
    if (!$) return;

    const MAX_DISPLAY_CHIPS = 8;
    const PLATFORM_LIMITS = {
        facebook: 63206,
        instagram: 2200,
        google: 1500
    };

    let userManuallyCheckedKeys = new Set();
    let currentPreviewTab = 'facebook';
    let previewMediaSrc = null;
    let previewMediaType = 'none';

    // 1. Group Badges & Synchronization
    function updateGroupBadgesAndBreakdown() {
        const selectEl = $('#target-groups-select2');
        if (!selectEl.length) return;

        const selectedGroupIds = selectEl.val() || [];
        const badgesContainer = $('#selected-groups-badges');
        const badgesWrapper = $('#selected-groups-badges-container');
        const breakdownCard = $('#selected-groups-breakdown-card');
        const breakdownRows = $('#breakdown-table-rows');
        const breakdownTitle = $('#breakdown-header-title');

        badgesContainer.empty();
        breakdownRows.empty();

        if (selectedGroupIds.length === 0) {
            badgesWrapper.hide();
            breakdownCard.hide();
            syncDestinationsFromGroups(new Set());
            return;
        }

        badgesWrapper.show();
        breakdownCard.show();

        const combinedGroupKeys = new Set();
        const uniqueDestinations = new Map();

        selectedGroupIds.forEach(function(gId) {
            const groupInfo = window.GROUP_MAP[gId];
            if (!groupInfo) return;

            // Render Badge
            const badgeHtml = '<span class="rs-group-badge" data-group-id="' + gId + '">'
                + '  <span>' + $('<div>').text(groupInfo.name).html() + '</span>'
                + '  <span style="font-size: 11px; opacity: 0.85;">(' + groupInfo.count + ' destinations)</span>'
                + '  <button type="button" class="rs-group-badge__remove" data-group-id="' + gId + '" title="Remove group">&times;</button>'
                + '</span>';
            badgesContainer.append(badgeHtml);

            // Collect member destinations
            (groupInfo.destinations || []).forEach(function(dest) {
                combinedGroupKeys.add(dest.key);
                if (!uniqueDestinations.has(dest.key)) {
                    uniqueDestinations.set(dest.key, {
                        key: dest.key,
                        platform: dest.platform,
                        label: dest.label,
                        account_name: dest.account_name,
                        groups: [groupInfo.name]
                    });
                } else {
                    uniqueDestinations.get(dest.key).groups.push(groupInfo.name);
                }
            });
        });

        // Render Breakdown Rows
        breakdownTitle.text('Destinations in Selected Groups (' + uniqueDestinations.size + ' unique destinations across ' + selectedGroupIds.length + ' groups)');
        if (uniqueDestinations.size === 0) {
            breakdownRows.append('<tr><td colspan="4" style="padding: 12px; text-align: center; color: #64748b;">No destinations currently assigned to the selected group(s).</td></tr>');
        } else {
            uniqueDestinations.forEach(function(d) {
                const platBadge = '<span class="rs-dest-item__badge rs-dest-item__badge--' + d.platform + '">' + d.platform.toUpperCase() + '</span>';
                const rowHtml = '<tr style="border-bottom: 1px solid #f1f5f9;">'
                    + '  <td style="padding: 6px 4px;">' + platBadge + '</td>'
                    + '  <td style="padding: 6px 4px; font-weight: 500;">' + $('<div>').text(d.label).html() + '</td>'
                    + '  <td style="padding: 6px 4px; color: #64748b;">' + $('<div>').text(d.account_name || '—').html() + '</td>'
                    + '  <td style="padding: 6px 4px; color: #1e40af; font-size: 11px;">' + $('<div>').text(d.groups.join(', ')).html() + '</td>'
                    + '</tr>';
                breakdownRows.append(rowHtml);
            });
        }

        // Synchronize member destinations to checkboxes
        syncDestinationsFromGroups(combinedGroupKeys);
    }

    function syncDestinationsFromGroups(groupKeys) {
        $('.dest-checkbox').each(function() {
            const key = $(this).val();
            const shouldBeChecked = groupKeys.has(key) || userManuallyCheckedKeys.has(key);
            $(this).prop('checked', shouldBeChecked);
            $(this).closest('.rs-dest-item').toggleClass('rs-dest-item--selected', shouldBeChecked);
        });
        updateCounterAndChips();
    }

    // Toggle Breakdown Details
    $(document).on('click', '#btn-toggle-breakdown', function(e) {
        e.preventDefault();
        const body = $('#selected-groups-breakdown-body');
        if (body.is(':visible')) {
            body.slideUp(150);
            $(this).text('Show List');
        } else {
            body.slideDown(150);
            $(this).text('Hide List');
        }
    });

    // Remove Group via Badge
    $(document).on('click', '.rs-group-badge__remove', function(e) {
        e.preventDefault();
        const gId = String($(this).data('group-id'));
        const selectEl = $('#target-groups-select2');
        let currentVals = selectEl.val() || [];
        currentVals = currentVals.filter(id => String(id) !== gId);
        selectEl.val(currentVals).trigger('change');
    });

    // 2. Destination Chips & Counters
    function updateCounterAndChips() {
        const checkedBoxes = $('.dest-checkbox:checked');
        const checkedCount = checkedBoxes.length;

        const placeholder = $('#dest-placeholder');
        const chipsContainer = $('#dest-chips-container');
        const clearBtn = $('#dest-clear-btn');

        chipsContainer.empty();

        if (checkedCount === 0) {
            placeholder.show();
            chipsContainer.hide();
            clearBtn.hide();
        } else {
            placeholder.hide();
            chipsContainer.show();
            clearBtn.show();

            let displayedCount = 0;
            checkedBoxes.each(function() {
                if (displayedCount < MAX_DISPLAY_CHIPS) {
                    const key = $(this).val();
                    const label = $(this).data('label') || $(this).next('.rs-dest-item__name').text();
                    const platform = $(this).data('platform') || 'google';

                    const safeLabel = $('<div>').text(label).html();
                    const platformDisplay = platform.charAt(0).toUpperCase() + platform.slice(1);

                    const chipHtml = '<span class="rs-dest-chip rs-dest-chip--' + platform + '" data-key="' + key + '">'
                                   + '  <span class="rs-dest-chip__platform">' + platformDisplay + ':</span>'
                                   + '  <span class="rs-dest-chip__name">' + safeLabel + '</span>'
                                   + '  <button type="button" class="rs-dest-chip__remove" data-key="' + key + '" title="Remove">&times;</button>'
                                   + '</span>';
                    chipsContainer.append(chipHtml);
                }
                displayedCount++;
            });

            if (checkedCount > MAX_DISPLAY_CHIPS) {
                const remaining = checkedCount - MAX_DISPLAY_CHIPS;
                chipsContainer.append('<span class="rs-dest-chip-more">+' + remaining + ' more</span>');
            }
        }

        updateTargetSummary();
        checkGoogleVideoWarning();
    }

    function updateTargetSummary() {
        const directCount = $('.dest-checkbox:checked').length;
        const groupCount = ($('#target-groups-select2').val() || []).length;

        if (directCount === 0 && groupCount === 0) {
            $('#target-summary-text').text('No locations or groups currently selected.');
            $('#target-selection-live-summary').css('border-color', 'var(--rs-border, #e2e8f0)');
        } else {
            const parts = [];
            if (groupCount > 0) {
                parts.push(groupCount + ' location group' + (groupCount === 1 ? '' : 's'));
            }
            if (directCount > 0) {
                parts.push(directCount + ' individual destination' + (directCount === 1 ? '' : 's'));
            }
            $('#target-summary-text').text('Targeting ' + parts.join(' + ') + ' (' + directCount + ' total destination' + (directCount === 1 ? '' : 's') + ')');
            $('#target-selection-live-summary').css('border-color', '#1d4ed8');
        }
    }

    // 3. Dropdown Search & Filter
    function applySearchAndFilter() {
        const query = ($('#dest-search-input').val() || '').toLowerCase().trim();
        const selectedPlatform = $('#platform-filter-select').val() || 'all';
        let totalVisible = 0;

        ['google', 'facebook', 'instagram'].forEach(function(plat) {
            const group = $('#group-' + plat);
            if (group.length === 0) return;

            if (selectedPlatform !== 'all' && selectedPlatform !== plat) {
                group.hide();
                return;
            }

            const items = group.find('.rs-dest-item');
            let platVisible = 0;

            items.each(function() {
                const searchStr = $(this).attr('data-search') || '';
                if (query === '' || searchStr.indexOf(query) !== -1) {
                    $(this).show();
                    platVisible++;
                    totalVisible++;
                } else {
                    $(this).hide();
                }
            });

            if (items.length > 0) {
                group.toggle(platVisible > 0);
            }
        });

        $('#dest-no-search-results').toggle(totalVisible === 0);
    }

    // Dropdown toggle
    $('#dest-trigger').on('click', function(e) {
        if ($(e.target).closest('.rs-dest-chip__remove, .rs-dest-clear-btn').length > 0) {
            return;
        }
        const multiselect = $('#dest-multiselect');
        const dropdown = $('#dest-dropdown');
        const isOpen = multiselect.hasClass('is-open');

        if (isOpen) {
            multiselect.removeClass('is-open');
            dropdown.hide();
            $(this).attr('aria-expanded', 'false');
        } else {
            multiselect.addClass('is-open');
            dropdown.show();
            $(this).attr('aria-expanded', 'true');
            setTimeout(function() {
                $('#dest-search-input').focus();
            }, 50);
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#dest-multiselect').length) {
            $('#dest-multiselect').removeClass('is-open');
            $('#dest-dropdown').hide();
            $('#dest-trigger').attr('aria-expanded', 'false');
        }
    });

    // Remove Chip
    $(document).on('click', '.rs-dest-chip__remove', function(e) {
        e.stopPropagation();
        const key = $(this).data('key');
        userManuallyCheckedKeys.delete(key);
        const cb = $('.dest-checkbox[value="' + key + '"]');
        cb.prop('checked', false);
        cb.closest('.rs-dest-item').removeClass('rs-dest-item--selected');
        updateCounterAndChips();
    });

    // Clear All Trigger
    $('#dest-clear-btn').on('click', function(e) {
        e.stopPropagation();
        userManuallyCheckedKeys.clear();
        $('.dest-checkbox').prop('checked', false);
        $('.rs-dest-item').removeClass('rs-dest-item--selected');
        updateCounterAndChips();
    });

    // Manual Checkbox Change
    $(document).on('change', '.dest-checkbox', function() {
        const isChecked = $(this).is(':checked');
        const key = $(this).val();
        if (isChecked) {
            userManuallyCheckedKeys.add(key);
        } else {
            userManuallyCheckedKeys.delete(key);
        }
        $(this).closest('.rs-dest-item').toggleClass('rs-dest-item--selected', isChecked);
        updateCounterAndChips();
    });

    // Search and Platform Filter
    $('#dest-search-input').on('input keyup', function() {
        const q = $(this).val();
        $('#dest-search-clear').toggle(q.length > 0);
        applySearchAndFilter();
    });

    $('#dest-search-clear').on('click', function() {
        $('#dest-search-input').val('').focus();
        $(this).hide();
        applySearchAndFilter();
    });

    $('#platform-filter-select').on('change', function() {
        applySearchAndFilter();
    });

    // Select/Deselect visible
    $('#btn-select-all-dest, #btn-dropdown-select-all').on('click', function(e) {
        e.preventDefault();
        $('.rs-dest-item:visible .dest-checkbox').each(function() {
            $(this).prop('checked', true);
            userManuallyCheckedKeys.add($(this).val());
            $(this).closest('.rs-dest-item').addClass('rs-dest-item--selected');
        });
        updateCounterAndChips();
    });

    $('#btn-deselect-all-dest, #btn-dropdown-deselect-all').on('click', function(e) {
        e.preventDefault();
        $('.rs-dest-item:visible .dest-checkbox').each(function() {
            $(this).prop('checked', false);
            userManuallyCheckedKeys.delete($(this).val());
            $(this).closest('.rs-dest-item').removeClass('rs-dest-item--selected');
        });
        updateCounterAndChips();
    });

    // Platform Group Toggle
    $(document).on('click', '.btn-toggle-platform', function(e) {
        e.preventDefault();
        const plat = $(this).data('platform');
        const cbs = $('#list-' + plat + ' .dest-checkbox');
        const allChecked = cbs.length > 0 && cbs.filter(':checked').length === cbs.length;
        cbs.each(function() {
            $(this).prop('checked', !allChecked);
            if (!allChecked) {
                userManuallyCheckedKeys.add($(this).val());
            } else {
                userManuallyCheckedKeys.delete($(this).val());
            }
            $(this).closest('.rs-dest-item').toggleClass('rs-dest-item--selected', !allChecked);
        });
        updateCounterAndChips();
    });

    // 4. Character Counter & Platform Limit Updates
    function getCheckedPlatforms() {
        const plats = [];
        $('.platform-filter-chk:checked').each(function() {
            plats.push($(this).val());
        });
        return plats;
    }

    function updateCharacterCounter() {
        const content = $('#post-content-textarea').val() || '';
        const len = content.length;
        const plats = getCheckedPlatforms();

        let minLimit = PLATFORM_LIMITS.facebook;
        if (plats.length > 0) {
            plats.forEach(p => {
                if (PLATFORM_LIMITS[p] && PLATFORM_LIMITS[p] < minLimit) {
                    minLimit = PLATFORM_LIMITS[p];
                }
            });
        }

        $('#char-counter-display').text(len.toLocaleString() + ' / ' + minLimit.toLocaleString() + ' characters');

        if (plats.length > 0) {
            const names = plats.map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(' + ');
            $('#platform-limit-note').text('Selected: ' + names + ' (Max: ' + minLimit.toLocaleString() + ')');
        } else {
            $('#platform-limit-note').text('No platform selected');
        }

        if (len > minLimit) {
            $('#char-counter-display').css('color', '#dc2626');
        } else if (len > minLimit * 0.85) {
            $('#char-counter-display').css('color', '#d97706');
        } else {
            $('#char-counter-display').css('color', '#475569');
        }
    }

    function checkGoogleVideoWarning() {
        const isVideo = previewMediaType === 'video';
        const hasGooglePlatform = $('.platform-filter-chk[value="google"]:checked').length > 0;
        const hasGoogleDest = $('.dest-google:checked').length > 0;
        if (isVideo && (hasGooglePlatform || hasGoogleDest)) {
            $('#google-video-warning').slideDown(150);
        } else {
            $('#google-video-warning').slideUp(150);
        }
    }

    $('#post-content-textarea').on('input keyup change', updateCharacterCounter);
    $('.platform-filter-chk').on('change', function() {
        updateCharacterCounter();
        checkGoogleVideoWarning();
    });

    // 5. File Upload & Preview Handler
    $('#social-media-file-input').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const name = file.name ? file.name.toLowerCase() : '';
            if (file.type.startsWith('video/') || name.endsWith('.mp4') || name.endsWith('.mov')) {
                previewMediaType = 'video';
            } else {
                previewMediaType = 'image';
            }
            const reader = new FileReader();
            reader.onload = function(evt) {
                previewMediaSrc = evt.target.result;
                checkGoogleVideoWarning();
            };
            reader.readAsDataURL(file);
        } else {
            previewMediaSrc = null;
            previewMediaType = 'none';
            checkGoogleVideoWarning();
        }
    });

    // 6. Publishing Mode Toggle
    $('input[name="post_type"]').on('change', function() {
        if ($(this).val() === 'scheduled') {
            $('#schedule-picker-container').slideDown(200);
        } else {
            $('#schedule-picker-container').slideUp(200);
        }
    });

    // 7. Modern Monthly Calendar & Time Picker
    const now = new Date();
    const minDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const maxDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 7, 23, 59, 59);

    let currentCalYear = now.getFullYear();
    let currentCalMonth = now.getMonth();
    let selectedDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1); // Default Tomorrow
    let selectedHour = 9;
    let selectedMinute = 0;
    let selectedAmPm = 'AM';

    function populateTimeDropdowns() {
        let hourOptions = '';
        for (let h = 1; h <= 12; h++) {
            const val = h < 10 ? '0' + h : '' + h;
            hourOptions += '<option value="' + h + '">' + val + '</option>';
        }
        $('#time-hour-select').html(hourOptions);

        let minOptions = '';
        for (let m = 0; m < 60; m += 5) {
            const val = m < 10 ? '0' + m : '' + m;
            minOptions += '<option value="' + m + '">' + val + '</option>';
        }
        $('#time-minute-select').html(minOptions);
    }

    function formatDateStr(dt) {
        const y = dt.getFullYear();
        const m = String(dt.getMonth() + 1).padStart(2, '0');
        const d = String(dt.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function renderCalendar() {
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        $('#cal-month-year-label').text(monthNames[currentCalMonth] + ' ' + currentCalYear);

        const firstDayIndex = new Date(currentCalYear, currentCalMonth, 1).getDay();
        const totalDaysInMonth = new Date(currentCalYear, currentCalMonth + 1, 0).getDate();

        let gridHtml = '';
        for (let i = 0; i < firstDayIndex; i++) {
            gridHtml += '<div></div>';
        }

        const selStr = formatDateStr(selectedDate);
        const todayStr = formatDateStr(now);

        for (let day = 1; day <= totalDaysInMonth; day++) {
            const cellDate = new Date(currentCalYear, currentCalMonth, day);
            const cellStr = formatDateStr(cellDate);

            const isPast = cellDate < minDate;
            const isBeyond7Days = cellDate > maxDate;
            const isDisabled = isPast || isBeyond7Days;

            let style = 'padding: 8px; border-radius: 6px; font-weight: 600; cursor: pointer; border: 1px solid transparent; font-size: 13px;';
            if (isDisabled) {
                style += ' opacity: 0.35; background-color: #f3f4f6; color: #9ca3af; pointer-events: none;';
            } else if (cellStr === selStr) {
                style += ' background-color: #1d4ed8; color: #ffffff;';
            } else if (cellStr === todayStr) {
                style += ' border-color: #1d4ed8; color: #1d4ed8; background-color: #eff6ff;';
            } else {
                style += ' background-color: #ffffff; border-color: #e5e7eb; color: #1f2937;';
            }

            gridHtml += '<div class="cal-day-cell" data-date="' + cellStr + '" style="' + style + '">' + day + '</div>';
        }

        $('#calendar-days-container').html(gridHtml);
    }

    $(document).on('click', '.cal-day-cell', function() {
        const dStr = $(this).data('date');
        if (dStr) {
            const parts = dStr.split('-');
            selectedDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
            renderCalendar();
            validateTimeSelection();
        }
    });

    $('#cal-prev-month').on('click', function() {
        if (currentCalMonth === 0) {
            currentCalMonth = 11;
            currentCalYear--;
        } else {
            currentCalMonth--;
        }
        renderCalendar();
    });

    $('#cal-next-month').on('click', function() {
        if (currentCalMonth === 11) {
            currentCalMonth = 0;
            currentCalYear++;
        } else {
            currentCalMonth++;
        }
        renderCalendar();
    });

    function getSelected24Hour() {
        let h = parseInt($('#time-hour-select').val() || '9');
        const ampm = $('#time-ampm-select').val();
        if (ampm === 'PM' && h < 12) h += 12;
        if (ampm === 'AM' && h === 12) h = 0;
        return h;
    }

    function validateTimeSelection() {
        const selYear = selectedDate.getFullYear();
        const selMonth = selectedDate.getMonth();
        const selDay = selectedDate.getDate();

        const hour24 = getSelected24Hour();
        const min = parseInt($('#time-minute-select').val() || '0');

        const chosenDateTime = new Date(selYear, selMonth, selDay, hour24, min, 0);

        if (chosenDateTime.getTime() <= Date.now()) {
            $('#time-past-warning').slideDown(150);
            $('#btn-confirm-schedule').prop('disabled', true).css('opacity', '0.6').css('cursor', 'not-allowed');
        } else {
            $('#time-past-warning').slideUp(150);
            $('#btn-confirm-schedule').prop('disabled', false).css('opacity', '1').css('cursor', 'pointer');
        }
    }

    $('#time-hour-select, #time-minute-select, #time-ampm-select').on('change', validateTimeSelection);

    function openScheduleModal() {
        renderCalendar();
        $('#time-hour-select').val(selectedHour);
        $('#time-minute-select').val(selectedMinute);
        $('#time-ampm-select').val(selectedAmPm);
        validateTimeSelection();
        $('#rs-schedule-modal').css('display', 'flex');
    }

    function closeScheduleModal() {
        $('#rs-schedule-modal').hide();
    }

    $('#btn-open-schedule-picker').on('click', openScheduleModal);
    $('#btn-close-schedule-modal, #btn-cancel-schedule').on('click', closeScheduleModal);

    $('#btn-confirm-schedule').on('click', function() {
        const selYear = selectedDate.getFullYear();
        const selMonth = String(selectedDate.getMonth() + 1).padStart(2, '0');
        const selDay = String(selectedDate.getDate()).padStart(2, '0');

        const h24 = getSelected24Hour();
        const hourStr = String(h24).padStart(2, '0');
        const minStr = String($('#time-minute-select').val()).padStart(2, '0');

        const fullTimestamp = selYear + '-' + selMonth + '-' + selDay + ' ' + hourStr + ':' + minStr + ':00';
        $('#socialpostform-scheduled_at').val(fullTimestamp);

        selectedHour = parseInt($('#time-hour-select').val());
        selectedMinute = parseInt($('#time-minute-select').val());
        selectedAmPm = $('#time-ampm-select').val();

        updateScheduleSummaryCard(fullTimestamp);
        closeScheduleModal();
    });

    function updateScheduleSummaryCard(timestampStr) {
        if (timestampStr) {
            const dt = new Date(timestampStr.replace(' ', 'T'));
            if (!isNaN(dt.getTime())) {
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                const formattedHeadline = dt.toLocaleDateString('en-AU', options);

                $('#schedule-trigger-label').text(dt.toLocaleDateString('en-AU', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }));
                $('#schedule-summary-headline').text(formattedHeadline).css('color', '#1e293b');
                $('#schedule-summary-details').text(timestampStr + ' (Australia/Perth)');
                return;
            }
        }
        $('#schedule-trigger-label').text('Select Date & Time');
        $('#schedule-summary-headline').text('No schedule selected');
        $('#schedule-summary-details').text('Timezone: Australia/Perth • Window: Today + Next 7 Days');
    }

    // Initialize Scheduler Default: Tomorrow at 09:00
    populateTimeDropdowns();
    const tmrw = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 9, 0, 0);
    const defaultTimestamp = formatDateStr(tmrw) + ' 09:00:00';
    $('#socialpostform-scheduled_at').val(defaultTimestamp);
    updateScheduleSummaryCard(defaultTimestamp);

    // 8. Social Post Preview Modal
    function getSelectedDestinationName() {
        const firstChecked = $('.dest-checkbox:checked').first();
        if (firstChecked.length > 0) {
            const label = firstChecked.data('label') || firstChecked.closest('.rs-dest-item').data('label');
            if (label) return label.trim();
        }
        const selectedGroupOption = $('#target-groups-select2 option:selected').first();
        if (selectedGroupOption.length > 0 && selectedGroupOption.val()) {
            return selectedGroupOption.text().replace(/\s*\([^)]+\)$/, '').trim();
        }
        return null;
    }

    function renderPreviewTabs(platforms) {
        let tabsHtml = '';
        platforms.forEach(p => {
            const isActive = (p === currentPreviewTab);
            const activeStyle = isActive ? 'border-bottom: 2px solid #1d4ed8; color: #1d4ed8; font-weight: 700;' : 'color: #64748b; font-weight: 500;';
            let label = 'Facebook Page';
            let iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2" style="margin-right:6px;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>';

            if (p === 'instagram') {
                label = 'Instagram Business';
                const gradId = 'ig-prev-' + Math.random().toString(36).substr(2, 5);
                iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="margin-right:6px;"><defs><linearGradient id="' + gradId + '" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" stop-color="#fdf497"/><stop offset="5%" stop-color="#fdf497"/><stop offset="45%" stop-color="#fd5949"/><stop offset="60%" stop-color="#d6249f"/><stop offset="90%" stop-color="#285AEB"/></linearGradient></defs><rect width="24" height="24" rx="6.5" fill="url(#' + gradId + ')"/><rect x="4.75" y="4.75" width="14.5" height="14.5" rx="3.75" stroke="#ffffff" stroke-width="1.6"/><circle cx="12" cy="12" r="3.25" stroke="#ffffff" stroke-width="1.6"/><circle cx="16.25" cy="7.75" r="1" fill="#ffffff"/></svg>';
            } else if (p === 'google') {
                label = 'Google Business';
                iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" style="margin-right:6px;"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/></svg>';
            }

            tabsHtml += '<button type="button" class="btn-preview-tab" data-platform="' + p + '" style="background: none; border: none; padding: 8px 14px; cursor: pointer; display: inline-flex; align-items: center; ' + activeStyle + '">' + iconSvg + label + '</button>';
        });
        $('#preview-tabs').html(tabsHtml);
    }

    $(document).on('click', '.btn-preview-tab', function() {
        currentPreviewTab = $(this).data('platform');
        renderPreviewTabs(getCheckedPlatforms());
        renderPreviewContent();
    });

    function renderPreviewContent() {
        const content = $('#post-content-textarea').val() || '';
        const limit = PLATFORM_LIMITS[currentPreviewTab] || 63206;
        const isOverLimit = content.length > limit;

        let alertHtml = '';
        if (isOverLimit) {
            alertHtml = '<div style="padding:10px 14px; background:#fee2e2; color:#991b1b; border-radius:6px; font-weight:500;">Warning: Content exceeds allowed limit for ' + currentPreviewTab.toUpperCase() + ' (' + content.length.toLocaleString() + ' / ' + limit.toLocaleString() + ' characters)</div>';
        } else if (currentPreviewTab === 'instagram' && !previewMediaSrc) {
            alertHtml = '<div style="padding:10px 14px; background:#fef3c7; color:#92400e; border-radius:6px; font-weight:500;">Instagram Notice: Instagram Business API requires an image or video attachment.</div>';
        } else if (currentPreviewTab === 'google' && previewMediaType === 'video') {
            alertHtml = '<div style="padding:10px 14px; background:#fee2e2; color:#991b1b; border-radius:6px; font-weight:500;">Google Business Profile Notice: Local Posts API only supports images (JPG/PNG). Video uploads are not supported for Google Local Posts.</div>';
        }
        $('#preview-alert-banner').html(alertHtml).toggle(alertHtml !== '');

        let cardHtml = '';
        const safeContent = $('<div>').text(content).html().replace(/\r?\n/g, '<br>');
        const targetName = getSelectedDestinationName();

        if (currentPreviewTab === 'facebook') {
            const pageName = targetName || 'Your Facebook Page';
            cardHtml = '<div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">'
                     + '  <div style="padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9;">'
                     + '    <div style="display: flex; align-items: center; gap: 10px;">'
                     + '      <div style="width: 40px; height: 40px; border-radius: 50%; background: #1877F2; display: flex; align-items: center; justify-content: center; color: white;">'
                     + '        <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'
                     + '      </div>'
                     + '      <div>'
                     + '        <div style="font-weight: 700; font-size: 14px; color: #0f172a;">' + $('<div>').text(pageName).html() + '</div>'
                     + '        <div style="font-size: 11px; color: #64748b;">Just now &bull; 🌐</div>'
                     + '      </div>'
                     + '    </div>'
                     + '  </div>'
                     + '  <div style="padding: 16px; font-size: 14px; color: #1e293b; line-height: 1.5;">' + (safeContent || '<span style="color:#9ca3af; font-style:italic;">Your Facebook post content will appear here...</span>') + '</div>';

            if (previewMediaSrc) {
                cardHtml += '<div style="background: #000; text-align: center; max-height: 320px; overflow: hidden;">';
                if (previewMediaType === 'video') {
                    cardHtml += '<video src="' + previewMediaSrc + '" controls style="max-height: 320px; max-width: 100%;"></video>';
                } else {
                    cardHtml += '<img src="' + previewMediaSrc + '" alt="Post media preview" style="max-height: 320px; max-width: 100%; object-fit: contain;">';
                }
                cardHtml += '</div>';
            }

            cardHtml += '  <div style="padding: 8px 16px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-around; color: #64748b; font-size: 13px; font-weight: 600;">'
                     + '    <span>👍 Like</span><span>💬 Comment</span><span>↗ Share</span>'
                     + '  </div>'
                     + '</div>';
        } else if (currentPreviewTab === 'instagram') {
            const username = targetName ? targetName.toLowerCase().replace(/[^a-z0-9_]/g, '_') : 'your_instagram_business';
            const igAvatarGrad = 'ig-av-g-' + Math.random().toString(36).substr(2, 5);

            cardHtml = '<div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">'
                     + '  <div style="padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9;">'
                     + '    <div style="display: flex; align-items: center; gap: 10px;">'
                     + '      <div style="width: 40px; height: 40px;">'
                     + '        <svg width="40" height="40" viewBox="0 0 24 24" fill="none"><defs><linearGradient id="' + igAvatarGrad + '" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" stop-color="#fdf497"/><stop offset="5%" stop-color="#fdf497"/><stop offset="45%" stop-color="#fd5949"/><stop offset="60%" stop-color="#d6249f"/><stop offset="90%" stop-color="#285AEB"/></linearGradient></defs><rect width="24" height="24" rx="6.5" fill="url(#' + igAvatarGrad + ')"/><rect x="4.75" y="4.75" width="14.5" height="14.5" rx="3.75" stroke="#ffffff" stroke-width="1.6"/><circle cx="12" cy="12" r="3.25" stroke="#ffffff" stroke-width="1.6"/><circle cx="16.25" cy="7.75" r="1" fill="#ffffff"/></svg>'
                     + '      </div>'
                     + '      <div style="font-weight: 700; font-size: 14px; color: #0f172a;">@' + $('<div>').text(username).html() + '</div>'
                     + '    </div>'
                     + '  </div>';

            if (previewMediaSrc) {
                cardHtml += '<div style="background: #000; text-align: center; max-height: 320px; overflow: hidden;">';
                if (previewMediaType === 'video') {
                    cardHtml += '<video src="' + previewMediaSrc + '" controls style="max-height: 320px; max-width: 100%;"></video>';
                } else {
                    cardHtml += '<img src="' + previewMediaSrc + '" alt="Instagram media preview" style="max-height: 320px; max-width: 100%; object-fit: contain;">';
                }
                cardHtml += '</div>';
            } else {
                cardHtml += '<div style="padding: 32px 16px; text-align: center; background: #f8fafc; border-bottom: 1px solid #f1f5f9;">'
                         + '  <div style="font-weight: 700; font-size: 15px; color: #111827;">No Media Attached</div>'
                         + '  <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Instagram Business posts require an image or video attachment.</div>'
                         + '</div>';
            }

            cardHtml += '  <div style="padding: 12px 16px; font-size: 14px; color: #1e293b; line-height: 1.5;">'
                     + '    <strong style="margin-right: 6px;">@' + $('<div>').text(username).html() + '</strong>'
                     +      (safeContent || '<span style="color:#8e8e8e; font-style:italic;">Your caption will appear here...</span>')
                     + '    <div style="font-size: 11px; color: #94a3b8; margin-top: 6px;">Just now</div>'
                     + '  </div>'
                     + '</div>';
        } else if (currentPreviewTab === 'google') {
            const profileName = targetName || 'Google Business Profile';
            cardHtml = '<div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">'
                     + '  <div style="padding: 12px 16px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f1f5f9;">'
                     + '    <div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">'
                     + '      <svg width="24" height="24" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/></svg>'
                     + '    </div>'
                     + '    <div>'
                     + '      <div style="font-weight: 700; font-size: 14px; color: #0f172a;">' + $('<div>').text(profileName).html() + '</div>'
                     + '      <div style="font-size: 11px; color: #64748b;">Local Update &bull; Just now</div>'
                     + '    </div>'
                     + '  </div>'
                     + '  <div style="padding: 16px;">'
                     + '    <div style="font-size: 14px; color: #1e293b; line-height: 1.5; margin-bottom: 12px;">' + (safeContent || '<span style="color:#70757a; font-style:italic;">Your GMB post content will appear here...</span>') + '</div>';

            if (previewMediaSrc) {
                cardHtml += '<div style="margin-bottom: 12px; text-align: center; max-height: 280px; overflow: hidden; background: #000; border-radius: 4px;">';
                if (previewMediaType === 'video') {
                    cardHtml += '<video src="' + previewMediaSrc + '" controls style="max-height: 280px; max-width: 100%;"></video>';
                } else {
                    cardHtml += '<img src="' + previewMediaSrc + '" alt="Google post media preview" style="max-height: 280px; max-width: 100%; object-fit: contain;">';
                }
                cardHtml += '</div>';
            }

            cardHtml += '    <div><button type="button" style="padding: 6px 16px; background: #1a73e8; color: white; border: none; border-radius: 4px; font-weight: 600; font-size: 13px; cursor: default;">Learn More</button></div>'
                     + '  </div>'
                     + '</div>';
        }

        $('#preview-viewport').html(cardHtml);
    }

    function openPreviewModal() {
        const platforms = getCheckedPlatforms();
        if (platforms.length === 0) {
            alert('Please select at least one platform to preview your post.');
            return;
        }
        if (!platforms.includes(currentPreviewTab)) {
            currentPreviewTab = platforms[0];
        }
        renderPreviewTabs(platforms);
        renderPreviewContent();
        $('#rs-social-preview-modal').css('display', 'flex');
    }

    function closePreviewModal() {
        $('#rs-social-preview-modal').hide();
    }

    $('#btn-preview-post, .btn-preview-post-footer').on('click', openPreviewModal);
    $('#btn-close-preview-modal, #btn-close-preview-modal-footer').on('click', closePreviewModal);

    // Escape Key Handler
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            closeScheduleModal();
            closePreviewModal();
            if ($('#dest-multiselect').hasClass('is-open')) {
                $('#dest-multiselect').removeClass('is-open');
                $('#dest-dropdown').hide();
                $('#dest-trigger').attr('aria-expanded', 'false').focus();
            }
        }
    });

    // 9. Submit Loading State & Double-submit Prevention
    $('#social-post-create-form').on('submit', function() {
        const btn = $('#btn-submit-social-post');
        btn.prop('disabled', true).css('opacity', '0.75').css('cursor', 'not-allowed');
        btn.html('<svg class="rs-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg> Submitting Post...');
        return true;
    });

    // 10. Initialization on DOM Ready
    $(document).ready(function() {
        if ($('#target-groups-select2').length) {
            if ($.fn.select2) {
                $('#target-groups-select2').select2({
                    placeholder: 'Search and select location groups...',
                    allowClear: true,
                    width: '100%'
                }).on('change', function() {
                    updateGroupBadgesAndBreakdown();
                });
            } else {
                $('#target-groups-select2').on('change', function() {
                    updateGroupBadgesAndBreakdown();
                });
            }
        }

        updateCharacterCounter();
        updateCounterAndChips();
        checkGoogleVideoWarning();
    });

})(window.jQuery);
</script>
