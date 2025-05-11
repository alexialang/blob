/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/@taiga-ui/cdk@4.36.0/fesm2022/taiga-ui-cdk-utils-browser.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import{isIos as t}from"@ng-web-apis/platform";function e(t){return t.platform.startsWith("Mac")||"iPhone"===t.platform}function n(t){return t.toLowerCase().includes("edge")}function o(t){return t.toLowerCase().includes("firefox")}const i=t;function r({ownerDocument:t}){const e=t?.defaultView,n=void 0!==e.safari&&"[object SafariRemoteNotification]"===e.safari?.pushNotification?.toString(),o=!!e.navigator?.vendor?.includes("Apple")&&!e.navigator?.userAgent?.includes("CriOS")&&!e.navigator?.userAgent?.includes("FxiOS");return n||o}export{e as tuiIsApple,n as tuiIsEdge,o as tuiIsFirefox,i as tuiIsIos,r as tuiIsSafari};export default null;
