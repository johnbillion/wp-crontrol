import React, { useEffect } from "react";
import { __ } from "@wordpress/i18n";

export default function Modal({ show, children, onClose, title }) {
	const bgStyle = show ? "modal-bg-show" : "modal-bg-hide";

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
			<div className={`crontrol-modal ${bgStyle}`}>
				<div className="modal-container">
					<div className="modal-header">
						<h2 className="modal-title">
							{ title }
						</h2>
						<button aria-label="close" onClick={onClose} className="modal-close-button">
							<span aria-hidden="true">&times;</span>
							<span className="screen-reader-text">
								{ __( 'Close', 'wp-crontrol' ) }
							</span>
						</button>
					</div>
					<div className="modal-content">
						{children}
					</div>
				</div>
			</div>
	);
}
