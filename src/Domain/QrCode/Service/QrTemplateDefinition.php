<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Service;

/**
 * QR Code Template and Frame Definitions.
 * Centralized, immutable definition of all 12 legacy style templates,
 * 16 frame positioning offsets, and generation constraints.
 */
final class QrTemplateDefinition
{
    public const DEFAULT_ERROR_CORRECTION = 'H';
    public const DEFAULT_DOWNLOAD_SIZE = 1000;

    public const LOGO_MIN_WIDTH = 250;
    public const LOGO_MIN_HEIGHT = 250;
    public const LOGO_MAX_WIDTH = 1080;
    public const LOGO_MAX_HEIGHT = 1080;
    public const LOGO_MAX_BYTES = 512_000; // 500 KB

    /**
     * Exact vertical Y-offset adjustments for each of the 16 legacy frame overlays.
     * Horizontal X-offset remains centered (0).
     *
     * @var array<string, int>
     */
    public const FRAME_OFFSETS = [
        'balloon-top' => 150,
        'focus' => -170,
        'balloon-bottom' => -150,
        'box-Top' => 150,
        'box-bottom' => -150,
        'banner-top' => 130,
        'banner-bottom' => -150,
        'balloon-top-2' => 150,
        'focus-8' => -170,
        'balloon-bottom-1' => -150,
        'box-Top-6' => 150,
        'box-bottom-5' => -150,
        'banner-top-4' => 130,
        'banner-bottom-3' => -150,
        'default' => 0,
        'default-7' => 0,
    ];

