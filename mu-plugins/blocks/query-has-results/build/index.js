(()=>{"use strict";const e=window.React,r=window.wp.i18n,t=window.wp.blocks,o=window.wp.blockEditor,n=JSON.parse('{"UU":"wporg/query-has-results"}'),s=[["core/paragraph",{placeholder:(0,r.__)("Add text or blocks that will display only when a query has results.","wporg")}]];(0,t.registerBlockType)(n.UU,{edit:function(){const r=(0,o.useBlockProps)(),t=(0,o.useInnerBlocksProps)(r,{template:s});return(0,e.createElement)("div",{...t})},save:function(){return(0,e.createElement)(o.InnerBlocks.Content,null)}})})();