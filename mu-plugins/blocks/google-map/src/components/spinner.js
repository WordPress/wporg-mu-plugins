export default function Spinner() {
	return (
		<svg
			className="wporg-google-map__spinner"
			viewBox="0 0 100 100"
			width="16"
			height="16"
			xmlns="http://www.w3.org/2000/svg"
			role="presentation"
			focusable="false"
		>
			<circle cx="50" cy="50" r="50" vectorEffect="non-scaling-stroke"></circle>
			<path d="m 50 0 a 50 50 0 0 1 50 50" vectorEffect="non-scaling-stroke"></path>
		</svg>
	);
}
