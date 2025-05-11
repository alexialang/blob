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
        'version' => '19.2.9',
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
        'version' => '19.2.5',
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
        'version' => '19.2.5',
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
];
