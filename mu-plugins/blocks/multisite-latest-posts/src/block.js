/**
 * WordPress dependencies
 */
import { Fragment, useEffect, useState } from '@wordpress/element';
import { gmdate, gmdateI18n } from '@wordpress/date';
import { __ } from '@wordpress/i18n';

const getCategory = ( embed, id ) => {
	if ( ! embed[ 'wp:term' ] ) {
		return;
	}

	for ( let idx = 0; idx < embed[ 'wp:term' ].length; idx++ ) {
		const terms = embed[ 'wp:term' ][ idx ];

		return terms.find( ( term ) => term.id === id );
	}
};

const Block = ( { endpoint, perPage } ) => {
	const [ posts, setPosts ] = useState( [] );
	const [ error, setError ] = useState();
	const [ loading, setLoading ] = useState( false );

	const getPosts = async () => {
		setLoading( true );
		try {
			const response = await fetch( `${ endpoint }/posts?per_page=${ perPage }&_embed=true` );

			if ( response.ok ) {
				const result = await response.json();

				setPosts( result );
			} else {
				throw new Error();
			}
		} catch ( exception ) {
			setError( __( 'Error loading posts.', 'wporg' ) );
		}

		setLoading( false );
	};

	useEffect( () => {
		getPosts();
	}, [ endpoint, perPage ] );

	if ( loading ) {
		return __( 'Loading posts …', 'wporg' );
	}

	if ( error ) {
		return <div>{ error }</div>;
	}

	return (
		<ul className="wporg-multisite-latest-posts">
			{ posts.map( ( i ) => {
				const category = getCategory( i._embedded, i.categories[ 0 ] );
				return (
					<li key={ i.id }>
						<a href={ i.link }>{ i.title.rendered }</a>
						<div className="wporg-multisite-latest-posts-details">
							{ category && (
								<Fragment>
									<a href={ category.link } className="wporg-multisite-latest-posts-category">
										{ category.name }
									</a>
									<span>·</span>
								</Fragment>
							) }
							<time dateTime={ gmdate( 'c', i.date ) }>{ gmdateI18n( 'F j, Y', i.date ) }</time>
						</div>
					</li>
				);
			} ) }
		</ul>
	);
};

export default Block;
