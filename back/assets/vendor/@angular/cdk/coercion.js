/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/@angular/cdk@19.2.15/fesm2022/coercion.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import{ElementRef as n}from"@angular/core";function r(n){return null!=n&&"false"!=`${n}`}function t(n,r=0){return u(n)?Number(n):2===arguments.length?r:0}function u(n){return!isNaN(parseFloat(n))&&!isNaN(Number(n))}function e(r){return r instanceof n?r.nativeElement:r}function o(n){return Array.isArray(n)?n:[n]}function i(n){return null==n?"":"string"==typeof n?n:`${n}px`}function s(n,r=/\s+/){const t=[];if(null!=n){const u=Array.isArray(n)?n:`${n}`.split(r);for(const n of u){const r=`${n}`.trim();r&&t.push(r)}}return t}export{u as _isNumberValue,o as coerceArray,r as coerceBooleanProperty,i as coerceCssPixelValue,e as coerceElement,t as coerceNumberProperty,s as coerceStringArray};export default null;
