/**
 * WordPress dependencies
 */

import { InnerBlocks } from '@wordpress/block-editor';

import { __, _x } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import { Button, Modal } from '@wordpress/components';

import { ServerSideRender } from '@wordpress/server-side-render';

/**
 * Render a list of the map markers.
 *
 * @param {Object}   props
 * @param {Array}    props.filters
 * @param {Function} props.onChange
 *
 * @return {JSX.Element}
 */
export default function Filters( { filters, onChange } ) {
	return (
		<div className="wp-block-group wporg-query-filters is-nowrap is-layout-flex wp-container-core-group-layout-2 wp-block-group-is-layout-flex">
			<div
				className="wp-block-wporg-query-filter"
				data-wp-interactive=""
				data-wp-context='{"wporg":{"queryFilter":{"isOpen":false,"hasHover":false,"hasMultiple":true}}}'
				data-wp-effect="effects.wporg.queryFilter.init"
				data-wp-class--is-modal-open="context.wporg.queryFilter.isOpen"
				data-wp-on--keydown="actions.wporg.queryFilter.handleKeydown"
			>
				<button
					className="wporg-query-filter__toggle has-no-filter-applied"
					data-wp-class--is-active="context.wporg.queryFilter.isOpen"
					data-wp-on--click="actions.wporg.queryFilter.toggle"
					data-wp-bind--aria-expanded="context.wporg.queryFilter.isOpen"
					aria-controls="filter-map_format-5"
					aria-expanded="false"
				>
					Format x
				</button>

				<div
					className="wporg-query-filter__modal-backdrop"
					data-wp-bind--hidden="!context.wporg.queryFilter.isOpen"
					data-wp-on--click="actions.wporg.queryFilter.toggle"
					hidden=""
				></div>

				<div
					className="wporg-query-filter__modal"
					id="filter-map_format-5"
					data-wp-bind--hidden="!context.wporg.queryFilter.isOpen"
					data-wp-effect--focus="effects.wporg.queryFilter.focusFirstElement"
					data-wp-effect--position="effects.wporg.queryFilter.checkPosition"
					hidden=""
				>
					<form action="javascript:;" data-wp-on--change="actions.wporg.queryFilter.handleFormChange">
						<div className="wporg-query-filter__modal-header">
							<h2>Format y</h2>
							<input
								type="button"
								className="wporg-query-filter__modal-close"
								data-wp-on--click="actions.wporg.queryFilter.toggle"
								aria-label="Close"
							/>
						</div>

						<fieldset className="wporg-query-filter__modal-content">
							<legend className="screen-reader-text">Format y</legend>
							<div className="wporg-query-filter__option">
								<input
									type="checkbox"
									name="map_format[]"
									value="in-person"
									id="filter-map_format-5-in-person"
								/>
								<label htmlFor="filter-map_format-5-in-person">In Person</label>
							</div>
							<div className="wporg-query-filter__option">
								<input
									type="checkbox"
									name="map_format[]"
									value="online"
									id="filter-map_format-5-online"
								/>
								<label htmlFor="filter-map_format-5-online">Online</label>
							</div>
						</fieldset>

						<div className="wporg-query-filter__modal-actions">
							<input
								type="button"
								className="wporg-query-filter__modal-action-clear"
								value="Clear"
								data-wp-on--click="actions.wporg.queryFilter.clearSelection"
								aria-disabled="true"
							/>
							<input type="submit" value="Apply" />
						</div>
					</form>
				</div>
			</div>
		</div>
	);
}

// could setup a component for Radio buttons, then another for Checkbox buttons
// gutenberg already has those speceic things, but would still need wrapper for the other stuff

// need a way to supply the POSSIBLE values

function Filter( { name, onChange } ) {
	const [ modalOpen, setModalOpen ] = useState( false );
	const openModal = () => setModalOpen( true );
	const closeModal = () => setModalOpen( false );
	const chevron = modalOpen ? chevronUp : chevronDown;

	// modal

	// if value is a string, then radio, else checklist. or pass in as prop?
	// when call onchange, maybe pass the name as well as tphe event?

	return (
		<>
			<Button variant="link" onClick={ openModal }>
				<span className="wporg-marker-filter__type">{ name }</span>
				<Icon icon={ chevron } />
			</Button>
		</>
	);
}
