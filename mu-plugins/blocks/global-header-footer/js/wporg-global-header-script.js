/**
 * File wporg-global-header-script.js.
 *
 * Applies a priority navigation pattern to the header menu.
 * https://css-tricks.com/the-priority-navigation-pattern/
 *
 */
( function () {
	/**
	 * Menu Responsive navigation
	 *
	 * @param {string} selector
	 */
	const navMenu = function ( selector ) {
		this.wrapper = document.querySelector( selector );

		if ( ! this.wrapper ) {
			return;
		}

		/**
		 * Resets the menu item classes and removes the extra submenu
		 */
		this.resetMenu = function () {
			for ( const el of this.listItems ) {
				el.classList.remove( 'global-header__overflow-item' );
			}
			this.removeSubMenu();
			this.hasHiddenItems = false;
			this.listItems = this.getListItems();
		};

		/**
		 * Get the top-level list items.
		 */
		this.getListItems = function () {
			return this.wrapper.querySelectorAll(
				'.wp-block-navigation__container > li:not(.global-header__mobile-get-wordpress)'
			);
		};

		/**
		 * Removes the ... submenu
		 */
		this.removeSubMenu = function () {
			if ( this.wrapper.querySelector( '.global-header__overflow-menu' ) ) {
				this.wrapper.querySelector( '.global-header__overflow-menu' ).remove();
			}
		};

		/**
		 * Saves an array with the widths of the menu items
		 */
		this.getItemWidths = function () {
			this.itemsWidths = [];
			for ( const el of this.listItems ) {
				el.classList.remove( 'global-header__overflow-item' );
				this.itemsWidths.push( el.offsetWidth );
			}
		};

		/**
		 * Hide menu items that exceed the container's width
		 */
		this.hideExtraItems = function () {
			//not pretty! but it's not likely to change
			const dotMenuWidth = 83;
			let totalWidth = dotMenuWidth;
			this.resetMenu();

			for ( let i = 0, len = this.itemsWidths.length; i < len; i++ ) {
				totalWidth += this.itemsWidths[ i ];
				// If this is the last item, we don't need to account for the … menu item.
				if ( i === len - 1 ) {
					totalWidth -= dotMenuWidth;
				}
				if ( totalWidth >= this.wrapper.offsetWidth ) {
					this.listItems[ i ].classList.add( 'global-header__overflow-item' );
					if ( ! this.hasHiddenItems ) {
						this.hasHiddenItems = true;
					}
				}
			}
		};

		/**
		 * Generates an extra menu item with all the hidden elements inside it
		 */
		this.populateExtendedSubmenu = function () {
			if ( this.hasHiddenItems ) {
				this.removeSubMenu();

				let itemsContainer = this.wrapper.querySelector( '.wp-block-navigation__container' );

				//create the ... menu list item
				let newItem = document.createElement( 'li' );
				newItem.classList.add(
					'wp-block-navigation-item',
					'wp-block-navigation-link',
					'has-child',
					'global-header__overflow-menu'
				);

				let newLink = document.createElement( 'a' );
				newLink.classList.add( 'wp-block-navigation-item__content' );
				newLink.setAttribute( 'href', '#' );
				newLink.appendChild( document.createTextNode( '...' ) );
				newItem.appendChild( newLink );

				//create the submenu where the hidden links will live
				let newSubMenu = document.createElement( 'ul' );
				newSubMenu.classList.add( 'wp-block-navigation__submenu-container' );
				newItem.appendChild( newSubMenu );

				//populate submenu with clones of the hidden menu items
				for ( const el of this.listItems ) {
					if ( el.classList.contains( 'global-header__overflow-item' ) ) {
						let clone = el.cloneNode( true );
						newSubMenu.appendChild( clone );
					}
				}

				itemsContainer.appendChild( newItem );
			}
		};

		/**
		 * Checks if the responsive menu is visible
		 */
		this.isResponsive = function () {
			let burgerButton = this.wrapper.querySelector( '.wp-block-navigation__responsive-container-open' );
			if ( burgerButton.offsetWidth > 0 ) {
				return true;
			}
			return false;
		};

		this.listItems = this.getListItems();
		this.itemsWidths = [];
		this.hasHiddenItems = false;

		if ( ! this.isResponsive() ) {
			this.getItemWidths();
			this.hideExtraItems();
			this.populateExtendedSubmenu();
		}

		window.addEventListener(
			'resize',
			function () {
				this.resetMenu();
				if ( ! this.isResponsive() ) {
					this.getItemWidths();
					this.hideExtraItems();
					this.populateExtendedSubmenu();
				}
			}.bind( this )
		);
	};

	window.addEventListener( 'load', function () {
		new navMenu( '.global-header .global-header__navigation' );
	} );
} )();
