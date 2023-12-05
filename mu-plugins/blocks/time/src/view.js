function convertTimes() {
	const parseDate = function ( text ) {
		const match =
			/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})([+-][0-2]{1}[0-9]{1}):00$/.exec(
				text
			);

		return (
			match &&
			new Date(
				// Date.UTC(year, monthIndex (0..11), day, hour, minute, second)
				Date.UTC( +match[ 1 ], +match[ 2 ] - 1, +match[ 3 ], +match[ 4 ], +match[ 5 ], +match[ 6 ] )
			)
		);
	};

	const formatTime = function (
		date,
		dateFormat = {
			weekday: 'long',
			month: 'long',
			day: 'numeric',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
			timeZoneName: 'short',
		}
	) {
		return date.toLocaleTimeString( window.navigator.language, dateFormat );
	};

	const formatDate = function ( date ) {
		return date.toLocaleDateString( window.navigator.language, {
			weekday: 'long',
			month: 'long',
			day: 'numeric',
			year: 'numeric',
		} );
	};

	// Not all browsers, particularly Safari, support arguments to .toLocaleTimeString().
	const toLocaleTimeStringSupportsLocales = ( function () {
		try {
			new Date().toLocaleTimeString( 'i' );
		} catch ( event ) {
			return event.name === 'RangeError';
		}

		return false;
	} )();

	document.querySelectorAll( '.wporg-time' ).forEach( ( dateElement ) => {
		let localTime = '';
		const datetime = dateElement.getAttribute( 'datetime' );
		const datetimeISO = dateElement.getAttribute( 'data-iso' );
		const dateFormat = dateElement.getAttribute( 'data-date-format' );
		const date = datetime && parseDate( datetime );

		if ( date ) {
			if ( ! toLocaleTimeStringSupportsLocales ) {
				localTime += formatDate( date );
				localTime += ' ';
			}

			if ( !! dateFormat ) {
				localTime += formatTime( date, JSON.parse( dateFormat ) );
			} else {
				localTime += formatTime( date );
			}

			dateElement.innerText = localTime;

			if ( !! datetimeISO ) {
				dateElement.removeAttribute( 'data-iso' );
				// Remove the dotted underline style applied for the editor only
				dateElement.style.textDecoration = '';
				dateElement.style.textDecorationStyle = '';

				const linkElement = document.createElement( 'a' );
				linkElement.setAttribute(
					'href',
					`https://www.timeanddate.com/worldclock/fixedtime.html?iso=${ datetimeISO }`
				);

				linkElement.appendChild( dateElement.cloneNode( true ) );
				dateElement.parentNode.replaceChild( linkElement, dateElement );
			}
		}
	} );
}

window.onload = () => {
	convertTimes();
};
