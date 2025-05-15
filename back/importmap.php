<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@angular/router' => [
        'version' => '19.2.10',
    ],
    '@angular/common' => [
        'version' => '19.2.9',
    ],
    '@angular/core' => [
        'version' => '19.2.9',
    ],
    'rxjs' => [
        'version' => '7.8.2',
    ],
    'rxjs/operators' => [
        'version' => '7.8.2',
    ],
    '@angular/platform-browser' => [
        'version' => '19.2.9',
    ],
    '@angular/core/primitives/signals' => [
        'version' => '19.2.9',
    ],
    '@angular/core/primitives/di' => [
        'version' => '19.2.9',
    ],
    'tslib' => [
        'version' => '2.8.1',
    ],
    '@angular/common/http' => [
        'version' => '19.2.9',
    ],
    '@taiga-ui/addon-table' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/addon-table/components' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/addon-table/directives' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/addon-table/tokens' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/addon-table/components/reorder' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/addon-table/components/table' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/addon-table/components/table-pagination' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/addon-table/directives/table-filters' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/utils/miscellaneous' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/i18n/utils' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/button' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/icon' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/tiles' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/polymorpheus' => [
        'version' => '4.9.0',
    ],
    '@ng-web-apis/intersection-observer' => [
        'version' => '4.12.0',
    ],
    '@taiga-ui/core/components/textfield' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/badge' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/chip' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/progress' => [
        'version' => '4.36.0',
    ],
    '@angular/core/rxjs-interop' => [
        'version' => '19.2.9',
    ],
    '@taiga-ui/cdk/observables' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/constants' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/utils/dom' => [
        'version' => '4.36.0',
    ],
    '@angular/cdk/coercion' => [
        'version' => '19.2.15',
    ],
    '@taiga-ui/kit/directives/present' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/chevron' => [
        'version' => '4.36.0',
    ],
    '@ng-web-apis/resize-observer' => [
        'version' => '4.12.0',
    ],
    '@taiga-ui/cdk/classes' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/data-list' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/link' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives/dropdown' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/tokens' => [
        'version' => '4.36.0',
    ],
    '@angular/forms' => [
        'version' => '19.2.9',
    ],
    '@taiga-ui/cdk/utils/math' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/i18n/tokens' => [
        'version' => '4.36.0',
    ],
    '@ng-web-apis/common' => [
        'version' => '4.12.0',
    ],
    '@taiga-ui/core/directives/appearance' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives/icons' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/utils/di' => [
        'version' => '4.36.0',
    ],
    '@ng-web-apis/mutation-observer' => [
        'version' => '4.12.0',
    ],
    '@taiga-ui/event-plugins' => [
        'version' => '4.5.1',
    ],
    '@taiga-ui/cdk/tokens' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/label' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/native-validator' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives/items-handlers' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/services' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/utils/focus' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/avatar' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/checkbox' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/switch' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/utils' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/active-zone' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/animations' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/classes' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/scrollbar' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/services' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/utils' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/obscured' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/utils/miscellaneous' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/date-time' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/i18n/languages/english' => [
        'version' => '4.36.0',
    ],
    '@angular/animations/browser' => [
        'version' => '19.2.9',
    ],
    '@ng-web-apis/platform' => [
        'version' => '4.12.0',
    ],
    '@taiga-ui/kit/directives/fade' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/utils/browser' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/utils/color' => [
        'version' => '4.36.0',
    ],
    '@angular/animations' => [
        'version' => '19.2.9',
    ],
    '@taiga-ui/core/utils/dom' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/utils/format' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/font-size' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/pipes' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/tokens' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/utils' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/accordion' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/action-bar' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/badge-notification' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/badged-content' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/block' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/breadcrumbs' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/button-loading' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/calendar-month' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/calendar-range' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/carousel' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/comment' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/compass' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/confirm' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/data-list-wrapper' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/drawer' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/elastic-container' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/files' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/filter' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/floating-container' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/input-inline' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/input-month' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/input-month-range' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/input-number' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/input-password' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/input-phone-international' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/input-pin' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/input-slider' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/items-with-more' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/like' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/line-clamp' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/message' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/pager' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/pagination' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/pdf-viewer' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/pin' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/preview' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/pulse' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/push' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/radio' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/radio-list' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/range' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/rating' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/routable-dialog' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/segmented' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/select' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/slider' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/status' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/stepper' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/tabs' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/textarea' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/components/tree' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/button-close' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/button-group' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/button-select' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/connected' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/copy' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/data-list-dropdown-manager' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/fluid-typography' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/highlight' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/icon-badge' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/lazy-loading' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/password' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/sensitive' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/skeleton' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/tooltip' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/unfinished-validator' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/directives/unmask-handler' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/pipes/emails' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/pipes/field-error' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/pipes/filter-by-input' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/pipes/sort-countries' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/pipes/stringify' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/kit/pipes/stringify-content' => [
        'version' => '4.36.0',
    ],
    '@maskito/angular' => [
        'version' => '3.7.2',
    ],
    'libphonenumber-js/core' => [
        'version' => '1.12.8',
    ],
    '@taiga-ui/core/directives/group' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/expand' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/item' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives/hint' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/loader' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/hovered' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/let' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/repeat-times' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/calendar' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/spin-button' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/pipes/mapper' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/pan' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/swipe' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/auto-focus' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/dialog' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/element' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/validator' => [
        'version' => '4.36.0',
    ],
    '@maskito/core' => [
        'version' => '3.7.2',
    ],
    '@maskito/kit' => [
        'version' => '3.7.2',
    ],
    '@maskito/phone' => [
        'version' => '3.7.2',
    ],
    '@taiga-ui/core/pipes/flag' => [
        'version' => '4.36.0',
    ],
    'libphonenumber-js' => [
        'version' => '1.12.8',
    ],
    '@taiga-ui/cdk/directives/popover' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/zoom' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/pipes/format-date' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/alert' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/pipes' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/focus-trap' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/notification' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives/title' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/pipes/auto-color' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/pipes/calendar-sheet' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/pipes/fallback-src' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/pipes/format-number' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/pipes/initials' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/pipes/month' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/pipes/order-week-days' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/tokens' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/app-bar' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/block-details' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/block-status' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/card' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/cell' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/dynamic-header' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/form' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/header' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/input-search' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/item-group' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/navigation' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/layout/components/search' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives/popup' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/error' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/fullscreen' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/components/root' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives/date-format' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives/number-format' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/core/directives/surface' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/platform' => [
        'version' => '4.36.0',
    ],
    '@taiga-ui/cdk/directives/visual-viewport' => [
        'version' => '4.36.0',
    ],
    '@ng-web-apis/screen-orientation' => [
        'version' => '4.12.0',
    ],
    'gsap' => [
        'version' => '3.13.0',
    ],
];
