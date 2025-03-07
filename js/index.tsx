import React from "react";
import { useState, useEffect } from "react";
import { createRoot } from "react-dom/client";

import EditEvent from "./EditEvent";
import Modal from "./Modal";
import { __ } from "@wordpress/i18n";

export default function App() {
	const [modalState, setModalState] = useState(false);
	const [editState, setEditState] = useState({
		args: null,
		id: null,
		nonce: null,
		protectedHook: null,
		schedule: null,
		sig: null,
		timestamp: null,
		type: null,
	});

	useEffect(() => {
		const elements = document.querySelectorAll("[data-crontrol-edit]");

		if (!elements.length) {
			return; // Ensure there are elements before adding listeners
		}

		const handleClick = (event) => {
			// Ignore if any modifiers are pressed
			if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
				return;
			}

			event.preventDefault();
			const url = new URL(event.target.href);
			const id = url.searchParams.get("crontrol_id");
			const sig = url.searchParams.get("crontrol_sig");
			const timestamp = url.searchParams.get("crontrol_next_run_utc");

			const {
				crontrolArgs: args,
				crontrolNonce: nonce,
				crontrolSchedule: schedule,
				crontrolType: type,
			} = event.target.dataset;
			const protectedHook = event.target.dataset.crontrolProtectedHook === 'true';

			setModalState(true);
			setEditState({
				args,
				id,
				nonce,
				protectedHook,
				schedule,
				sig,
				timestamp,
				type,
			});
		};

		elements.forEach(element => {
			element.addEventListener("click", handleClick);
		});

		return () => {
			elements.forEach(element => {
				element.removeEventListener("click", handleClick);
			});
		};
	}, []);

	return (
		<Modal show={modalState} onClose={() => setModalState(false)} title={ __( 'Edit Cron Event', 'wp-crontrol' ) }>
			<EditEvent
				args={editState.args}
				id={editState.id}
				nonce={editState.nonce}
				protectedHook={editState.protectedHook}
				schedule={editState.schedule}
				sig={editState.sig}
				timestamp={editState.timestamp}
				type={editState.type}
			/>
		</Modal>
	);
}
document.addEventListener('DOMContentLoaded', () => {
	const appElement = document.getElementById('crontrol-app');
	if (appElement) {
		const root = createRoot(appElement);
		root.render(<App />);
	}
});
