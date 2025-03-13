import React, { useEffect, useRef } from "react";
import { __ } from "@wordpress/i18n";

export default function Modal({ show, children, onClose, title, wide = false }) {
	const ref = useRef();

	useEffect(() => {
		if (show) {
			ref.current.showModal();
		} else {
			ref.current.close();
		}
	}, [show]);

	const onCloseButton = ( event ) => {
		event.preventDefault();

		onClose();
	};

	const modalClass = wide ? 'crontrol-modal-wide' : '';

	return (
		<dialog className={`crontrol-modal ${modalClass}`} ref={ref} onCancel={onClose}>
			<div className="modal-container">
				<div className="modal-header">
					<h2 className="modal-title">
						{ title }
					</h2>
					<button onClick={onCloseButton} className="modal-close-button">
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
		</dialog>
	);
}
