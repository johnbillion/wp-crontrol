import React from "react";
import { useState, useEffect } from "react";
import { createRoot } from "react-dom/client";

import EditEvent from "./EditEvent";
import Modal from "./Modal";

export default function App() {
	const [modalState, setModalState] = useState(false);
	const [editState, setEditState] = useState({
		id: null,
		sig: null,
		timestamp: null,
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
				crontrolType: type,
				crontrolSchedule: schedule,
				crontrolNonce: nonce,
				crontrolProtectedHook: protectedHook,
				crontrolArgs: args,
			} = event.target.dataset;

			console.log(event.target.dataset);

			setModalState(true);
			setEditState({
				id,
				sig,
				timestamp,
				type,
				nonce,
				schedule,
				protectedHook,
				args,
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
		<Modal show={modalState} onClose={() => setModalState(false)}>
			<EditEvent
				id={editState.id}
				sig={editState.sig}
				timestamp={editState.timestamp}
				schedule={editState.schedule}
				nonce={editState.nonce}
				type={editState.type}
				protectedHook={editState.protectedHook}
				args={editState.args}
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
