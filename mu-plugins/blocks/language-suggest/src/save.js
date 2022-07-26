/**
 * WordPress dependencies
 */
 import { __ } from '@wordpress/i18n';
 import { useBlockProps } from '@wordpress/block-editor';
 
 /**
  * The save function defines the way in which the different attributes should
  * be combined into the final markup, which is then serialized by the block
  * editor into `post_content`.
  *
  * @return {WPElement} Element to render.
  */
 export default function save() {
     return <div { ...useBlockProps.save() }></div>;
 }
 