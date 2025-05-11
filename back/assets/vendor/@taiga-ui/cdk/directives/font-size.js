/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/@taiga-ui/cdk@4.36.0/fesm2022/taiga-ui-cdk-directives-font-size.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import{isPlatformBrowser as e}from"@angular/common";import*as t from"@angular/core";import{inject as r,DestroyRef as n,PLATFORM_ID as o,Directive as s}from"@angular/core";import{WA_WINDOW as a}from"@ng-web-apis/common";import{EMPTY_FUNCTION as i}from"@taiga-ui/cdk/constants";import{tuiCreateToken as m,tuiFontSizeWatcher as p}from"@taiga-ui/cdk/utils/miscellaneous";const c=m();class l{constructor(){this.handler=r(c,{optional:!0}),this.nothing=r(n).onDestroy(this.handler&&e(r(o))&&"undefined"!=typeof ResizeObserver?p(this.handler,r(a)):i)}static{this.ɵfac=t.ɵɵngDeclareFactory({minVersion:"12.0.0",version:"16.2.12",ngImport:t,type:l,deps:[],target:t.ɵɵFactoryTarget.Directive})}static{this.ɵdir=t.ɵɵngDeclareDirective({minVersion:"14.0.0",version:"16.2.12",type:l,isStandalone:!0,ngImport:t})}}t.ɵɵngDeclareClassMetadata({minVersion:"12.0.0",version:"16.2.12",ngImport:t,type:l,decorators:[{type:s,args:[{standalone:!0}]}]});export{c as TUI_FONT_SIZE_HANDLER,l as TuiFontSize};export default null;