    /**
     * All 12 pre-configured style templates matching legacy common/config/params.php.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getTemplates(): array
    {
        return [
            'rain' => [
                'body' => 'circle-zebra-vertical',
                'eye' => 'frame13',
                'eyeBall' => 'ball15',
                'erf1' => [],
                'erf2' => [],
                'erf3' => [],
                'brf1' => [],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#0277BD',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#075685',
                'eye2Color' => '#075685',
                'eye3Color' => '#075685',
                'eyeBall1Color' => '#0277BD',
                'eyeBall2Color' => '#0277BD',
                'eyeBall3Color' => '#0277BD',
                'gradientColor1' => '#075685',
                'gradientColor2' => '#0277BD',
                'gradientType' => 'linear',
                'gradientOnEyes' => false,
                'logoMode' => 'default',
            ],
            'facebook' => [
                'body' => 'pointed-smooth',
                'eye' => 'frame2',
                'eyeBall' => 'ball2',
                'erf1' => ['fv'],
                'erf2' => [],
                'erf3' => [],
                'brf1' => ['fv'],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#3b5998',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#3B5998',
                'eye2Color' => '#3b5998',
                'eye3Color' => '#3b5998',
                'eyeBall1Color' => '#3b5998',
                'eyeBall2Color' => '#3b5998',
                'eyeBall3Color' => '#3b5998',
                'gradientColor1' => '#2C4270',
                'gradientColor2' => '#476CB9',
                'gradientType' => 'linear',
                'gradientOnEyes' => false,
            ],
            'jungle' => [
                'body' => 'rounded-pointed',
                'eye' => 'frame14',
                'eyeBall' => 'ball16',
                'erf1' => [],
                'erf2' => ['fh'],
                'erf3' => ['fv'],
                'brf1' => [],
                'brf2' => ['fh'],
                'brf3' => ['fv'],
                'bodyColor' => '#5C8B29',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#3F6B2B',
                'eye2Color' => '#3F6B2B',
                'eye3Color' => '#3F6B2B',
                'eyeBall1Color' => '#60A541',
                'eyeBall2Color' => '#60A541',
                'eyeBall3Color' => '#60A541',
                'gradientColor1' => '#5C8B29',
                'gradientColor2' => '#25492F',
                'gradientType' => 'radial',
                'gradientOnEyes' => false,
            ],
            'mosaic' => [
                'body' => 'mosaic',
                'eye' => 'frame16',
                'eyeBall' => 'ball8',
                'erf1' => [],
                'erf2' => [],
                'erf3' => [],
                'brf1' => ['fv'],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#000000',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#000000',
                'eye2Color' => '#000000',
                'eye3Color' => '#000000',
                'eyeBall1Color' => '#000000',
                'eyeBall2Color' => '#000000',
                'eyeBall3Color' => '#000000',
                'gradientColor1' => '#A13535',
                'gradientColor2' => '#0277bd',
                'gradientType' => 'linear',
                'gradientOnEyes' => true,
            ],
            'ninja' => [
                'body' => 'japnese',
                'eye' => 'frame12',
                'eyeBall' => 'ball14',
                'erf1' => [],
                'erf2' => [],
                'erf3' => [],
                'brf1' => [],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#0F8EAA',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#000000',
                'eye2Color' => '#000000',
                'eye3Color' => '#000000',
                'eyeBall1Color' => '#303236',
                'eyeBall2Color' => '#303236',
                'eyeBall3Color' => '#303236',
                'gradientColor1' => '#BD1D2B',
                'gradientColor2' => '#96207E',
                'gradientType' => 'linear',
                'gradientOnEyes' => false,
            ],
            'twitter' => [
                'body' => 'circular',
                'eye' => 'frame5',
                'eyeBall' => 'ball11',
                'erf1' => ['fh'],
                'erf2' => [],
                'erf3' => ['fh', 'fv'],
                'brf1' => ['fh'],
                'brf2' => [],
                'brf3' => ['fh', 'fv'],
                'bodyColor' => '#55ACEE',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#55acee',
                'eye2Color' => '#55acee',
                'eye3Color' => '#55acee',
                'eyeBall1Color' => '#55ACEE',
                'eyeBall2Color' => '#55acee',
                'eyeBall3Color' => '#55acee',
                'gradientColor1' => '',
                'gradientColor2' => '',
                'gradientType' => 'radial',
                'gradientOnEyes' => false,
            ],
            'youtube' => [
                'body' => 'circle',
                'eye' => 'frame13',
                'eyeBall' => 'ball14',
                'erf1' => [],
                'erf2' => [],
                'erf3' => [],
                'brf1' => [],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#EE0F0F',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#BF2626',
                'eye2Color' => '#BF2626',
                'eye3Color' => '#BF2626',
                'eyeBall1Color' => '#EE0F0F',
                'eyeBall2Color' => '#EE0F0F',
                'eyeBall3Color' => '#EE0F0F',
                'gradientColor1' => '',
                'gradientColor2' => '',
                'gradientType' => 'linear',
                'gradientOnEyes' => false,
            ],
            'bitcoin' => [
                'body' => 'circle',
                'eye' => 'frame1',
                'eyeBall' => 'ball1',
                'erf1' => ['fh'],
                'erf2' => [],
                'erf3' => ['fh', 'fv'],
                'brf1' => ['fh'],
                'brf2' => [],
                'brf3' => ['fh', 'fv'],
                'bodyColor' => '#4D4D4D',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#4D4D4D',
                'eye2Color' => '#4D4D4D',
                'eye3Color' => '#4D4D4D',
                'eyeBall1Color' => '#F38E14',
                'eyeBall2Color' => '#F38E14',
                'eyeBall3Color' => '#F38E14',
                'gradientColor1' => '',
                'gradientColor2' => '',
                'gradientType' => 'linear',
                'gradientOnEyes' => false,
            ],
            'default' => [
                'body' => 'square',
                'eye' => 'frame0',
                'eyeBall' => 'ball0',
                'erf1' => [],
                'erf2' => [],
                'erf3' => [],
                'brf1' => [],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#000000',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#000000',
                'eye2Color' => '#000000',
                'eye3Color' => '#000000',
                'eyeBall1Color' => '#000000',
                'eyeBall2Color' => '#000000',
                'eyeBall3Color' => '#000000',
                'gradientColor1' => '',
                'gradientColor2' => '',
                'gradientType' => 'linear',
                'gradientOnEyes' => true,
            ],
            'instagram-frame' => [
                'body' => 'circle',
                'eye' => 'frame2',
                'eyeBall' => 'ball15',
                'erf1' => ['fv'],
                'erf2' => [],
                'erf3' => [],
                'brf1' => [],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#55ACEE',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#0029b8',
                'eye2Color' => '#0029b8',
                'eye3Color' => '#0029b8',
                'eyeBall1Color' => '#CD2DD2',
                'eyeBall2Color' => '#CD2DD2',
                'eyeBall3Color' => '#CD2DD2',
                'gradientColor1' => '#CD2DD2',
                'gradientColor2' => '#0029b8',
                'gradientType' => 'radial',
                'gradientOnEyes' => false,
            ],
            'easy' => [
                'body' => 'circular',
                'eye' => 'frame13',
                'eyeBall' => 'ball15',
                'erf1' => [],
                'erf2' => [],
                'erf3' => [],
                'brf1' => [],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#113151',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#113151',
                'eye2Color' => '#113151',
                'eye3Color' => '#113151',
                'eyeBall1Color' => '#113151',
                'eyeBall2Color' => '#113151',
                'eyeBall3Color' => '#113151',
                'gradientColor1' => '',
                'gradientColor2' => '',
                'gradientType' => 'linear',
                'gradientOnEyes' => true,
                'logoMode' => 'default',
            ],
            'classic-frame' => [
                'body' => 'diamond',
                'eye' => 'frame12',
                'eyeBall' => 'ball14',
                'erf1' => [],
                'erf2' => [],
                'erf3' => [],
                'brf1' => [],
                'brf2' => [],
                'brf3' => [],
                'bodyColor' => '#000000',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#000000',
                'eye2Color' => '#000000',
                'eye3Color' => '#000000',
                'eyeBall1Color' => '#bf2626',
                'eyeBall2Color' => '#bf2626',
                'eyeBall3Color' => '#bf2626',
                'gradientColor1' => '',
                'gradientColor2' => '',
                'gradientType' => 'linear',
                'gradientOnEyes' => false,
                'logoMode' => 'default',
            ],
        ];
    }

    /**
     * Retrieve the configuration array for a specified template name.
     * Falls back to 'default' if template name is invalid or empty.
     *
     * @return array<string, mixed>
     */
    public static function getTemplate(string $name): array
    {
        $templates = self::getTemplates();
        return $templates[$name] ?? $templates['default'];
    }

    /**
     * Returns the vertical offset adjustment for the specified frame overlay.
     */
    public static function getFrameVerticalOffset(string $frame): int
    {
        return self::FRAME_OFFSETS[$frame] ?? 0;
    }
}
