import React, { useEffect } from "react";

export default function Modal({ show, children, onClose }) {
	const bgStyle = {
		display: show ? "block" : "none",
		backgroundColor: "rgba(0, 0, 0, 0.7)",
		position: "fixed",
		top: 0,
		left: 0,
		width: "100%",
		height: "100%",
		zIndex: "9999",
		padding: "30px",
	};
	const contentStyle = {
		backgroundColor: "#f0f0f1",
		margin: "100px auto 30px",
		width: "auto",
		maxWidth: "900px",
		padding: "20px",
	};

	useEffect(() => {
		const handleKeyDown = (event) => {
			if (event.key === "Escape") {
				onClose();
			}
		};

		if (show) {
			document.addEventListener("keydown", handleKeyDown);
		} else {
			document.removeEventListener("keydown", handleKeyDown);
		}

		return () => {
			document.removeEventListener("keydown", handleKeyDown);
		};
	}, [show, onClose]);

	return (
		<div style={bgStyle}>
			<div style={contentStyle}>
				<div>
					{children}
				</div>
			</div>
			<button className="modal-close is-large" aria-label="close" onClick={onClose}>CLOSE</button>
		</div>
	);
}
